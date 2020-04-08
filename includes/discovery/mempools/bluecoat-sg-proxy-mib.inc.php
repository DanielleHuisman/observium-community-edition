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

$mempool_array = snmpwalk_cache_oid($device, 'sgProxyMem', array(), $mib);

if (is_array($mempool_array))
{
  foreach ($mempool_array as $index => $entry)
  {
    if (is_numeric($index) && is_numeric($entry['sgProxyMemAvailable']))
    {
      $total = $entry['sgProxyMemAvailable'];
      $used  = $entry['sgProxyMemSysUsage'];
      discover_mempool($valid['mempool'], $device, $index, 'BLUECOAT-SG-PROXY-MIB', "Memory $index", 1, $total, $used);
    }
  }
}

unset ($mempool_array, $index, $total, $used);

// EOF
