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

// EGW4MIB::EGW4GatewaySensorDeviceCount.0 = INTEGER: 2

if (!snmp_get_oid($device, 'EGW4GatewaySensorDeviceCount.0', $mib)) {
    return;
}

// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceID.856694 = Gauge32: 856694
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceID.875433 = Gauge32: 875433
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceType.856694 = STRING: "WaterDetect"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceType.875433 = STRING: "Temperature+Humidity"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceReadingValuesAll.856694 = STRING: "No"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceReadingValuesAll.875433 = STRING: "24.57, 40.46"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceReadingUnitsAll.856694 = STRING: "Detected"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceReadingUnitsAll.875433 = STRING: "deg. C, RH"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceRptUT.856694 = STRING: "0x00"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceRptUT.875433 = STRING: "0x9909CE0F"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceReadingTime.856694 = STRING: "392901311"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceReadingTime.875433 = STRING: "392900464"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceReadingAge.856694 = Gauge32: 4511
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceReadingAge.875433 = Gauge32: 5358
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceTypeNo.856694 = STRING: "4"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceTypeNo.875433 = STRING: "43"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceRFStrength.856694 = STRING: "96"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceRFStrength.875433 = STRING: "34"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceActive.856694 = STRING: "Active"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceActive.875433 = STRING: "Active"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceAlarming.856694 = STRING: "Not alarming"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceAlarming.875433 = STRING: "Not alarming"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceVoltage.856694 = STRING: "3.02"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceVoltage.875433 = STRING: "3.04"
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceWDIndexNo.856694 = Wrong Type (should be OCTET STRING): INTEGER: 1
// EGW4MIB::EGW4SensorInfoTranslatedFormatSensorDeviceWDIndexNo.875433 = Wrong Type (should be OCTET STRING): INTEGER: 2
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberSensorDeviceID.856694.1 = Gauge32: 856694
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberSensorDeviceID.875433.1 = Gauge32: 875433
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberSensorDeviceID.875433.2 = Gauge32: 875433
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberDatumNumber.856694.1 = INTEGER: 1
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberDatumNumber.875433.1 = INTEGER: 1
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberDatumNumber.875433.2 = INTEGER: 2
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberReadingValue.856694.1 = STRING: "No"
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberReadingValue.875433.1 = STRING: "24.57"
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberReadingValue.875433.2 = STRING: "40.46"
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberReadingUnits.856694.1 = STRING: "Detected"
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberReadingUnits.875433.1 = STRING: "deg. C"
// EGW4MIB::EGW4SensorInfoTranslatedFormatByDatumNumberReadingUnits.875433.2 = STRING: "RH"

$egw4 = snmpwalk_cache_oid($device, 'EGW4SensorInfoTranslatedFormatEntry', [], $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'EGW4SensorInfoTranslatedFormatByDatumNumberEntry', [], $mib);
foreach ($oids as $egw4deviceid => $egw4device) {
    if ($egw4[$egw4deviceid]['EGW4SensorInfoTranslatedFormatSensorDeviceActive'] !== 'Active') {
        continue;
    }

    $name      = $egw4[$egw4deviceid]['EGW4SensorInfoTranslatedFormatSensorDeviceType'];
    $egw4count = count($egw4device);
    foreach ($egw4device as $sensorid => $entry) {
        //$entry = array_merge($entry, (array)$egw4[$egw4deviceid]);
        $descr = $name;
        if ($egw4count > 1) {
            $descr .= " #$sensorid";
        }
        $index    = "$egw4deviceid.$sensorid";
        $oid_name = 'EGW4SensorInfoTranslatedFormatByDatumNumberReadingValue';
        $oid_num  = '.1.3.6.1.4.1.41542.2.2.2.3.' . $index;
        $value    = $entry[$oid_name];

        switch (strtolower($entry['EGW4SensorInfoTranslatedFormatByDatumNumberReadingUnits'])) {
            case 'deg. c':
                discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value);
                break;
            case 'ph':
                discover_sensor_ng($device, 'humidity', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value);
                break;
            case 'detected':
                discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'EGW4SensorDetected', $descr, $value, ['entPhysicalClass' => 'sensor']);
                break;
            default:
                print_warning("Unknown sensor Unit: " . $entry['EGW4SensorInfoTranslatedFormatByDatumNumberReadingUnits']);
                print_debug_vars($egw4[$egw4deviceid]);
                print_debug_vars($entry);
        }
    }
}

// EOF
