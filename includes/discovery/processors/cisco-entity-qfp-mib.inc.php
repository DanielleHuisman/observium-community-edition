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

$array = snmpwalk_cache_oid($device, 'ceqfpUtilizationEntry', array(), $mib);

if (is_array($array))
{
  foreach ($array as $index => $entry)
  {
    list($entPhysicalIndex, $interval) = explode('.', $index);
    if ($interval == 'fiveMinutes' && is_numeric($entry['ceqfpUtilProcessingLoad']))
    {
      $descr = snmp_get($device, "entPhysicalName.$entPhysicalIndex", '-Oqv', 'ENTITY-MIB');
      $usage_oid = ".1.3.6.1.4.1.9.9.715.1.1.6.1.14.$entPhysicalIndex.3";

      discover_processor($valid['processor'], $device, $usage_oid, $entPhysicalIndex, 'qfp', $descr, 1, $entry['ceqfpUtilProcessingLoad'], $entPhysicalIndex);
    }
  }
}

// EOF
