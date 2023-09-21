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

$mib = 'TIMETRA-CHASSIS-MIB';

// TIMETRA-CHASSIS-MIB::tmnxChassisTotalNumber.0 = INTEGER: 1
$chassis_count = snmp_get_oid($device, "tmnxChassisTotalNumber.0", $mib);

//TIMETRA-CHASSIS-MIB::tmnxHwName.1.50331649 = STRING: "chassis"
//TIMETRA-CHASSIS-MIB::tmnxHwName.1.134217729 = STRING: "Slot 1"
//TIMETRA-CHASSIS-MIB::tmnxHwTempSensor.1.50331649 = INTEGER: true(1)
//TIMETRA-CHASSIS-MIB::tmnxHwTempSensor.1.134217729 = INTEGER: true(1)
//TIMETRA-CHASSIS-MIB::tmnxHwTemperature.1.50331649 = INTEGER: 37 degrees celsius
//TIMETRA-CHASSIS-MIB::tmnxHwTemperature.1.134217729 = INTEGER: 37 degrees celsius
$oids = snmp_cache_table($device, 'tmnxHwTable', NULL, 'TIMETRA-CHASSIS-MIB'); // Cached also for inventory module
print_debug_vars($oids);
foreach ($oids as $index => $entry) {
    [ $chassis, $system_index ] = explode('.', $index);
    $chassis_name = $chassis_count > 1 ? ", Chassis $chassis" : "";

    if ($entry['tmnxHwTempSensor'] === 'true' && $entry['tmnxHwTemperature'] > 0) {
        $descr   = rewrite_entity_name($entry['tmnxHwName']) . $chassis_name;
        $oid     = ".1.3.6.1.4.1.6527.3.1.2.2.1.8.1.18.$index";
        $options = [ 'limit_high' => $entry['tmnxHwTempThreshold'] ];

        discover_sensor('temperature', $device, $oid, $index, 'timetra-chassis-temp', $descr, 1, $entry['tmnxHwTemperature'], $options);
    }

}

//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyACStatus.1.1 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyACStatus.1.2 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyDCStatus.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyDCStatus.1.2 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyTempStatus.1.1 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyTempStatus.1.2 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyTempThreshold.1.1 = INTEGER: 58 degrees celsius
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyTempThreshold.1.2 = INTEGER: 58 degrees celsius
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupply1Status.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupply1Status.1.2 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupply2Status.1.1 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupply2Status.1.2 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyAssignedType.1.1 = INTEGER: dc(1)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyAssignedType.1.2 = INTEGER: dc(1)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyInputStatus.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyInputStatus.1.2 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyOutputStatus.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyOutputStatus.1.2 = INTEGER: deviceStateOk(3)
$oids = snmpwalk_cache_oid($device, 'tmnxChassisPowerSupplyEntry', [], 'TIMETRA-CHASSIS-MIB');
print_debug_vars($oids);
foreach ($oids as $index => $entry) {
    $new_index = '3.'.$index;
    [ $chassis, $tray ] = explode('.', $index);
    $chassis_name = $chassis_count > 1 ? ", Chassis $chassis" : "";

    // tmnxChassisPowerSupplyTempStatus
    $descr    = 'Power Supply Temperature' . " (Tray $tray" . $chassis_name . ")";
    $oid_name = 'tmnxChassisPowerSupplyTempStatus';
    $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.4.' . $index;
    $value    = $entry[$oid_name];
    $options  = [
        'entPhysicalClass' => 'powersupply',
        'rename_rrd'       => "timetra-chassis-state-$oid_name.%index%"
    ];
    if ($value !== 'deviceNotEquipped' &&
        !isset($valid['status']['TIMETRA-CHASSIS-MIB']['tmnxPhysChassPowerSupTempStatus'][$new_index])) {
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'timetra-chassis-state', $descr, $value, $options);
    }

    // tmnxChassisPowerSupply1Status
    $descr    = 'Power Supply 1' . " (Tray $tray" . $chassis_name . ")";
    $oid_name = 'tmnxChassisPowerSupply1Status';
    $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.6.' . $index;
    $value    = $entry[$oid_name];
    $options  = [
        'entPhysicalClass' => 'powersupply',
        'rename_rrd'       => "timetra-chassis-state-$oid_name.%index%"
    ];
    if ($value !== 'deviceNotEquipped' &&
        !isset($valid['status']['TIMETRA-CHASSIS-MIB']['tmnxPhysChassPowerSup1Status'][$new_index])) {
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'timetra-chassis-state', $descr, $value, $options);
    }

    // tmnxChassisPowerSupply2Status
    $descr    = 'Power Supply 2' . " (Tray $tray" . $chassis_name . ")";
    $oid_name = 'tmnxChassisPowerSupply2Status';
    $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.7.' . $index;
    $value    = $entry[$oid_name];
    $options  = [
        'entPhysicalClass' => 'powersupply',
        'rename_rrd'       => "timetra-chassis-state-$oid_name.%index%"
    ];
    if ($value !== 'deviceNotEquipped' &&
        !isset($valid['status']['TIMETRA-CHASSIS-MIB']['tmnxPhysChassPowerSup2Status'][$new_index])) {
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'timetra-chassis-state', $descr, $value, $options);
    }
}

//TIMETRA-CHASSIS-MIB::tmnxChassisFanOperStatus.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisFanSpeed.1.1 = INTEGER: halfSpeed(2)
$oids = snmpwalk_cache_oid($device, 'tmnxChassisFanEntry', [], 'TIMETRA-CHASSIS-MIB');
print_debug_vars($oids);
foreach ($oids as $index => $entry) {
    $new_index = '3.'.$index;
    [ $chassis, $tray ] = explode('.', $index);
    $chassis_name = $chassis_count > 1 ? ", Chassis $chassis" : "";

    // tmnxChassisFanOperStatus
    $descr    = 'Fan' . " (Tray $tray" . $chassis_name . ")";
    $oid_name = 'tmnxChassisFanOperStatus';
    $oid_num  = '.1.3.6.1.4.1.6527.3.1.2.2.1.4.1.2.' . $index;
    $value    = $entry[$oid_name];
    $options  = [
        'entPhysicalClass' => 'fan',
        'rename_rrd'       => "timetra-chassis-state-$oid_name.%index%"
    ];
    if ($value !== 'deviceNotEquipped' &&
        !isset($valid['status']['TIMETRA-CHASSIS-MIB']['tmnxPhysChassisFanOperStatus'][$new_index])) {
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'timetra-chassis-state', $descr, $value, $options);
    }
}

unset($oids);

// EOF
