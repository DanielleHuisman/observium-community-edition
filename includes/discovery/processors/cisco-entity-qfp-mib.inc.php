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

$array = snmpwalk_cache_oid($device, 'ceqfpUtilizationEntry', [], $mib);

foreach ($array as $index => $entry) {
    [$entPhysicalIndex, $interval] = explode('.', $index);
    if ($interval === 'fiveMinutes' && is_numeric($entry['ceqfpUtilProcessingLoad'])) {
        $descr     = snmp_get_oid($device, "entPhysicalName.$entPhysicalIndex", 'ENTITY-MIB');
        $usage_oid = ".1.3.6.1.4.1.9.9.715.1.1.6.1.14.$entPhysicalIndex.3";

        discover_processor($valid['processor'], $device, $usage_oid, $entPhysicalIndex, 'qfp', $descr, 1, $entry['ceqfpUtilProcessingLoad'], $entPhysicalIndex);
    }
}

// EOF
