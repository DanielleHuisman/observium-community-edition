<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

$lm_array['temp'] = snmpwalk_cache_oid($device, 'lmTempSensorsEntry', array(), 'LM-SENSORS-MIB');
$lm_array['fan']  = snmpwalk_cache_oid($device, 'lmFanSensorsEntry', array(), 'LM-SENSORS-MIB');
$lm_array['volt'] = snmpwalk_cache_oid($device, 'lmVoltSensorsEntry', array(), 'LM-SENSORS-MIB');
//$lm_array['misc'] = snmpwalk_cache_oid($device, 'lmMiscSensorsEntry', array(), 'LM-SENSORS-MIB');

$scale = 0.001;
foreach ($lm_array['temp'] as $index => $entry)
{
  $oid   = ".1.3.6.1.4.1.2021.13.16.2.1.3.$index";
  $descr = str_ireplace(array('temperature-', 'temp-'), '', $entry['lmTempSensorsDevice']);
  $value = $entry['lmTempSensorsValue'];
  /* VM:
  lmTempSensorsDevice.1 = Core 0
  lmTempSensorsDevice.2 = Core 1
  lmTempSensorsValue.1 = 100000
  lmTempSensorsValue.2 = 100000
   */
  if ($entry['lmTempSensorsValue'] > 0 &&
    $value != 100000 && // VM always report 100000
    $value * $scale <= 200)
  {
    discover_sensor_ng($device,'temperature', $mib, 'lmTempSensorsValue', $oid, $index, NULL, $descr, $scale, $value, ['rename_rrd' => "lmsensors-$index"]);
  }
}

$scale = 1;
foreach ($lm_array['fan'] as $index => $entry)
{
  $oid   = ".1.3.6.1.4.1.2021.13.16.3.1.3.$index";
  $descr = str_ireplace('fan-', '', $entry['lmFanSensorsDevice']);
  $value = $entry['lmFanSensorsValue'];
  if ($entry['lmFanSensorsValue'] > 0)
  {
    discover_sensor_ng($device,'fanspeed', $mib, 'lmFanSensorsValue', $oid, $index, NULL, $descr, $scale, $value, ['rename_rrd' => "lmsensors-$index"]);
  }
}

$scale = 0.001;
foreach ($lm_array['volt'] as $index => $entry)
{
  $oid   = ".1.3.6.1.4.1.2021.13.16.4.1.3.$index";
  $descr = str_ireplace(array('voltage, ', 'volt-'), '', $entry['lmVoltSensorsDevice']);
  $value = $entry['lmVoltSensorsValue'];
  if (is_numeric($entry['lmVoltSensorsValue']))
  {
    discover_sensor_ng($device,'voltage', $mib, 'lmVoltSensorsValue', $oid, $index, NULL, $descr, $scale, $value, ['rename_rrd' => "lmsensors-$index"]);
  }
}

// EOF
