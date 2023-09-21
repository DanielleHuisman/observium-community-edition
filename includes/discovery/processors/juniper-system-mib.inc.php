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

$processors_array = snmpwalk_cache_double_oid($device, 'juniSystemModule', [], 'Juniper-System-MIB');

foreach ($processors_array as $index => $entry) {
    if ($entry['juniSystemModuleCpuUtilPct'] && $entry['juniSystemModuleCpuUtilPct'] != -1) {
        $entPhysicalIndex = $entry['juniSystemModulePhysicalIndex'];
        $usage_oid        = ".1.3.6.1.4.1.4874.2.2.2.1.3.5.1.3.$index";
        $descr_oid        = ".1.3.6.1.4.1.4874.2.2.2.1.3.5.1.6.$index";
        $descr            = $entry['juniSystemModuleDescr'];
        $usage            = $entry['juniSystemModuleCpuFiveMinAvgPct'];

        if (!strstr($descr, 'No') && !strstr($usage, 'No') && $descr != '') {
            discover_processor($valid['processor'], $device, $usage_oid, $index, 'junose', $descr, 1, $usage, $entPhysicalIndex);
        }
    }
}

unset ($processors_array);

// EOF
