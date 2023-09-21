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

$scale = 1; // Start at 1 for 2 digits setting.
//$scale = 0.01;

$oids = snmpwalk_cache_oid($device, "digital", [], "ROOMALERT3S-MIB");

$index = 0;
$i     = 1;

//for ($i = 1; $i <= 2; $i++) {
if (isset($oids[$index]["digital-sen$i-1"])) {
    $name = "External Digital Sensor";
    // Sensor is present.
    if (!isset($oids[$index]["digital-sen$i-3"])) {
        // Temp sensor
        $descr = "$name: Temperature";
        $oid   = ".1.3.6.1.4.1.20916.1.13.1.2.$i.1.$index";
        $value = $oids[$index]["digital-sen$i-1"];
        if ($value > 100) {
            $scale = 0.01;
        }

        discover_sensor_ng($device, 'temperature', $mib, "digital-sen$i-1", $oid, $index, NULL, $descr, $scale, $value);
    } elseif (isset($oids[$index]["digital-sen$i-5"])) {
        // Temp/Humidity sensor
        $descr = "$name: Temperature";
        $oid   = ".1.3.6.1.4.1.20916.1.13.1.2.$i.1.$index";
        $value = $oids[$index]["digital-sen$i-1"];
        if ($value > 100) {
            $scale = 0.01;
        }

        discover_sensor_ng($device, 'temperature', $mib, "digital-sen$i-1", $oid, $index, NULL, $descr, $scale, $value);

        $descr = "$name: Heat index";
        $oid   = ".1.3.6.1.4.1.20916.1.13.1.2.$i.5.$index";
        $value = $oids[$index]["digital-sen$i-5"];

        discover_sensor_ng($device, 'temperature', $mib, "digital-sen$i-5", $oid, $index, NULL, $descr, $scale, $value);

        $descr = "$name: Humidity";
        $oid   = ".1.3.6.1.4.1.20916.1.13.1.2.$i.3.$index";
        $value = $oids[$index]["digital-sen$i-3"];

        discover_sensor_ng($device, 'humidity', $mib, "digital-sen$i-3", $oid, $index, NULL, $descr, $scale, $value);

        $descr = "$name: Dew Point";
        $oid   = ".1.3.6.1.4.1.20916.1.13.1.2.$i.6.$index";
        $value = $oids[$index]["digital-sen$i-6"];

        discover_sensor_ng($device, 'dewpoint', $mib, "digital-sen$i-6", $oid, $index, NULL, $descr, $scale, $value);
    } else {
        // Power sensor
        $descr = "Channel $i: Current";
        $oid   = ".1.3.6.1.4.1.20916.1.13.1.2.$i.1.$index";
        $value = $oids[$index]["digital-sen$i-1"];
        discover_sensor('current', $device, $oid, "digital-sen$i-1.$index", 'roomalert', $descr, $scale, $value);

        $descr = "Channel $i: Power";
        $oid   = ".1.3.6.1.4.1.20916.1.13.1.2.$i.2.$index";
        $value = $oids[$index]["digital-sen$i-2"];
        discover_sensor('power', $device, $oid, "digital-sen$i-2.$index", 'roomalert', $descr, $scale, $value);

        $descr = "Channel $i: Voltage";
        $oid   = ".1.3.6.1.4.1.20916.1.13.1.2.$i.3.$index";
        $value = $oids[$index]["digital-sen$i-3"];
        discover_sensor('voltage', $device, $oid, "digital-sen$i-3.$index", 'roomalert', $descr, $scale, $value);

        $descr = "Channel $i: Reference voltage";
        $oid   = ".1.3.6.1.4.1.20916.1.13.1.2.$i.4.$index";
        $value = $oids[$index]["digital-sen$i-4"];
        discover_sensor('voltage', $device, $oid, "digital-sen$i-4.$index", 'roomalert', $descr, $scale, $value);
    }
}
//}

// EOF
