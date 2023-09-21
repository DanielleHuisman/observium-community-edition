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

// DELL-NETWORKING-CHASSIS-MIB

//DELL-NETWORKING-CHASSIS-MIB::dellNetCpuUtil5Min.stack.1.1 = Gauge32: 14 percent

$processors_array = snmpwalk_cache_threepart_oid($device, 'dellNetCpuUtil5Min', [], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
//print_vars($processors_array);

foreach ($processors_array as $type => $entry1) {
    // Hrm, this is possible for multiple types?
    $first_unit = array_shift(array_keys($entry1));
    foreach ($entry1 as $unit => $entry2) {
        $processors_count = count($entry2);
        foreach ($entry2 as $processor => $entry) {
            $dot_index = ".{$type}.{$unit}.{$processor}";
            $descr     = 'Unit ' . strval($unit - $first_unit);
            if ($processors_count > 1) {
                $descr .= " Processor {$processor}";
            }
            $oid_table = 'dellNetCpuUtilTable';
            $oid_name  = 'dellNetCpuUtil5Min';
            $oid_num   = ".1.3.6.1.4.1.6027.3.26.1.4.4.1.5{$dot_index}";
            $usage     = $entry[$oid_name];

            discover_processor($valid['processor'], $device, $oid_num, $oid_table . $dot_index, $oid_name, $descr, 1, $usage);
        }
    }
}

unset($processors_array, $dot_index, $entry, $descr, $oid_table, $oid_name, $oid_num, $usage);

// EOF
