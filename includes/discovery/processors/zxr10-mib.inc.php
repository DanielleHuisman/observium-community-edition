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

$processors_array = snmpwalk_cache_oid($device, 'zxr10SystemUnitTable', [], $mib);
$processors_count = count($processors_array);
//print_vars($processors_array);

foreach ($processors_array as $index => $entry) {
    if ($entry['zxr10SystemUnitRunStatus'] == 'down') {
        continue;
    }

    $descr = 'CPU';
    if ($processors_count > 1) {
        $descr = 'Unit ' . $index . ' ' . $descr;
    }
    if (is_numeric($entry['zxr10SystemCpuUtility5m'])) {
        $oid_name = 'zxr10SystemCpuUtility5m';
        $oid_num  = '.1.3.6.1.4.1.3902.3.3.1.1.12.' . $index;
    } else {
        $oid_name = 'zxr10SystemCpuUtility2m';
        $oid_num  = '.1.3.6.1.4.1.3902.3.3.1.1.5.' . $index;
    }

    $type  = $mib . '-' . $oid_name;
    $usage = $entry[$oid_name];

    discover_processor($valid['processor'], $device, $oid_num, $index, $type, $descr, 1, $usage);
}

unset($processors_array);

// EOF
