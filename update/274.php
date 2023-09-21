<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($row = dbFetchRow("SELECT * FROM config WHERE `config_key` = 'location_menu_geocoded'"))
{
  $value = safe_unserialize($row['config_value']);
  if ($value) { $newtype = 'geocoded'; } else { $newtype = 'plain'; }
  
  dbInsert(array('config_key' => 'location|menu|type', 'config_value' => serialize($newtype)), 'config');
  dbDelete('config', '`config_key` = ?', array('location_menu_geocoded'));
  echo('.');
}

// EOF
