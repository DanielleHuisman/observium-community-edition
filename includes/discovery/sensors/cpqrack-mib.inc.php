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

$cpqrack = snmpwalk_cache_oid($device, 'cpqRackCommonEnclosureHasPowerSupplies', [], 'CPQRACK-MIB');
$cpqrack = snmpwalk_cache_oid($device, 'cpqRackCommonEnclosureHasTempSensors', $cpqrack, 'CPQRACK-MIB');
$cpqrack = snmpwalk_cache_oid($device, 'cpqRackCommonEnclosureHasFans', $cpqrack, 'CPQRACK-MIB');
//print_vars($cpqrack);

// Rack Blade (useful for inventory or so)
//$oids = snmpwalk_cache_oid($device, 'cpqRackServerBladeTable',    array(), 'CPQRACK-MIB');
//print_vars($oids);

// Power Supplies
$oids = snmp_cache_table($device, 'cpqRackPowerSupplyTable', NULL, 'CPQRACK-MIB');

foreach ($oids as $index => $entry) {
    $rack = $entry['cpqRackPowerSupplyRack'];
    if ($cpqrack[$rack]['cpqRackCommonEnclosureHasPowerSupplies'] == 'false' ||
        $entry['cpqRackPowerSupplyPresent'] != 'present' ||
        $entry['cpqRackPowerSupplyMaxPwrOutput'] <= 0) {
        continue;
    }
    $chassis = $entry['cpqRackPowerSupplyChassis'];
    $name    = ($entry['cpqRackPowerSupplyEnclosureName'] ? $entry['cpqRackPowerSupplyEnclosureName'] : $entry['cpqRackPowerSupplyIndex']);
    $descr   = "$name - Rack $rack, Chassis $chassis, " . $entry['cpqRackPowerSupplyMaxPwrOutput'] . "W";

    // Power Output
    $oid_name = 'cpqRackPowerSupplyCurPwrOutput';
    $oid      = '.1.3.6.1.4.1.232.22.2.5.1.1.1.10.' . $index;
    $type     = 'CPQRACK-MIB' . '-' . $oid_name;
    $value    = $entry[$oid_name];

    if ($value > 0) {
        discover_sensor_ng($device, 'power', 'CPQRACK-MIB', $oid_name, $oid, $index, $type, 'Power Supply Output ' . $descr, 1, $value);
    }

    // Intake Temperature
    $oid_name = 'cpqRackPowerSupplyIntakeTemp';
    $oid      = '.1.3.6.1.4.1.232.22.2.5.1.1.1.12.' . $index;
    $type     = 'CPQRACK-MIB' . '-' . $oid_name;
    $value    = $entry[$oid_name];

    if ($value > 0) {
        discover_sensor_ng($device, 'temperature', 'CPQRACK-MIB', $oid_name, $oid, $index, $type, 'Power Supply Intake ' . $descr, 1, $value);
    }

    // Exhaust Temperature
    $oid_name = 'cpqRackPowerSupplyExhaustTemp';
    $oid      = '.1.3.6.1.4.1.232.22.2.5.1.1.1.13.' . $index;
    $type     = 'CPQRACK-MIB' . '-' . $oid_name;
    $value    = $entry[$oid_name];

    if ($value > 0) {
        discover_sensor_ng($device, 'temperature', 'CPQRACK-MIB', $oid_name, $oid, $index, $type, 'Power Supply Exhaust ' . $descr, 1, $value);
    }

    // Status
    $oid   = '.1.3.6.1.4.1.232.22.2.5.1.1.1.14.' . $index;
    $value = $entry['cpqRackPowerSupplyStatus'];

    discover_status_ng($device, $mib, 'cpqRackPowerSupplyStatus', $oid, $index, 'cpqRackPowerSupplyStatus', 'Power Supply Status ' . $descr, $value, ['entPhysicalClass' => 'powersupply']);

    // InputLine
    $oid   = '.1.3.6.1.4.1.232.22.2.5.1.1.1.15.' . $index;
    $value = $entry['cpqRackPowerSupplyInputLineStatus'];

    discover_status_ng($device, $mib, 'cpqRackPowerSupplyInputLineStatus', $oid, $index, 'cpqRackPowerSupplyInputLineStatus', 'Power Supply InputLine ' . $descr, $value, ['entPhysicalClass' => 'powersupply']);

    // Condition
    $oid   = '.1.3.6.1.4.1.232.22.2.5.1.1.1.17.' . $index;
    $value = $entry['cpqRackPowerSupplyCondition'];

    discover_status_ng($device, $mib, 'cpqRackPowerSupplyCondition', $oid, $index, 'cpqRackCommonEnclosureCondition', 'Power Supply ' . $descr, $value, ['entPhysicalClass' => 'powersupply']);
}

// Rack Power
$oids = snmpwalk_cache_oid($device, 'cpqRackPowerEnclosureTable', [], 'CPQRACK-MIB');
//print_vars($oids);
foreach ($oids as $index => $entry) {
    $rack = $entry['cpqRackPowerEnclosureRack'];
    if ($entry['cpqRackPowerEnclosurePwrFeedMax'] <= 0) {
        continue;
    }
    $name  = ($entry['cpqRackPowerEnclosureName'] ? $entry['cpqRackPowerEnclosureName'] : $entry['cpqRackPowerEnclosureIndex']);
    $descr = "Power $name - Rack $rack, " . $entry['cpqRackPowerEnclosurePwrFeedMax'] . "W";
    $oid   = '.1.3.6.1.4.1.232.22.2.3.3.1.1.9.' . $index;
    $value = $entry['cpqRackPowerEnclosureCondition'];

    discover_status_ng($device, $mib, 'cpqRackPowerEnclosureCondition', $oid, $index, 'cpqRackCommonEnclosureCondition', $descr, $value, ['entPhysicalClass' => 'power']);
}

// Temperatures
$oids = snmpwalk_cache_oid($device, 'cpqRackCommonEnclosureTempTable', [], 'CPQRACK-MIB');
//print_vars($oids);
foreach ($oids as $index => $entry) {
    $rack = $entry['cpqRackCommonEnclosureTempRack'];
    if ($cpqrack[$rack]['cpqRackCommonEnclosureHasTempSensors'] == 'false' ||
        $entry['cpqRackCommonEnclosureTempCurrent'] <= 0) {
        continue;
    }
    $chassis = $entry['cpqRackCommonEnclosureTempChassis'];
    $name    = ($entry['cpqRackCommonEnclosureTempLocation'] ? $entry['cpqRackCommonEnclosureTempLocation'] : 'Sensor ' . $entry['cpqRackCommonEnclosureTempSensorIndex']);
    $descr   = "$name - Rack $rack, Chassis $chassis";

    $oid_name = 'cpqRackCommonEnclosureTempCurrent';
    $oid      = '.1.3.6.1.4.1.232.22.2.3.1.2.1.6.' . $index;
    $type     = 'CPQRACK-MIB' . '-' . $oid_name;
    $value    = $entry[$oid_name];
    $limits   = ['limit_high' => $entry['cpqRackCommonEnclosureTempThreshold']];

    discover_sensor_ng($device, 'temperature', 'CPQRACK-MIB', $oid_name, $oid, $index, $type, $descr, 1, $value, $limits);

    // State
    $oid   = '.1.3.6.1.4.1.232.22.2.3.1.2.1.8.' . $index;
    $value = $entry['cpqRackCommonEnclosureTempCondition'];

    //discover_status($device, $oid, 'cpqRackCommonEnclosureTempCondition.'.$index, 'cpqRackCommonEnclosureCondition', $descr, $value, array('entPhysicalClass' => 'chassis'));
}

// Fans
$oids = snmpwalk_cache_oid($device, 'cpqRackCommonEnclosureFanTable', [], 'CPQRACK-MIB');
//print_vars($oids);
foreach ($oids as $index => $entry) {
    $rack = $entry['cpqRackCommonEnclosureFanRack'];
    if ($cpqrack[$rack]['cpqRackCommonEnclosureHasFans'] == 'false' ||
        $entry['cpqRackCommonEnclosureFanPresent'] != 'present') {
        continue;
    }
    $chassis = $entry['cpqRackCommonEnclosureFanChassis'];
    $name    = ($entry['cpqRackCommonEnclosureFanLocation'] ? $entry['cpqRackCommonEnclosureFanLocation'] : $entry['cpqRackCommonEnclosureFanIndex']);
    $descr   = "Fan $name - Rack $rack, Chassis $chassis";
    $oid     = '.1.3.6.1.4.1.232.22.2.3.1.3.1.11.' . $index;
    $value   = $entry['cpqRackCommonEnclosureFanCondition'];

    discover_status_ng($device, $mib, 'cpqRackCommonEnclosureFanCondition', $oid, $index, 'cpqRackCommonEnclosureCondition', $descr, $value, ['entPhysicalClass' => 'fan']);
}

// EOF
