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

$mempool_array = snmpwalk_cache_oid($device, 'extremeMemoryMonitorSystemTable', [], $mib);

foreach ($mempool_array as $index => $entry) {
    if (is_numeric($entry['extremeMemoryMonitorSystemFree']) && is_numeric($index)) {
        $descr = "Memory $index";
        $free  = $entry['extremeMemoryMonitorSystemFree'];
        $total = $entry['extremeMemoryMonitorSystemTotal'];
        $used  = $total - $free;
        discover_mempool($valid['mempool'], $device, $index, 'EXTREME-SOFTWARE-MONITOR-MIB', $descr, 1024, $total, $used);
    }
}

unset($mempool_array, $index, $descr, $total, $used, $free);

// EOF
