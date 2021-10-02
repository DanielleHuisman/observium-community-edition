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

// FIXME migrate to definitions

$cache_discovery['DKSF-70-5-X-X-1']['smoke'] = snmpwalk_cache_oid($device, 'npSmokeTable', array(), 'DKSF-70-5-X-X-1');
foreach ($cache_discovery['DKSF-70-5-X-X-1']['smoke'] as $index => $entry)
{
  if ($entry['npSmokePower'] == 'off') { continue; }

  $oid   = '.1.3.6.1.4.1.25728.8200.1.1.2.'.$index;
  $descr = ($entry['npSmokeMemo'] ? $entry['npSmokeMemo'] : 'Smoke '.$index);
  $value = $entry['npSmokeStatus'];

  if ($value)
  {
    discover_status($device, $oid, 'npSmokeStatus.'.$index, 'dskf-mib-smoke-state', $descr, $value, array('entPhysicalClass' => 'other'));
  }
}

$cache_discovery['DKSF-70-5-X-X-1']['loop'] = snmpwalk_cache_oid($device, 'npCurLoopTable', array(), 'DKSF-70-5-X-X-1');
foreach ($cache_discovery['DKSF-70-5-X-X-1']['loop'] as $index => $entry)
{
  if ($entry['npCurLoopPower'] == 'off' || $entry['npCurLoopStatus'] == 'notPowered') { continue; }

  $descr = 'Analog Smoke '.$index;

  // Loop state
  $oid   = '.1.3.6.1.4.1.25728.8300.1.1.2.'.$index;
  $value = $entry['npCurLoopStatus'];

  if ($value)
  {
    discover_status($device, $oid, 'npCurLoopStatus.'.$index, 'dskf-mib-loop-state', $descr, $value, array('entPhysicalClass' => 'other'));
  }

  // Loop current
  $oid   = '.1.3.6.1.4.1.25728.8300.1.1.3.'.$index;
  $value = $entry['npCurLoopI'];

  if ($value)
  {
    discover_sensor('current', $device, $oid, 'npCurLoopI.'.$index, 'dskf-mib-loop', $descr, 0.001, $value);
  }

  // Loop voltage
  $oid   = '.1.3.6.1.4.1.25728.8300.1.1.4.'.$index;
  $value = $entry['npCurLoopV'];

  if ($value)
  {
    discover_sensor('voltage', $device, $oid, 'npCurLoopV.'.$index, 'dskf-mib-loop', $descr, 0.001, $value);
  }

  // Loop resistance
  $oid   = '.1.3.6.1.4.1.25728.8300.1.1.5.'.$index;
  $value = $entry['npCurLoopR'];

  if ($value && $value < 99999)
  {
    discover_sensor('resistance', $device, $oid, 'npCurLoopR.'.$index, 'dskf-mib-loop', $descr, 1, $value);
  }
}

$cache_discovery['DKSF-70-5-X-X-1']['temphum'] = snmpwalk_cache_oid($device, 'npRelHumTable', array(), 'DKSF-70-5-X-X-1');
foreach ($cache_discovery['DKSF-70-5-X-X-1']['temphum'] as $index => $entry)
{
  // Temperature
  $descr = ($entry['npRelHumMemo'] ? $entry['npRelHumMemo'] : 'Temperature '.$index);

  $value = $entry['npRelHumTempValue'];
  if ($value && $entry['npRelHumTempStatus'] != 'sensorFailed')
  {
    $oid = '.1.3.6.1.4.1.25728.8400.1.1.4.'.$index;
    $limits = array('limit_high' => $entry['npRelHumTempSafeRangeHigh'], 'limit_low' => $entry['npRelHumTempSafeRangeLow']);
    discover_sensor('temperature', $device, $oid, "npRelHumTempValue.$index", 'dskf-mib', $descr, 1, $value, $limits);
  }

  // Humidity
  $descr = ($entry['npRelHumMemo'] ? $entry['npRelHumMemo'] : 'Humidity '.$index);

  $value = $entry['npRelHumValue'];
  if ($value >= 0 && $entry['npRelHumStatus'] != 'sensorFailed')
  {
    $oid = '.1.3.6.1.4.1.25728.8400.1.1.2.'.$index;
    $limits = array('limit_high' => $entry['npRelHumSafeRangeHigh'], 'limit_low' => $entry['npRelHumSafeRangeLow']);
    discover_sensor('humidity', $device, $oid, "npRelHumValue.$index", 'dskf-mib', $descr, 1, $value, $limits);
  }
}

$cache_discovery['DKSF-70-5-X-X-1']['thermo'] = snmpwalk_cache_oid($device, 'npThermoTable', array(), 'DKSF-70-5-X-X-1');
foreach ($cache_discovery['DKSF-70-5-X-X-1']['thermo'] as $index => $entry)
{
  // Temperature
  $descr = ($entry['npThermoMemo'] ? $entry['npThermoMemo'] : 'Thermo '.$index);

  $value = $entry['npThermoValue'];
  if ($value && $entry['npThermoStatus'] != 'failed')
  {
    $oid = '.1.3.6.1.4.1.25728.8800.1.1.2.'.$index;
    $limits = array('limit_high' => $entry['npThermoHigh'], 'limit_low' => $entry['npThermoLow']);
    discover_sensor('temperature', $device, $oid, "npThermoValue.$index", 'dskf-mib', $descr, 1, $value, $limits);
  }
}

$cache_discovery['DKSF-70-5-X-X-1']['io'] = snmpwalk_cache_oid($device, 'npIoTable', array(), 'DKSF-70-5-X-X-1');
foreach ($cache_discovery['DKSF-70-5-X-X-1']['io'] as $index => $entry)
{
  if ($entry['npIoLevelIn'] == '0') { continue; }

  $descr = ($entry['npIoMemo'] ? $entry['npIoMemo'] : 'Pulse Counter '.$index);
  $descr .= ' (' . $entry['npIoSinglePulseDuration'] . 'ms)';
  $oid_name = 'npIoPulseCounter';
  $value = $entry['npIoPulseCounter'];
  $oid = '.1.3.6.1.4.1.25728.8900.1.1.9.'.$index;
  //discover_sensor('counter', $device, $oid, "npIoPulseCounter.$index", 'dskf-mib', $descr, 1, $value);
  discover_counter($device, 'counter', $mib, $oid_name, $oid, $index, $descr, 1, $value);
}

print_debug_vars($cache_discovery['DKSF-70-5-X-X-1']);

unset($cache_discovery['DKSF-70-5-X-X-1']);

// EOF
