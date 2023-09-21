<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Get chassis PhysicalIndex
$sql        = 'SELECT `entPhysicalIndex` FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalClass` = ? AND `inventory_mib` = ?';
$chassis_index = dbFetchCell($sql, [ $device['device_id'], 'chassis', 'ENTITY-MIB' ]);
if (!$chassis_index) {
    return;
}

// Add PSU to inventory

// S5-CHASSIS-MIB::s5ChasPsuInfoPsuId.0 = INTEGER: 0
// S5-CHASSIS-MIB::s5ChasPsuInfoPsuId.1 = INTEGER: 1
// S5-CHASSIS-MIB::s5ChasPsuInfoOrderCode.0 = STRING: AL1905A19-E6
// S5-CHASSIS-MIB::s5ChasPsuInfoOrderCode.1 = STRING: AL1905A19-E6
// S5-CHASSIS-MIB::s5ChasPsuInfoOrderCodeRev.0 = STRING: 02
// S5-CHASSIS-MIB::s5ChasPsuInfoOrderCodeRev.1 = STRING: 02
// S5-CHASSIS-MIB::s5ChasPsuInfoDescription.0 = STRING: AC-DC-12V-54V-1025W
// S5-CHASSIS-MIB::s5ChasPsuInfoDescription.1 = STRING: AC-DC-12V-54V-1025W
// S5-CHASSIS-MIB::s5ChasPsuInfoSerialNumber.0 = STRING: 17OL07501751
// S5-CHASSIS-MIB::s5ChasPsuInfoSerialNumber.1 = STRING: 17AR253011X5
// S5-CHASSIS-MIB::s5ChasPsuInfoSuppPartNum.0 = STRING: PA-2102-1N-LF
// S5-CHASSIS-MIB::s5ChasPsuInfoSuppPartNum.1 = STRING: PA-2102-1N-LF
// S5-CHASSIS-MIB::s5ChasPsuInfoModelRevision.0 = STRING: 02
// S5-CHASSIS-MIB::s5ChasPsuInfoModelRevision.1 = STRING: 02
// S5-CHASSIS-MIB::s5ChasPsuInfoManufacturer.0 = STRING: LITE-ON
// S5-CHASSIS-MIB::s5ChasPsuInfoManufacturer.1 = STRING: LITE-ON

foreach (snmpwalk_cache_oid($device, 's5ChasPsuInfoTable', [], 'S5-CHASSIS-MIB') as $index => $part) {
    print_debug_vars($part);

    $part_index = 1024 + $index;
    $inventory[$part_index] = [
        'entPhysicalDescr'        => $part['s5ChasPsuInfoDescription'],
        'entPhysicalClass'        => 'powerSupply',
        'entPhysicalName'         => "psu-$index",
        'entPhysicalSerialNum'    => $part['s5ChasPsuInfoSerialNumber'],
        'entPhysicalAssetID'      => '',
        'entPhysicalIsFRU'        => 'true',
        'entPhysicalContainedIn'  => $chassis_index, // ENTITY-MIB exposes the chassis with index 1.
        'entPhysicalParentRelPos' => 1,
        'entPhysicalMfgName'      => $part['s5ChasPsuInfoManufacturer'],
        'entPhysicalHardwareRev'  => $part['s5ChasPsuInfoModelRevision'],
        'entPhysicalModelName'    => $part['s5ChasPsuInfoSuppPartNum'],
    ];
    discover_inventory($device, $part_index, $inventory[$part_index], $mib);
}

// EOF
