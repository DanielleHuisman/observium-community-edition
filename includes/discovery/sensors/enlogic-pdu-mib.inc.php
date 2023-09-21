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

//$mib = 'ENLOGIC-PDU2-MIB'; Do not hardcode mib name here!

$scale         = 1;
$scale_current = 0.01;

// ENLOGIC-PDU2-MIB::pduUnitStatusIndex.1.1 = INTEGER: 1
// ENLOGIC-PDU2-MIB::pduUnitStatusName.1 = STRING: a.4bh36.vlt.pdu
// ENLOGIC-PDU2-MIB::pduUnitStatusLoadState.1 = INTEGER: normal(5)
// ENLOGIC-PDU2-MIB::pduUnitStatusActivePower.1 = INTEGER: 868
// ENLOGIC-PDU2-MIB::pduUnitStatusApparentPower.1 = INTEGER: 1008
// ENLOGIC-PDU2-MIB::pduUnitStatusPeakPower.1 = INTEGER: 1888
// ENLOGIC-PDU2-MIB::pduUnitStatusPeakPowerTimestamp.1 = STRING: 2020/09/16 01:20:29.
// ENLOGIC-PDU2-MIB::pduUnitStatusPeakPowerStartTime.1 = STRING: 2020/07/03 11:10:3.
// ENLOGIC-PDU2-MIB::pduUnitStatusEnergy.1 = INTEGER: 6301
// ENLOGIC-PDU2-MIB::pduUnitStatusResettableEnergy.1 = INTEGER: 6301
// ENLOGIC-PDU2-MIB::pduUnitStatusEnergyStartTime.1 = STRING: 2020/09/16 01:20:2.
// ENLOGIC-PDU2-MIB::pduUnitStatusOutletsEnergyStartTime.1 = STRING: 2020/09/16 01:20:2.

// ENLOGIC-PDU2-MIB::pduUnitConfigIndex.1.1 = INTEGER: 1
// ENLOGIC-PDU2-MIB::pduUnitConfigName.1 = STRING: a.4bh36.vlt.pdu
// ENLOGIC-PDU2-MIB::pduUnitConfigLowerCriticalThreshold.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
// ENLOGIC-PDU2-MIB::pduUnitConfigLowerWarningThreshold.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
// ENLOGIC-PDU2-MIB::pduUnitConfigUpperCriticalThreshold.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
// ENLOGIC-PDU2-MIB::pduUnitConfigUpperWarningThreshold.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
// ENLOGIC-PDU2-MIB::pduUnitConfigAlarmResetThreshold.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
// ENLOGIC-PDU2-MIB::pduUnitConfigAlarmStateChangeDelay.1 = Wrong Type (should be Gauge32 or Unsigned32): INTEGER: 0
// ENLOGIC-PDU2-MIB::pduUnitConfigEnabledThresholds.1 = BITS: 00

$oids = snmpwalk_cache_oid($device, 'pduUnitStatusEntry', [], $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigLowerWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigUpperWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'pduUnitConfigEnabledThresholds', $oids, $mib);
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    $name = "Unit $index";

    // pduUnitStatusLoadState
    $descr    = "$name Load";
    $oid_name = 'pduUnitStatusLoadState';
    $oid_num  = '.1.3.6.1.4.1.38446.1.2.4.1.3.' . $index;
    $value    = $entry[$oid_name];
    $old_rrd  = 'pduUnitStatusState-' . $oid_name . '.' . $index;

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'pduUnitStatusState', $descr, $value, ['entPhysicalClass' => 'other', 'rename_rrd' => $old_rrd]);

    // pduUnitStatusActivePower

    // Limits (based on enabled thresholds)
    //  SYNTAX  BITS {
    //        lowerCritical (0),
    //        lowerWarning (1),
    //        upperWarning  (2),
    //        upperCritical (3)
    //  }
    $options      = [];
    $limits_flags = base_convert(str_replace(' ', '', $entry['pduUnitConfigEnabledThresholds']), 16, 10);
    if (is_flag_set(bindec(10000000), $limits_flags)) { // 0b 1000 0000
        $options['limit_low'] = $entry['pduUnitConfigLowerCriticalThreshold'] * $scale;
    }
    if (is_flag_set(bindec(1000000), $limits_flags)) { // 0b 0100 0000
        $options['limit_low_warn'] = $entry['pduUnitConfigLowerWarningThreshold'] * $scale;
    }
    if (is_flag_set(bindec(100000), $limits_flags)) { // 0b 0010 0000
        $options['limit_high_warn'] = $entry['pduUnitConfigUpperWarningThreshold'] * $scale;
    }
    if (is_flag_set(bindec(10000), $limits_flags)) { // 0b 0001 0000
        $options['limit_high'] = $entry['pduUnitConfigUpperCriticalThreshold'] * $scale;
    }

    $descr                 = "$name Active Power";
    $oid_name              = 'pduUnitStatusActivePower';
    $oid_num               = ".1.3.6.1.4.1.38446.1.2.4.1.4.$index";
    $value                 = $entry[$oid_name];
    $options['rename_rrd'] = 'ENLOGIC-PDU-MIB-' . $oid_name . '-' . $index;

    discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    // pduUnitStatusApparentPower
    $descr    = "$name Apparent Power";
    $oid_name = 'pduUnitStatusApparentPower';
    $oid_num  = ".1.3.6.1.4.1.38446.1.2.4.1.5.$index";
    $value    = $entry[$oid_name];
    $options  = ['rename_rrd' => 'ENLOGIC-PDU-MIB-' . $oid_name . '-' . $index];

    discover_sensor_ng($device, 'apower', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    // pduUnitStatusEnergy (tenths of kilowatt-hours)
    $descr    = "$name Energy";
    $oid_name = 'pduUnitStatusEnergy';
    $oid_num  = ".1.3.6.1.4.1.38446.1.2.4.1.9.$index";
    $value    = $entry[$oid_name];

    if ($value > 0) {
        discover_counter($device, 'energy', $mib, $oid_name, $oid_num, $index, $descr, 1, $value);
    }
}

// ENLOGIC-PDU2-MIB::pduUnitPropertiesName.1 = STRING: a.4bh36.vlt.pdu
// ENLOGIC-PDU2-MIB::pduUnitPropertiesOutletCount.1 = INTEGER: 32
// ENLOGIC-PDU2-MIB::pduUnitPropertiesSwitchedOutletCount.1 = INTEGER: 32
// ENLOGIC-PDU2-MIB::pduUnitPropertiesMeteredOutletCount.1 = INTEGER: 32
// ENLOGIC-PDU2-MIB::pduUnitPropertiesInputPhaseCount.1 = INTEGER: 1
// ENLOGIC-PDU2-MIB::pduUnitPropertiesCircuitBreakerCount.1 = INTEGER: 2
// ENLOGIC-PDU2-MIB::pduUnitPropertiesMaxExternalSensorCount.1 = INTEGER: 10
// ENLOGIC-PDU2-MIB::pduUnitPropertiesConnExternalSensorCount.1 = INTEGER: 0
// ENLOGIC-PDU2-MIB::pduUnitPropertiesRatedVoltage.1 = STRING: 200-240.
// ENLOGIC-PDU2-MIB::pduUnitPropertiesRatedMaxCurrent.1 = Wrong Type (should be OCTET STRING): INTEGER: 32
// ENLOGIC-PDU2-MIB::pduUnitPropertiesRatedFrequency.1 = STRING: 50/60Hz.
// ENLOGIC-PDU2-MIB::pduUnitPropertiesRatedPower.1 = STRING: 7.4kVA.
// ENLOGIC-PDU2-MIB::pduUnitPropertiesOrientation.1 = INTEGER: vertical(2)
// ENLOGIC-PDU2-MIB::pduUnitPropertiesOutletLayout.1 = INTEGER: seqPhaseToNuetral(1)
// ENLOGIC-PDU2-MIB::pduUnitPropertiesDaisyChainMemberType.1 = INTEGER: standalone(1)
$units = snmpwalk_cache_oid($device, 'pduUnitPropertiesInputPhaseCount', [], $mib);             // The total number of phases on the PDU
$units = snmpwalk_cache_oid($device, 'pduUnitPropertiesCircuitBreakerCount', $units, $mib);     // The total number of circuit breaker on the PDU
$units = snmpwalk_cache_oid($device, 'pduUnitPropertiesConnExternalSensorCount', $units, $mib); // The current number of external sensors connected to the PDU
print_debug_vars($units);

$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseStatusEntry', [], $mib);

$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentLowerWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentUpperWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigCurrentEnabledThresholds', $oids, $mib);

$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageLowerWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageUpperWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduInputPhaseConfigVoltageEnabledThresholds', $oids, $mib);
print_debug_vars($oids);

foreach ($oids as $unit => $entry1) {
    foreach ($entry1 as $phase => $entry) {
        if ($entry['pduInputPhaseStatusCount'] < 1) {
            continue;
        } // Skip not exist phases

        $name  = "Unit $unit, Phase $phase Input";
        $index = $unit . '.' . $phase;

        // pduInputPhaseStatusCurrentState
        $descr    = "$name Current";
        $oid_name = 'pduInputPhaseStatusCurrentState';
        $oid_num  = '.1.3.6.1.4.1.38446.1.3.4.1.3.' . $index;
        $value    = $entry[$oid_name];
        $old_rrd  = 'pduUnitStatusState-' . $oid_name . '.' . $index;

        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'pduUnitStatusState', $descr, $value, ['entPhysicalClass' => 'other', 'rename_rrd' => $old_rrd]);

        // pduInputPhaseStatusVoltageState
        $descr    = "$name Voltage";
        $oid_name = 'pduInputPhaseStatusVoltageState';
        $oid_num  = '.1.3.6.1.4.1.38446.1.3.4.1.4.' . $index;
        $value    = $entry[$oid_name];
        $old_rrd  = 'pduUnitStatusState-' . $oid_name . '.' . $index;

        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'pduUnitStatusState', $descr, $value, ['entPhysicalClass' => 'other', 'rename_rrd' => $old_rrd]);

        // pduInputPhaseStatusCurrent

        // Limits (based on enabled thresholds)
        //  SYNTAX  BITS {
        //        lowerCritical (0),
        //        lowerWarning (1),
        //        upperWarning  (2),
        //        upperCritical (3)
        //  }
        $options      = [];
        $limits_flags = base_convert(str_replace(' ', '', $entry['pduInputPhaseConfigCurrentEnabledThresholds']), 16, 10);
        if (is_flag_set(bindec(10000000), $limits_flags)) { // 0b 1000 0000
            $options['limit_low'] = $entry['pduInputPhaseConfigCurrentLowerCriticalThreshold'] * $scale_current;
        }
        if (is_flag_set(bindec(1000000), $limits_flags)) { // 0b 0100 0000
            $options['limit_low_warn'] = $entry['pduInputPhaseConfigCurrentLowerWarningThreshold'] * $scale_current;
        }
        if (is_flag_set(bindec(100000), $limits_flags)) { // 0b 0010 0000
            $options['limit_high_warn'] = $entry['pduInputPhaseConfigCurrentUpperWarningThreshold'] * $scale_current;
        }
        if (is_flag_set(bindec(10000), $limits_flags)) { // 0b 0001 0000
            $options['limit_high'] = $entry['pduInputPhaseConfigCurrentUpperCriticalThreshold'] * $scale_current;
        }

        $descr                 = "$name Current";
        $oid_name              = 'pduInputPhaseStatusCurrent';
        $oid_num               = ".1.3.6.1.4.1.38446.1.3.4.1.5.$index";
        $value                 = $entry[$oid_name];
        $options['rename_rrd'] = 'ENLOGIC-PDU-MIB-' . $oid_name . '-' . $index;

        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale_current, $value, $options);

        // pduInputPhaseStatusVoltage

        // Limits (based on enabled thresholds)
        //  SYNTAX  BITS {
        //        lowerCritical (0),
        //        lowerWarning (1),
        //        upperWarning  (2),
        //        upperCritical (3)
        //  }
        $options      = [];
        $limits_flags = base_convert(str_replace(' ', '', $entry['pduInputPhaseConfigVoltageEnabledThresholds']), 16, 10);
        if (is_flag_set(bindec(10000000), $limits_flags)) { // 0b 1000 0000
            $options['limit_low'] = $entry['pduInputPhaseConfigVoltageLowerCriticalThreshold'] * $scale;
        }
        if (is_flag_set(bindec(1000000), $limits_flags)) { // 0b 0100 0000
            $options['limit_low_warn'] = $entry['pduInputPhaseConfigVoltageLowerWarningThreshold'] * $scale;
        }
        if (is_flag_set(bindec(100000), $limits_flags)) { // 0b 0010 0000
            $options['limit_high_warn'] = $entry['pduInputPhaseConfigVoltageUpperWarningThreshold'] * $scale;
        }
        if (is_flag_set(bindec(10000), $limits_flags)) { // 0b 0001 0000
            $options['limit_high'] = $entry['pduInputPhaseConfigVoltageUpperCriticalThreshold'] * $scale;
        }

        $descr                 = "$name Voltage";
        $oid_name              = 'pduInputPhaseStatusVoltage';
        $oid_num               = ".1.3.6.1.4.1.38446.1.3.4.1.6.$index";
        $value                 = $entry[$oid_name];
        $options['rename_rrd'] = 'ENLOGIC-PDU-MIB-' . $oid_name . '-' . $index;

        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        // pduInputPhaseStatusActivePower
        $descr    = "$name Active Power";
        $oid_name = 'pduInputPhaseStatusActivePower';
        $oid_num  = ".1.3.6.1.4.1.38446.1.3.4.1.7.$index";
        $value    = $entry[$oid_name];
        $options  = ['rename_rrd' => 'ENLOGIC-PDU-MIB-' . $oid_name . '-' . $index];

        discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        // pduInputPhaseStatusApparentPower
        $descr    = "$name Apparent Power";
        $oid_name = 'pduInputPhaseStatusApparentPower';
        $oid_num  = ".1.3.6.1.4.1.38446.1.3.4.1.8.$index";
        $value    = $entry[$oid_name];
        $options  = ['rename_rrd' => 'ENLOGIC-PDU-MIB-' . $oid_name . '-' . $index];

        discover_sensor_ng($device, 'apower', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        // pduInputPhaseStatusPowerFactor
        $descr    = "$name Power Factor";
        $oid_name = 'pduInputPhaseStatusPowerFactor';
        $oid_num  = ".1.3.6.1.4.1.38446.1.3.4.1.9.$index";
        $value    = $entry[$oid_name];
        $options  = ['rename_rrd' => 'ENLOGIC-PDU-MIB-' . $oid_name . '-' . $index];

        discover_sensor_ng($device, 'powerfactor', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
    }
}

$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerStatusEntry', [], $mib);

$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigLowerWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigUpperWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduCircuitBreakerConfigEnabledThresholds', $oids, $mib);
print_debug_vars($oids);

foreach ($oids as $unit => $entry1) {
    foreach ($entry1 as $i => $entry) {
        if ($entry['pduCircuitBreakerStatusCount'] < 1) {
            continue;
        } // Skip not exist Circuit Breaker

        $name  = "Unit $unit, Circuit Breaker $i";
        $index = $unit . '.' . $i;

        // pduCircuitBreakerStatusLoadState
        $descr    = "$name Load";
        $oid_name = 'pduCircuitBreakerStatusLoadState';
        $oid_num  = '.1.3.6.1.4.1.38446.1.4.4.1.4.' . $index;
        $value    = $entry[$oid_name];
        $old_rrd  = 'pduUnitStatusState-' . $oid_name . '.' . $index;

        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'pduUnitStatusState', $descr, $value, ['entPhysicalClass' => 'other', 'rename_rrd' => $old_rrd]);

        // pduCircuitBreakerStatusCurrent

        // Limits (based on enabled thresholds)
        //  SYNTAX  BITS {
        //        lowerCritical (0),
        //        lowerWarning (1),
        //        upperWarning  (2),
        //        upperCritical (3)
        //  }
        $options      = [];
        $limits_flags = base_convert(str_replace(' ', '', $entry['pduCircuitBreakerConfigEnabledThresholds']), 16, 10);
        if (is_flag_set(bindec(10000000), $limits_flags)) { // 0b 1000 0000
            $options['limit_low'] = $entry['pduCircuitBreakerConfigLowerCriticalThreshold'] * $scale_current;
        }
        if (is_flag_set(bindec(1000000), $limits_flags)) { // 0b 0100 0000
            $options['limit_low_warn'] = $entry['pduCircuitBreakerConfigLowerWarningThreshold'] * $scale_current;
        }
        if (is_flag_set(bindec(100000), $limits_flags)) { // 0b 0010 0000
            $options['limit_high_warn'] = $entry['pduCircuitBreakerConfigUpperWarningThreshold'] * $scale_current;
        }
        if (is_flag_set(bindec(10000), $limits_flags)) { // 0b 0001 0000
            $options['limit_high'] = $entry['pduCircuitBreakerConfigUpperCriticalThreshold'] * $scale_current;
        }

        $descr    = "$name Current";
        $oid_name = 'pduCircuitBreakerStatusCurrent';
        $oid_num  = ".1.3.6.1.4.1.38446.1.4.4.1.5.$index";
        $value    = $entry[$oid_name];

        $options['rename_rrd'] = 'ENLOGIC-PDU-MIB-' . $oid_name . '-' . $index;

        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale_current, $value, $options);
    }
}

// NOTE, next part not tested, but should be working (mike)
$sensors_count = 0;
foreach ($units as $entry) {
    $sensors_count += $entry['pduUnitPropertiesConnExternalSensorCount'];
}

if ($sensors_count == 0) {
    return;
} // Skip next sensors discovery (not exist)

$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorStatusEntry', [], $mib);

$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorNamePlateType', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorNamePlateUnits', $oids, $mib);

$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigLowerCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigLowerWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigUpperCriticalThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigUpperWarningThreshold', $oids, $mib);
$oids = snmpwalk_cache_twopart_oid($device, 'pduExternalSensorConfigEnabledThresholds', $oids, $mib);
print_debug_vars($oids);

foreach ($oids as $unit => $entry1) {
    foreach ($entry1 as $i => $entry) {
        if ($entry['pduExternalSensorStatusState'] === 'notPresent') {
            continue;
        } // Skip not exist Sensors

        $name  = "Unit $unit, Sensor " . $entry['pduExternalSensorStatusName'];
        $index = $unit . '.' . $i;

        // pduExternalSensorStatusState
        $descr    = $name;
        $oid_name = 'pduExternalSensorStatusState';
        $oid_num  = '.1.3.6.1.4.1.38446.1.6.4.1.5.' . $index;
        $value    = $entry[$oid_name];
        $old_rrd  = 'pduExternalSensorStatusState-' . $oid_name . '.' . $index;

        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'pduExternalSensorStatusState', $descr, $value, ['entPhysicalClass' => 'other', 'rename_rrd' => $old_rrd]);

        // pduExternalSensorStatusValue

        $scale = 1;
        // Limits (based on enabled thresholds)
        //  SYNTAX  BITS {
        //        lowerCritical (0),
        //        lowerWarning (1),
        //        upperWarning  (2),
        //        upperCritical (3)
        //  }
        $options      = [];
        $limits_flags = base_convert(str_replace(' ', '', $entry['pduExternalSensorConfigEnabledThresholds']), 16, 10);
        if (is_flag_set(bindec(10000000), $limits_flags)) { // 0b 1000 0000
            $options['limit_low'] = $entry['pduExternalSensorConfigLowerCriticalThreshold'] * $scale;
        }
        if (is_flag_set(bindec(1000000), $limits_flags)) { // 0b 0100 0000
            $options['limit_low_warn'] = $entry['pduExternalSensorConfigLowerWarningThreshold'] * $scale;
        }
        if (is_flag_set(bindec(100000), $limits_flags)) { // 0b 0010 0000
            $options['limit_high_warn'] = $entry['pduExternalSensorConfigUpperWarningThreshold'] * $scale;
        }
        if (is_flag_set(bindec(10000), $limits_flags)) { // 0b 0001 0000
            $options['limit_high'] = $entry['pduExternalSensorConfigUpperCriticalThreshold'] * $scale;
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
        switch ($entry['pduExternalSensorNamePlateType']) {
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
        if ($entry['pduExternalSensorNamePlateUnits'] === 'degreeF') {
            $options['sensor_unit'] = 'F';
        }

        if (isset($entry['pduExternalSensorStatusHighPrecisionValue'])) { // && $entry['pduExternalSensorStatusHighPrecisionValue'] > 0)
            $scale    = 0.1;
            $oid_name = 'pduExternalSensorStatusHighPrecisionValue';
            $oid_num  = ".1.3.6.1.4.1.38446.1.6.4.1.8.$index";
        } else {
            $oid_name = 'pduExternalSensorStatusValue';
            $oid_num  = ".1.3.6.1.4.1.38446.1.6.4.1.6.$index";
        }
        $value                 = $entry[$oid_name];
        $options['rename_rrd'] = 'ENLOGIC-PDU-MIB-' . $oid_name . '-' . $index;

        discover_sensor_ng($device, $sensor_class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
    }
}

// EOF
