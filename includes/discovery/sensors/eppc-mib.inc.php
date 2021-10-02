<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2015 Adam Armstrong
 *
 */

echo('Caching OIDs: ');
$ups_array = array();

$InputTableCount = snmp_get($device, 'upsESystemInputNumPhases.0', '-OQv', $mib);
echo('upsESystemInputTable ('.$InputTableCount.' entries)');
$ups_array = snmpwalk_cache_oid($device, 'upsESystemInputTable', $ups_array, $mib);

$OutputTableCount = snmp_get($device, 'upsESystemOutputNumPhase.0', '-OQv', $mib);
echo('upsESystemOutputTable ('.$OutputTableCount.' entries)');
$ups_array = snmpwalk_cache_oid($device, 'upsESystemOutputTable', $ups_array, $mib);

$BypassTableCount = snmp_get($device, 'upsESystemBypassNumPhase.0', '-OQv', $mib);
echo('upsESystemBypassTable ('.$BypassTableCount.' entries)');
$ups_array = snmpwalk_cache_oid($device, 'upsESystemBypassTable', $ups_array, $mib);

$scale = 0.1;
$nominal = snmp_get($device, 'upsESystemConfigOutputVoltage.0', '-OQv', $mib) * $scale;
$voltage_limits = array('limit_high' => ($nominal * 1.03), 'limit_high_warn' => ($nominal * 1.01), 'limit_low' => ($nominal * 0.97), 'limit_low_warn' => ($nominal * 0.99));

# Input Sensors
for ($index = 1; $index <= $InputTableCount; $index++)
{
  $data = $ups_array[$index];

  $descr  = 'Input';
  $oid    = '.1.3.6.1.4.1.935.10.1.1.2.16.1.3.'.$index; # EPPC-MIB:upsESystemInputVoltage.$index
  $value  = $data['upsESystemInputVoltage'];
  discover_sensor('voltage', $device, $oid, "upsESystemInputVoltage.$index", 'eppc-mib', $descr, 0.1, $value, $voltage_limits);

  $descr  = "Input";
  $oid    = ".1.3.6.1.4.1.935.10.1.1.2.16.1.2.".$index; # EPPC-MIB:upsESystemInputFrequency.$index
  $value  = $data['upsESystemInputFrequency'];
  $limits = array('limit_high' => 55, 'limit_high_warn' => 51, 'limit_low' => 45, 'limit_low_warn' => 49);
  discover_sensor('frequency', $device, $oid, "upsESystemInputFrequency.$index", 'eppc-mib', $descr, 0.1, $value, $limits);
}

# Output Sensors
for ($index = 1; $index <= $InputTableCount; $index++)
{
  $data = $ups_array[$index];

  $descr  = "Output";
  $oid    = ".1.3.6.1.4.1.935.10.1.1.2.18.1.3.$index"; # EPPC-MIB:upsESystemOutputVoltage.$index
  $value  = $data['upsESystemOutputVoltage'];
  discover_sensor('voltage', $device, $oid, "upsESystemOutputVoltage.$index", 'eppc-mib', $descr, 0.1, $value, $voltage_limits);

  $descr  = "Output";
  $oid    = ".1.3.6.1.4.1.935.10.1.1.2.18.1.2.$index"; # EPPC-MIB:upsESystemOutputFrequency.$index
  $value  = $data['upsESystemOutputFrequency'];
  $limits = array('limit_high' => 55, 'limit_high_warn' => 51, 'limit_low' => 45, 'limit_low_warn' => 49); // FIXME orly? 50Hz only?
  discover_sensor('frequency', $device, $oid, "upsESystemOutputFrequency.$index", 'eppc-mib', $descr, 0.1, $value, $limits);

  $descr  = "Output";
  $oid    = ".1.3.6.1.4.1.935.10.1.1.2.18.1.7.$index"; # EPPC-MIB:upsESystemOutputLoad.$index
  $value  = $data['upsESystemOutputLoad'];
  $limits = array('limit_high' => 100, 'limit_high_warn' => 75, 'limit_low' => 0);
  discover_sensor('load', $device, $oid, "upsESystemOutputLoad.$index", 'eppc-mib', $descr, 1, $value, $limits);
}

// FIXME Sensors below are a definite candidate for definition-based discovery

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

unset($limits, $ups_array);

// EOF
