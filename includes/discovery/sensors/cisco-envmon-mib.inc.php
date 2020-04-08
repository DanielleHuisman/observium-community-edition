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

// Old CISCO-ENVMON-MIB

$sensor_type       = 'cisco-envmon';
$sensor_state_type = 'cisco-envmon-state';

// Temperatures:
$oids = snmpwalk_cache_oid($device, 'ciscoEnvMonTemperatureStatusEntry', array(), 'CISCO-ENVMON-MIB');

foreach ($oids as $index => $entry)
{
  $descr = $entry['ciscoEnvMonTemperatureStatusDescr'];
  if ($descr == '') { continue; } // Skip sensors with empty description, seems like Cisco bug

  if (isset($entry['ciscoEnvMonTemperatureStatusValue']))
  {
    $oid = '.1.3.6.1.4.1.9.9.13.1.3.1.3.'.$index;
    // Exclude duplicated entries from CISCO-ENTITY-SENSOR
    //$ent_exist = dbFetchCell('SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?) AND CONCAT(`sensor_limit`) = ?;',
    $ent_exist = dbExist('sensors', '`device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?) AND CONCAT(`sensor_limit`) = ?',
                              array($device['device_id'], 'cisco-entity-sensor', 'temperature', $descr.'%', '%- '.$descr, $entry['ciscoEnvMonTemperatureThreshold']));
    if (!$ent_exist && $entry['ciscoEnvMonTemperatureStatusValue'] != 0)
    {
      $options = array();
      $options['limit_high'] = $entry['ciscoEnvMonTemperatureThreshold'];
      $options['rename_rrd'] = 'cisco-envmon-'.$index;
      discover_sensor_ng( $device,'temperature', $mib, 'ciscoEnvMonTemperatureStatusValue', $oid, $index, NULL, $descr, 1, $entry['ciscoEnvMonTemperatureStatusValue'], $options);
    }
  }
  else if (isset($entry['ciscoEnvMonTemperatureState']))
  {
    $oid = '.1.3.6.1.4.1.9.9.13.1.3.1.6.'.$index;
    // Exclude duplicated entries from CISCO-ENTITY-SENSOR
    //$ent_exist = dbFetchCell('SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?);',
    $ent_exist = dbExist('sensors', '`device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?)',
                              array($device['device_id'], 'cisco-entity-state', 'state', $descr.'%', '%- '.$descr));
    // Not numerical values, only states
    if (!$ent_exist)
    {
      $options['rename_rrd'] = $sensor_state_type.'-temp-'.$index;
      discover_status_ng($device, $mib, 'ciscoEnvMonTemperatureState', $oid, $index, $sensor_state_type, $descr, $entry['ciscoEnvMonTemperatureState'], array('entPhysicalClass' => 'chassis'));
    }
  }
}

// Voltages
$scale = si_to_scale('milli');

$oids = snmpwalk_cache_oid($device, 'ciscoEnvMonVoltageStatusEntry', array(), 'CISCO-ENVMON-MIB');

foreach ($oids as $index => $entry)
{
  $descr = str_replace(' in mV', '', $entry['ciscoEnvMonVoltageStatusDescr']);
  if ($descr == '') { continue; } // Skip sensors with empty description, seems like Cisco bug

  if (isset($entry['ciscoEnvMonVoltageStatusValue']))
  {
    $oid = '.1.3.6.1.4.1.9.9.13.1.2.1.3.'.$index;
    // Exclude duplicated entries from CISCO-ENTITY-SENSOR
    //$query = 'SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?) ';
    $where  = '`device_id` = ? AND `sensor_type` = ? AND `sensor_class` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?) ';
    $where .= ($entry['ciscoEnvMonVoltageThresholdHigh'] > $entry['ciscoEnvMonVoltageThresholdLow']) ? 'AND CONCAT(`sensor_limit`) = ? AND CONCAT(`sensor_limit_low`) = ?' : 'AND CONCAT(`sensor_limit_low`) = ? AND CONCAT(`sensor_limit`) = ?'; //swich negative numbers
    //$ent_exist = dbFetchCell($query, array($device['device_id'], 'cisco-entity-sensor', 'voltage', $descr.'%', '%- '.$descr, $entry['ciscoEnvMonVoltageThresholdHigh'] * $scale, $entry['ciscoEnvMonVoltageThresholdLow'] * $scale));
    $ent_exist = dbExist('sensors', $where, array($device['device_id'], 'cisco-entity-sensor', 'voltage', $descr.'%', '%- '.$descr, $entry['ciscoEnvMonVoltageThresholdHigh'] * $scale, $entry['ciscoEnvMonVoltageThresholdLow'] * $scale));
    if (!$ent_exist)
    {
      $options = array('limit_high' => $entry['ciscoEnvMonVoltageThresholdLow']  * $scale,
                       'limit_low'  => $entry['ciscoEnvMonVoltageThresholdHigh'] * $scale);


      $options['rename_rrd'] = 'cisco-envmon-'.$index;
      discover_sensor_ng( $device,'voltage', $mib, 'ciscoEnvMonVoltageStatusValue', $oid, $index, NULL, $descr, $scale, $entry['ciscoEnvMonVoltageStatusValue'], $options);
    }
  }
  else if (isset($entry['ciscoEnvMonVoltageState']))
  {
    $oid   = '.1.3.6.1.4.1.9.9.13.1.2.1.7.'.$index;
    //$query = 'SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_type` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?);';
    //$ent_exist = dbFetchCell($query, array($device['device_id'], 'cisco-entity-state', $descr.'%', '%- '.$descr));
    $where = '`device_id` = ? AND `status_type` = ? AND (`sensor_descr` LIKE ? OR `sensor_descr` LIKE ?)';
    $ent_exist = dbExist('status', $where, array($device['device_id'], 'cisco-entity-state', $descr.'%', '%- '.$descr));
    if (!$ent_exist)
    {
      $options['rename_rrd'] = $sensor_state_type.'-voltage-'.$index;
      discover_status_ng($device, $mib, 'ciscoEnvMonVoltageState', $oid, $index, $sensor_state_type, $descr, $entry['ciscoEnvMonVoltageState'], array('entPhysicalClass' => 'chassis'));
    }
  }
}

// Supply
$oids = snmpwalk_cache_oid($device, 'ciscoEnvMonSupplyStatusEntry', array(), 'CISCO-ENVMON-MIB');

foreach ($oids as $index => $entry)
{
  $descr = $entry['ciscoEnvMonSupplyStatusDescr'];
  if ($descr == '') { continue; } // Skip sensors with empty description, seems like Cisco bug

  if (isset($entry['ciscoEnvMonSupplyState']))
  {
    $oid = '.1.3.6.1.4.1.9.9.13.1.5.1.3.'.$index;
    // Exclude duplicated entries from CISCO-ENTITY-SENSOR
    //$ent_exist = dbFetchCell('SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_type` = ? AND (`status_descr` LIKE ? OR `status_descr` LIKE ?);',
    $ent_exist = dbExist('status', '`device_id` = ? AND `status_type` = ? AND (`status_descr` LIKE ? OR `status_descr` LIKE ?)',
                              array($device['device_id'], 'cisco-entity-state', $descr.'%', '%- '.$descr));
    if (!$ent_exist)
    {
      $options['rename_rrd'] = $sensor_state_type.'-supply-'.$index;
      discover_status_ng($device, $mib, 'ciscoEnvMonSupplyState', $oid, $index, $sensor_state_type, $descr, $entry['ciscoEnvMonSupplyState'], array('entPhysicalClass' => 'powersupply'));

      $oid_name = 'ciscoEnvMonSupplySource';
      $oid_num  = '.1.3.6.1.4.1.9.9.13.1.5.1.4.'.$index;
      $type     = 'ciscoEnvMonSupplySource';
      $value    = $entry[$oid_name];

      $options['rename_rrd'] = 'ciscoEnvMonSupplySource-ciscoEnvMonSupplySource.'.$index;
      discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr . ' Source', $value, array('entPhysicalClass' => 'powersupply'));
    }
  }
}

// Fans
echo(" Fans ");

$oids = snmpwalk_cache_oid($device, 'ciscoEnvMonFanStatusEntry', array(), 'CISCO-ENVMON-MIB');

foreach ($oids as $index => $entry)
{
  $descr = $entry['ciscoEnvMonFanStatusDescr'];
  if ($descr == '') { continue; } // Skip sensors with empty description, seems like Cisco bug

  if (isset($entry['ciscoEnvMonFanState']))
  {
    $oid = '.1.3.6.1.4.1.9.9.13.1.4.1.3.'.$index;
    // Exclude duplicated entries from CISCO-ENTITY-SENSOR
    //$ent_exist = dbFetchCell('SELECT COUNT(*) FROM `status` WHERE `device_id` = ? AND `status_type` = ? AND (`status_descr` LIKE ? OR `status_descr` LIKE ?);',
    $ent_exist = dbExist('status', '`device_id` = ? AND `status_type` = ? AND (`status_descr` LIKE ? OR `status_descr` LIKE ?)',
                              array($device['device_id'], 'cisco-entity-state', $descr.'%', '%- '.$descr));

    if (!$ent_exist)
    {
      $options['rename_rrd'] = $sensor_state_type.'-fan-'.$index;
      discover_status_ng($device, $mib, 'ciscoEnvMonFanState', $oid, $index, $sensor_state_type, $descr, $entry['ciscoEnvMonFanState'], array('entPhysicalClass' => 'fan'));
    }
  }
}

// EOF
