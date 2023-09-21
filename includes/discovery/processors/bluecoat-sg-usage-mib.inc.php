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

// ProxyAV devices hide their CPUs/Memory/Interfaces in here

$av_array = snmpwalk_cache_oid($device, 'deviceUsage', [], $mib);

foreach ($av_array as $index => $entry) {
    if (str_contains($entry['deviceUsageName'], 'CPU')) {
        $descr = $entry['deviceUsageName'];
        $oid   = ".1.3.6.1.4.1.3417.2.4.1.1.1.4.$index";
        $usage = $entry['deviceUsagePercent'];

        discover_processor($valid['processor'], $device, $oid, $index, 'cpu', $descr, 1, $usage);
    }
}

unset($av_array, $descr, $oid, $usage, $index, $entry);

// EOF
