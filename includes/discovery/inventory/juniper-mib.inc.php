<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

if (!safe_empty($valid['inventory']['ENTITY-MIB'])) {
    // JunOS EVO support common ENTITY-MIB
    print_debug('Inventory by JUNIPER-MIB skipped.');
    return;
}

$jnxBoxDescr = snmp_get_oid($device, 'jnxBoxDescr.0', 'JUNIPER-MIB');
if (safe_empty($jnxBoxDescr)) {
    return;
}

$jnxBoxSerialNo = snmp_get_oid($device, 'jnxBoxSerialNo.0', 'JUNIPER-MIB');

// Insert chassis as index 1, everything hangs off of this.
$system_index = 1;
$inventory[$system_index] = [
    'entPhysicalDescr'        => $jnxBoxDescr,
    'entPhysicalClass'        => 'chassis',
    'entPhysicalName'         => 'Chassis',
    'entPhysicalSerialNum'    => $jnxBoxSerialNo,
    'entPhysicalIsFRU'        => 'true',
    'entPhysicalContainedIn'  => 0,
    'entPhysicalParentRelPos' => -1,
    'entPhysicalMfgName'      => 'Juniper'
];

discover_inventory($device, $system_index, $inventory[$system_index], $mib);

// Now fetch data for the rest of the hardware in the chassis
$data = snmpwalk_cache_oid($device, 'jnxContentsTable', [], 'JUNIPER-MIB:JUNIPER-CHASSIS-DEFINES-MIB');
$data = snmpwalk_cache_oid($device, 'jnxFruTable',   $data, 'JUNIPER-MIB:JUNIPER-CHASSIS-DEFINES-MIB');

$global_relPos = 0;

foreach ($data as $part) {
    // Index can only be int in the database, so we create our own from 7.1.1.0:
    $system_index = $part['jnxContentsContainerIndex'] * 16777216 + $part['jnxContentsL1Index'] * 65536 + $part['jnxContentsL2Index'] * 256 + $part['jnxContentsL3Index'];

    if ($system_index != 0) {
        if ($part['jnxContentsL2Index'] == 0 && $part['jnxContentsL3Index'] == 0) {
            $containedIn = 1; // Attach to chassis inserted above

            $global_relPos++;
            $relPos = $global_relPos;
        } else {
            $containerIndex = $part['jnxContentsContainerIndex'];

            if ($containerIndex == 8) {
                $containerIndex--;
            } // Convert PIC (8) to FPC (7) parent

            $containedIn = $containerIndex * 16777216 + $part['jnxContentsL1Index'] * 65536;

            $relPos = $part['jnxContentsL2Index'];
        }

        // [jnxFruTemp] => 45 - Could link to sensor somehow? (like we do for ENTITY-SENSOR-MIB)

        $inventory[$system_index] = [
            'entPhysicalDescr'        => ucfirst($part['jnxContentsDescr']),
            'entPhysicalHardwareRev'  => $part['jnxContentsRevision'],
            'entPhysicalClass'        => $part['jnxFruType'] ?? 'chassis',
            'entPhysicalName'         => ucfirst($part['jnxFruName'] ?: $part['jnxContentsDescr']),
            'entPhysicalSerialNum'    => str_replace([ 'S/N ', 'BUILTIN' ], '', $part['jnxContentsSerialNo']),
            'entPhysicalModelName'    => str_replace('BUILTIN', '', $part['jnxContentsPartNo']),
            'entPhysicalVendorType'   => $part['jnxContentsType'], //$part['jnxContentsModel'],
            'entPhysicalIsFRU'        => isset($part['jnxFruType']) ? 'true' : 'false',
            'entPhysicalContainedIn'  => $containedIn,
            'entPhysicalParentRelPos' => $relPos,
            'entPhysicalMfgName'      => 'Juniper'
        ];

        discover_inventory($device, $system_index, $inventory[$system_index], $mib);
    }
}

// EOF
