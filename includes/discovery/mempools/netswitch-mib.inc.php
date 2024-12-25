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

// NETSWITCH-MIB::hpLocalMemSlotIndex.1 = INTEGER: 1
// NETSWITCH-MIB::hpLocalMemSlabCnt.1 = Counter32: 3966
// NETSWITCH-MIB::hpLocalMemFreeSegCnt.1 = Counter32: 166
// NETSWITCH-MIB::hpLocalMemAllocSegCnt.1 = Counter32: 3803
// NETSWITCH-MIB::hpLocalMemTotalBytes.1 = INTEGER: 11337704
// NETSWITCH-MIB::hpLocalMemFreeBytes.1 = INTEGER: 9669100
// NETSWITCH-MIB::hpLocalMemAllocBytes.1 = INTEGER: 1668732

// NETSWITCH-MIB::hpGlobalMemSlotIndex.1 = INTEGER: 1
// NETSWITCH-MIB::hpGlobalMemSlabCnt.1 = Counter32: 3966
// NETSWITCH-MIB::hpGlobalMemFreeSegCnt.1 = Counter32: 166
// NETSWITCH-MIB::hpGlobalMemAllocSegCnt.1 = Counter32: 3803
// NETSWITCH-MIB::hpGlobalMemTotalBytes.1 = INTEGER: 11337704
// NETSWITCH-MIB::hpGlobalMemFreeBytes.1 = INTEGER: 9669104
// NETSWITCH-MIB::hpGlobalMemAllocBytes.1 = INTEGER: 1668728

$mempool_array = snmpwalk_cache_oid($device, 'hpLocal', [], $mib);

foreach ($mempool_array as $index => $entry) {
    if (is_numeric($index) && is_numeric($entry['hpLocalMemTotalBytes'])) {
        $total = $entry['hpLocalMemTotalBytes'];
        $used  = $entry['hpLocalMemAllocBytes'];
        discover_mempool($valid['mempool'], $device, $index, 'NETSWITCH-MIB', "Memory $index", 1, $total, $used);
    }
}

unset($mempool_array, $index, $total, $used);

// EOF
