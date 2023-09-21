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

$processors_array = snmpwalk_cache_triple_oid($device, 'snAgentCpuUtilEntry', $processors_array, $mib);

foreach ($processors_array as $index => $entry) {
    if ((isset($entry['snAgentCpuUtilValue']) || isset($entry['snAgentCpuUtil100thPercent'])) && $entry['snAgentCpuUtilInterval'] == 300) {
        #$entPhysicalIndex = $entry['cpmCPUTotalPhysicalIndex'];

        if ($entry['snAgentCpuUtil100thPercent']) {
            $usage_oid = ".1.3.6.1.4.1.1991.1.1.2.11.1.1.6.$index";
            $usage     = $entry['snAgentCpuUtil100thPercent'];
            $precision = 100;
        } elseif ($entry['snAgentCpuUtilValue']) {
            $usage_oid = ".1.3.6.1.4.1.1991.1.1.2.11.1.1.4.$index";
            $usage     = $entry['snAgentCpuUtilValue'];
            $precision = 100;
        }

        [$slot, $instance, $interval] = explode('.', $index);

        $descr_oid = 'snAgentConfigModuleDescription.' . $entry['snAgentCpuUtilSlotNum'];
        $descr     = snmp_get($device, $descr_oid, '-Oqv', $mib);
        $descr     = str_replace('"', '', $descr);
        [$descr] = explode(' ', $descr);

        $descr = 'Slot ' . $entry['snAgentCpuUtilSlotNum'] . ' ' . $descr;
        $descr = $descr . ' [' . $instance . ']';

        if (!strstr($descr, 'No') && !strstr($usage, 'No') && $descr != '') {
            discover_processor($valid['processor'], $device, $usage_oid, $index, 'ironware', $descr, $precision, $usage, $entPhysicalIndex);
        }
    }
}

unset ($processors_array);

// EOF
