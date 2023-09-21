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

// Ignore this discovery module if we have already discovered things in CISCO-ENHANCED-MEMPOOL-MIB. Dirty duplication.
if (!isset($valid['mempool']['cisco-enhanced-mempool-mib']) && !isset($valid['mempool']['CISCO-ENHANCED-MEMPOOL-MIB']) &&
    !isset($valid['mempool']['cisco-memory-pool-mib']) && !isset($valid['mempool']['CISCO-MEMORY-POOL-MIB'])) {
    $mempool_array = snmpwalk_cache_oid($device, 'cpmCPUMemoryUsed', [], $mib);
    $mempool_array = snmpwalk_cache_oid($device, 'cpmCPUMemoryFree', $mempool_array, $mib);
    $mempool_array = snmpwalk_cache_oid($device, 'cpmCPUTotalPhysicalIndex', $mempool_array, $mib);

    foreach ($mempool_array as $index => $entry) {
        if (is_numeric($entry['cpmCPUMemoryUsed']) && is_numeric($entry['cpmCPUMemoryFree'])) {
            $descr = '';
            if (!safe_empty($entry['cpmCPUTotalPhysicalIndex'])) {
                $descr = snmp_cache_oid($device, 'entPhysicalName.' . $entry['cpmCPUTotalPhysicalIndex'], 'ENTITY-MIB');
            }
            if (safe_empty($descr)) {
                $descr = "Memory Pool $index";
            }

            $used  = $entry['cpmCPUMemoryUsed'];
            $free  = $entry['cpmCPUMemoryFree'];
            $total = $used + $free;

            discover_mempool($valid['mempool'], $device, $index, 'CISCO-PROCESS-MIB', $descr, 1024, $total, $used);
        }
    }
}

// EOF
