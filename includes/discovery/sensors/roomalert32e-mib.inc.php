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

// ATTENTION! In the SNMP settings of the device, there is a checkbox called [ ] Use 2 digit SNMP.
// If checked, values will be rounded; SNMP will return 20 for 20.4 degrees. If unchecked, values are *1000, so 2040 for 20.4 degrees.

$scale = 0.01; // Internal to device itself

// ROOMALERT32E-MIB::internal-tempc.0 = INTEGER: 2880
// ROOMALERT32E-MIB::internal-humidity.0 = INTEGER: 1767
// ROOMALERT32E-MIB::internal-heat-indexC.0 = INTEGER: 2960
// ROOMALERT32E-MIB::internal-dew-point-c.0 = INTEGER: 1304
// ROOMALERT32E-MIB::internal-power.0 = INTEGER: 1

$oids = snmpwalk_cache_oid($device, "internal", [], "ROOMALERT32E-MIB");

foreach ($oids as $index => $entry) {
    $oid_name = "internal-tempc";
    $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.1.1.2.$index";
    //$type = $mib."-".$oid_name;
    $descr = "Internal Temperature";
    $value = $entry['internal-tempc'];

    discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

    $oid_name = "internal-humidity";
    $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.1.2.1.$index";
    //$type = $mib."-".$oid_name;
    $descr = "Internal Humidity";
    $value = $entry['internal-humidity'];

    discover_sensor_ng($device, 'humidity', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

    $oid_name = "internal-heat-indexC";
    $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.1.4.2.$index";
    //$type = $mib."-".$oid_name;
    $descr = "Internal Heat Index";
    $value = $entry['internal-heat-indexC'];

    discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

    $oid_name = "internal-dew-point-c";
    $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.1.7.1.$index";
    //$type = $mib."-".$oid_name;
    $descr = "Internal Dew Point";
    $value = $entry['internal-dew-point-c'];

    discover_sensor_ng($device, 'dewpoint', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

    $oid_name = "internal-power";
    $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.1.3.1.$index";
    $type     = $oid_name;
    $descr    = "Internal Power Source";
    $value    = $entry['internal-power'];

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value);
}

/****
 *
 * // Digital sensors -- ARGH, why not digital-sen.1.1.0 instead of digital-sen1-1.0 !
 * // "Monitor for Temperature, Humidity, Heat Index (Feels Like), Power, Flood/Water, Smoke/Fire, Room Entry, Air Flow, Network Cameras and more."
 * // Great, now how do I see what sensor is connected over SNMP? Right... No OID for that.
 * //
 * // So we'll go by this table:
 * //
 * //         | Temp sensor        | Temp/Humidity sensor     | Power Sensor
 * // --------+--------------------+--------------------------+-----------------
 * // sen1-1: | Temp in Celsius    | Temp in Celsius          | Amperes
 * // sen1-2: | Temp in Fahrenheit | Temp in Fahrenheit       | Watts
 * // sen1-3: | N/A                | Humidity                 | Volts
 * // sen1-4: | N/A                | Heat index in Fahrenheit | Reference Volts
 * // sen1-5: | N/A                | Heat index in Celsius    | N/A
 * //
 * // You can name the sensors in the web interface, but the descriptions are not exported through SNMP :(
 * // ROOMALERT32E-MIB::digital-sen1-1.0 = INTEGER: 3606
 * // ROOMALERT32E-MIB::digital-sen1-2.0 = INTEGER: 9690
 * // ROOMALERT32E-MIB::digital-sen2-1.0 = INTEGER: 2912
 * // ROOMALERT32E-MIB::digital-sen2-2.0 = INTEGER: 8441
 ****/

$oids = snmpwalk_cache_oid($device, "digital", [], "ROOMALERT32E-MIB");

$index = 0;
for ($i = 1; $i <= 8; $i++) {

    if (isset($oids[$index]["digital-sen$i-1"])) {
        // Sensor is present.
        if (!isset($oids[$index]["digital-sen$i-3"])) {
            // Only Temp sensor

            // ROOMALERT32E-MIB::digital-sen8-1.0 = INTEGER: 2125
            // ROOMALERT32E-MIB::digital-sen8-2.0 = INTEGER: 7025

            $oid_name = "digital-sen$i-1";
            $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.2.$i.1.$index";
            $descr    = "External Digital Sensor " . str_pad($i, 2, '0', STR_PAD_LEFT) . ": Temperature";
            $value    = $oids[$index][$oid_name];

            discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);
        } elseif (isset($oids[$index]["digital-sen$i-5"])) {
            // Don't hav any of these sensors, unable to test

            // ROOMALERT32E-MIB::digital-sen1-1.0 = INTEGER: 2100
            // ROOMALERT32E-MIB::digital-sen1-2.0 = INTEGER: 6980
            // ROOMALERT32E-MIB::digital-sen1-3.0 = INTEGER: 3454
            // ROOMALERT32E-MIB::digital-sen1-4.0 = INTEGER: 6980
            // ROOMALERT32E-MIB::digital-sen1-5.0 = INTEGER: 2100

            // Temp/Humidity sensor
            $oid_name = "digital-sen$i-1";
            $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.2.$i.1.$index";
            $descr    = "External Digital Sensor " . str_pad($i, 2, '0', STR_PAD_LEFT) . ": Temperature";
            $value    = $oids[$index][$oid_name];

            discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

            $oid_name = "digital-sen$i-3";
            $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.2.$i.3.$index";
            $descr    = "External Digital Sensor " . str_pad($i, 2, '0', STR_PAD_LEFT) . ": Humidity";
            $value    = $oids[$index][$oid_name];

            discover_sensor_ng($device, 'humidity', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

            $oid_name = "digital-sen$i-5";
            $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.2.$i.5.$index";
            $descr    = "External Digital Sensor " . str_pad($i, 2, '0', STR_PAD_LEFT) . ": Heat Index";
            $value    = $oids[$index][$oid_name];

            discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

        } else {
            // Don't have any of these sensors, unable to test
            // Power sensor

            $oid_name = "digital-sen$i-1";
            $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.2.$i.1.$index";
            $descr    = "External Digital Sensor " . str_pad($i, 2, '0', STR_PAD_LEFT) . ": Current";
            $value    = $oids[$index][$oid_name];

            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

            $oid_name = "digital-sen$i-2";
            $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.2.$i.2.$index";
            $descr    = "External Digital Sensor " . str_pad($i, 2, '0', STR_PAD_LEFT) . ": Power";
            $value    = $oids[$index][$oid_name];

            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

            $oid_name = "digital-sen$i-3";
            $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.2.$i.3.$index";
            $descr    = "External Digital Sensor " . str_pad($i, 2, '0', STR_PAD_LEFT) . ": Voltage";
            $value    = $oids[$index][$oid_name];

            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

            $oid_name = "digital-sen$i-4";
            $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.2.$i.4.$index";
            $descr    = "External Digital Sensor " . str_pad($i, 2, '0', STR_PAD_LEFT) . ": Reference Voltage";
            $value    = $oids[$index][$oid_name];

            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

        }
    }
}

// Switch Sensors
// ROOMALERT32E-MIB::switch-sen1.0 = INTEGER: 1
// ROOMALERT32E-MIB::switch-sen2.0 = INTEGER: 0
$oids = snmpwalk_cache_oid($device, "switch", [], "ROOMALERT32E-MIB");

$l = 0;
foreach ($oids as $index => $entry) {
    foreach ($entry as $k => $value) {
        //switches start counting from 1;
        $l++;

        $oid_name = $k;
        $oid_num  = ".1.3.6.1.4.1.20916.1.8.1.3.$l.$index";
        $descr    = "External Switch Sensor " . str_pad($l, 2, '0', STR_PAD_LEFT);

        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, "switch-sen", $descr, $value);
    }
}

// EOF
