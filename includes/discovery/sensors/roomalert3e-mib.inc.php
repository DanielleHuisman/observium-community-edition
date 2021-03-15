
<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// ATTENTION! In the SNMP settings of the device, there is a checkbox called [ ] Use 2 digit SNMP.
// If checked, values will be rounded; SNMP will return 20 for 20.4 degrees. If unchecked, values are *1000, so 2040 for 20.4 degrees.
//
// Some Nagios plugins only work with 2 digits mode. We support both formats with this code.
// We will detect the scale based on whether the measured humidity is > 100. This will fail on 4-digit humidity < 1% ;-)
$scale = 1; // Start at 1 for 2 digits setting.

// Internal Temperature
// ROOMALERT3E-MIB::digital-sen1-1.0 = 2882
$oids = snmpwalk_cache_multi_oid($device, "digital-sen1-1", array(), "ROOMALERT3E-MIB");

foreach ($oids as $index => $entry)
{
  $descr = "Internal Temperature"; if (count($oids) > 1) { $descr .= " " . ($index+1); }
  $oid   = ".1.3.6.1.4.1.20916.1.9.1.1.1.1.$index";
  $value = $entry['digital-sen1-1'];
  if ($value > 100) { $scale = 0.01; }

  discover_sensor('temperature', $device, $oid, "digital-sen1-1.$index", 'roomalert', $descr, $scale, $value);
}

// Digital sensors -- ARGH, why not digital-sen.1.1.0 instead of digital-sen1-1.0 !
// "Monitor for Temperature, Humidity, Heat Index (Feels Like), Power, Flood/Water, Smoke/Fire, Room Entry, Air Flow, Network Cameras and more."
// Great, now how do I see what sensor is connected over SNMP? Right... No OID for that.
//
// So we'll go by this table:
//
//         | Temp sensor        | Temp/Humidity sensor     | Power Sensor
// --------+--------------------+--------------------------+-----------------
// sen1-1: | Temp in Celsius    | Temp in Celsius          | Amperes
// sen1-2: | Temp in Fahrenheit | Temp in Fahrenheit       | Watts
// sen1-3: | N/A                | Humidity                 | Volts
// sen1-4: | N/A                | Heat index in Fahrenheit | Reference Volts
// sen1-5: | N/A                | Heat index in Celsius    | N/A
//
// You can name the sensors in the web interface, but the descriptions are not exported through SNMP :(
//
// ROOMALERT3E-MIB::digital-sen1-1.0 = 2293
// ROOMALERT3E-MIB::digital-sen1-2.0 = 7327
// ROOMALERT3E-MIB::digital-sen1.6.0 = "External Temp"

$oids = snmpwalk_cache_multi_oid($device, "digital", array(), "ROOMALERT3E-MIB");

$index = 0;

for ($i = 1;$i <= 6;$i++)
{
  if (isset($oids[$index]["digital-sen$i-1"]))
  {
    // Sensor is present.
    if (!isset($oids[$index]["digital-sen$i-3"]))
    {
      // Temp sensor
      $descr = "Channel $i: Temperature";
      $oid   = ".1.3.6.1.4.1.20916.1.9.1.1.$i.1.$index";
      $value = $oids[$index]["digital-sen$i-1"];

      discover_sensor('temperature', $device, $oid, "digital-sen$i-1.$index", 'roomalert', $descr, $scale, $value);
    }
    elseif (isset($oids[$index]["digital-sen$i-5"]))
    {
      // Temp/Humidity sensor
      $descr = "Channel $i: Temperature";
      $oid   = ".1.3.6.1.4.1.20916.1.9.1.1.$i.1.$index";
      $value = $oids[$index]["digital-sen$i-1"];
      discover_sensor('temperature', $device, $oid, "digital-sen$i-1.$index", 'roomalert', $descr, $scale, $value);

      $descr = "Channel $i: Heat index";
      $oid   = ".1.3.6.1.4.1.20916.1.9.1.1.$i.5.$index";
      $value = $oids[$index]["digital-sen$i-5"];
      discover_sensor('temperature', $device, $oid, "digital-sen$i-5.$index", 'roomalert', $descr, $scale, $value);

      $descr = "Channel $i: Humidity";
      $oid   = ".1.3.6.1.4.1.20916.1.9.1.1.$i.3.$index";
      $value = $oids[$index]["digital-sen$i-3"];
      discover_sensor('humidity', $device, $oid, "digital-sen$i-3.$index", 'roomalert', $descr, $scale, $value);
    } else {
      // Power sensor
      $descr = "Channel $i: Current";
      $oid   = ".1.3.6.1.4.1.20916.1.9.1.1.$i.1.$index";
      $value = $oids[$index]["digital-sen$i-1"];
      discover_sensor('current', $device, $oid, "digital-sen$i-1.$index", 'roomalert', $descr, $scale, $value);

      $descr = "Channel $i: Power";
      $oid   = ".1.3.6.1.4.1.20916.1.9.1.1.$i.2.$index";
      $value = $oids[$index]["digital-sen$i-2"];
      discover_sensor('power', $device, $oid, "digital-sen$i-2.$index", 'roomalert', $descr, $scale, $value);

      $descr = "Channel $i: Voltage";
      $oid   = ".1.3.6.1.4.1.20916.1.9.1.1.$i.3.$index";
      $value = $oids[$index]["digital-sen$i-3"];
      discover_sensor('voltage', $device, $oid, "digital-sen$i-3.$index", 'roomalert', $descr, $scale, $value);

      $descr = "Channel $i: Reference voltage";
      $oid   = ".1.3.6.1.4.1.20916.1.9.1.1.$i.4.$index";
      $value = $oids[$index]["digital-sen$i-4"];
      discover_sensor('voltage', $device, $oid, "digital-sen$i-4.$index", 'roomalert', $descr, $scale, $value);
    }
  }
}

/*

On/off digital switches may be supported later; 1 = alarm, 0 = ok
FIXME They are now - state sensors!

ROOMALERT3E-MIB::switch-sen1.0 = INTEGER: 1
ROOMALERT3E-MIB::switch-sen2.0 = INTEGER: 0

*/

// EOF