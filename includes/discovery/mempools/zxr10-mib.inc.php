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

$mempool_array = snmpwalk_cache_oid($device, 'zxr10SystemUnitTable', [], $mib);
$mempool_count = count($mempool_array);
if (OBS_DEBUG > 1 && $mempool_count) {
    print_vars($mempool_array);
}

foreach ($mempool_array as $index => $entry) {
    if ($entry['zxr10SystemUnitRunStatus'] == 'down') {
        continue;
    }

    $descr = 'Memory';
    if ($mempool_count > 1) {
        $descr = 'Unit ' . $index . ' ' . $descr;
    }

    $oid_name = 'zxr10SystemMemUsed'; // Percent
    $oid_num  = '.1.3.6.1.4.1.3902.3.3.1.1.4.' . $index;
    $type     = $mib . '-' . $oid_name;

    $percent = $entry[$oid_name];
    $total   = $entry['zxr10SystemMemSize'];
    $used    = $total * $percent / 100;

    discover_mempool($valid['mempool'], $device, $index, $mib, $descr, 1, $total, $used);
}

unset ($mempool_array, $index, $descr, $precision, $total, $used, $percent);

// EOF
