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

# Input Sensors

// EPPC-MIB::upsESystemConfigInputVoltage.0 = INTEGER: 2300
// EPPC-MIB::upsESystemConfigInputFrequence.0 = INTEGER: 500
// EPPC-MIB::upsESystemInputSourceNum.0 = INTEGER: 1
// EPPC-MIB::upsESystemInputLineBads.0 = Counter32: 0
// EPPC-MIB::upsESystemInputNumPhases.0 = INTEGER: 1
// EPPC-MIB::upsESystemInputFrequency.1 = INTEGER: 500
// EPPC-MIB::upsESystemInputVoltage.1 = INTEGER: 2301
// EPPC-MIB::upsESystemInputCurrent.1 = INTEGER: -1
// EPPC-MIB::upsESystemInputWatts.1 = INTEGER: -1
$input_phases = snmp_get_oid($device, 'upsESystemInputNumPhases.0', $mib);
if ($input_phases > 0) {
    echo('upsESystemInputTable (' . $input_phases . ' phases)');

    $scale         = 0.1;
    $ups_array     = snmpwalk_cache_oid($device, 'upsESystemInputTable', [], $mib);
    $input_voltage = snmp_get_oid($device, 'upsESystemConfigInputVoltage.0', $mib) * $scale;
    if ($input_voltage > 0) {
        $voltage_limits = [
          'limit_high' => sensor_limit_high('voltage', $input_voltage), 'limit_high_warn' => sensor_limit_high_warn('voltage', $input_voltage),
          'limit_low'  => sensor_limit_low('voltage', $input_voltage), 'limit_low_warn' => sensor_limit_low_warn('voltage', $input_voltage)
        ];
    } else {
        $voltage_limits = [];
    }
    $input_frequency = snmp_get_oid($device, 'upsESystemConfigInputFrequence.0', $mib) * $scale;
    if ($input_frequency > 0) {
        $frequency_limits = [
          'limit_high' => sensor_limit_high('frequency', $input_frequency), 'limit_high_warn' => sensor_limit_high_warn('frequency', $input_frequency),
          'limit_low'  => sensor_limit_low('frequency', $input_frequency), 'limit_low_warn' => sensor_limit_low_warn('frequency', $input_frequency)
        ];
    } else {
        $frequency_limits = [];
    }

    foreach ($ups_array as $phase => $entry) {
        $descr = 'Input';
        if ($input_phases > 1) {
            $descr .= ' (Phase ' . $phase . ')';
        }

        $oid_name = 'upsESystemInputVoltage';
        $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.16.1.3.' . $phase;
        $value    = $entry[$oid_name];

        $options               = $voltage_limits;
        $options['rename_rrd'] = "eppc-mib-upsESystemInputVoltage.%index%";
        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $phase, NULL, $descr, $scale, $value, $options);

        if ($entry['upsESystemInputFrequency'] > 0) {
            $oid_name = 'upsESystemInputFrequency';
            $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.16.1.2.' . $phase;
            $value    = $entry[$oid_name];

            $options               = $frequency_limits;
            $options['rename_rrd'] = "eppc-mib-upsESystemInputFrequency.%index%";
            //discover_sensor('frequency', $device, $oid, "upsESystemInputFrequency.$index", 'eppc-mib', $descr, 0.1, $value, $limits);
            discover_sensor_ng($device, 'frequency', $mib, $oid_name, $oid_num, $phase, NULL, $descr, $scale, $value, $options);
        }

        if ($entry['upsESystemInputCurrent'] > 0) {
            $oid_name = 'upsESystemInputCurrent';
            $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.16.1.4.' . $phase;
            $value    = $entry[$oid_name];

            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $phase, NULL, $descr, $scale, $value);
        }

        if ($entry['upsESystemInputWatts'] > 0) {
            $oid_name = 'upsESystemInputWatts';
            $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.16.1.5.' . $phase;
            $value    = $entry[$oid_name];

            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $phase, NULL, $descr, 1, $value);
        }
    }
}

# Output Sensors
// EPPC-MIB::upsESystemConfigOutputVoltage.0 = INTEGER: -1
// EPPC-MIB::upsESystemConfigOutputFrequency.0 = INTEGER: -1
// EPPC-MIB::upsESystemConfigOutputVA.0 = INTEGER: -1
// EPPC-MIB::upsESystemConfigOutputPower.0 = INTEGER: -1
// EPPC-MIB::upsESystemConfigOutputLoadHighSetPoint.0 = INTEGER: 90
// EPPC-MIB::upsESystemOutputNumPhase.0 = INTEGER: 1
// EPPC-MIB::upsESystemOutputFrequency.1 = INTEGER: 500
// EPPC-MIB::upsESystemOutputVoltage.1 = INTEGER: 2299
// EPPC-MIB::upsESystemOutputCurrent.1 = INTEGER: 64
// EPPC-MIB::upsESystemOutputWatts.1 = INTEGER: 1300
// EPPC-MIB::upsESystemOutputVA.1 = INTEGER: 1400
// EPPC-MIB::upsESystemOutputLoad.1 = INTEGER: 24

$output_phases = snmp_get_oid($device, 'upsESystemOutputNumPhase.0', $mib);
if ($output_phases > 0) {
    echo('upsESystemOutputTable (' . $output_phases . ' phases)');

    $scale     = 0.1;
    $ups_array = snmpwalk_cache_oid($device, 'upsESystemOutputTable', [], $mib);

    $output_voltage = snmp_get_oid($device, 'upsESystemConfigOutputVoltage.0', $mib) * $scale;
    if ($output_voltage > 0) {
        $voltage_limits = [
          'limit_high' => sensor_limit_high('voltage', $output_voltage), 'limit_high_warn' => sensor_limit_high_warn('voltage', $output_voltage),
          'limit_low'  => sensor_limit_low('voltage', $output_voltage), 'limit_low_warn' => sensor_limit_low_warn('voltage', $output_voltage)
        ];
    } else {
        // Keep input voltage limits
        //$voltage_limits = [];
    }

    $output_frequency = snmp_get_oid($device, 'upsESystemConfigOutputFrequency.0', $mib) * $scale;
    if ($output_frequency > 0) {
        $frequency_limits = [
          'limit_high' => sensor_limit_high('frequency', $output_frequency), 'limit_high_warn' => sensor_limit_high_warn('frequency', $output_frequency),
          'limit_low'  => sensor_limit_low('frequency', $output_frequency), 'limit_low_warn' => sensor_limit_low_warn('frequency', $output_frequency)
        ];
    } else {
        // Keep input frequency limits
        //$frequency_limits = [];
    }

    foreach ($ups_array as $phase => $entry) {
        $descr = 'Output';
        if ($input_phases > 1) {
            $descr .= ' (Phase ' . $phase . ')';
        }

        $oid_name = 'upsESystemOutputVoltage';
        $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.18.1.3.' . $phase;
        $value    = $entry[$oid_name];

        $options               = $voltage_limits;
        $options['rename_rrd'] = "eppc-mib-upsESystemOutputVoltage.%index%";
        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $phase, NULL, $descr, $scale, $value, $options);

        $oid_name = 'upsESystemOutputFrequency';
        $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.18.1.2.' . $phase;
        $value    = $entry[$oid_name];

        $options               = $frequency_limits;
        $options['rename_rrd'] = "eppc-mib-upsESystemOutputFrequency.%index%";
        discover_sensor_ng($device, 'frequency', $mib, $oid_name, $oid_num, $phase, NULL, $descr, $scale, $value, $options);

        $oid_name = 'upsESystemOutputLoad';
        $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.18.1.7.' . $phase;
        $value    = $entry[$oid_name];

        $options               = ['limit_high' => 90, 'limit_high_warn' => 75];
        $options['rename_rrd'] = "eppc-mib-upsESystemOutputLoad.%index%";
        discover_sensor_ng($device, 'load', $mib, $oid_name, $oid_num, $phase, NULL, $descr, 1, $value, $options);

        if ($entry['upsESystemOutputCurrent'] > 0) {
            $oid_name = 'upsESystemOutputCurrent';
            $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.18.1.4.' . $phase;
            $value    = $entry[$oid_name];

            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $phase, NULL, $descr, $scale, $value);
        }

        if ($entry['upsESystemOutputWatts'] > 0) {
            $oid_name = 'upsESystemOutputWatts';
            $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.18.1.5.' . $phase;
            $value    = $entry[$oid_name];

            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $phase, NULL, $descr, 1, $value);
        }

        if ($entry['upsESystemOutputVA'] > 0) {
            $oid_name = 'upsESystemOutputVA';
            $oid_num  = '.1.3.6.1.4.1.935.10.1.1.2.18.1.6.' . $phase;
            $value    = $entry[$oid_name];

            discover_sensor_ng($device, 'apower', $mib, $oid_name, $oid_num, $phase, NULL, $descr, 1, $value);
        }
    }
}

# bypass sensors
// EPPC-MIB::upsESystemBypassNumPhase.0 = INTEGER: -1
$bypass_phases = snmp_get_oid($device, 'upsESystemBypassNumPhase.0', $mib);
if ($bypass_phases > 0) {
    echo('upsESystemBypassTable (' . $bypass_phases . ' phases)');
    $ups_array = snmpwalk_cache_oid($device, 'upsESystemBypassTable', [], $mib);
}

/* FIXME Sensors below are a definite candidate for definition-based discovery

$descr  = 'Charge Remaining';
$oid    = '.1.3.6.1.4.1.935.10.1.1.3.4.0'; # EPPC-MIB:upsEBatteryEstimatedChargeRemaining
$value  = snmp_get($device, 'upsEBatteryEstimatedChargeRemaining.0', '-OQv', $mib);
$limits = array('limit_high' => 100, 'limit_low_warn' => 10, 'limit_low' => 0);
discover_sensor('capacity', $device, $oid, 'upsEBatteryEstimatedChargeRemaining', 'eppc-mib', $descr, 1, $value, $limits); // FIXME should be upsEBatteryEstimatedChargeRemaining.0

$descr  = 'Seconds on Battery';
$oid    = '.1.3.6.1.4.1.935.10.1.1.3.2.0'; # EPPC-MIB:upsESecondsOnBattery
$value  = snmp_get($device, 'upsESecondsOnBattery.0', '-OQv', $mib);
discover_sensor('runtime', $device, $oid, 'upsESecondsOnBattery.0', 'eppc-mib', $descr, 1, $value);

$descr  = 'Runtime Remaining (minutes)';
$oid    = '.1.3.6.1.4.1.935.10.1.1.3.3.0'; # EPPC-MIB:upsEBatteryEstimatedMinutesRemaining
$value  = snmp_get($device, 'upsEBatteryEstimatedMinutesRemaining.0', '-OQv', $mib);
$limits = array('limit_high' => 100, 'limit_high_warn' => 99, 'limit_low_warn' => 25, 'limit_low' => 0);
discover_sensor('runtime', $device, $oid, 'upsEBatteryEstimatedMinutesRemaining.0', 'eppc-mib', $descr, 1, $value, $limts);

$descr  = 'Temperature';
$oid    = '.1.3.6.1.4.1.935.10.1.1.2.2.0'; # EPPC-MIB:upsESystemTemperature
$value  = snmp_get($device, 'upsESystemTemperature.0', '-OQv', $mib);
$high   = snmp_get($device, 'upsEEnvironmentTemperatureHighSetPoint.0', '-OQv', $mib);
$low    = snmp_get($device, 'upsEEnvironmentTemperatureLowSetPoint.0', '-OQv', $mib);
$limits = array('limit_high' => $high * $scale, 'limit_high_warn' => ($high * $scale) * .75, 'limit_low' => $low * $scale);
discover_sensor('temperature', $device, $oid, 'upsESystemTemperature', 'eppc-mib', $descr, $scale, $value, $limits); // FIXME should be upsESystemTemperature.0
*/
unset($limits, $ups_array);

// EOF
