<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Redetect OS if necessary (controlled by discover_device function)
if ($detect_os)
{
  $os = get_device_os($device);

  if ($os != $device['os'])
  {
    $type = (isset($config['os'][$os]['type']) ? $config['os'][$os]['type'] : 'unknown'); // Also change $type
    print_cli_data('Device OS changed', $device['os']." -> $os", 1);
    log_event('OS changed: '.$device['os'].' -> '.$os, $device, 'device', $device['device_id'], 'warning');

    // Additionally reset icon and type for device if os changed 
    dbUpdate(array('os' => $os, 'icon' => array('NULL'), 'type' => $type), 'devices', '`device_id` = ?', array($device['device_id']));
    if (isset($attribs['override_icon']))
    {
      del_entity_attrib('device', $device, 'override_icon');
    }
    if (isset($attribs['override_type']))
    {
      del_entity_attrib('device', $device, 'override_type');
    }

    $device['os']   = $os;
    $device['type'] = $type;

    // Set device sysObjectID when device os changed
    $sysObjectID = snmp_cache_sysObjectID($device);
    if ($device['sysObjectID'] != $sysObjectID)
    {
      dbUpdate(array('sysObjectID' => $sysObjectID), 'devices', '`device_id` = ?', array($device['device_id']));
      $device['sysObjectID'] = $sysObjectID;
    }
  }
}

// EOF
