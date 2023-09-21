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

// ZHONE-CARD-RESOURCES-MIB::cardRuntimeTable
// ZHONE-CARD-RESOURCES-MIB::cardPeakMemUsage.1.1 = INTEGER: 80762
// ZHONE-CARD-RESOURCES-MIB::cardAvailMem.1.1 = INTEGER: 145131
// ZHONE-CARD-RESOURCES-MIB::cardTotalMem.1.1 = INTEGER: 225421
// ZHONE-CARD-RESOURCES-MIB::cardMemStatus.1.1 = INTEGER: ramMemOK(1)

$mempool_array = snmpwalk_cache_oid($device, "cardAvailMem", [], $mib);
$mempool_array = snmpwalk_cache_oid($device, "cardTotalMem", $mempool_array, $mib);

$mempool_count = count($mempool_array);

foreach ($mempool_array as $index => $entry) {
    $descr = "Memory";
    if ($mempool_count > 1) {
        [$zhoneShelfIndex, $zhoneSlotIndex] = explode('.', $index);
        $descr .= " - Shelf $zhoneShelfIndex, Slot $zhoneSlotIndex";
    }
    $oid_name = 'cardAvailMem';
    $used     = $entry['cardTotalMem'] - $entry[$oid_name];

    discover_mempool($valid['mempool'], $device, $index, $mib, $descr, 1024, $entry['cardTotalMem'], $used);
}

unset ($mempool_array);

// EOF
