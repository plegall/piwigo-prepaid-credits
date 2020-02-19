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

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+

check_status(ACCESS_ADMINISTRATOR);

// +-----------------------------------------------------------------------+
// | template init                                                         |
// +-----------------------------------------------------------------------+

$template->set_filenames(
  array(
    'plugin_admin_content' => dirname(__FILE__).'/admin_config.tpl'
    )
  );

// sizes
$type_map = ImageStdParams::get_defined_type_map();
$sizes_keys = array_diff(array_keys($type_map), array('square', 'thumb'));
$sizes_names = array_map(function ($s) {return l10n($s);}, $sizes_keys);

$sizes_options = array_combine($sizes_keys, $sizes_names);
$sizes_options['original'] = l10n('Original');

$currencies = array(
  'AUD' => 'Australian Dollar',
  'BRL' => 'Brazilian Real',
  'CAD' => 'Canadian Dollar',
  'CZK' => 'Czech Koruna',
  'DKK' => 'Danish Krone',
  'EUR' => 'Euro',
  'HKD' => 'Hong Kong Dollar',
  'HUF' => 'Hungarian Forint',
  'ILS' => 'Israeli New Sheqel',
  'JPY' => 'Japanese Yen',
  'MYR' => 'Malaysian Ringgit',
  'MXN' => 'Mexican Peso',
  'NOK' => 'Norwegian Krone',
  'NZD' => 'New Zealand Dollar',
  'PHP' => 'Philippine Peso',
  'PLN' => 'Polish Zloty',
  'GBP' => 'Pound Sterling',
  'SGD' => 'Singapore Dollar',
  'SEK' => 'Swedish Krona',
  'CHF' => 'Swiss Franc',
  'TWD' => 'Taiwan New Dollar',
  'THB' => 'Thai Baht',
  'USD' => 'U.S. Dollar',
  );

check_input_parameter('currency', $_POST, false, '/^('.implode('|', array_keys($currencies)).')$/');

$time_units = array(
  'minute' => l10n('minute(s)'),
  'hour' => l10n('hour(s)'),
  'day' => l10n('day(s)'),
  );

check_input_parameter('download_period_unity', $_POST, false, '/^('.implode('|', array_keys($time_units)).')$/');

// +-----------------------------------------------------------------------+
// | Save config                                                           |
// +-----------------------------------------------------------------------+

if (isset($_POST['save_config']))
{
  $conf['ppcredits'] = array(
    'download_period' => intval($_POST['download_period_length']).' '.$_POST['download_period_unity'],
    'photo_cost' => intval($_POST['photo_cost']),
    );

  if (!empty($_POST['sell_credits']))
  {

    $conf['ppcredits']['sell_credits'] = true;
    $conf['ppcredits']['paypal_account'] = $_POST['paypal_account'];
    $conf['ppcredits']['price_per_credit'] = sprintf('%.2f', $_POST['price_per_credit']);
    $conf['ppcredits']['currency'] = $_POST['currency'];
  }
  else
  {
    $conf['ppcredits']['sell_credits'] = false;
    $conf['ppcredits']['paypal_account'] = null;
    $conf['ppcredits']['price_per_credit'] = null;
    $conf['ppcredits']['currency'] = 'EUR';
  }

  foreach (array_keys($sizes_options) as $size)
  {
    $conf['ppcredits']['price_coefficient'][$size] = (!empty($_POST['size_'.$size.'_enabled']) and !empty($_POST['size_'.$size])) ? intval($_POST['size_'.$size]) : null;
  }

  conf_update_param('ppcredits', $conf['ppcredits'], true);

  $page['infos'][] = l10n('Information data registered in database');
}

if ($conf['ppcredits']['sell_credits'])
{
  if (!email_check_format($conf['ppcredits']['paypal_account']))
  {
    $page['errors'][] = l10n('Invalid email address for PayPal account');
  }

  if ($conf['ppcredits']['price_per_credit'] < 0.01)
  {
    $page['errors'][] = l10n('The price per credit must be at least 0.01');
  }
}

list($download_period_length, $download_period_unity) = explode(' ', $conf['ppcredits']['download_period']);

$template->assign(
  array(
    'photo_cost' => $conf['ppcredits']['photo_cost'],
    'download_period_length' => $download_period_length,
    'download_period_unity_options' => $time_units,
    'download_period_unity_options_selected' => $download_period_unity,
    'sizes' => $conf['ppcredits']['price_coefficient'],
    'sell_credits' => $conf['ppcredits']['sell_credits'],
    'currency_options' => $currencies,
    'currency_options_selected' => $conf['ppcredits']['currency'],
    'paypal_account' => $conf['ppcredits']['paypal_account'],
    'price_per_credit' => $conf['ppcredits']['price_per_credit'],
    )
  );

// +-----------------------------------------------------------------------+
// | sending html code                                                     |
// +-----------------------------------------------------------------------+

$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');
?>