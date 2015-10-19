<?php
/*
Plugin Name: Prepaid Credits
Version: auto
Description: Visitors buy photos with prepaid credits
Plugin URI: http://piwigo.org/ext/extension_view.php?eid=
Author: plg
Author URI: http://le-gall.net/pierrick
*/

if (!defined('PHPWG_ROOT_PATH'))
{
  die('Hacking attempt!');
}

global $prefixeTable;

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+

defined('PPCREDITS_ID') or define('PPCREDITS_ID', basename(dirname(__FILE__)));
define('PPCREDITS_PATH' , PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
define('PPCREDITS_PAID_TABLE', $prefixeTable.'ppcredits_paid');
define('PPCREDITS_SPENT_TABLE', $prefixeTable.'ppcredits_spent');

include_once(PPCREDITS_PATH.'include/functions.inc.php');

// init the plugin
add_event_handler('init', 'ppcredits_init');
/**
 * plugin initialization
 *   - check for upgrades
 *   - unserialize configuration
 *   - load language
 */
function ppcredits_init()
{
  global $conf, $user;

  // prepare plugin configuration
  // $conf['ppcredits'] = safe_unserialize($conf['ppcredits']);
  $conf['ppcredits'] = array(
    'paypal_account' => 'paypal@piwigo.com',
    'price_per_credit' => 0.01,
    'currency' => 'EUR',
    'download_period' => '7 day',
    'photo_cost' => 1,
    );

  // overwrite $user['enabled_high'] depending on recent purchase
  if ('action' == script_basename() and isset($_GET['id']) and is_numeric($_GET['id']))
  {
    if (!ppcredits_recently_purchased($_GET['id']))
    {
      $user['enabled_high'] = false;
    }
  }
}

add_event_handler('get_admin_plugin_menu_links', 'ppcredits_admin_menu');
function ppcredits_admin_menu($menu)
{
  global $page;

  $name = 'Prepaid Credits';

  array_push(
    $menu,
    array(
      'NAME' => $name,
      'URL'  => get_root_url().'admin.php?page=plugin-prepaid_credits'
      )
    );

  return $menu;
}

add_event_handler('loc_end_profile', 'ppcredits_end_profile', 70);
function ppcredits_end_profile()
{
  global $template, $conf, $user;

  $template->set_prefilter('profile', 'ppcredits_profile_prefilter');
  
  $template->set_filename('credits', realpath(PPCREDITS_PATH.'profile.tpl'));

  $default_nb_credits = 5;

  $template->assign(
    array(
      'CREDITS_LEFT' => $user['ppcredits'],
      'NB_CREDITS' => $default_nb_credits,
      'PRICE_PER_CREDIT' => $conf['ppcredits']['price_per_credit'],
      'PAYPAL_ACCOUNT' => $conf['ppcredits']['paypal_account'],
      'MONEY_AMOUNT' => $default_nb_credits * $conf['ppcredits']['price_per_credit'],
      'CURRENCY' => $conf['ppcredits']['currency'],
      'RETURN_URL' => get_absolute_root_url().'profile.php',
      'IPN_URL' => get_absolute_root_url().'ws.php?method=ppcredits.paypal.ipn',
//      'IPN_URL' => 'http://pigolabs.com/dev/lavaprint/ipn.php',
      )
    );

  $query = '
SELECT
    *,
    validated_on AS occured_on
  FROM '.PPCREDITS_PAID_TABLE.'
  WHERE user_id = '.$user['id'].'
    AND validated_on IS NOT NULL
;';
  $paid_lines = query2array($query);

  $query = '
SELECT
    *,
    used_on AS occured_on
  FROM '.PPCREDITS_SPENT_TABLE.'
    LEFT JOIN '.IMAGES_TABLE.' AS i ON image_id = i.id
  WHERE user_id = '.$user['id'].'
;';
  $spent_lines = query2array($query);

  $history_lines = array_merge($paid_lines, $spent_lines);

  // echo '<pre>'; print_r($history_lines); echo '</pre>';

  foreach ($history_lines as $key => $row)
  {
    $occured_on[$key]  = $row['occured_on'];
    
    $history_lines[$key]['since'] = time_since($row['occured_on'], 'year');
    $history_lines[$key]['occured_on_string'] = format_date($row['occured_on'], array('day', 'month', 'year', 'time'));

    if (isset($row['order_uuid']))
    {
      // we are on a paid line
      $history_lines[$key]['details'] = l10n(
        '%u credits bought on %s (%s %s)',
        $row['nb_credits'],
        (!empty($row['paypal_transaction_id']) ? 'PayPal' : '?'),
        $row['amount'],
        $row['currency']
        );
    }
    else
    {
      // we are on a spent line
      $history_lines[$key]['details'] = l10n(
        '%u credits spent for %s',
        $row['nb_credits'],
        $row['name']
        );

      $history_lines[$key]['details'] .= sprintf(
        ', <a href="%s">%s →</a>',
        make_picture_url(array('image_id' => $row['image_id'], 'image_file' => $row['file'])),
        l10n('open it')
        );
    }
  }

  array_multisort($occured_on, SORT_DESC, $history_lines);

  $template->assign('history_lines', $history_lines);

  $template->assign_var_from_handle('CREDITS_CONTENT', 'credits');
}

function ppcredits_profile_prefilter($content, $smarty)
{
  $pattern = '#\{\$PROFILE_CONTENT\}#';
  $replacement = '{$PROFILE_CONTENT}{$CREDITS_CONTENT}';

  return preg_replace($pattern, $replacement, $content);
}

add_event_handler('ws_add_methods', 'ppcredits_add_methods');
function ppcredits_add_methods($arr)
{
  $service = &$arr[0];
  
  $service->addMethod(
    'ppcredits.paypal.create',
    'ws_ppcredits_paypal_create',
    array(
      'nb_credits' => array('default' => null, 'type' => WS_TYPE_ID),
      ),
    'Create a PayPal order (to be validated by IPN)'
    );

  // warning: this method won't work if $conf['guest_access'] is set to false
  $service->addMethod(
    'ppcredits.paypal.ipn',
    'ws_ppcredits_paypal_ipn',
    array(), // we manage with POST variables directly in the bind function
    'IPN request sent by PayPal'
    );
  
  $service->addMethod(
    'ppcredits.photo.buy',
    'ws_ppcredits_photo_buy',
    array(
      'image_id' => array('default' => null, 'type' => WS_TYPE_ID),
      ),
    'Buy a photo with your credits'
    );
}

add_event_handler('loc_end_picture', 'ppcredits_picture');
function ppcredits_picture()
{
  global $conf, $template, $user, $picture;

  // unset U_DOWNLOAD if user did not recently purchased the photo
  if (!ppcredits_recently_purchased($picture['current']['id']))
  {
    $template->append('current', array('U_DOWNLOAD' => null), true);
    $picture['current']['download_url'] = null;
  }
  
  $template->set_prefilter('picture', 'ppcredits_picture_prefilter');

  $template->set_filename('credits', realpath(PPCREDITS_PATH.'picture.tpl'));

  $template->assign(
    array(
      'CREDITS_LEFT' => $user['ppcredits'],
      'PHOTO_NB_CREDITS' => !empty($picture['current']['ppcredits_price'])
        ? $picture['current']['ppcredits_price']
        : $conf['ppcredits']['photo_cost'],
      )
    );

  $template->assign_var_from_handle('CREDITS_CONTENT', 'credits');
}

function ppcredits_picture_prefilter($content, &$smarty)
{
  $search = '<dl id="standard"';
  
  $replace = '{$CREDITS_CONTENT}'.$search;
  
  $content = str_replace($search, $replace, $content);
  return $content;
}

// +-----------------------------------------------------------------------+
// | Batch Manager                                                         |
// +-----------------------------------------------------------------------+

add_event_handler('loc_begin_element_set_global', 'ppcredits_element_set_global_add_action');
function ppcredits_element_set_global_add_action()
{
  global $template, $page, $conf;
  
  $template->set_filename('ppcredits', realpath(PPCREDITS_PATH.'element_set_global_action.tpl'));
  
  $template->assign(
    array(
      'PPCREDITS_DEFAULT_PRICE' => $conf['ppcredits']['photo_cost'],
      )
    );
  
  $template->append(
    'element_set_global_plugins_actions',
    array(
      'ID' => 'ppcredits',
      'NAME' => l10n('Set price').' (Prepaid Credits)',
      'CONTENT' => $template->parse('ppcredits', true),
      )
    );
}

add_event_handler('element_set_global_action', 'ppcredits_batch_global_submit', EVENT_HANDLER_PRIORITY_NEUTRAL, 2);
function ppcredits_batch_global_submit($action, $collection)
{
  global $page;
  
  // If its our plugin that is called
  if ($action == 'ppcredits')
  {
    check_input_parameter('price', $_POST, false, '/^(default|specific)$/');
    
    if ('specific' == $_POST['price'])
    {
      check_input_parameter('nb_credits', $_POST, false, PATTERN_ID);
    }
    
    $datas = array();
    foreach ($collection as $image_id)
    {
      array_push(
        $datas,
        array(
          'id' => $image_id,
          'ppcredits_price' => ('default' == $_POST['price'] ? null : $_POST['nb_credits']),
          )
        );
    }

    mass_updates(
      IMAGES_TABLE,
      array('primary' => array('id'), 'update' => array('ppcredits_price')),
      $datas
      );

    $page['infos'][] = l10n('Information data registered in database');
  }
}
?>
