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

if (count($valid['sensor']['temperature']['DNOS-BOXSERVICES-PRIVATE-MIB']) ||
    count($valid['sensor']['power']['DNOS-BOXSERVICES-PRIVATE-MIB']))
{
  // Exit from discovery, since already added valid sensors by DNOS-BOXSERVICES-PRIVATE-MIB
  // Note, DNOS-BOXSERVICES-PRIVATE-MIB and OLD-DNOS-BOXSERVICES-PRIVATE-MIB are crossed
  echo 'Skipped by DNOS-BOXSERVICES-PRIVATE-MIB';
  return;
}

// Retrieve temperature limits
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMin.0 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMax.0 = INTEGER: 57
$boxServicesNormalTempRangeMin = snmp_get($device, 'boxServicesNormalTempRangeMin.0', '-Ovq', 'OLD-DNOS-BOXSERVICES-PRIVATE-MIB');
$boxServicesNormalTempRangeMax = snmp_get($device, 'boxServicesNormalTempRangeMax.0', '-Ovq', 'OLD-DNOS-BOXSERVICES-PRIVATE-MIB');

// Initialize check variable to false
$boxServicesStackTempSensorsTable = FALSE;

// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.1.0 = INTEGER: 1
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.2.0 = INTEGER: 2
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorIndex.1.0 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorIndex.2.0 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorType.1.0 = INTEGER: fixed(1)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorType.2.0 = INTEGER: fixed(1)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorState.1.0 = INTEGER: normal(1)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorState.2.0 = INTEGER: normal(1)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorTemperature.1.0 = INTEGER: 28
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorTemperature.2.0 = INTEGER: 27

$oids = snmpwalk_cache_oid($device, 'boxServicesStackTempSensorsTable', array(), 'OLD-DNOS-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry)
{
  if (preg_match('/[\d:]/', $entry['boxServicesStackTempSensorType']))
  {
    // Incorrect Stack table on new Dell firmwares,
    // should to use DNOS-BOXSERVICES-PRIVATE-MIB instead
    // OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorType.2.89 = Wrong Type (should be INTEGER): STRING: "239d:04:02:39"
    // OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorType.2.90 = Wrong Type (should be INTEGER): STRING: "239d:03:02:38"
    print_debug('Device must use DNOS-BOXSERVICES-PRIVATE-MIB');
    return;
  }

  $boxServicesStackTempSensorsTable = TRUE;

  $descr = (count($oids) > 1 ? 'Stack Unit ' . $entry['boxServicesUnitIndex'] . ' ' : '') . 'Internal Temperature';
  $oid = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.9.1.5.$index";
  $value = $entry['boxServicesStackTempSensorTemperature'];

  $options = array(
    'limit_low'        => $boxServicesNormalTempRangeMin,
    'limit_high'       => $boxServicesNormalTempRangeMax,
    'entPhysicalClass' => 'temperature',
  );

  discover_sensor('temperature', $device, $oid, "boxServicesStackTempSensorTemperature.$index", 'fastpath-boxservices-private-mib', $descr, 1, $value, $options);

  $oid   = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.9.1.4.$index";
  $value = $entry['boxServicesStackTempSensorState'];

  discover_status($device, $oid, "boxServicesStackTempSensorState.$index", 'fastpath-boxservices-private-temp-state', $descr, $value, array('entPhysicalClass' => 'temperature'));
}

if (!$boxServicesStackTempSensorsTable)
{
  // This table has been obsoleted by boxServicesStackTempSensorsTable - run it only if we didn't find that table.
  $oids = snmpwalk_cache_oid($device, 'boxServicesTempSensorsTable', array(), 'OLD-DNOS-BOXSERVICES-PRIVATE-MIB');

  // OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.0 = INTEGER: 0
  // OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.0 = INTEGER: fixed(1)
  // OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorState.0 = INTEGER: normal(1)
  // OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.0 = INTEGER: 26

  foreach ($oids as $index => $entry)
  {
    $descr = 'Internal Temperature';
    $oid = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.8.1.4.$index";
    $value = $entry['boxServicesTempSensorTemperature'];

    $options = array(
      'limit_low'        => $boxServicesNormalTempRangeMin,
      'limit_high'       => $boxServicesNormalTempRangeMax,
      'entPhysicalClass' => 'temperature',
    );

    discover_sensor('temperature', $device, $oid, "boxServicesTempSensorTemperature.$index", 'fastpath-boxservices-private-mib', $descr, 1, $value, $options);

    $oid   = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.8.1.3.$index";
    $value = $entry['boxServicesTempSensorState'];

    discover_status($device, $oid, "boxServicesTempSensorState.$index", 'fastpath-boxservices-private-temp-state', $descr, $value, array('entPhysicalClass' => 'temperature'));
  }
}

// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.0 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.1 = INTEGER: 1
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.2 = INTEGER: 2
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.3 = INTEGER: 3
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.4 = INTEGER: 4
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.0 = INTEGER: operational(2)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.1 = INTEGER: operational(2)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.2 = INTEGER: operational(2)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.3 = INTEGER: operational(2)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.4 = INTEGER: operational(2)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.0 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.1 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.2 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.3 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.4 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.0 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.1 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.2 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.3 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.4 = INTEGER: 0

$oids = snmpwalk_cache_oid($device, 'boxServicesFansTable', array(), 'OLD-DNOS-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry)
{
  $descr = 'Fan'; if (count($oids) > 1) { $descr .= ' ' . ($index+1); }
  $oid   = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.6.1.3.$index";
  $value = $entry['boxServicesFanItemState'];

  if ($entry['boxServicesFanItemState'] !== 'notpresent')
  {
     discover_status($device, $oid, "boxServicesFanItemState.$index", 'fastpath-boxservices-private-state', $descr, $value, array('entPhysicalClass' => 'fan'));

    if ($entry['boxServicesFanSpeed'] != 0)
    {
      // FIXME - could add a fan speed sensor here, but none of my devices have non-zero values.
      // duty level is most likely a percentage?
       discover_sensor('fanspeed', $device, $oid, "boxServicesFanSpeed.$index", 'fastpath-boxservices-private-mib', $descr, 1, $entry['boxServicesFanSpeed'], array('entPhysicalClass' => 'fan'));
    }
  }
}

// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex.0 = INTEGER: 0
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex.1 = INTEGER: 1
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemType.0 = INTEGER: fixed(1)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemType.1 = INTEGER: removable(2)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemState.0 = INTEGER: operational(2)
// OLD-DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemState.1 = INTEGER: operational(2)

$oids = snmpwalk_cache_oid($device, 'boxServicesPowSuppliesTable', array(), 'OLD-DNOS-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry)
{
  $descr = ucfirst($entry['boxServicesPowSupplyItemType'] . ' Power Supply');
  $oid   = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.7.1.3.$index";
  $value = $entry['boxServicesPowSupplyItemState'];

  if ($entry['boxServicesPowSupplyItemState'] === 'notpresent' ||
      ($entry['boxServicesPowSupplyItemType'] == 0 && $entry['boxServicesPowSupplyItemState'] === 'failed')) // This sensor not really exist
  {
    continue;
  }

  discover_status($device, $oid, "boxServicesPowSupplyItemState.$index", 'fastpath-boxservices-private-state', $descr, $value, array('entPhysicalClass' => 'power'));
}

// EOF
