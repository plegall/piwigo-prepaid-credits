<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based picture gallery                                  |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2015 Piwigo Team                  http://piwigo.org |
// | Copyright(C) 2003-2008 PhpWebGallery Team    http://phpwebgallery.net |
// | Copyright(C) 2002-2003 Pierrick LE GALL   http://le-gall.net/pierrick |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

if( !defined("PHPWG_ROOT_PATH") )
{
  die ("Hacking attempt!");
}

// include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');

function ws_ppcredits_paypal_create($params, &$service)
{
  global $conf, $user;
  
  // TODO checks

  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW()'));
  
  single_insert(
    PPCREDITS_PAID_TABLE,
    array(
      'order_uuid' => ppcredits_get_order_uuid(),
      'user_id' => $user['id'],
      'nb_credits' => $params['nb_credits'],
      'created_on' => $dbnow,
      'amount' => $params['nb_credits'] * $conf['ppcredits']['price_per_credit'],
      'currency' => $conf['ppcredits']['currency'],
      )
    );

  $order_id = pwg_db_insert_id();

  $query = '
SELECT *
  FROM '.PPCREDITS_PAID_TABLE.'
  WHERE id = '.$order_id.'
;';
  $orders = query2array($query);
  $order = array_shift($orders);
  
  return array(
    'order_uuid' => $order['order_uuid'],
    'amount' => $order['amount'],
    );
}

function ppcredits_get_order_uuid()
{
  $date_part = date('Ymd');
  $random_part = strtoupper(generate_key(4));
  $candidate = $date_part.'-'.$random_part;

  $query = '
SELECT
    COUNT(*)
  FROM '.PPCREDITS_PAID_TABLE.'
  WHERE order_uuid = \''.$candidate.'\'
;';
  list($counter) = pwg_db_fetch_row(pwg_query($query));

  if (0 == $counter)
  {
    return $candidate;
  }
  else
  {
    return get_order_uuid();
  }
}

function ws_ppcredits_paypal_ipn($params, &$service)
// function ppcredits_paypal_ipn()
{
  // notification default subject/message
  $notification_subject_prefix = 'Paypal IPN, ';
  
  $notification_message_prefix = '
order_uuid : '.$_POST['custom'].'
';

  $paypal_data = $_POST;

  $req = '';
  
  $_POST = array_merge(array('cmd' => '_notify-validate'), $_POST);
  
  foreach ($_POST as $key => $value)
  {
    $value = urlencode(stripslashes($value));
    $req .= '&'.$key.'='.$value;
  }
  
  // post back to PayPal system to validate
  $header ="POST /cgi-bin/webscr HTTP/1.1\r\n";
  $header .="Content-Type: application/x-www-form-urlencoded\r\n";
  $header .="Host: www.paypal.com\r\n";
  $header .="Content-Length: " . strlen($req) . "\r\n";
  $header .="Connection: close\r\n\r\n";
  $fp = fsockopen('www.paypal.com', 80, $errno, $errstr, 30);

  if (!$fp)
  {
    // HTTP error
    ppcredits_notify_team(
      $notification_subject_prefix.'http error',
      $notification_message_prefix.var_export($paypal_data, true)
      );
    return false;
  }

  $is_payment_verified = false;
  $response = '';
  fputs ($fp, $header . $req);
  while (!feof($fp))
  {
    $res = fgets ($fp, 1024);
    $response.= $res;
    
    if (strcmp ($res, "VERIFIED") == 0)
    {
      $is_payment_verified = true;
    }
    else if (strcmp ($res, "INVALID") == 0)
    {
      // log for manual investigation
      ppcredits_notify_team(
        $notification_subject_prefix.'INVALID response',
        $notification_message_prefix.var_export($paypal_data, true)
        );
      return false;
    }
  }
  fclose ($fp);

  if (!$is_payment_verified)
  {
    ppcredits_notify_team(
      $notification_subject_prefix.'payment not verified',
      $notification_message_prefix.var_export($paypal_data, true)."\n\n".' response to IP : '."\n".$response
      );
    return false;
  }

  // check the payment_status is Completed
  if ('Completed' != $paypal_data['payment_status'])
  {
    ppcredits_notify_team(
      $notification_subject_prefix.'payment not completed',
      $notification_message_prefix.var_export($paypal_data, true)
      );
    return false;
  }

  // is the order_uuid correctly sent?
  if (!preg_match('/\d{8}-\w{4}/', $paypal_data['custom']))
  {
    ppcredits_notify_team(
      $notification_subject_prefix.'invalid order uuid',
      $notification_message_prefix.var_export($paypal_data, true)
      );
    return false;
  }

  $query = '
SELECT
    id,
    order_uuid,
    user_id,
    nb_credits,
    amount,
    currency,
    created_on,
    validated_on,
    cancelled_on,
    paypal_transaction_id
  FROM '.PPCREDITS_PAID_TABLE.'
  WHERE order_uuid = \''.$paypal_data['custom'].'\'
;';
  $result = pwg_query($query);
  $order = null;
  while ($row = pwg_db_fetch_assoc($result))
  {
    $order = $row;
  }

  if (!isset($order))
  {
    ppcredits_notify_team(
      $notification_subject_prefix.'unknown order uuid',
      $notification_message_prefix.var_export($paypal_data, true)
      );
    return false;
  }

  // check that payment_amount/payment_currency are correct
  if ($paypal_data['mc_gross'] != $order['amount'])
  {
    ppcredits_notify_team(
      $notification_subject_prefix.'order amount does not match',
      $notification_message_prefix.var_export($paypal_data, true)
      );
    return false;
  }

  if (empty($order['validated_on']))
  {
    // what if the order was cancelled?
    //
    // let's update the "validated_on" + "paypal_transaction_id" fields and
    // update the accounts.customer_status if necessary but sends a warning

    $query = '
UPDATE '.PPCREDITS_PAID_TABLE.'
  SET validated_on = NOW()
    , paypal_transaction_id = \''.pwg_db_real_escape_string($paypal_data['txn_id']).'\'
  WHERE id = '.$order['id'].'
;';
    pwg_query($query);

    $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET ppcredits = ppcredits + '.$order['nb_credits'].'
  WHERE user_id = '.$order['user_id'].'
;';
    pwg_query($query);

    if (!empty($order['cancelled_on']))
    {
      ppcredits_notify_team(
        $notification_subject_prefix.'the order was cancelled, but we validate the order anyway',
        $notification_message_prefix.var_export($paypal_data, true)
        );
    }
  }
  
  ppcredits_notify_team(
    $notification_subject_prefix.'order successfully validated',
    $notification_message_prefix.var_export($paypal_data, true)
    );

  return true;
}

function ppcredits_notify_team($subject, $message)
{
  global $conf;
  
  $headers = 'From: '.get_webmaster_mail_address()."\n";
  $headers.= 'X-Mailer: Piwigo.com Mailer'."\n";
  
  $headers.= "MIME-Version: 1.0\n";
  $headers.= "Content-type: text/plain; charset=utf-8\n";
  $headers.= "Content-Transfer-Encoding: quoted-printable\n";

  $to = get_webmaster_mail_address();
  
  mail($to, $subject, $message, $headers);
}

function ws_ppcredits_photo_buy($params, &$service)
{
  global $conf, $user;

  // is this size on sale?
  if (!isset($conf['ppcredits']['price_coefficient'][ $params['size'] ]))
  {
    return new PwgError(404, "size not found");
  }

  // does the photo exists?
  $query = '
SELECT *
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$params['image_id'].'
;';
  $images = query2array($query);

  if (count($images) == 0)
  {
    return new PwgError(404, "image_id not found");
  }

  $image = array_shift($images);

  list($dbnow) = pwg_db_fetch_row(pwg_query('SELECT NOW()'));
  
  // has this image already been recently bought by the same user?
  if (ppcredits_recently_purchased($image['id'], $params['size']))
  {
    return new PwgError(401, "already purchased recently");
  }
  
  // how much does the photo cost?
  $image_credits = $image['ppcredits_price'];
  if (empty($image_credits))
  {
    $image_credits = $conf['ppcredits']['photo_cost'];
  }

  $image_credits *= $conf['ppcredits']['price_coefficient'][ $params['size'] ];
  
  // does the user has enough credits?
  if ($user['ppcredits'] < $image_credits)
  {
    return new PwgError(401, "not enough credits");
  }
    
  single_insert(
    PPCREDITS_SPENT_TABLE,
    array(
      'user_id' => $user['id'],
      'image_id' => $image['id'],
      'size' => $params['size'],
      'nb_credits' => $image_credits,
      'used_on' => $dbnow,
      )
    );

  $spent_id = pwg_db_insert_id();

  $query = '
SELECT *
  FROM '.PPCREDITS_SPENT_TABLE.'
  WHERE id = '.$spent_id.'
;';
  $spents = query2array($query);
  $spent = array_shift($spents);

  // decrease the number of credits for the user
  $query = '
UPDATE '.USER_INFOS_TABLE.'
  SET ppcredits = ppcredits - '.$spent['nb_credits'].'
  WHERE user_id = '.$spent['user_id'].'
;';
  pwg_query($query);
  
  return array(
    'nb_credits' => $spent['nb_credits'],
    );
}

function ppcredits_recently_purchased($image_id, $size)
{
  $sizes = ppcredits_recently_purchased_sizes($image_id);

  if (in_array($size, $sizes))
  {
    return true;
  }

  return false;
}

function ppcredits_recently_purchased_sizes($image_id)
{
  global $user, $conf;

  $query = '
SELECT
    size
  FROM '.PPCREDITS_SPENT_TABLE.'
  WHERE image_id = '.$image_id.'
    AND user_id = '.$user['id'].'
    AND used_on > SUBDATE(NOW(), INTERVAL '.$conf['ppcredits']['download_period'].')
;';
  $sizes = query2array($query, null, 'size');
  
  return $sizes;
}

/**
 * getFilename function, copied from Batch Manager
 */
function ppcredits_getFilename($row, $filesize=array())
{
  global $conf;

  $row['filename'] = stripslashes(get_filename_wo_extension($row['file']));

  // datas
  $search = array('%id%', '%filename%', '%author%', '%dimensions%');
  $replace = array($row['id'], $row['filename']);

  $replace[2] = empty($row['author']) ? null : $row['author'];
  $replace[3] = empty($filesize) ? null : $filesize['width'].'x'.$filesize['height'];

  $filename = str_replace($search, $replace, $conf['ppcredits']['file_pattern']);

  // functions
  $filename = preg_replace_callback('#\$escape\((.*?)\)#', create_function('$m', 'return str2url($m[1]);'),   $filename);
  $filename = preg_replace_callback('#\$upper\((.*?)\)#',  create_function('$m', 'return str2upper($m[1]);'), $filename);
  $filename = preg_replace_callback('#\$lower\((.*?)\)#',  create_function('$m', 'return str2lower($m[1]);'), $filename);
  $filename = preg_replace_callback('#\$strpad\((.*?),(.*?),(.*?)\)#', create_function('$m', 'return str_pad($m[1],$m[2],$m[3],STR_PAD_LEFT);'), $filename);

  // cleanup
  $filename = preg_replace(
    array('#_+#', '#-+#', '# +#', '#^([_\- ]+)#', '#([_\- ]+)$#'),
    array('_', '-', ' ', null, null),
    $filename
    );

  if (empty($filename) || $filename == $conf['ppcredits']['file_pattern'])
  {
    $filename = $row['filename'];
  }

  $filename.= '.'.get_extension($row['path']);

  return $filename;
}

if (!function_exists('str2lower'))
{
  if (function_exists('mb_strtolower') && defined('PWG_CHARSET'))
  {
    function str2lower($term)
    {
      return mb_strtolower($term, PWG_CHARSET);
    }
    function str2upper($term)
    {
      return mb_strtoupper($term, PWG_CHARSET);
    }
  }
  else
  {
    function str2lower($term)
    {
      return strtolower($term);
    }
    function str2upper($term)
    {
      return strtoupper($term);
    }
  }
}
?>