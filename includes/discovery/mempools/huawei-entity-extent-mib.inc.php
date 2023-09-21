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

// Huawei VRP mempools

$mempool_array = snmpwalk_cache_oid($device, "hwEntityMemUsage", [], $mib);

if (!safe_empty($mempool_array)) {
    $mempool_array = snmpwalk_cache_oid($device, "hwEntityMemSize", $mempool_array, $mib);
    $mempool_array = snmpwalk_cache_oid($device, "hwEntityMemUsageThreshold", $mempool_array, $mib);
    $mempool_array = snmpwalk_cache_oid($device, "entPhysicalName", $mempool_array, 'ENTITY-MIB');
    foreach ($mempool_array as $index => $entry) {
        if (isset($entry['hwEntityMemSize'])) {
            // not all platforms have hwEntityMemSize.. what tf
            if ($entry['hwEntityMemSize'] == 0) {
                print_debug("Entity is not support Memory usage:");
                print_debug_vars($entry);
                continue;
            }
        } elseif (isset($entry['hwEntityMemUsageThreshold']) && $entry['hwEntityMemUsageThreshold'] == 0 && $entry['hwEntityMemUsage'] == 0) {
            print_debug("Entity is not support Memory usage:");
            print_debug_vars($entry);
            continue;
        }

        $descr   = rewrite_entity_name($entry['entPhysicalName']);
        $percent = $entry['hwEntityMemUsage'];
        if (!safe_empty($descr) && !str_contains($descr, 'No') && !str_contains($percent, 'No')) {
            $total = isset($entry['hwEntityMemSize']) && $entry['hwEntityMemSize'] > 0 ? $entry['hwEntityMemSize'] : 100;
            $used  = $total * $percent / 100;
            discover_mempool($valid['mempool'], $device, $index, 'HUAWEI-ENTITY-EXTENT-MIB', $descr, 1, $total, $used);
        }
    }
}

unset($mempool_array, $index, $descr, $total, $used, $percent);

// EOF
