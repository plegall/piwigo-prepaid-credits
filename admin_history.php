<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based picture gallery                                  |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2011 Piwigo Team                  http://piwigo.org |
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
    '.$conf['user_fields']['id'].' AS id,
    '.$conf['user_fields']['username'].' AS username,
    ppcredits
  FROM '.USERS_TABLE.' AS u
    INNER JOIN '.USER_INFOS_TABLE.' AS uf ON uf.user_id = u.'.$conf['user_fields']['id'].'
  WHERE id != '.$conf['guest_id'].'
;';
$result = pwg_query($query);
while ($row = pwg_db_fetch_assoc($result))
{
  $users[$row['id']] = $row['username'].' ('.$row['ppcredits'].' credits left)';

  $user_details[ $row['id'] ] = $row;
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
      '%s',
      $row['name']
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