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

$mempool_array = snmpwalk_cache_oid($device, 'cempMemPoolEntry', [], $mib);

foreach ($mempool_array as $index => $entry) {
    if (is_numeric($entry['cempMemPoolUsed']) && $entry['cempMemPoolValid'] === 'true') {
        if (is_numeric($entry['cempMemPoolHCUsed'])) {
            // Use HC counters
            $hc = 1;
            print_debug('HC');
            $used = $entry['cempMemPoolHCUsed'];
            $free = $entry['cempMemPoolHCFree'];
        } else {
            // Use 32bit counters
            $hc   = 0;
            $used = $entry['cempMemPoolUsed'];
            $free = $entry['cempMemPoolFree'];
        }
        $total = $used + $free;

        [$entPhysicalIndex] = explode('.', $index);
        $entPhysicalName = trim(snmp_cache_oid($device, "entPhysicalName.$entPhysicalIndex", 'ENTITY-MIB'));

        $descr = $entPhysicalName . ' (' . $entry['cempMemPoolName'] . ')';
        $descr = str_replace(['Cisco ', 'Network Processing Engine', 'CPU of'], '', $descr);
        $descr = preg_replace('/Sub-Module (\d+) CFC Card/', "Module \\1 CFC", $descr);

        discover_mempool($valid['mempool'], $device, $index, 'CISCO-ENHANCED-MEMPOOL-MIB', $descr, 1, $total, $used, $hc);
    }
}

unset($mempool_array, $index, $descr, $total, $used, $free, $entPhysicalIndex, $entPhysicalName);

// EOF
