<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) Adam Armstrong
 *
 */

/* This file is specifically to cover the legacy WxGoos-II devices since MIB handling is a bit different.
 * These devices have a builtin suite of sensors as well as some optional extra sensors. This code will add
 * the builtin sensor first, and add external sensors if enumerated.
 */


#Builtin sensor resides at the below:
# IT-WATCHDOGS-MIB::climateName.1       - Name of sensor, usually "Climate Monitor".
# IT-WATCHDOGS-MIB::climateAvail.1      - Whether or not the sensor is "available".  This should always be "1".
# IT-WATCHDOGS-MIB::climateTempC.1      - Builtin Temperature in Celsius.
# IT-WATCHDOGS-MIB::climateHumidity.1   - Builtin Humidity sensor in percent 0-100.
# IT-WATCHDOGS-MIB::climateAirflow.1    - Builtin Airflow sensor (not used for this script).
# IT-WATCHDOGS-MIB::climateLight.1      - Builtin Photoelectric Cell sensor (not used for this script).
# IT-WATCHDOGS-MIB::climateSound.1      - Builtin Sound Sensor (not used for this script)

# Add-on sensors are enumerated in IT-WATCHDOGS-MIB::tempSensorCount.0 and reflect non-builtin sensors.
#   If you have two external sensors, this will return "2", with "1" being the first sensor detected and so on.
# Add-on sensor names are reflected in IT-WATCHDOGS-MIB::tempSensorName.(SensorID).
#   The return from this structure is free-text.
# Add-on sensor temperatures are reflected in IT-WATCHDOGS-MIB::tempSensorTempC.(SensorID)
#   The return from this structure is Integer.


#BUILT-IN Sensors
$cache['itwatchdogs']['climateTable'] = snmpwalk_cache_oid($device, 'climateTable', [], 'IT-WATCHDOGS-MIB');

foreach ($cache['itwatchdogs']['climateTable'] as $index => $entry) {
    if ($entry['climateAvail']) {
        $descr = $entry['climateName'] . ' Temperature';
        $oid   = ".1.3.6.1.4.1.17373.2.2.1.5.$index";
        $value = $entry['climateTempC'];

        if (is_numeric($value)) {
            discover_sensor($valid['sensor'], 'temperature', $device, $oid, 'climateTempC.' . $index, 'it-watchdogs-mib', $descr, $scale, $value);
        } else {
            print_debug("Sensor data '$value' not-numeric.");
        }


        $descr = $entry['climateName'] . ' Humidity';
        $oid   = ".1.3.6.1.4.1.17373.2.2.1.6.$index";
        $value = $entry['climateHumidity'];

        if (is_numeric($value)) {
            discover_sensor($valid['sensor'], 'humidity', $device, $oid, 'climateHumidity.' . $index, 'it-watchdogs-mib', $descr, $scale, $value);
        } else {
            print_debug("Sensor data '$value' not-numeric.");
        }
    }
}

#Add-on sensors

$cache['itwatchdogs']['tempSensorTable'] = snmpwalk_cache_oid($device, 'tempSensorTable', [], 'IT-WATCHDOGS-MIB');

foreach ($cache['itwatchdogs']['tempSensorTable'] as $index => $entry) {
    if ($entry['tempSensorAvail']) {
        $descr = $entry['tempSensorName'];
        $oid   = ".1.3.6.1.4.1.17373.2.4.1.5.$index";
        $value = $entry['tempSensorTempC'];

        if (is_numeric($value)) {
            discover_sensor($valid['sensor'], 'temperature', $device, $oid, 'tempSensorTempC' . $index, 'it-watchdogs-mib', $descr, $scale, $value);
        } else {
            print_debug("Sensor data '$value' not-numeric for oid $oid Sensor: $index");
        }
    }
}

// EOF
