<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) Adam Armstrong
 *
 */

$tmm_memory = snmpwalk_cache_oid($device, 'sysTmmStatMemoryUsed', [], $mib);
$tmm_memory = snmpwalk_cache_oid($device, 'sysTmmStatMemoryTotal', $tmm_memory, $mib);

foreach ($tmm_memory as $index => $entry) {
    $total = $entry['sysTmmStatMemoryTotal'];
    if ($total == 0) {
        continue;
    }

    $used  = $entry['sysTmmStatMemoryUsed'];
    $descr = "TMM $index Memory";
    discover_mempool($valid['mempool'], $device, $index, 'F5-BIGIP-SYSTEM-MIB', $descr, 1, $total, $used);
}

unset ($mempool_array, $index, $total, $used);

// EOF
