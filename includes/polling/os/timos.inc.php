<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

//TIMETRA-SYSTEM-MIB::sgiSwMajorVersion.0 = Gauge32: 6
//TIMETRA-SYSTEM-MIB::sgiSwMinorVersion.0 = Gauge32: 0
//TIMETRA-SYSTEM-MIB::sgiSwVersionModifier.0 = STRING: "R6"
//TIMETRA-CHASSIS-MIB::tmnxChassisTypeName.20 = STRING: "7210 SAS-M 24F 2XFP-1"

// Prefer db serial
$entPhysical = dbFetchRow('SELECT * FROM `entPhysical` WHERE `device_id` = ? AND `entPhysicalContainedIn` = ? AND `entPhysicalParentRelPos` = ? AND `deleted` IS NULL', [$device['device_id'], 0, -1]);
if (is_valid_param($entPhysical['entPhysicalSerialNum'], 'serial')) {
    $serial = $entPhysical['entPhysicalSerialNum'];
}

//TIMETRA-CHASSIS-MIB::tmnxHwSerialNumber.1.50331649 = STRING: XX1416X2339
//TIMETRA-CHASSIS-MIB::tmnxHwSerialNumber.1.83886081 = STRING:
//TIMETRA-CHASSIS-MIB::tmnxHwSerialNumber.1.83886082 = STRING:
foreach (snmpwalk_cache_oid($device, 'tmnxHwSerialNumber', NULL, 'TIMETRA-CHASSIS-MIB') as $index => $entry) {
    if (!is_valid_param($entry['tmnxHwSerialNumber'], 'serial')) {
        continue;
    }

    // In mostly cases first entry is chassis, but need to check it
    $check = snmp_get_multi_oid($device, "tmnxHwContainedIn.$index tmnxHwParentRelPos.$index", [], 'TIMETRA-CHASSIS-MIB');
    if ($check[$index]['tmnxHwContainedIn'] === '0' && $check[$index]['tmnxHwParentRelPos'] === '-1') {
        $serial = $entry['tmnxHwSerialNumber'];
    }
}

unset($check, $index, $entry);

// EOF
