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

echo("ENTITY-MIB ");

$entity_array = snmpwalk_cache_oid($device, "entPhysicalEntry", array(), snmp_mib_entity_vendortype($device, 'ENTITY-MIB'));
if (snmp_status())
{
  $entity_array = snmpwalk_cache_twopart_oid($device, "entAliasMappingIdentifier", $entity_array, 'ENTITY-MIB:IF-MIB');

  $GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']] = $entity_array; // Cache this array for sensors discovery (see in cisco-entity-sensor-mib or entity-sensor-mib)

  foreach ($entity_array as $entPhysicalIndex => $entry)
  {
    if ($device['os'] == "hpuww") // Root-Container does not have ContainedIn = 0, so Inventory can not be shown
    {
      if ($entPhysicalIndex == 1)
      {
        $entry['entPhysicalContainedIn'] = 0;
      }
    }
    if (isset($entity_array[$entPhysicalIndex]['0']['entAliasMappingIdentifier']))
    {
      $ifIndex = $entity_array[$entPhysicalIndex]['0']['entAliasMappingIdentifier'];
      if (!strpos($ifIndex, "fIndex") || $ifIndex == '')
      {
        unset($ifIndex);
      } else {
        list(,$ifIndex) = explode(".", $ifIndex);
        $entry['ifIndex'] = $ifIndex;
      }
    }

    if (isset($config['rewrites']['entPhysicalVendorTypes'][$entry['entPhysicalVendorType']]) && !$entry['entPhysicalModelName'])
    {
      $entry['entPhysicalModelName'] = $config['rewrites']['entPhysicalVendorTypes'][$entry['entPhysicalVendorType']];
    }

    if ($entry['entPhysicalDescr'] || $entry['entPhysicalName'])
    {
      discover_inventory($device, $entPhysicalIndex, $entry, $mib);
    }
  }
}

// EOF
