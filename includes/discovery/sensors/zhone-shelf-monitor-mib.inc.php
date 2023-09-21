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

$mib = 'ZHONE-SHELF-MONITOR-MIB';

//ZHONE-SHELF-MONITOR-MIB::shelfAPowerStatus.1 = INTEGER: powerOk(1)
//ZHONE-SHELF-MONITOR-MIB::shelfBPowerStatus.1 = INTEGER: powerOk(1)
//ZHONE-SHELF-MONITOR-MIB::shelfTemperatureStatus.1 = INTEGER: normal(1)
//ZHONE-SHELF-MONITOR-MIB::shelfFanTrayStatus.1 = INTEGER: operational(1)
//ZHONE-SHELF-MONITOR-MIB::shelfAlarmContactsStatus.1 = BITS: 80 contactAlarm0(0)
//ZHONE-SHELF-MONITOR-MIB::shelfCardStatus.1 = Hex-STRING: 05
//ZHONE-SHELF-MONITOR-MIB::shelfLedStatus.1 = BITS: 04 criticalAlarm(5)
//ZHONE-SHELF-MONITOR-MIB::shelfAdminResets.1 = Gauge32: 0
//ZHONE-SHELF-MONITOR-MIB::shelfFaultResets.1 = Gauge32: 0
//ZHONE-SHELF-MONITOR-MIB::shelfPowerResets.1 = Gauge32: 0
//ZHONE-SHELF-MONITOR-MIB::shelfCPowerStatus.1 = INTEGER: 0
//ZHONE-SHELF-MONITOR-MIB::shelfDPowerStatus.1 = INTEGER: 0
//ZHONE-SHELF-MONITOR-MIB::shelfBatteryAVoltage.1 = STRING:
//ZHONE-SHELF-MONITOR-MIB::shelfBatteryBVoltage.1 = STRING:
//ZHONE-SHELF-MONITOR-MIB::shelfChassisReturnVoltage.1 = STRING:

$oids = snmpwalk_cache_oid($device, 'shelfStatusTable', [], $mib);
//print_vars($oids);
foreach ($oids as $index => $entry) {
    $name = "Shelf $index";

    // Power Status
    $descr    = "Power A - $name";
    $oid_name = 'shelfAPowerStatus';
    $oid_num  = '.1.3.6.1.4.1.5504.3.2.2.1.1.' . $index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, 'shelfPowerStatus', $descr, $value, ['entPhysicalClass' => 'powersupply']);

    $descr    = "Power B - $name";
    $oid_name = 'shelfBPowerStatus';
    $oid_num  = '.1.3.6.1.4.1.5504.3.2.2.1.2.' . $index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, 'shelfPowerStatus', $descr, $value, ['entPhysicalClass' => 'powersupply']);

    // Temperature Status
    $descr    = "Temperature - $name";
    $oid_name = 'shelfTemperatureStatus';
    $oid_num  = '.1.3.6.1.4.1.5504.3.2.2.1.3.' . $index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, 'shelfTemperatureStatus', $descr, $value, ['entPhysicalClass' => 'temperature']);

    // Fan Status
    $descr    = "Fan Tray - $name";
    $oid_name = 'shelfFanTrayStatus';
    $oid_num  = '.1.3.6.1.4.1.5504.3.2.2.1.4.' . $index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, 'shelfFanTrayStatus', $descr, $value, ['entPhysicalClass' => 'fan']);
}

//ZHONE-SHELF-MONITOR-MIB::shelfFanSpeed.1.1 = INTEGER: 9000
//ZHONE-SHELF-MONITOR-MIB::shelfFanSpeed.1.2 = INTEGER: 9000
//ZHONE-SHELF-MONITOR-MIB::shelfFanSpeed.1.3 = INTEGER: 9000
//ZHONE-SHELF-MONITOR-MIB::shelfFanLocation.1.1 = STRING: back
//ZHONE-SHELF-MONITOR-MIB::shelfFanLocation.1.2 = STRING: middle
//ZHONE-SHELF-MONITOR-MIB::shelfFanLocation.1.3 = STRING: front
//ZHONE-SHELF-MONITOR-MIB::shelfFanLowSpeedThreshold.1.1 = INTEGER: 544
//ZHONE-SHELF-MONITOR-MIB::shelfFanLowSpeedThreshold.1.2 = INTEGER: 544
//ZHONE-SHELF-MONITOR-MIB::shelfFanLowSpeedThreshold.1.3 = INTEGER: 544

$oids = snmpwalk_cache_oid($device, 'shelfFanTable', [], $mib);
//print_vars($oids);
foreach ($oids as $index => $entry) {
    [$zhoneShelfIndex, $shelfFanIndex] = explode('.', $index);
    $name = "Shelf $zhoneShelfIndex";

    $descr    = 'Fan ' . trim($entry['shelfFanLocation']) . ' - ' . $name;
    $oid_name = 'shelfFanSpeed';
    $oid_num  = ".1.3.6.1.4.1.5504.3.2.3.1.2.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];
    $limits   = ['limit_low' => $entry['shelfFanLowSpeedThreshold']];

    discover_sensor('fanspeed', $device, $oid_num, $index, $type, $descr, 1, $value, $limits);
}

//ZHONE-SHELF-MONITOR-MIB::shelfTemperature.1.1 = INTEGER: 29
//ZHONE-SHELF-MONITOR-MIB::shelfTemperatureLocation.1.1 = STRING: Inlet
//ZHONE-SHELF-MONITOR-MIB::shelfTemperatureHighThreshold.1.1 = INTEGER: 75
//ZHONE-SHELF-MONITOR-MIB::shelfTemperatureLowThreshold.1.1 = INTEGER: -12

$oids = snmpwalk_cache_oid($device, 'shelfTemperatureTable', [], $mib);
//print_vars($oids);
foreach ($oids as $index => $entry) {
    [$zhoneShelfIndex, $shelfTemperatureIndex] = explode('.', $index);
    $name = "Shelf $zhoneShelfIndex";

    $descr    = 'Temperature ' . trim($entry['shelfTemperatureLocation']) . ' - ' . $name;
    $oid_name = 'shelfTemperature';
    $oid_num  = ".1.3.6.1.4.1.5504.3.2.4.1.2.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];
    $limits   = ['limit_high' => $entry['shelfTemperatureHighThreshold'],
                 'limit_low'  => $entry['shelfTemperatureLowThreshold']];

    discover_sensor('temperature', $device, $oid_num, $index, $type, $descr, 1, $value, $limits);
}

// EOF
