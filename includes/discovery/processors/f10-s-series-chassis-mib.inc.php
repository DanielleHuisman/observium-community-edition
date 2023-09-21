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

// Force10 S-Series

# chStackUnitCpuUtil5Min.1 = Gauge32: 47

$processors_array = snmpwalk_cache_oid($device, 'chStackUnitCpuUtil5Min', [], $mib);
$processors_array = snmpwalk_cache_oid($device, 'chStackUnitSysType', $processors_array, $mib);

foreach ($processors_array as $index => $entry) {
    $descr = 'Unit ' . ($index - 1) . ' ' . $entry['chStackUnitSysType'];
    $oid   = ".1.3.6.1.4.1.6027.3.10.1.2.9.1.4.$index";
    $usage = $entry['chStackUnitCpuUtil5Min'];

    discover_processor($valid['processor'], $device, $oid, $index, 'ftos-sseries', $descr, 1, $usage);
}

unset($processors_array, $index, $entry, $descr, $oid, $usage);

// EOF
