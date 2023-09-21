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

$processors_array = snmpwalk_cache_oid($device, 'cpmCPU', [], $mib);

foreach ($processors_array as $index => $entry) {
    if (is_numeric($entry['cpmCPUTotal5minRev']) || is_numeric($entry['cpmCPUTotal5min'])) {

        if (isset($entry['cpmCPUTotal5minRev'])) {
            $usage_oid = ".1.3.6.1.4.1.9.9.109.1.1.1.1.8.$index";
            $usage     = $entry['cpmCPUTotal5minRev'];
        } elseif (isset($entry['cpmCPUTotal5min'])) {
            $usage_oid = ".1.3.6.1.4.1.9.9.109.1.1.1.1.5.$index";
            $usage     = $entry['cpmCPUTotal5min'];
        }

        $descr = '';
        if (!safe_empty($entry['cpmCPUTotalPhysicalIndex'])) {
            $descr = snmp_cache_oid($device, 'entPhysicalName.' . $entry['cpmCPUTotalPhysicalIndex'], 'ENTITY-MIB');
        }
        if (safe_empty($descr)) {
            $descr = "Processor $index";
        }

        if (!str_contains($descr, 'No') && !str_contains($usage, 'No') && $descr != '') {
            discover_processor($valid['processor'], $device, $usage_oid, $index, 'cpm', $descr, 1, $usage, $entry['cpmCPUTotalPhysicalIndex']);
        }
    }
}

if (!is_array($valid['processor']['cpm'])) {
    // OLD-CISCO-CPU-MIB::avgBusy5.0
    $avgBusy5 = snmp_get_oid($device, 'avgBusy5.0', 'OLD-CISCO-CPU-MIB', 'cisco'); // not have mib definition
    if (is_numeric($avgBusy5)) {
        discover_processor($valid['processor'], $device, '.1.3.6.1.4.1.9.2.1.58.0', 0, 'ios', 'Processor', 1, $avgBusy5);
    }
}

unset($processors_array);

// EOF
