<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */

// FIXME : bit hacky, I think.

if ($vars['id'] == OBS_VAR_UNSET) {
  $vars['id'] = '';
}

foreach (dbFetchRows("SELECT * FROM `devices` WHERE `location` = ?", [$vars['id']]) as $device) {
  if ($auth || device_permitted($device_id)) {
    $devices[] = $device;
    //$title     = $vars['id'];
    $auth      = TRUE;
  }
}

// EOF
