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
// ZHONE-CARD-RESOURCES-MIB::cardProcessorIdle.1.1 = INTEGER: 29
// ZHONE-CARD-RESOURCES-MIB::cardProcessorUsage.1.1 = INTEGER: 71
// ZHONE-CARD-RESOURCES-MIB::cardProcessorHighUsage.1.1 = INTEGER: 13
// ZHONE-CARD-RESOURCES-MIB::cardProcessorServicesUsage.1.1 = INTEGER: 20
// ZHONE-CARD-RESOURCES-MIB::cardProcessorFrameworkUsage.1.1 = INTEGER: 35
// ZHONE-CARD-RESOURCES-MIB::cardProcessorLowUsage.1.1 = INTEGER: 2

$processors_array = snmpwalk_cache_oid($device, 'cardProcessorUsage', [], $mib); // INDEX { zhoneShelfIndex, zhoneSlotIndex }
$processors_count = count($processors_array);

foreach ($processors_array as $index => $entry) {
    $descr = 'Processor';
    if ($processors_count > 1) {
        [$zhoneShelfIndex, $zhoneSlotIndex] = explode('.', $index);
        $descr .= " - Shelf $zhoneShelfIndex, Slot $zhoneSlotIndex";
    }
    $oid_name = 'cardProcessorUsage';
    $oid_num  = ".1.3.6.1.4.1.5504.3.3.3.1.5.$index";
    $type     = 'ZHONE-CARD-RESOURCES-MIB-' . $oid_name;
    discover_processor($valid['processor'], $device, $oid_num, $index, $type, $descr, 1, $entry[$oid_name]);
}

unset($processors_array);

// EOF
