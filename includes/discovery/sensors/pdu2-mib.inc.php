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

echo(" PDU2-MIB ");
$mib = 'PDU2-MIB';

$pduId = '1'; // PX2/3 and transfer switch: pduiId = 1

// SensorTypeEnumeration
$oid_types = array(
  'rmsCurrent'            => array('type' => 'current',     'index' => 1),
  'peakCurrent'           => array('type' => 'current',     'index' => 2),
  'unbalancedCurrent'     => array('type' => 'current',     'index' => 3),
  'rmsVoltage'            => array('type' => 'voltage',     'index' => 4),
  'activePower'           => array('type' => 'power',       'index' => 5),
  'apparentPower'         => array('type' => 'apower',      'index' => 6),
  'powerFactor'           => array('type' => 'powerfactor', 'index' => 7),
  'activeEnergy'          => array('type' => 'energy',      'index' => 8),
  'apparentEnergy'        => array('type' => 'aenergy',     'index' => 9),
  'temperature'           => array('type' => 'temperature', 'index' => 10),
  'humidity'              => array('type' => 'humidity',    'index' => 11),
  'airFlow'               => array('type' => '',            'index' => 12),
  'airPressure'           => array('type' => '',            'index' => 13),
  'onOff'                 => array('type' => '',            'index' => 14),
  'trip'                  => array('type' => '',            'index' => 15),
  'vibration'             => array('type' => '',            'index' => 16),
  'waterDetection'        => array('type' => '',            'index' => 17),
  'smokeDetection'        => array('type' => '',            'index' => 18),
  'binary'                => array('type' => '',            'index' => 19),
  //'contact'               => array('type' => '',            'index' => 20),
  'fanSpeed'              => array('type' => 'fanspeed',    'index' => 21),
  'surgeProtectorStatus'  => array('type' => '',            'index' => 22),
  'frequency'             => array('type' => 'frequency',   'index' => 23),
  //'phaseAngle'            => array('type' => '',            'index' => 24),
  'rmsVoltageLN'          => array('type' => 'voltage',     'index' => 25),
  'residualCurrent'       => array('type' => 'current',     'index' => 26),
  'rcmState'              => array('type' => '',            'index' => 27),
  'absoluteHumidity'      => array('type' => 'humidity',    'index' => 28),
  'reactivePower'         => array('type' => 'rpower',      'index' => 29),
  'other'                 => array('type' => '',            'index' => 30),
  'none'                  => array('type' => '',            'index' => 31),
  'powerQuality'          => array('type' => '',            'index' => 32),
  'overloadStatus'        => array('type' => '',            'index' => 33),
  'overheatStatus'        => array('type' => '',            'index' => 34),
  'displacementPowerFactor' => array('type' => '',          'index' => 35),
  'fanStatus'             => array('type' => '',            'index' => 37),
  //'inletPhaseSyncAngle'   => array('type' => '',            'index' => 38),
  //'inletPhaseSync'        => array('type' => '',            'index' => 39),
  'operatingState'        => array('type' => '',            'index' => 40),
  'activeInlet'           => array('type' => '',            'index' => 41),
  'illuminance'           => array('type' => 'illuminance', 'index' => 42),
  'doorContact'           => array('type' => '',            'index' => 43),
  'tamperDetection'       => array('type' => '',            'index' => 44),
  'motionDetection'       => array('type' => '',            'index' => 45),
  'i1smpsStatus'          => array('type' => '',            'index' => 46),
  'i2smpsStatus'          => array('type' => '',            'index' => 47),
  'switchStatus'          => array('type' => '',            'index' => 48),
);

// SensorUnitsEnumeration
$oid_units = array(
  //none(-1),
  //other(0),
  'volt'            => array('type' => 'voltage'),
  'amp'             => array('type' => 'current'),
  'watt'            => array('type' => 'power'),
  'voltamp'         => array('type' => 'apower'),
  'wattHour'        => array('type' => 'energy'),
  'voltampHour'     => array('type' => 'aenergy'),
  'degreeC'         => array('type' => 'temperature', 'unit' => 'C'),
  'hertz'           => array('type' => 'frequency'),
  'percent'         => array('type' => 'humidity'),
  'meterpersec'     => array('type' => 'velocity'),
  'pascal'          => array('type' => 'pressure'),
  'psi'             => array('type' => 'pressure', 'unit' => 'psi'),
  //g(13),
  'degreeF'         => array('type' => 'temperature', 'unit' => 'F'),
  //feet(15),
  //inches(16),
  //cm(17),
  //meters(18),
  'rpm'             => array('type' => 'fanspeed'),
  //degrees(20),
  'lux'             => array('type' => 'illuminance'),
  //grampercubicmeter(22),
  'var'             => array('type' => 'rpower'),
);

// Inlets
$names = snmpwalk_cache_oid($device, "inletName",                          array(), $mib);
$oids  = snmpwalk_cache_oid($device, "inletSensorUnits",                   array(), $mib);
$oids  = snmpwalk_cache_oid($device, "inletSensorDecimalDigits",             $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "inletSensorEnabledThresholds",         $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "inletSensorLowerCriticalThreshold",    $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "inletSensorLowerWarningThreshold",     $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "inletSensorUpperWarningThreshold",     $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "inletSensorUpperCriticalThreshold",    $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsInletSensorIsAvailable",   $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsInletSensorState",         $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsInletSensorValue",         $oids, $mib);
print_debug_vars($names);
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  list($pduId, $id, $sensorType) = explode('.', $index);
  //list(, $id, $sensorType) = explode('.', $index);
  $index_id     = $pduId . '.' . $id;
  $index        = $index_id . '.' . $oid_types[$sensorType]['index']; // Convert to numeric index

  if (!isset($oid_types[$sensorType]) || $entry['measurementsInletSensorIsAvailable'] === 'false')
  {
    continue;
  }

  if ($names[$index_id]['inletName'] != '')
  {
    $descr = "Inlet $index_id: " . $names[$index_id]['inletName'];
  } else {
    $descr = "Inlet $index_id";
  }

  $scale    = si_to_scale($entry['inletSensorDecimalDigits'] * -1);
  $oid_name = 'measurementsInletSensorValue';
  $oid_num  = '.1.3.6.1.4.1.13742.6.5.2.3.1.4.' . $index;
  $type     = $mib . '-' . $oid_name;
  $value    = $entry[$oid_name];

  // Limits (based on enabled thresholds)
  //  SYNTAX BITS {
  //    lowerCritical(0),
  //    lowerWarning(1),
  //    upperWarning(2),
  //    upperCritical(3),
  // }
  $options      = array();
  $limits_flags = base_convert(str_replace(' ', '', $entry['inletSensorEnabledThresholds']), 16, 10);
  if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
  {
    $options['limit_low']       = $entry['inletSensorLowerCriticalThreshold'] * $scale;
  }
  if (is_flag_set(bindec(1000000),  $limits_flags)) // 0b 0100 0000
  {
    $options['limit_low_warn']  = $entry['inletSensorLowerWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(100000),   $limits_flags)) // 0b 0010 0000
  {
    $options['limit_high_warn'] = $entry['inletSensorUpperWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(10000),    $limits_flags)) // 0b 0001 0000
  {
    $options['limit_high']      = $entry['inletSensorUpperCriticalThreshold'] * $scale;
  }

  // Detect type & unit
  $unit = array();
  if (isset($oid_units[$entry['inletSensorUnits']]))
  {
    $unit = $oid_units[$entry['inletSensorUnits']];
  }
  elseif (!empty($oid_types[$sensorType]['type']))
  {
    // Other sensors based on SensorTypeEnumeration
    $unit = $oid_types[$sensorType];
  } else {
    $oid_name = 'measurementsInletSensorState';
    $oid_num  = '.1.3.6.1.4.1.13742.6.5.2.3.1.3.'.$index;
    $type     = 'pdu2-sensorstate';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'other'));
    continue;
  }

  if (isset($unit['type']))
  {
    if (isset($unit['unit']))
    {
      $options['sensor_unit'] = $unit['unit'];
    }

    if (isset($config['counter_types'][$unit['type']]))
    {
      // Counters
      discover_counter($device, $unit['type'], $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, $options);
    } else {
      // FIXME convert to discover_sensor_ng()
      discover_sensor($unit['type'], $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
    }
  }
}

// Outlets
$names = snmpwalk_cache_oid($device, "outletName",                         array(), $mib);
$oids  = snmpwalk_cache_oid($device, "outletSensorUnits",                  array(), $mib);
$oids  = snmpwalk_cache_oid($device, "outletSensorDecimalDigits",            $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "outletSensorEnabledThresholds",        $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "outletSensorLowerCriticalThreshold",   $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "outletSensorLowerWarningThreshold",    $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "outletSensorUpperWarningThreshold",    $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "outletSensorUpperCriticalThreshold",   $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsOutletSensorIsAvailable",  $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsOutletSensorState",        $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsOutletSensorValue",        $oids, $mib);
print_debug_vars($names);
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  list($pduId, $id, $sensorType) = explode('.', $index);
  //list(, $id, $sensorType) = explode('.', $index);
  $index_id = $pduId . '.' . $id;
  $index        = $index_id . '.' . $oid_types[$sensorType]['index']; // Convert to numeric index

  if (!isset($oid_types[$sensorType]) || $entry['measurementsOutletSensorIsAvailable'] === 'false')
  {
    continue;
  }

  if ($names[$index_id]['outletName'] != '')
  {
    $descr = "Outlet $index_id: " . $names[$index_id]['outletName'];
  } else {
    $descr = "Outlet $index_id";
  }

  $scale    = si_to_scale($entry['outletSensorDecimalDigits'] * -1);
  $oid_name = 'measurementsOutletSensorValue';
  $oid_num  = '.1.3.6.1.4.1.13742.6.5.4.3.1.4.' . $index;
  $type     = $mib . '-' . $oid_name;
  $value    = $entry[$oid_name];

  // Limits (based on enabled thresholds)
  //  SYNTAX BITS {
  //    lowerCritical(0),
  //    lowerWarning(1),
  //    upperWarning(2),
  //    upperCritical(3),
  // }
  $options      = array();
  $limits_flags = base_convert(str_replace(' ', '', $entry['outletSensorEnabledThresholds']), 16, 10);
  if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
  {
    $options['limit_low']       = $entry['outletSensorLowerCriticalThreshold'] * $scale;
  }
  if (is_flag_set(bindec(1000000),  $limits_flags)) // 0b 0100 0000
  {
    $options['limit_low_warn']  = $entry['outletSensorLowerWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(100000),   $limits_flags)) // 0b 0010 0000
  {
    $options['limit_high_warn'] = $entry['outletSensorUpperWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(10000),    $limits_flags)) // 0b 0001 0000
  {
    $options['limit_high']      = $entry['outletSensorUpperCriticalThreshold'] * $scale;
  }

  // Detect type & unit
  $unit = array();
  if (isset($oid_units[$entry['outletSensorUnits']]))
  {
    $unit = $oid_units[$entry['outletSensorUnits']];
  }
  else if (!empty($oid_types[$sensorType]['type']))
  {
    // Other sensors based on SensorTypeEnumeration
    $unit = $oid_types[$sensorType];
  } else {
    $oid_name = 'measurementsOutletSensorState';
    $oid_num  = '.1.3.6.1.4.1.13742.6.5.4.3.1.3.'.$index;
    $type     = 'pdu2-sensorstate';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'other'));
    continue;
  }

  if (isset($unit['type']))
  {
    if (isset($unit['unit']))
    {
      $options['sensor_unit'] = $unit['unit'];
    }

    discover_sensor($unit['type'], $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
  }
}

// Over Current Protectors
$names = snmpwalk_cache_oid($device, "overCurrentProtectorName",                         array(), $mib);
$oids  = snmpwalk_cache_oid($device, "overCurrentProtectorSensorUnits",                  array(), $mib);
$oids  = snmpwalk_cache_oid($device, "overCurrentProtectorSensorDecimalDigits",            $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "overCurrentProtectorSensorEnabledThresholds",        $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "overCurrentProtectorSensorLowerCriticalThreshold",   $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "overCurrentProtectorSensorLowerWarningThreshold",    $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "overCurrentProtectorSensorUpperWarningThreshold",    $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "overCurrentProtectorSensorUpperCriticalThreshold",   $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsOverCurrentProtectorSensorIsAvailable",  $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsOverCurrentProtectorSensorState",        $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsOverCurrentProtectorSensorValue",        $oids, $mib);
print_debug_vars($names);
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  list($pduId, $id, $sensorType) = explode('.', $index);
  //list(, $id, $sensorType) = explode('.', $index);
  $index_id = $pduId . '.' . $id;
  $index        = $index_id . '.' . $oid_types[$sensorType]['index']; // Convert to numeric index

  if (!isset($oid_types[$sensorType]) || $entry['measurementsOverCurrentProtectorSensorIsAvailable'] === 'false')
  {
    continue;
  }

  if ($names[$index_id]['overCurrentProtectorName'] != '')
  {
    $descr = "Over Current Protector $index_id: " . $names[$index_id]['overCurrentProtectorName'];
  } else {
    $descr = "Over Current Protector $index_id";
  }

  $scale    = si_to_scale($entry['overCurrentProtectorSensorDecimalDigits'] * -1);
  $oid_name = 'measurementsOverCurrentProtectorSensorValue';
  $oid_num  = '.1.3.6.1.4.1.13742.6.5.3.3.1.4.' . $index;
  $type     = $mib . '-' . $oid_name;
  $value    = $entry[$oid_name];

  // Limits (based on enabled thresholds)
  //  SYNTAX BITS {
  //    lowerCritical(0),
  //    lowerWarning(1),
  //    upperWarning(2),
  //    upperCritical(3),
  // }
  $options      = array();
  $limits_flags = base_convert(str_replace(' ', '', $entry['overCurrentProtectorSensorEnabledThresholds']), 16, 10);
  if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
  {
    $options['limit_low']       = $entry['overCurrentProtectorSensorLowerCriticalThreshold'] * $scale;
  }
  if (is_flag_set(bindec(1000000),  $limits_flags)) // 0b 0100 0000
  {
    $options['limit_low_warn']  = $entry['overCurrentProtectorSensorLowerWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(100000),   $limits_flags)) // 0b 0010 0000
  {
    $options['limit_high_warn'] = $entry['overCurrentProtectorSensorUpperWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(10000),    $limits_flags)) // 0b 0001 0000
  {
    $options['limit_high']      = $entry['overCurrentProtectorSensorUpperCriticalThreshold'] * $scale;
  }

  // Detect type & unit
  $unit = array();
  if (isset($oid_units[$entry['overCurrentProtectorSensorUnits']]))
  {
    $unit = $oid_units[$entry['overCurrentProtectorSensorUnits']];
  }
  else if (!empty($oid_types[$sensorType]['type']))
  {
    // Other sensors based on SensorTypeEnumeration
    $unit = $oid_types[$sensorType];
  } else {
    $oid_name = 'measurementsOverCurrentProtectorSensorState';
    $oid_num  = '.1.3.6.1.4.1.13742.6.5.3.3.1.3.'.$index;
    $type     = 'pdu2-sensorstate';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'other'));
    continue;
  }

  if (isset($unit['type']))
  {
    if (isset($unit['unit']))
    {
      $options['sensor_unit'] = $unit['unit'];
    }

    discover_sensor($unit['type'], $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
  }
}

// External Sensors
$oids  = snmpwalk_cache_oid($device, "externalSensorName",                   array(), $mib);
$oids  = snmpwalk_cache_oid($device, "externalSensorDescription",              $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "externalSensorType",                     $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "externalSensorUnits",                    $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "externalSensorDecimalDigits",            $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "externalSensorEnabledThresholds",        $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "externalSensorLowerCriticalThreshold",   $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "externalSensorLowerWarningThreshold",    $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "externalSensorUpperWarningThreshold",    $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "externalSensorUpperCriticalThreshold",   $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsExternalSensorIsAvailable",  $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsExternalSensorState",        $oids, $mib);
$oids  = snmpwalk_cache_oid($device, "measurementsExternalSensorValue",        $oids, $mib);
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  $sensorType = $entry['externalSensorType'];

  if (!isset($oid_types[$sensorType]) || $entry['measurementsExternalSensorIsAvailable'] === 'false')
  {
    continue;
  }

  $descr = "Sensor $index";
  if ($entry['externalSensorName'] != '')
  {
    $descr .= ": " . $entry['externalSensorName'];
  }
  else if ($entry['externalSensorDescription'] != '')
  {
    $descr .= ": " . $entry['externalSensorDescription'];
  }

  $scale    = si_to_scale($entry['externalSensorDecimalDigits'] * -1);
  $oid_name = 'measurementsExternalSensorValue';
  $oid_num  = '.1.3.6.1.4.1.13742.6.5.5.3.1.4.' . $index;
  $type     = $mib . '-' . $oid_name;
  $value    = $entry[$oid_name];

  // Limits (based on enabled thresholds)
  //  SYNTAX BITS {
  //    lowerCritical(0),
  //    lowerWarning(1),
  //    upperWarning(2),
  //    upperCritical(3),
  // }
  $options      = array();
  $limits_flags = base_convert(str_replace(' ', '', $entry['externalSensorEnabledThresholds']), 16, 10);
  if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
  {
    $options['limit_low']       = $entry['externalSensorLowerCriticalThreshold'] * $scale;
  }
  if (is_flag_set(bindec(1000000),  $limits_flags)) // 0b 0100 0000
  {
    $options['limit_low_warn']  = $entry['externalSensorLowerWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(100000),   $limits_flags)) // 0b 0010 0000
  {
    $options['limit_high_warn'] = $entry['externalSensorUpperWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(10000),    $limits_flags)) // 0b 0001 0000
  {
    $options['limit_high']      = $entry['externalSensorUpperCriticalThreshold'] * $scale;
  }

  // Detect type & unit
  $unit = array();
  if (isset($oid_units[$entry['externalSensorUnits']]))
  {
    $unit = $oid_units[$entry['externalSensorUnits']];
  }
  else if (!empty($oid_types[$sensorType]['type']))
  {
    // Other sensors based on SensorTypeEnumeration
    $unit = $oid_types[$sensorType];
  } else {
    $oid_name = 'measurementsExternalSensorState';
    $oid_num  = '.1.3.6.1.4.1.13742.6.5.5.3.1.3.'.$index;
    $type     = 'pdu2-sensorstate';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'other'));
    continue;
  }

  if (isset($unit['type']))
  {
    if (isset($unit['unit']))
    {
      $options['sensor_unit'] = $unit['unit'];
    }

    discover_sensor($unit['type'], $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
  }
}

// EOF
