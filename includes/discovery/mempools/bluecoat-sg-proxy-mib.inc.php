<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) Adam Armstrong
 *
 */

$mempool_array = snmpwalk_cache_oid($device, 'sgProxyMem', [], $mib);

foreach ($mempool_array as $index => $entry) {
    if (is_numeric($index) && is_numeric($entry['sgProxyMemAvailable'])) {
        $total = $entry['sgProxyMemAvailable'];
        $used  = $entry['sgProxyMemSysUsage'];
        discover_mempool($valid['mempool'], $device, $index, 'BLUECOAT-SG-PROXY-MIB', "Memory $index", 1, $total, $used);
    }
}

unset($mempool_array, $index, $total, $used);

// EOF
