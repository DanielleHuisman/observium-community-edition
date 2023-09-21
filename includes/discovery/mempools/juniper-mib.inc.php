<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$mempool_array = snmpwalk_cache_oid($device, 'jnxOperatingBuffer', [], $mib);

if (!safe_empty($mempool_array)) {
    $mempool_array = snmpwalk_cache_oid($device, 'jnxOperatingMemory', $mempool_array, $mib);
    $mempool_array = snmpwalk_cache_oid($device, 'jnxOperatingDRAMSize', $mempool_array, $mib);
    $mempool_array = snmpwalk_cache_oid($device, 'jnxOperatingDescr', $mempool_array, $mib);
    foreach ($mempool_array as $index => $entry) {
        $descr = $entry['jnxOperatingDescr'];
        if (!is_numeric($entry['jnxOperatingBuffer']) || str_icontains_array($descr, ['sensor', 'fan', 'pcmcia', 'no'])) {
            continue;
        }
        if ($entry['jnxOperatingDRAMSize']) {
            $precision = 1;
            $total     = $entry['jnxOperatingDRAMSize'];  // Size in bytes
        } elseif ($entry['jnxOperatingMemory']) {
            $precision = 1024 * 1024;
            $total     = $entry['jnxOperatingMemory'];    // Size in megabytes
        } else {
            continue;
        }
        $percent = $entry['jnxOperatingBuffer'];
        $used    = $total * $percent / 100;

        discover_mempool($valid['mempool'], $device, $index, $mib, $descr, $precision, $total, $used);
    }
}

unset($mempool_array, $index, $descr, $precision, $total, $used, $percent);

// EOF
