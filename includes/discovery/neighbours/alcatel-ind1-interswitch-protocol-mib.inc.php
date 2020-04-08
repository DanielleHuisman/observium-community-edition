<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$amap_array = snmpwalk_cache_threepart_oid($device, "aipAMAPportConnectionTable", array(), "ALCATEL-IND1-INTERSWITCH-PROTOCOL-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

if ($amap_array)
{
  foreach (array_keys($amap_array) as $key)
  {
    $amap = array_shift(array_shift($amap_array[$key]));

    $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ?", array($device['device_id'], $amap['aipAMAPLocalIfindex']));

    $remote_device_id = FALSE;
    if (is_valid_hostname($amap['aipAMAPRemHostname']))
    {
      if (isset($GLOBALS['cache']['discovery-protocols'][$amap['aipAMAPRemHostname']]))
      {
        // This hostname already checked, skip discover
        $remote_device_id = $GLOBALS['cache']['discovery-protocols'][$amap['aipAMAPRemHostname']];
      } else {
        $remote_device = dbFetchRow("SELECT `device_id`, `hostname` FROM `devices` WHERE `sysName` = ? OR `hostname` = ?", array($amap['aipAMAPRemHostname'], $amap['aipAMAPRemHostname']));
        $remote_device_id = $remote_device['device_id'];

        if (!$remote_device_id && !is_bad_xdp($amap['aipAMAPRemHostname'], $amap['aipAMAPRemDeviceType']))
        {
          $remote_device_id = discover_new_device($amap['aipAMAPRemHostname'], 'xdp', 'AMAP', $device, $port);
        }

        // Cache remote device ID for other protocols
        $GLOBALS['cache']['discovery-protocols'][$amap['aipAMAPRemHostname']] = $remote_device_id;
      }
    }

    if ($remote_device_id)
    {
      $if = $amap['aipAMAPRemSlot']."/".$amap['aipAMAPRemPort'];
      $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE (`ifDescr` = ? OR `ifName` = ?) AND `device_id` = ?", array($if, $if, $remote_device_id));
    } else {
      $remote_port_id = NULL;
    }

    if (!is_bad_xdp($amap['aipAMAPRemHostname']) && is_numeric($port['port_id']) && isset($amap['aipAMAPRemHostname']))
    {
      discover_link($port, 'amap', $remote_port_id, $amap['aipAMAPRemHostname'], $amap['aipAMAPRemSlot']."/".$amap['aipAMAPRemPort'], $amap['aipAMAPRemDeviceType'], $amap['aipAMAPRemDevModelName']);
    }
  }
}

// EOF
