<?php
define('PHPWG_ROOT_PATH','../../');

include_once(PHPWG_ROOT_PATH.'include/common.inc.php');
include_once(PHPWG_ROOT_PATH.'plugins/prepaid_credits/include/functions.inc.php');

$page['errors'] = array();
$page['infos'] = array();

ppcredits_paypal_ipn();
?>