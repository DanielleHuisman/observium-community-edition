<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!isset($valid['sensor']['temperature']['UBNT-EdgeMAX-MIB-ubntThermTemperature']) &&
    !isset($valid['sensor']['temperature']['ENTITY-SENSOR-MIB-entPhySensorValue'])) {
  $scale = 0.001;
  $oids  = snmpwalk_cache_oid($device, 'lmTempSensorsEntry', [], 'LM-SENSORS-MIB');
  foreach ($oids as $index => $entry) {
    $oid   = ".1.3.6.1.4.1.2021.13.16.2.1.3.$index";
    $descr = str_ireplace([ 'temperature-', 'temp-' ], '', $entry['lmTempSensorsDevice']);
    $value = $entry['lmTempSensorsValue'];
    /* VM:
    lmTempSensorsDevice.1 = Core 0
    lmTempSensorsDevice.2 = Core 1
    lmTempSensorsValue.1 = 100000
    lmTempSensorsValue.2 = 100000
     */
    if ($entry['lmTempSensorsValue'] > 0 &&
        $value != 100000 && // VM always report 100000
        $value * $scale <= 200) {
      discover_sensor_ng($device, 'temperature', $mib, 'lmTempSensorsValue', $oid, $index, NULL, $descr, $scale, $value, [ 'rename_rrd' => "lmsensors-$index" ]);
    }
  }
}

if (!isset($valid['sensor']['fanspeed']['UBNT-EdgeMAX-MIB-ubntFanRpm']) &&
    !isset($valid['sensor']['fanspeed']['ENTITY-SENSOR-MIB-entPhySensorValue'])) {
  $scale = 1;
  $oids  = snmpwalk_cache_oid($device, 'lmFanSensorsEntry', [], 'LM-SENSORS-MIB');
  foreach ($oids as $index => $entry) {
    $oid   = ".1.3.6.1.4.1.2021.13.16.3.1.3.$index";
    $descr = str_ireplace('fan-', '', $entry['lmFanSensorsDevice']);
    $value = $entry['lmFanSensorsValue'];
    if ($entry['lmFanSensorsValue'] > 0) {
      discover_sensor_ng($device, 'fanspeed', $mib, 'lmFanSensorsValue', $oid, $index, NULL, $descr, $scale, $value, [ 'rename_rrd' => "lmsensors-$index" ]);
    }
  }
}

//if (!isset($valid['sensor']['voltage'])) {
  $scale = 0.001;
  $oids  = snmpwalk_cache_oid($device, 'lmVoltSensorsEntry', [], 'LM-SENSORS-MIB');
  foreach ($oids as $index => $entry) {
    $oid   = ".1.3.6.1.4.1.2021.13.16.4.1.3.$index";
    $descr = str_ireplace(array( 'voltage, ', 'volt-' ), '', $entry['lmVoltSensorsDevice']);
    $value = $entry['lmVoltSensorsValue'];
    if (is_numeric($entry['lmVoltSensorsValue'])) {
      discover_sensor_ng($device, 'voltage', $mib, 'lmVoltSensorsValue', $oid, $index, NULL, $descr, $scale, $value, [ 'rename_rrd' => "lmsensors-$index" ]);
    }
  }
//}

//$oids = snmpwalk_cache_oid($device, 'lmMiscSensorsEntry', array(), 'LM-SENSORS-MIB');

unset($oids);

// EOF
