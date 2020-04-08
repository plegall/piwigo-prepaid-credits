<?php
// +-----------------------------------------------------------------------+
// | This file is part of Piwigo plugin Prepaid Credits                    |
// |                                                                       |
// | For copyright and license information, please view the COPYING.txt    |
// | file that was distributed with this source code.                      |
// +-----------------------------------------------------------------------+

if( !defined("PHPWG_ROOT_PATH") )
{
  die ("Hacking attempt!");
}

include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
include_once(PHPWG_ROOT_PATH.'include/functions_picture.inc.php');

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+

check_status(ACCESS_ADMINISTRATOR);

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$template->set_filenames(
  array(
    'plugin_admin_content' => dirname(__FILE__).'/admin_history.tpl'
    )
  );

// +-----------------------------------------------------------------------+
// | pending photos list                                                   |
// +-----------------------------------------------------------------------+

// list of users
$users = array();
$user_details = array();

$query = '
SELECT
    DISTINCT(`user_id`)
  FROM '.PPCREDITS_SPENT_TABLE.'
;';
$user_ids_spent = query2array($query, null, 'user_id');

$query = '
SELECT
    DISTINCT(`user_id`)
  FROM '.PPCREDITS_PAID_TABLE.'
;';
$user_ids_paid = query2array($query, null, 'user_id');

$user_ids = array_unique(array_merge($user_ids_spent, $user_ids_paid));

if (count($user_ids) > 0)
{
  $query = '
SELECT
    '.$conf['user_fields']['id'].' AS id,
    '.$conf['user_fields']['username'].' AS username,
    '.$conf['user_fields']['email'].' AS email,
    ppcredits
  FROM '.USERS_TABLE.' AS u
    INNER JOIN '.USER_INFOS_TABLE.' AS uf ON uf.user_id = u.'.$conf['user_fields']['id'].'
  WHERE id IN ('.join(',', $user_ids).')
;';
  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    $users[$row['id']] = $row['username'].' ('.$row['ppcredits'].' credits left)';
    $user_details[ $row['id'] ] = $row;
  }
}

natcasesort($users);

$template->assign(
  array(
    'user_options' => $users,
    )
  );

// history lines
$clauses = array('1=1');

if (isset($_GET['user']) and isset($users[ $_GET['user'] ]))
{
  $clauses[] = 'user_id = '.$_GET['user'];

  $template->assign('user_options_selected', $_GET['user']);
}

$query = '
SELECT
    *,
    validated_on AS occured_on
  FROM '.PPCREDITS_PAID_TABLE.'
  WHERE validated_on IS NOT NULL
    AND '.implode(' AND ', $clauses).'
;';
$paid_lines = query2array($query);

$query = '
SELECT
    *,
    used_on AS occured_on
  FROM '.PPCREDITS_SPENT_TABLE.'
    LEFT JOIN '.IMAGES_TABLE.' AS i ON image_id = i.id
  WHERE '.implode(' AND ', $clauses).'
;';
$spent_lines = query2array($query);

$history_lines = array_merge($paid_lines, $spent_lines);

// echo '<pre>'; print_r($history_lines); echo '</pre>';

foreach ($history_lines as $key => $row)
{
  $occured_on[$key]  = $row['occured_on'];
  
  $history_lines[$key]['since'] = time_since($row['occured_on'], 'year');
  $history_lines[$key]['occured_on_string'] = format_date($row['occured_on'], array('day', 'month', 'year', 'time'));
  
  $history_lines[$key]['user'] = isset($user_details[ $row['user_id'] ])
    ? $user_details[ $row['user_id'] ]['username']
    : 'deleted'
    ;

  $history_lines[$key]['user_email'] = isset($user_details[ $row['user_id'] ])
    ? $user_details[ $row['user_id'] ]['email']
    : 'deleted'
    ;

  if (isset($row['order_uuid'])) // we are on a paid line
  {
    $history_lines[$key]['paid'] = $row['nb_credits'];
    
    $history_lines[$key]['details'] = l10n(
      '%s, %s %s, transaction %s',
      (!empty($row['paypal_transaction_id']) ? 'PayPal' : '?'),
      $row['amount'],
      $row['currency'],
      (!empty($row['paypal_transaction_id']) ? $row['paypal_transaction_id'] : '?')
      );
  }
  else // we are on a spent line
  {
    $history_lines[$key]['spent'] = $row['nb_credits'];
    
    $history_lines[$key]['details'] = l10n(
      '%s (size %s)',
      $row['name'],
      l10n($row['size'])
      );

    $history_lines[$key]['details'] .= sprintf(
      ', <a class="icon-eye" href="%s">%s</a>',
      'admin.php?page=photo-'.$row['image_id'],
      l10n('view')
      );
  }
}

if (count($history_lines) > 0)
{
  array_multisort($occured_on, SORT_DESC, $history_lines);
}

$template->assign('history_lines', $history_lines);

// +-----------------------------------------------------------------------+
// | sending html code                                                     |
// +-----------------------------------------------------------------------+

$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');
?>