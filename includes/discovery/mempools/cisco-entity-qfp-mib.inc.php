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

$mempool_array = snmpwalk_cache_oid($device, 'ceqfpMemoryResourceEntry', [], $mib);

foreach ($mempool_array as $index => $entry) {
    if (is_numeric($entry['ceqfpMemoryResInUse'])) {
        [$entPhysicalIndex, $entMemoryResource] = explode('.', $index);
        $entPhysicalName = snmp_cache_oid($device, "entPhysicalName.$entPhysicalIndex", 'ENTITY-MIB');

        $descr = $entPhysicalName . ' - ' . $entMemoryResource;
        $used  = $entry['ceqfpMemoryResInUse'];
        $total = $entry['ceqfpMemoryResTotal'];

        discover_mempool($valid['mempool'], $device, $index, 'CISCO-ENTITY-QFP-MIB', $descr, 1, $total, $used);
    }
}

unset($mempool_array, $index, $descr, $total, $used, $free, $entPhysicalIndex, $entPhysicalName, $entMemoryResource);

// EOF
