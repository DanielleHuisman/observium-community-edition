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

//ISILON-MIB::nodeCPUUser.0 = Gauge32: 25
//ISILON-MIB::nodeCPUNice.0 = Gauge32: 0
//ISILON-MIB::nodeCPUSystem.0 = Gauge32: 53
//ISILON-MIB::nodeCPUInterrupt.0 = Gauge32: 13
//ISILON-MIB::nodeCPUIdle.0 = Gauge32: 909
//ISILON-MIB::nodePerCPUUser.1 = Gauge32: 6
//ISILON-MIB::nodePerCPUUser.2 = Gauge32: 1
//ISILON-MIB::nodePerCPUNice.1 = Gauge32: 0
//ISILON-MIB::nodePerCPUNice.2 = Gauge32: 0
//ISILON-MIB::nodePerCPUSystem.1 = Gauge32: 25
//ISILON-MIB::nodePerCPUSystem.2 = Gauge32: 24
//ISILON-MIB::nodePerCPUInterrupt.1 = Gauge32: 7
//ISILON-MIB::nodePerCPUInterrupt.2 = Gauge32: 3
//ISILON-MIB::nodePerCPUIdle.1 = Gauge32: 962
//ISILON-MIB::nodePerCPUIdle.2 = Gauge32: 972

// Skip this processors discovery if HOST-RESOURCES-MIB discovered and exist
if (isset($valid['processor']['hr']) || isset($valid['processor']['hr-average'])) {
    return;
}

$processors_array = snmpwalk_cache_oid($device, 'nodePerCPUIdle', [], 'ISILON-MIB');
//print_vars($processors_array);

$processors_count = count($processors_array);
foreach ($processors_array as $index => $entry) {
    $dot_index = ".{$index}";
    $descr     = 'Processor ' . strval($index - 1);
    $oid_table = 'nodeCPUPerfTable';
    $oid_name  = 'nodePerCPUIdle';
    $oid_num   = ".1.3.6.1.4.1.12124.2.2.3.10.1.5{$dot_index}";
    $usage     = $entry[$oid_name];
    $idle      = 1;

    discover_processor($valid['processor'], $device, $oid_num, $oid_table . $dot_index, $oid_name, $descr, 10, $usage, NULL, NULL, $idle);
}

unset($processors_array, $dot_index, $entry, $descr, $oid_table, $oid_name, $oid_num, $usage, $idle);

// EOF
