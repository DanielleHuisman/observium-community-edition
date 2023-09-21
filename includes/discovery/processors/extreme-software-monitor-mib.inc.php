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

$processors_array = snmpwalk_cache_oid($device, 'extremeCpuMonitorSystemTable', [], $mib);

foreach ($processors_array as $index => $entry) {
    if (is_numeric($entry['extremeCpuMonitorSystemUtilization5mins']) && is_numeric($index)) {
        $descr = "Slot $index";
        $usage = $entry['extremeCpuMonitorSystemUtilization5mins'];
        $oid   = ".1.3.6.1.4.1.1916.1.32.1.4.1.9.$index";
        discover_processor($valid['processor'], $device, $oid, $index, 'xos', $descr, 1, $usage);
    }
}

unset($processors_array, $index, $descr, $usage, $oid);

// EOF
