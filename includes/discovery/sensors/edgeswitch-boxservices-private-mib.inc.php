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

/*
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.1.0 = Gauge32: 1
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.1.1 = Gauge32: 1
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.1.0 = Gauge32: 0
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.1.1 = Gauge32: 1
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.1.0 = INTEGER: fixed(1)
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.1.1 = INTEGER: fixed(1)
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.1.0 = INTEGER: 50
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.1.1 = INTEGER: 33
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempUnitState.1 = INTEGER: normal(1)
*/

$oids = snmpwalk_cache_multi_oid($device, 'boxServicesTempSensorsTable', array(), 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

// By first detect if device used old FAST-BOXSERVICES-PRIVATE-MIB, it use single key in boxServicesTempSensorsTable
$first_key = current(array_keys($oids));
if (count(explode('.', $first_key)) === 1)
{
  print_debug('Device must use old FASTPATH-BOXSERVICES-PRIVATE-MIB');
  return;
}

// Retrieve temperature limits
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMin.0 = INTEGER: -5
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMax.0 = INTEGER: 85

$boxServicesNormalTempRangeMin = snmp_get($device, 'boxServicesNormalTempRangeMin.0', '-Ovq', 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');
$boxServicesNormalTempRangeMax = snmp_get($device, 'boxServicesNormalTempRangeMax.0', '-Ovq', 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry)
{
  $boxServicesStackTempSensorsTable = TRUE;

  $descr = (count($oids) > 1 ? 'Unit ' . $entry['boxServicesUnitIndex'] . ' ' : '') . 'Internal Sensor ' . $entry['boxServicesTempSensorIndex'];
  $oid = ".1.3.6.1.4.1.4413.1.1.43.1.8.1.5.$index";
  $value = $entry['boxServicesTempSensorTemperature'];

  $options = array(
    'limit_low'        => $boxServicesNormalTempRangeMin,
    'limit_high'       => $boxServicesNormalTempRangeMax,
    'entPhysicalClass' => 'chassis',
  );

  if ($value != 0)
  {
    discover_sensor('temperature', $device, $oid, "boxServicesTempSensorTemperature.$index", 'edgeswitch-boxservices-private-mib', $descr, 1, $value, $options);
  }
}

// Statuses

$oids = snmpwalk_cache_oid($device, 'boxServicesTempUnitState', array(), 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry)
{
  $oid = ".1.3.6.1.4.1.4413.1.1.43.1.15.1.2.$index";
  $value = $entry['boxServicesTempUnitState'];
  $descr = (count($oids) > 1 ? 'Stack Unit ' . $entry['index'] . ' ' : '') . 'Temperature Status';

  discover_status($device, $oid, $index, 'edgeswitch-boxServicesTempSensorState', $descr, $value, array('entPhysicalClass' => 'chassis'));
}

//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.0 = INTEGER: 0
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemType.0 = INTEGER: removable(2)
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.0 = INTEGER: operational(2)
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.0 = Wrong Type (should be OCTET STRING): INTEGER: 7938
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.0 = Wrong Type (should be OCTET STRING): INTEGER: 32

$oids = snmpwalk_cache_multi_oid($device, 'boxServicesFansTable', array(), 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry)
{
  if ($entry['boxServicesFanItemState'] == 'notpresent') { continue; }

  $descr = ucfirst($entry['boxServicesFanItemType']) . ' Fan ' . $index;

  $oid_name = 'boxServicesFanSpeed';
  $oid_num  = ".1.3.6.1.4.1.4413.1.1.43.1.6.1.4.{$index}";
  $type     = $mib . '-' . $oid_name;
  $scale    = 1;
  $value    = $entry[$oid_name];

  if ($value != 0)
  {
    discover_sensor('fanspeed', $device, $oid_num, $index, $type, $descr, $scale, $value);

    $oid_name = 'boxServicesFanDutyLevel';
    $oid_num  = ".1.3.6.1.4.1.4413.1.1.43.1.6.1.5.{$index}";
    $type     = $mib . '-' . $oid_name;
    $scale    = 1;
    $value    = $entry[$oid_name];

    discover_sensor('load', $device, $oid_num, $index, $type, $descr, $scale, $value);
  }

  $oid_name = 'boxServicesFanItemState';
  $oid_num  = ".1.3.6.1.4.1.4413.1.1.43.1.6.1.3.{$index}";
  $type     = 'edgeswitch-boxServicesItemState';
  $value    = $entry[$oid_name];

  discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'fan'));
}

//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex.0 = INTEGER: 0
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemType.0 = INTEGER: fixed(1)
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemState.0 = INTEGER: operational(2)

$oids = snmpwalk_cache_multi_oid($device, 'boxServicesPowSuppliesTable', array(), 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry)
{
  if ($entry['boxServicesPowSupplyItemType'] == 0 && $entry['boxServicesPowSupplyItemState'] == 'failed') { continue; } // This sensor not really exist

  $descr = ucfirst($entry['boxServicesPowSupplyItemType'] . ' Power Supply ' ) . $index;
  $oid   = ".1.3.6.1.4.1.4413.1.1.43.1.7.1.3.$index";
  $value = $entry['boxServicesPowSupplyItemState'];

  if ($value != 'notpresent')
  {
    discover_status($device, $oid, "boxServicesPowSupplyItemState.$index", 'edgeswitch-boxServicesItemState', $descr, $value, array('entPhysicalClass' => 'powerSupply'));
  }
}

// EOF
