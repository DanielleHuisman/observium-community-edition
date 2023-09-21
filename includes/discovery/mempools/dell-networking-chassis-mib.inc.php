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

// DELL-NETWORKING-CHASSIS-MIB::dellNetCpuUtilMemUsage.stack.1.1 = Gauge32: 41 percent
// DELL-NETWORKING-CHASSIS-MIB::dellNetProcessorMemSize.stack.1.1 = INTEGER: 2029

$mempool_array = snmpwalk_cache_threepart_oid($device, 'dellNetCpuUtilMemUsage', [], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
if (!safe_empty($mempool_array)) {
    $mempool_array = snmpwalk_cache_threepart_oid($device, 'dellNetProcessorMemSize', $mempool_array, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    print_debug_vars($mempool_array);

    foreach ($mempool_array as $type => $entry1) {
        // Hrm, this is possible for multiple types?
        $first_unit = array_key_first($entry1);

        foreach ($entry1 as $unit => $entry2) {
            $mempool_count = count($entry2);
            foreach ($entry2 as $mempool => $entry) {
                $index     = "{$type}.{$unit}.{$mempool}";
                $dot_index = ".{$index}";
                $descr     = 'Unit ' . ($unit - $first_unit);
                if ($mempool_count > 1) {
                    $descr .= " Memory {$mempool}";
                }

                $oid_table = 'dellNetCpuUtilTable';
                $oid_name  = 'dellNetCpuUtilMemUsage';
                $precision = 1024 * 1024;
                $total     = $entry['dellNetProcessorMemSize'];
                $percent   = $entry['dellNetCpuUtilMemUsage'];
                $used      = $total * $percent / 100;

                discover_mempool($valid['mempool'], $device, $index, $mib, $descr, $precision, $total, $used);
            }
        }
    }
}

unset($mempool_array, $index, $descr, $precision, $total, $used, $percent);

// EOF
