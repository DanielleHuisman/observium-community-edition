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

// S5-CHASSIS-MIB::s5ChasUtilMemoryTotalMB.3.10.0 = Gauge32: 128 MegaBytes
// S5-CHASSIS-MIB::s5ChasUtilMemoryAvailableMB.3.10.0 = Gauge32: 65 MegaBytes

$mempool_array = snmpwalk_cache_oid($device, 's5ChasUtilEntry', [], $mib);
//$mempool_array = snmpwalk_cache_oid($device, 's5ChasComTable', $mempool_array, 'S5-CHASSIS-MIB:S5-REG-MIB');
//print_vars($mempool_array);

if (!safe_empty($mempool_array)) {
    $i = 1;
    foreach ($mempool_array as $index => $entry) {
        if (is_numeric($entry['s5ChasUtilMemoryAvailableMB']) && is_numeric($entry['s5ChasUtilMemoryTotalMB'])) {
            $precision = 1024 * 1024;
            $total     = $entry['s5ChasUtilMemoryTotalMB'];
            //$total    *= $precision;
            $free = $entry['s5ChasUtilMemoryAvailableMB'];
            //$free     *= $precision;
            $used  = $total - $free;
            $descr = "Memory Unit $i";
            discover_mempool($valid['mempool'], $device, $index, 'S5-CHASSIS-MIB', $descr, $precision, $total, $used);
            $i++;
        }
    }
}

unset($mempool_array, $index, $descr, $precision, $total, $used, $free, $i);

// EOF
