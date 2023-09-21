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

// Temperature Table

$data = snmpwalk_cache_oid($device, "tempSensorEntry", [], "NETBOTZ410-MIB");

foreach ($data as $index => $entry) {
    $oid = '.1.3.6.1.4.1.5528.100.4.1.1.1.2.' . $index;
    discover_sensor('temperature', $device, $oid, $index, 'tempSensor', $entry['tempSensorLabel'], 0.1, $entry['tempSensorValue']);
}

unset($data, $index, $entry);

// Humidity Table

$data = snmpwalk_cache_oid($device, "humiSensorEntry", [], "NETBOTZ410-MIB");

foreach ($data as $index => $entry) {
    $oid = '.1.3.6.1.4.1.5528.100.4.1.2.1.2.' . $index;
    discover_sensor('humidity', $device, $oid, $index, 'humiSensor', $entry['humiSensorLabel'], 0.1, $entry['humiSensorValue']);
}

unset($data, $index, $entry);

// Dew Point Table

$data = snmpwalk_cache_oid($device, "dewPointSensorEntry", [], "NETBOTZ410-MIB");

foreach ($data as $index => $entry) {
    $oid = '.1.3.6.1.4.1.5528.100.4.1.3.1.2.' . $index;
    discover_sensor('dewpoint', $device, $oid, $index, 'dewPointSensor', $entry['dewPointSensorLabel'], 0.1, $entry['dewPointSensorValue']);
}

unset($data, $index, $entry);

/** Currently Disabled because the unit isn't reported
 *
 * // Audio Sensor Table
 *
 * $data = snmpwalk_cache_oid($device, "audioSensorEntry", array(), "NETBOTZ410-MIB");
 *
 * foreach($data as $index => $entry)
 * {
 * $oid = '.1.3.6.1.4.1.5528.100.4.1.4.1.2.' . $index;
 * discover_sensor('sound', $device, $oid, $index, 'audioPointSensor', $entry['audioPointSensorLabel'], 0.1, $entry['audioPointSensorValue']);
 * }
 *
 * unset($data, $index, $entry);
 */

// Airflow Table

$data = snmpwalk_cache_oid($device, "airFlowSensorEntry", [], "NETBOTZ410-MIB");

foreach ($data as $index => $entry) {
    $oid = '.1.3.6.1.4.1.5528.100.4.1.5.1.2.' . $index;
    //discover_sensor('airflow', $device, $oid, $index, 'airFlowSensor', $entry['dewPointSensorLabel'], 3.531466672, $entry['airFlowSensorValue']);
    // I not keep compatibility, because previously used incorrect class and unit
    discover_sensor_ng($device, 'velocity', 'NETBOTZ410-MIB', 'airFlowSensorValue', $oid, $index, NULL, $entry['airFlowSensorLabel'], 0.1, $entry['airFlowSensorValue'], ['sensor_unit' => 'm/min']);
}
unset($data, $index, $entry);

// Amperes Table

$data = snmpwalk_cache_oid($device, "ampDetectSensorEntry", [], "NETBOTZ410-MIB");

foreach ($data as $index => $entry) {
    $oid = '.1.3.6.1.4.1.5528.100.4.1.6.1.2.' . $index;
    discover_sensor('current', $device, $oid, $index, 'ampDetectSensor', $entry['ampDetectSensorLabel'], 0.1, $entry['ampDetectSensorValue']);
}
unset($data, $index, $entry);


// EOF
