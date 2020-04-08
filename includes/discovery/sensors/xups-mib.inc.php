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

$xups_array = array();
$xups_array = snmpwalk_cache_multi_oid($device, "xupsInput",  $xups_array, "XUPS-MIB");
$xups_array = snmpwalk_cache_multi_oid($device, "xupsOutput", $xups_array, "XUPS-MIB");
$xups_array = snmpwalk_cache_multi_oid($device, "xupsBypass", $xups_array, "XUPS-MIB");

$xups_base = $xups_array[0];
unset($xups_array[0]);

foreach ($xups_array as $index => $entry)
{
  // Input
  if (isset($entry['xupsInputPhase']))
  {
    $descr = "Input";
    if ($xups_base['xupsInputNumPhases'] > 1)
    {
      $descr .= " Phase $index";
    }

    ## Input voltage
    $oid   = ".1.3.6.1.4.1.534.1.3.4.1.2.$index"; # XUPS-MIB::xupsInputVoltage.$index
    $value = $entry['xupsInputVoltage'];

    if ($value != 0 &&
        !isset($valid['sensor']['voltage']['mge-ups'][100+$index]))
    {
      discover_sensor('voltage', $device, $oid, "xupsInputEntry.".$index, 'xups', $descr, 1, $value);
    }

    ## Input current
    $oid   = ".1.3.6.1.4.1.534.1.3.4.1.3.$index"; # XUPS-MIB::xupsInputCurrent.$index
    $value = $entry['xupsInputCurrent'];

    if ($value != 0 && $value < 10000 &&  // xupsInputCurrent.1 = 136137420 ? really? You're nuts.
        !isset($valid['sensor']['current']['mge-ups'][100+$index]))
    {
      discover_sensor('current', $device, $oid, "xupsInputEntry.".$index, 'xups', $descr, 1, $value);
    }

    ## Input power
    $oid   = ".1.3.6.1.4.1.534.1.3.4.1.4.$index"; # XUPS-MIB::xupsInputWatts.$index
    $value = $entry['xupsInputWatts'];
    if ($value != 0)
    {
      discover_sensor('power', $device, $oid, "xupsInputEntry.".$index, 'xups', $descr, 1, $value);
    }
  }

  // Output
  if (isset($entry['xupsOutputPhase']))
  {
    $descr = "Output";
    if ($xups_base['xupsOutputNumPhases'] > 1)
    {
      $descr .= " Phase $index";
    }

    ## Output voltage
    $oid   = ".1.3.6.1.4.1.534.1.4.4.1.2.$index"; # XUPS-MIB::xupsOutputVoltage.$index
    $value = $entry['xupsOutputVoltage'];
    if ($value != 0 &&
        !isset($valid['sensor']['voltage']['mge-ups'][$index]))
    {
      discover_sensor('voltage', $device, $oid, "xupsOutputEntry.".$index, 'xups', $descr, 1, $value);
    }

    ## Output current
    $oid   = ".1.3.6.1.4.1.534.1.4.4.1.3.$index"; # XUPS-MIB::xupsOutputCurrent.$index
    $value = $entry['xupsOutputCurrent'];
    if ($value != 0 &&
        !isset($valid['sensor']['current']['mge-ups'][$index]))
    {
      discover_sensor('current', $device, $oid, "xupsOutputEntry.".$index, 'xups', $descr, 1, $value);
    }

    ## Output power
    $oid   = ".1.3.6.1.4.1.534.1.4.4.1.4.$index"; # XUPS-MIB::xupsOutputWatts.$index
    $value = $entry['xupsOutputWatts'];
    if ($value != 0)
    {
      discover_sensor('power', $device, $oid, "xupsOutputEntry.".$index, 'xups', $descr, 1, $value);
    }
  }

  // Bypass
  if (isset($entry['xupsBypassPhase']))
  {
    $descr = "Bypass";
    if ($xups_base['xupsBypassNumPhases'] > 1)
    {
      $descr .= " Phase $index";
    }

    ## Bypass voltage
    $oid   = ".1.3.6.1.4.1.534.1.5.3.1.2.$index"; # XUPS-MIB::xupsBypassVoltage.$index
    $value = $entry['xupsBypassVoltage'];
    if ($value != 0)
    {
      discover_sensor('voltage', $device, $oid, "xupsBypassEntry.".$index, 'xups', $descr, 1, $value);
    }
  }
}

$entry = $xups_base;

## Input frequency
$oid   = ".1.3.6.1.4.1.534.1.3.1.0"; # XUPS-MIB::xupsInputFrequency.0
$scale = 0.1;
$value = $entry['xupsInputFrequency'];
if ($value != 0 &&
    !isset($valid['sensor']['frequency']['mge-ups'][101]))
{
  discover_sensor('frequency', $device, $oid, "xupsInputFrequency.0", 'xups', "Input", $scale, $value);
}

## Output Load
$oid   = ".1.3.6.1.4.1.534.1.4.1.0"; # XUPS-MIB::xupsOutputLoad.0
$descr = "Output Load";
$value = $entry['xupsOutputLoad'];
if (!isset($valid['sensor']['load']['mge-ups']['mgoutputLoadPerPhase.1']))
{
  discover_sensor('load', $device, $oid, "xupsOutputLoad.0", 'xups', $descr, 1, $value);
}

## Output Frequency
$oid   = ".1.3.6.1.4.1.534.1.4.2.0"; # XUPS-MIB::xupsOutputFrequency.0
$value = $entry['xupsOutputFrequency'];
if ($value != 0 &&
    !isset($valid['sensor']['frequency']['mge-ups'][1]))
{
  discover_sensor('frequency', $device, $oid, "xupsOutputFrequency.0", 'xups', "Output", $scale, $value);
}

## Bypass Frequency
$oid   = ".1.3.6.1.4.1.534.1.5.1.0"; # XUPS-MIB::xupsBypassFrequency.0
$value = $entry['xupsBypassFrequency'];
if ($value != 0)
{
  discover_sensor('frequency', $device, $oid, "xupsBypassFrequency.0", 'xups', "Bypass", $scale, $value);
}

// xupsInputSource
$descr    = 'Input Source';
$oid_name = 'xupsInputSource';
$oid_num  = '.1.3.6.1.4.1.534.1.3.5.0';
$type     = 'xupsInputSource';
$value    = $entry[$oid_name];

discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'other'));

// xupsOutputSource
$descr    = 'Output Source';
$oid_name = 'xupsOutputSource';
$oid_num  = '.1.3.6.1.4.1.534.1.4.5.0';
$type     = 'xupsOutputSource';
$value    = $entry[$oid_name];

discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'other'));

$xups_array = array();
$xups_array = snmpwalk_cache_multi_oid($device, "xupsBattery",     $xups_array, "XUPS-MIB");
$xups_array = snmpwalk_cache_multi_oid($device, "xupsEnvironment", $xups_array, "XUPS-MIB");

$entry = $xups_array[0];

if (isset($entry['xupsBatTimeRemaining']) &&
    !isset($valid['sensor']['runtime']['mge']['upsmgBatteryRemainingTime.0']))
{
  $oid   = ".1.3.6.1.4.1.534.1.2.1.0"; # XUPS-MIB::xupsBatTimeRemaining.0
  $scale = 1/60;
  $value = $entry['xupsBatTimeRemaining'];

  discover_sensor('runtime', $device, $oid, "xupsBatTimeRemaining.0", 'xups', "Battery Runtime Remaining", $scale, $value);
}

if (isset($entry['xupsBatCapacity']) &&
    !isset($valid['sensor']['capacity']['mge']['upsmgBatteryLevel.0']))
{
  $oid   = ".1.3.6.1.4.1.534.1.2.4.0"; # XUPS-MIB::xupsBatCapacity.0
  $value = $entry['xupsBatCapacity'];

  discover_sensor('capacity', $device, $oid, "xupsBatCapacity.0", 'xups', "Battery Capacity", 1, $value);
}

if (isset($entry['xupsBatCurrent']))
{
  $oid   = ".1.3.6.1.4.1.534.1.2.3.0"; # XUPS-MIB::xupsBatCurrent.0
  $value = $entry['xupsBatCurrent'];

  if ($value != 0 &&
      !isset($valid['sensor']['current']['mge']['upsmgBatteryCurrent.0']))
  {
    discover_sensor('current', $device, $oid, "xupsBatCurrent.0", 'xups', "Battery", 1, $value);
  }
}

if (isset($entry['xupsBatVoltage']))
{
  $oid   = ".1.3.6.1.4.1.534.1.2.2.0"; # XUPS-MIB::xupsBatVoltage.0
  $value = $entry['xupsBatVoltage'];

  if ($value != 0 &&
      !isset($valid['sensor']['voltage']['mge']['upsmgBatteryVoltage.0']))
  {
    discover_sensor('voltage', $device, $oid, "xupsBatVoltage.0", 'xups', "Battery", 1, $value);
  }
}

// xupsBatteryAbmStatus
$descr    = 'Battery Status';
$oid_name = 'xupsBatteryAbmStatus';
$oid_num  = '.1.3.6.1.4.1.534.1.2.5.0';
$type     = 'xupsBatteryAbmStatus';
$value    = $entry[$oid_name];

discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'battery'));

if (isset($entry['xupsEnvAmbientTemp']))
{
  $oid   = ".1.3.6.1.4.1.534.1.6.1.0"; # XUPS-MIB:xupsEnvAmbientTemp.0
  $value = $entry['xupsEnvAmbientTemp'];

  $limits = array('limit_low'  => $xups_array[0]['upsEnvAmbientLowerLimit'],
                  'limit_high' => $xups_array[0]['upsEnvAmbientUpperLimit']);

  if ($value != 0)
  {
    discover_sensor('temperature', $device, $oid, "xupsEnvAmbientTemp.0", 'xups', "Ambient", 1, $value, $limits);
  }
}

unset($xups_array, $xups_base);

// EOF
