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

$mib = 'ENLOGIC-PDU-MIB';

$scale         = 1;
$scale_current = 0.01;

//ENLOGIC-PDU-MIB::pduUnitStatusName.1 = STRING:
//ENLOGIC-PDU-MIB::pduUnitStatusName.2 = STRING:
//ENLOGIC-PDU-MIB::pduUnitStatusLoadState.1 = INTEGER: normal(5)
//ENLOGIC-PDU-MIB::pduUnitStatusLoadState.2 = INTEGER: normal(5)
//ENLOGIC-PDU-MIB::pduUnitStatusActivePower.1 = INTEGER: 548
//ENLOGIC-PDU-MIB::pduUnitStatusActivePower.2 = INTEGER: 432
//ENLOGIC-PDU-MIB::pduUnitStatusApparentPower.1 = INTEGER: 636
//ENLOGIC-PDU-MIB::pduUnitStatusApparentPower.2 = INTEGER: 455
//ENLOGIC-PDU-MIB::pduUnitStatusPeakPower.1 = INTEGER: 963
//ENLOGIC-PDU-MIB::pduUnitStatusPeakPower.2 = INTEGER: 539
//ENLOGIC-PDU-MIB::pduUnitStatusPeakPowerTimestamp.1 = STRING: 2011/06/18 07:26:08
//ENLOGIC-PDU-MIB::pduUnitStatusPeakPowerTimestamp.2 = STRING: 2012/03/29 18:09:05
//ENLOGIC-PDU-MIB::pduUnitStatusPeakPowerStartTime.1 = STRING: 2011/06/06 00:05:36
//ENLOGIC-PDU-MIB::pduUnitStatusPeakPowerStartTime.2 = STRING: 2011/06/06 00:05:44
//ENLOGIC-PDU-MIB::pduUnitStatusEnergy.1 = INTEGER: 114064
//ENLOGIC-PDU-MIB::pduUnitStatusEnergy.2 = INTEGER: 71644
//ENLOGIC-PDU-MIB::pduUnitStatusResettableEnergy.1 = INTEGER: 114064
//ENLOGIC-PDU-MIB::pduUnitStatusResettableEnergy.2 = INTEGER: 71644
//ENLOGIC-PDU-MIB::pduUnitStatusEnergyStartTime.1 = STRING: 2011/06/06 00:05:36
//ENLOGIC-PDU-MIB::pduUnitStatusEnergyStartTime.2 = STRING: 2011/06/06 00:05:44
//ENLOGIC-PDU-MIB::pduUnitStatusOutletsEnergyStartTime.1 = STRING:
//ENLOGIC-PDU-MIB::pduUnitStatusOutletsEnergyStartTime.2 = STRING:

$oids = snmpwalk_cache_oid($device, 'pduUnitStatusEntry', array(), $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigLowerWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigUpperWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigEnabledThresholds',      $oids, $mib);
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  $name    = "Unit $index";

  // pduUnitStatusLoadState
  $descr    = "$name Load";
  $oid_name = 'pduUnitStatusLoadState';
  $oid_num  = '.1.3.6.1.4.1.38446.1.2.4.1.3.'.$index;
  $value    = $entry[$oid_name];

  discover_status($device, $oid_num, $oid_name.'.'.$index, 'pduUnitStatusState', $descr, $value, array('entPhysicalClass' => 'other'));

  // pduUnitStatusActivePower
  $descr    = "$name Active Power";
  $oid_name = 'pduUnitStatusActivePower';
  $oid_num  = ".1.3.6.1.4.1.38446.1.2.4.1.4.$index";
  $type     = $mib . '-' . $oid_name;
  $value    = $entry[$oid_name];

  // Limits (based on enabled thresholds)
  //  SYNTAX  BITS {
  //        lowerCritical (0),
  //        lowerWarning (1),
  //        upperWarning  (2),
  //        upperCritical (3)
  //  }
  $options      = array();
  $limits_flags = base_convert($entry['pduUnitConfigEnabledThresholds'], 16, 10);
  if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
  {
    $options['limit_low']       = $entry['pduUnitConfigLowerCriticalThreshold'] * $scale;
  }
  if (is_flag_set(bindec(1000000),  $limits_flags)) // 0b 0100 0000
  {
    $options['limit_low_warn']  = $entry['pduUnitConfigLowerWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(100000),   $limits_flags)) // 0b 0010 0000
  {
    $options['limit_high_warn'] = $entry['pduUnitConfigUpperWarningThreshold']  * $scale;
  }
  if (is_flag_set(bindec(10000),    $limits_flags)) // 0b 0001 0000
  {
    $options['limit_high']      = $entry['pduUnitConfigUpperCriticalThreshold'] * $scale;
  }

  discover_sensor('power', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

  // pduUnitStatusApparentPower
  $descr    = "$name Apparent Power";
  $oid_name = 'pduUnitStatusApparentPower';
  $oid_num  = ".1.3.6.1.4.1.38446.1.2.4.1.5.$index";
  $type     = $mib . '-' . $oid_name;
  $value    = $entry[$oid_name];

  discover_sensor('apower', $device, $oid_num, $index, $type, $descr, $scale, $value);

  // pduUnitStatusEnergy
  // FIXME. Need discover_counter()
}

//ENLOGIC-PDU-MIB::pduUnitPropertiesName.1 = STRING:
//ENLOGIC-PDU-MIB::pduUnitPropertiesName.2 = STRING:
//ENLOGIC-PDU-MIB::pduUnitPropertiesOutletCount.1 = INTEGER: 24
//ENLOGIC-PDU-MIB::pduUnitPropertiesOutletCount.2 = INTEGER: 24
//ENLOGIC-PDU-MIB::pduUnitPropertiesSwitchedOutletCount.1 = INTEGER: 24
//ENLOGIC-PDU-MIB::pduUnitPropertiesSwitchedOutletCount.2 = INTEGER: 24
//ENLOGIC-PDU-MIB::pduUnitPropertiesMeteredOutletCount.1 = INTEGER: 0
//ENLOGIC-PDU-MIB::pduUnitPropertiesMeteredOutletCount.2 = INTEGER: 0
//ENLOGIC-PDU-MIB::pduUnitPropertiesInputPhaseCount.1 = INTEGER: 1
//ENLOGIC-PDU-MIB::pduUnitPropertiesInputPhaseCount.2 = INTEGER: 1
//ENLOGIC-PDU-MIB::pduUnitPropertiesCircuitBreakerCount.1 = INTEGER: 2
//ENLOGIC-PDU-MIB::pduUnitPropertiesCircuitBreakerCount.2 = INTEGER: 2
//ENLOGIC-PDU-MIB::pduUnitPropertiesMaxExternalSensorCount.1 = INTEGER: 6
//ENLOGIC-PDU-MIB::pduUnitPropertiesMaxExternalSensorCount.2 = INTEGER: 6
//ENLOGIC-PDU-MIB::pduUnitPropertiesConnExternalSensorCount.1 = INTEGER: 0
//ENLOGIC-PDU-MIB::pduUnitPropertiesConnExternalSensorCount.2 = INTEGER: 0
$units = snmpwalk_cache_oid($device, 'pduUnitPropertiesInputPhaseCount',        array(), $mib); // The total number of phases on the PDU
$units = snmpwalk_cache_oid($device, 'pduUnitPropertiesCircuitBreakerCount',     $units, $mib); // The total number of circuit breaker on the PDU
$units = snmpwalk_cache_oid($device, 'pduUnitPropertiesConnExternalSensorCount', $units, $mib); // The current number of external sensors connected to the PDU

$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseStatusEntry', array(), $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentLowerWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentUpperWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentEnabledThresholds',      $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageLowerWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageUpperWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageEnabledThresholds',      $oids, $mib);
print_debug_vars($units);
print_debug_vars($oids);

foreach ($oids as $unit => $entry1)
{
  foreach ($entry1 as $phase => $entry)
  {
    if ($entry['pduInputPhaseStatusCount'] < 1) { continue; } // Skip not exist phases

    $name     = "Unit $unit, Phase $phase Input";
    $index    = $unit . '.' . $phase;

    // pduInputPhaseStatusCurrentState
    $descr    = "$name Current";
    $oid_name = 'pduInputPhaseStatusCurrentState';
    $oid_num  = '.1.3.6.1.4.1.38446.1.3.4.1.3.'.$index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name.'.'.$index, 'pduUnitStatusState', $descr, $value, array('entPhysicalClass' => 'other'));

    // pduInputPhaseStatusVoltageState
    $descr    = "$name Voltage";
    $oid_name = 'pduInputPhaseStatusVoltageState';
    $oid_num  = '.1.3.6.1.4.1.38446.1.3.4.1.4.'.$index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name.'.'.$index, 'pduUnitStatusState', $descr, $value, array('entPhysicalClass' => 'other'));

    // pduInputPhaseStatusCurrent
    $descr    = "$name Current";
    $oid_name = 'pduInputPhaseStatusCurrent';
    $oid_num  = ".1.3.6.1.4.1.38446.1.3.4.1.5.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    // Limits (based on enabled thresholds)
    //  SYNTAX  BITS {
    //        lowerCritical (0),
    //        lowerWarning (1),
    //        upperWarning  (2),
    //        upperCritical (3)
    //  }
    $options      = array();
    $limits_flags = base_convert($entry['pduInputPhaseConfigCurrentEnabledThresholds'], 16, 10);
    if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
    {
      $options['limit_low']       = $entry['pduInputPhaseConfigCurrentLowerCriticalThreshold'] * $scale_current;
    }
    if (is_flag_set(bindec(1000000),  $limits_flags)) // 0b 0100 0000
    {
      $options['limit_low_warn']  = $entry['pduInputPhaseConfigCurrentLowerWarningThreshold']  * $scale_current;
    }
    if (is_flag_set(bindec(100000),   $limits_flags)) // 0b 0010 0000
    {
      $options['limit_high_warn'] = $entry['pduInputPhaseConfigCurrentUpperWarningThreshold']  * $scale_current;
    }
    if (is_flag_set(bindec(10000),    $limits_flags)) // 0b 0001 0000
    {
      $options['limit_high']      = $entry['pduInputPhaseConfigCurrentUpperCriticalThreshold'] * $scale_current;
    }

    discover_sensor('current', $device, $oid_num, $index, $type, $descr, $scale_current, $value, $options);

    // pduInputPhaseStatusVoltage
    $descr    = "$name Voltage";
    $oid_name = 'pduInputPhaseStatusVoltage';
    $oid_num  = ".1.3.6.1.4.1.38446.1.3.4.1.6.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    // Limits (based on enabled thresholds)
    //  SYNTAX  BITS {
    //        lowerCritical (0),
    //        lowerWarning (1),
    //        upperWarning  (2),
    //        upperCritical (3)
    //  }
    $options      = array();
    $limits_flags = base_convert($entry['pduInputPhaseConfigVoltageEnabledThresholds'], 16, 10);
    if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
    {
      $options['limit_low']       = $entry['pduInputPhaseConfigVoltageLowerCriticalThreshold'] * $scale;
    }
    if (is_flag_set(bindec(1000000),  $limits_flags)) // 0b 0100 0000
    {
      $options['limit_low_warn']  = $entry['pduInputPhaseConfigVoltageLowerWarningThreshold']  * $scale;
    }
    if (is_flag_set(bindec(100000),   $limits_flags)) // 0b 0010 0000
    {
      $options['limit_high_warn'] = $entry['pduInputPhaseConfigVoltageUpperWarningThreshold']  * $scale;
    }
    if (is_flag_set(bindec(10000),    $limits_flags)) // 0b 0001 0000
    {
      $options['limit_high']      = $entry['pduInputPhaseConfigVoltageUpperCriticalThreshold'] * $scale;
    }

    discover_sensor('voltage', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

    // pduInputPhaseStatusActivePower
    $descr    = "$name Active Power";
    $oid_name = 'pduInputPhaseStatusActivePower';
    $oid_num  = ".1.3.6.1.4.1.38446.1.3.4.1.7.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('power', $device, $oid_num, $index, $type, $descr, $scale, $value);

    // pduInputPhaseStatusApparentPower
    $descr    = "$name Apparent Power";
    $oid_name = 'pduInputPhaseStatusApparentPower';
    $oid_num  = ".1.3.6.1.4.1.38446.1.3.4.1.8.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('apower', $device, $oid_num, $index, $type, $descr, $scale, $value);

    // pduInputPhaseStatusPowerFactor
    $descr    = "$name Power Factor";
    $oid_name = 'pduInputPhaseStatusPowerFactor';
    $oid_num  = ".1.3.6.1.4.1.38446.1.3.4.1.9.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('powerfactor', $device, $oid_num, $index, $type, $descr, $scale, $value);
  }
}

$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerStatusEntry', array(), $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigLowerWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigUpperWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigEnabledThresholds',      $oids, $mib);
print_debug_vars($oids);

foreach ($oids as $unit => $entry1)
{
  foreach ($entry1 as $i => $entry)
  {
    if ($entry['pduCircuitBreakerStatusCount'] < 1) { continue; } // Skip not exist Circuit Breaker

    $name     = "Unit $unit, Circuit Breaker $i";
    $index    = $unit . '.' . $i;

    // pduCircuitBreakerStatusLoadState
    $descr    = "$name Load";
    $oid_name = 'pduCircuitBreakerStatusLoadState';
    $oid_num  = '.1.3.6.1.4.1.38446.1.4.4.1.4.'.$index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name.'.'.$index, 'pduUnitStatusState', $descr, $value, array('entPhysicalClass' => 'other'));

    // pduCircuitBreakerStatusCurrent
    $descr    = "$name Current";
    $oid_name = 'pduCircuitBreakerStatusCurrent';
    $oid_num  = ".1.3.6.1.4.1.38446.1.4.4.1.5.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    // Limits (based on enabled thresholds)
    //  SYNTAX  BITS {
    //        lowerCritical (0),
    //        lowerWarning (1),
    //        upperWarning  (2),
    //        upperCritical (3)
    //  }
    $options      = array();
    $limits_flags = base_convert($entry['pduCircuitBreakerConfigEnabledThresholds'], 16, 10);
    if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
    {
      $options['limit_low']       = $entry['pduCircuitBreakerConfigLowerCriticalThreshold'] * $scale_current;
    }
    if (is_flag_set(bindec(1000000),  $limits_flags)) // 0b 0100 0000
    {
      $options['limit_low_warn']  = $entry['pduCircuitBreakerConfigLowerWarningThreshold']  * $scale_current;
    }
    if (is_flag_set(bindec(100000),   $limits_flags)) // 0b 0010 0000
    {
      $options['limit_high_warn'] = $entry['pduCircuitBreakerConfigUpperWarningThreshold']  * $scale_current;
    }
    if (is_flag_set(bindec(10000),    $limits_flags)) // 0b 0001 0000
    {
      $options['limit_high']      = $entry['pduCircuitBreakerConfigUpperCriticalThreshold'] * $scale_current;
    }

    discover_sensor('current', $device, $oid_num, $index, $type, $descr, $scale_current, $value, $options);
  }
}

// NOTE, next part not tested, but should be working (mike)
$sensors_count = 0;
foreach ($units as $entry)
{
  $sensors_count += $entry['pduUnitPropertiesConnExternalSensorCount'];
}

if ($sensors_count == 0) { return; } // Skip next sensors discovery (not exist)

$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorStatusEntry',  array(), $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorNamePlateType',  $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorNamePlateUnits', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigLowerWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigUpperWarningThreshold',  $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigEnabledThresholds',      $oids, $mib);
print_debug_vars($oids);

foreach ($oids as $unit => $entry1)
{
  foreach ($entry1 as $i => $entry)
  {
    if ($entry['pduExternalSensorStatusState'] == 'notPresent') { continue; } // Skip not exist Sensors

    $name     = "Unit $unit, Sensor ".$entry['pduExternalSensorStatusName'];
    $index    = $unit . '.' . $i;

    // pduExternalSensorStatusState
    $descr    = $name;
    $oid_name = 'pduExternalSensorStatusState';
    $oid_num  = '.1.3.6.1.4.1.38446.1.6.4.1.5.'.$index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name.'.'.$index, 'pduExternalSensorStatusState', $descr, $value, array('entPhysicalClass' => 'other'));

    // pduExternalSensorStatusValue
    $descr    = "$name Voltage";
    //$oid_name = 'pduExternalSensorStatusValue';
    //$oid_num  = ".1.3.6.1.4.1.38446.1.6.4.1.6.$index";
    //$type     = $mib . '-' . $oid_name;
    //$value    = $entry[$oid_name];

    $scale = 1;
    // Limits (based on enabled thresholds)
    //  SYNTAX  BITS {
    //        lowerCritical (0),
    //        lowerWarning (1),
    //        upperWarning  (2),
    //        upperCritical (3)
    //  }
    $options      = array();
    $limits_flags = base_convert($entry['pduExternalSensorConfigEnabledThresholds'], 16, 10);
    if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
    {
      $options['limit_low']       = $entry['pduExternalSensorConfigLowerCriticalThreshold'] * $scale;
    }
    if (is_flag_set(bindec(1000000),  $limits_flags)) // 0b 0100 0000
    {
      $options['limit_low_warn']  = $entry['pduExternalSensorConfigLowerWarningThreshold']  * $scale;
    }
    if (is_flag_set(bindec(100000),   $limits_flags)) // 0b 0010 0000
    {
      $options['limit_high_warn'] = $entry['pduExternalSensorConfigUpperWarningThreshold']  * $scale;
    }
    if (is_flag_set(bindec(10000),    $limits_flags)) // 0b 0001 0000
    {
      $options['limit_high']      = $entry['pduExternalSensorConfigUpperCriticalThreshold'] * $scale;
    }

    // Known sensor types
    //  SYNTAX  INTEGER {
    //        temperature (1),
    //        humidity (2),
    //        doorSwitch (3),
    //        dryContact (4),
    //        spotFluid (5),
    //        ropeFluid (6),
    //        smoke (7),
    //        beacon (8),
    //        airVelocity (9),
    //        modbusAdapter (17),
    //        hidAdapter (18)
    //  }
    switch ($entry['pduExternalSensorNamePlateType'])
    {
      case 'temperature':
      case 'humidity':
        $sensor_class = $entry['pduExternalSensorNamePlateType'];
        break;
      case 'airVelocity':
        $sensor_class = 'velocity';
        break;
      default:
        continue 2;
    }
    if ($entry['pduExternalSensorNamePlateUnits'] == 'degreeF')
    {
      $options['sensor_unit'] = 'F';
    }

    if (isset($entry['pduExternalSensorStatusHighPrecisionValue'])) // && $entry['pduExternalSensorStatusHighPrecisionValue'] > 0)
    {
      $scale = 0.1;
      $oid_name = 'pduExternalSensorStatusHighPrecisionValue';
      $oid_num  = ".1.3.6.1.4.1.38446.1.6.4.1.8.$index";
    } else {
      $oid_name = 'pduExternalSensorStatusValue';
      $oid_num  = ".1.3.6.1.4.1.38446.1.6.4.1.6.$index";
    }
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor($sensor_class, $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
  }
}

// EOF
