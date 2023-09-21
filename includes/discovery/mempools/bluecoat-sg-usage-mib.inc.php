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
    if (str_contains($entry['deviceUsageName'], 'Memory')) {
        $descr = $entry['deviceUsageName'];
        $oid   = ".1.3.6.1.4.1.3417.2.4.1.1.1.4.$index";
        $perc  = $entry['deviceUsagePercent'];

        discover_mempool($valid['mempool'], $device, $index, $mib, $descr, 1, 100, $perc);
    }
}

unset($av_array);

// EOF
