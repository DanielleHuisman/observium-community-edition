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

// FIXME. Need snmpwalk for total size: F10-CHASSIS-MIB::chSysProcessorMemSize
// F10-CHASSIS-MIB::chRpmMemUsageUtil.1 = 5
// F10-CHASSIS-MIB::chRpmMemUsageUtil.2 = 36
// F10-CHASSIS-MIB::chRpmMemUsageUtil.3 = 9

$mempool_array = snmpwalk_cache_oid($device, 'chRpmMemUsageUtil', [], $mib);

if (!safe_empty($mempool_array)) {
    $total_array = snmpwalk_cache_oid($device, 'chSysProcessorMemSize.1', [], $mib);
    print_debug_vars($total_array);

    foreach ($mempool_array as $index => $entry) {
        if (is_numeric($entry['chRpmMemUsageUtil'])) {
            if (is_numeric($total_array['1.' . $index]['chSysProcessorMemSize'])) {
                $precision = 1024 * 1024;
                $total     = $total_array['1.' . $index]['chSysProcessorMemSize']; // FTOS display memory in MB
                //$total    *= $precision;
            } else {
                $precision = 1;
                $total     = 1090519040; // Hardcoded total. See FIXME above.
            }
            $percent = $entry['chRpmMemUsageUtil'];
            $used    = $total * $percent / 100;
            $descr   = $index == 1 ? 'CP' : 'RP' . ($index - 1);
            discover_mempool($valid['mempool'], $device, $index, 'F10-CHASSIS-MIB', $descr, $precision, $total, $used);
        }
    }
}

unset($mempool_array, $total_array, $index, $descr, $precision, $total, $used, $percent);

// EOF
