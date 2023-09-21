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

// FIXME migrate to definitions

$cache_discovery['DKSF-PWR-OLD-MIB']['smoke'] = snmpwalk_cache_oid($device, 'npSmokeTable', [], 'DKSF-PWR-OLD-MIB');
foreach ($cache_discovery['DKSF-PWR-OLD-MIB']['smoke'] as $index => $entry) {
    if ($entry['npSmokePower'] === 'off') {
        continue;
    }

    $oid   = '.1.3.6.1.4.1.25728.8200.1.1.2.' . $index;
    $descr = $entry['npSmokeMemo'] ?: 'Smoke ' . $index;
    $value = $entry['npSmokeStatus'];

    if ($value) {
        discover_status($device, $oid, 'npSmokeStatus.' . $index, 'dskf-mib-smoke-state', $descr, $value, [ 'entPhysicalClass' => 'other' ]);
    }
}

/*
$cache_discovery['DKSF-PWR-OLD-MIB']['loop'] = snmpwalk_cache_oid($device, 'npCurLoopTable', array(), 'DKSF-PWR-OLD-MIB');
foreach ($cache_discovery['DKSF-PWR-OLD-MIB']['loop'] as $index => $entry)
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
*/

$cache_discovery['DKSF-PWR-OLD-MIB']['temphum'] = snmpwalk_cache_oid($device, 'npRelHumidity', [], 'DKSF-PWR-OLD-MIB');
foreach ($cache_discovery['DKSF-PWR-OLD-MIB']['temphum'] as $index => $entry) {
    // Temperature
    $descr = 'Temperature ' . $index;

    $value = $entry['npRelHumSensorValueT100'];
    if ($value && $entry['npRelHumSensorStatus'] != 'error') {
        $oid = '.1.3.6.1.4.1.25728.8400.2.9.' . $index;
        discover_sensor('temperature', $device, $oid, "npRelHumSensorValueT100.$index", 'dskf-mib', $descr, 0.01, $value);
    }

    // Humidity
    $descr = 'Humidity ' . $index;

    $value = $entry['npRelHumSensorValueH'];
    if ($value >= 0 && $entry['npRelHumSensorStatusH'] != 'sensorFailed') {
        $oid    = '.1.3.6.1.4.1.25728.8400.2.2.' . $index;
        $limits = [ 'limit_high' => $entry['npRelHumSafeRangeHigh'], 'limit_low' => $entry['npRelHumSafeRangeLow'] ];
        discover_sensor('humidity', $device, $oid, "npRelHumSensorValueH.$index", 'dskf-mib', $descr, 1, $value, $limits);
    }
}

/* Moved to DEF
$cache_discovery['DKSF-PWR-OLD-MIB']['thermo'] = snmpwalk_cache_oid($device, 'npThermoTable', array(), 'DKSF-PWR-OLD-MIB');
foreach ($cache_discovery['DKSF-PWR-OLD-MIB']['thermo'] as $index => $entry)
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
*/

$cache_discovery['DKSF-PWR-OLD-MIB']['io'] = snmpwalk_cache_oid($device, 'npIoTable', [], 'DKSF-PWR-OLD-MIB');
foreach ($cache_discovery['DKSF-PWR-OLD-MIB']['io'] as $index => $entry) {
    if ($entry['npIoLevelIn'] == '0') {
        continue;
    }

    $descr    = $entry['npIoMemo'] ?: 'Pulse Counter ' . $index;
    $descr    .= ' (' . $entry['npIoSinglePulseDuration'] . 'ms)';
    $oid_name = 'npIoPulseCounter';
    $value    = $entry['npIoPulseCounter'];
    $oid      = '.1.3.6.1.4.1.25728.8900.1.1.9.' . $index;
    //discover_sensor('counter', $device, $oid, "npIoPulseCounter.$index", 'dskf-mib', $descr, 1, $value);
    discover_counter($device, 'counter', $mib, $oid_name, $oid, $index, $descr, 1, $value);
}

print_debug_vars($cache_discovery['DKSF-PWR-OLD-MIB']);

unset($cache_discovery['DKSF-PWR-OLD-MIB']);

// EOF
