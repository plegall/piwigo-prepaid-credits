<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

class prepaid_credits_maintain extends PluginMaintain
{
  private $installed = false;

  function __construct($plugin_id)
  {
    parent::__construct($plugin_id);
  }

  function install($plugin_version, &$errors=array())
  {
    global $prefixeTable;
    
    // create images.ppcredits_price
    $result = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "ppcredits_price";');
    if (!pwg_db_num_rows($result))
    {
      pwg_query('ALTER TABLE `'.IMAGES_TABLE.'` ADD `ppcredits_price` int DEFAULT NULL;');
    }

    // add piwigo_user_infos.ppcredits : how many credits the use has
    $result = pwg_query('SHOW COLUMNS FROM `'.USER_INFOS_TABLE.'` LIKE "ppcredits";');
    if (!pwg_db_num_rows($result))
    {
      pwg_query('ALTER TABLE `'.USER_INFOS_TABLE.'` ADD `ppcredits` INT NOT NULL DEFAULT 0;');
    }

    $query = '
CREATE TABLE IF NOT EXISTS '.$prefixeTable.'ppcredits_paid (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_uuid` char(13) NOT NULL,
  `user_id` mediumint(8) unsigned NOT NULL,
  `created_on` datetime NOT NULL,
  `validated_on` datetime DEFAULT NULL,
  `cancelled_on` datetime DEFAULT NULL,
  `nb_credits` int(11) NOT NULL,
  `amount` decimal(7,2) DEFAULT NULL,
  `currency` varchar(10) NOT NULL,
  `paypal_transaction_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;';
    pwg_query($query);

    $query = '
CREATE TABLE IF NOT EXISTS '.$prefixeTable.'ppcredits_spent (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` mediumint(8) unsigned NOT NULL,
  `image_id` mediumint(8) unsigned NOT NULL,
  `used_on` datetime NOT NULL,
  `nb_credits` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;';
    pwg_query($query);
    
    $this->installed = true;
  }

  function activate($plugin_version, &$errors=array())
  {
    global $prefixeTable;
    
    if (!$this->installed)
    {
      $this->install($plugin_version, $errors);
    }
  }

  function update($old_version, $new_version, &$errors=array())
  {
    $this->install($new_version, $errors);
  }
  
  function deactivate()
  {
  }

  function uninstall()
  {
    global $prefixeTable;
  
    pwg_query('DROP TABLE '.$prefixeTable.'ppcredits_paid;');
    pwg_query('DROP TABLE '.$prefixeTable.'ppcredits_spent;');
    pwg_query('ALTER TABLE '.IMAGES_TABLE.' DROP COLUMN ppcredits_price;');
    pwg_query('ALTER TABLE '.USER_INFOS_TABLE.' DROP COLUMN ppcredits;');
  }
}
?>
