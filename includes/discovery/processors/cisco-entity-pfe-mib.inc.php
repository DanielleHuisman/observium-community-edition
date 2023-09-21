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

$pxf_array = snmpwalk_cache_oid($device, 'cePfePerfCurrent5MinUtilization', [], $mib);

foreach ($pxf_array as $index => $entry) {
    $descr = 'PXF engine';
    if (count($pxf_array) > 1) {
        $descr .= ' ' . $index;
    }

    $oid = ".1.3.6.1.4.1.9.9.265.1.1.2.1.5.$index";
    discover_processor($valid['processor'], $device, $oid, $index, 'cisco-entity-pfe-mib', $descr, 1, $entry['cePfePerfCurrent5MinUtilization']);
}

// EOF
