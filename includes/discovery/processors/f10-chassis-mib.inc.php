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

// Force10 E-Series

// chRpmCpuUtil5Min.1 = Gauge32: 34
// chRpmCpuUtil5Min.2 = Gauge32: 34
// chRpmCpuUtil5Min.3 = Gauge32: 34

$processors_array = snmpwalk_cache_oid($device, 'chRpmCpuUtil5Min', [], $mib);

foreach ($processors_array as $index => $entry) {
    $descr = ($index == 1) ? 'CP' : 'RP' . ($index - 1);
    $oid   = ".1.3.6.1.4.1.6027.3.1.1.3.7.1.5.$index";
    $usage = $entry['chRpmCpuUtil5Min'];

    discover_processor($valid['processor'], $device, $oid, $index, 'ftos-eseries', $descr, 1, $usage);
}

unset($processors_array, $descr, $oid, $index, $entry, $usage);

// EOF
