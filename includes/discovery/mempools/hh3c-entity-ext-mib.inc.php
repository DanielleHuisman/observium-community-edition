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

// HH3C-ENTITY-EXT-MIB::hh3cEntityExtMemUsage.30 = INTEGER: 58
// HH3C-ENTITY-EXT-MIB::hh3cEntityExtMemUsage.36 = INTEGER: 59
// HH3C-ENTITY-EXT-MIB::hh3cEntityExtMemUsage.42 = INTEGER: 58
// HH3C-ENTITY-EXT-MIB::hh3cEntityExtMemUsage.48 = INTEGER: 58

$oids          = ['hh3cEntityExtMemUsage', 'hh3cEntityExtMemSize'];
$mempool_array = [];
foreach ($oids as $oid) {
    $mempool_array = snmpwalk_cache_oid($device, $oid, $mempool_array, $mib);
    if (!snmp_status()) {
        break;
    }
}

if (!safe_empty($mempool_array)) {
    $mempool_array = snmpwalk_cache_oid($device, 'entPhysicalName', $mempool_array, 'ENTITY-MIB');

    foreach ($mempool_array as $index => $entry) {
        $entry['hh3cEntityExtMemSize'] = snmp_dewrap32bit($entry['hh3cEntityExtMemSize']);
        if (is_numeric($entry['hh3cEntityExtMemUsage']) && $entry['hh3cEntityExtMemSize'] > 0) {
            $descr   = $entry['entPhysicalName'];
            $percent = $entry['hh3cEntityExtMemUsage'];
            $total   = $entry['hh3cEntityExtMemSize'];
            $used    = $total * $percent / 100;

            discover_mempool($valid['mempool'], $device, $index, $mib, $descr, 1, $total, $used);
        }
    }
}

unset($mempool_array, $index, $descr, $total, $used, $chassis_count, $percent);

// EOF
