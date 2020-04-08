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

// Huawei VRP mempools

$mempool_array = snmpwalk_cache_multi_oid($device, "hwEntityMemUsage", array(), $mib);

if (is_array($mempool_array))
{
  $mempool_array = snmpwalk_cache_multi_oid($device, "hwEntityMemSize", $mempool_array, $mib);
  $mempool_array = snmpwalk_cache_multi_oid($device, "entPhysicalName", $mempool_array, 'ENTITY-MIB');
  foreach ($mempool_array as $index => $entry)
  {
    if (is_numeric($entry['hwEntityMemUsage']) && $entry['hwEntityMemSize'] > 0 )
    {
      $descr   = rewrite_entity_name($entry['entPhysicalName']);
      $percent = $entry['entPhysicalName'];
      if (!strstr($descr, "No") && !strstr($percent, "No") && $descr != "" )
      {
        $total = $entry['hwEntityMemSize'];
        $used  = $total * $percent / 100;
        discover_mempool($valid['mempool'], $device, $index, 'HUAWEI-ENTITY-EXTENT-MIB', $descr, 1, $total, $used);
      }
    }
  }
}

unset ($mempool_array, $index, $descr, $total, $used, $percent);

// EOF
