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

// Power Supplies

$oids = snmpwalk_cache_oid($device, 'cpqHeFltTolPwrSupply', array(), 'CPQHLTH-MIB');

foreach ($oids as $index => $entry)
{
  if (isset($entry['cpqHeFltTolPowerSupplyBay']))
  {
    $descr      = "PSU ".$entry['cpqHeFltTolPowerSupplyBay'];
    $oid        = ".1.3.6.1.4.1.232.6.2.9.3.1.7.$index";
    $oid_name   = 'cpqHeFltTolPowerSupplyCapacityUsed';
    $value      = $entry['cpqHeFltTolPowerSupplyCapacityUsed'];
    $options     = array('limit_high' => $entry['cpqHeFltTolPowerSupplyCapacityMaximum']);

    $options['rename_rrd'] = "cpqhlth-cpqHeFltTolPwrSupply.$index";
    discover_sensor_ng($device, 'power', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
  }

  if (isset($entry['cpqHeFltTolPowerSupplyCondition']))
  {
    $descr      = $descr." Status";
    $oid        = ".1.3.6.1.4.1.232.6.2.9.3.1.4.$index";
    $value      = $entry['cpqHeFltTolPowerSupplyCondition'];

    $options['rename_rrd'] = 'cpqhlth-state-cpqHeFltTolPwrSupply.'.$index;
    discover_status_ng($device, $mib, 'cpqHeFltTolPowerSupplyCondition', $oid, $index, 'cpqhlth-state', $descr, $value, array('entPhysicalClass' => 'powersupply'));
  }
}

// Temperatures

$oids = snmpwalk_cache_oid($device, 'CpqHeTemperatureEntry', array(), 'CPQHLTH-MIB');

$descPatterns = array('/Cpu/', '/PowerSupply/');
$descReplace = array('CPU', 'PSU');
$descCount = array('CPU' => 1, 'PSU' => 1);

foreach ($oids as $index => $entry)
{
  if ($entry['cpqHeTemperatureThreshold'] > 0)
  {
    $descr   = ucfirst($entry['cpqHeTemperatureLocale']);

    if ($descr === 'System' || $descr === 'Memory') { continue; }
    if ($descr === 'Cpu' || $descr === 'PowerSupply')
    {
      $descr = preg_replace($descPatterns, $descReplace, $descr);
      $descr = $descr.' '.$descCount[$descr]++;
    }

    $oid        = ".1.3.6.1.4.1.232.6.2.6.8.1.4.$index";
    $oid_name   = 'cpqHeTemperatureCelsius';
    $value      = $entry['cpqHeTemperatureCelsius'];
    $options     = array('limit_high' =>$entry['cpqHeTemperatureThreshold']);

    $options['rename_rrd'] = "cpqhlth-CpqHeTemperatureEntry.$index";
    discover_sensor_ng($device,'temperature', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
  }
}

// Memory Modules

// CPQHLTH-MIB::cpqHeResMem2ModuleHwLocation.0 = STRING: "PROC  1 DIMM  1 "
// CPQHLTH-MIB::cpqHeResMem2ModuleStatus.0 = INTEGER: good(4)
// CPQHLTH-MIB::cpqHeResMem2ModuleStatus.1 = INTEGER: notPresent(2)
// .1.3.6.1.4.1.232.6.2.14.13.1.19.0 = INTEGER: good(4)
// CPQHLTH-MIB::cpqHeResMem2ModuleCondition.1 = INTEGER: ok(2)

$oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModuleHwLocation', array(), 'CPQHLTH-MIB');
$oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModuleStatus', $oids, 'CPQHLTH-MIB');
$oids = snmpwalk_cache_oid($device, 'cpqHeResMem2ModuleCondition', $oids, 'CPQHLTH-MIB');

foreach ($oids as $index => $entry)
{
  if (isset($entry['cpqHeResMem2ModuleStatus']) && $entry['cpqHeResMem2ModuleStatus'] != 'notPresent')
  {
    if (empty($entry['cpqHeResMem2ModuleHwLocation']))
    {
       $descr = 'DIMM '.$index.' ECC';
    }
    else
    {
       $descr = $entry['cpqHeResMem2ModuleHwLocation'];
    }

    $oid        = ".1.3.6.1.4.1.232.6.2.14.13.1.19.".$index;
    $status     = $entry['cpqHeResMem2ModuleStatus'];

    discover_status_ng($device, $mib, 'cpqHeResMem2ModuleStatus', $oid, $index, 'cpqHeResMem2ModuleStatus', $descr.' Status', $status, array('entPhysicalClass' => 'other'));

    $oid        = ".1.3.6.1.4.1.232.6.2.14.13.1.20.".$index;
    $status     = $entry['cpqHeResMem2ModuleCondition'];
    discover_status_ng($device, $mib, 'cpqHeResMem2ModuleCondition', $oid, $index, 'cpqHeResMem2ModuleCondition', $descr.' Condition', $status, array('entPhysicalClass' => 'other'));
  }
}

unset($oids);

// EOF
