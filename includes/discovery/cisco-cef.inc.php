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

echo("Cisco CEF Switching Path: ");

$cefs = snmpwalk_cache_threepart_oid($device, "cefSwitchingPath", array(), 'CISCO-CEF-MIB');
if (OBS_DEBUG > 1) { print_vars($cefs); }

if (is_array($cefs))
{
  if (!is_array($entity_array))
  {
    echo("Caching OIDs: ");
    $entity_array = array();
    echo(" entPhysicalDescr");
    $entity_array = snmpwalk_cache_oid($device, "entPhysicalDescr", $entity_array, "ENTITY-MIB");
    echo(" entPhysicalName");
    $entity_array = snmpwalk_cache_oid($device, "entPhysicalName", $entity_array, "ENTITY-MIB");
    echo(" entPhysicalModelName");
    $entity_array = snmpwalk_cache_oid($device, "entPhysicalModelName", $entity_array, "ENTITY-MIB");
  }
    foreach ($cefs as $entity => $afis)
  {
    $entity_name = $entity_array[$entity]['entPhysicalName'] ." - ".$entity_array[$entity]['entPhysicalModelName'];
    echo("\n$entity $entity_name\n");
    foreach ($afis as $afi => $paths)
    {
      echo(" |- $afi\n");
      foreach ($paths as $path => $path_name)
      {
        echo(" | |-".$path.": ".$path_name['cefSwitchingPath']."\n");
        $cef_exists[$device['device_id']][$entity][$afi][$path] = 1;

        // FIXME, old code was incorrect, but not sure that still fixed..
        //if (dbFetchCell("SELECT COUNT(*) from `cef` WHERE `device_id` = ? AND `entPhysicalIndex` = ? AND `afi` = ? AND `cef_index` = ?", array($device['device_id'], $entity, $afi, $path)) != "1") // Why != 1 ???
        if (!dbExist('cef_switching', '`device_id` = ? AND `entPhysicalIndex` = ? AND `afi` = ? AND `cef_path` = ?', array($device['device_id'], $entity, $afi, $path)))
        {
          dbInsert(array('device_id' => $device['device_id'], 'entPhysicalIndex' => $entity, 'afi' => $afi, 'cef_path' => $path), 'cef_switching');
          echo("+");
        }

      }
    }
  }
}

// FIXME - need to delete old ones. FIXME REALLY.

echo(PHP_EOL);

// EOF
