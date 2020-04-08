<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2015 Observium Limited
 *
 */

echo(" F5-BIGIP-SYSTEM-MIB ");

$index = 1; $chassis_pos = 0;
$inventory[$index] = array(
    'entPhysicalName'         => $device['hardware'].' Chassis',
    'entPhysicalDescr'        => $device['hostname'],
    'entPhysicalClass'        => 'chassis',
    'entPhysicalIsFRU'        => 'true',
    'entPhysicalModelName'    => $device['hardware'],
    'entPhysicalSerialNum'    => $device['serial'],
    'entPhysicalHardwareRev'  => snmp_get($device, "sysGeneralHwName.0", "-Oqv", "F5-BIGIP-SYSTEM-MIB"),
    'entPhysicalFirmwareRev'  => $device['version'],
    'entPhysicalAssetID'      => $device['asset_tag'],
    'entPhysicalContainedIn'  => 0,
    'entPhysicalParentRelPos' => -1,
    'entPhysicalMfgName'      => 'F5'
);
discover_inventory($device, $index, $inventory[$index], $mib);

if (!isset($cache_discovery['f5-bigip-system-mib']))
{
  $cache_discovery['f5-bigip-system-mib']['chassis']['port']        = snmpwalk_cache_oid($device, 'sysInterfaceTable',          array(), 'F5-BIGIP-SYSTEM-MIB');
  $cache_discovery['f5-bigip-system-mib']['chassis']['powerSupply'] = snmpwalk_cache_oid($device, 'sysChassisPowerSupplyTable', array(), 'F5-BIGIP-SYSTEM-MIB');
  $cache_discovery['f5-bigip-system-mib']['chassis']['fan']         = snmpwalk_cache_oid($device, 'sysChassisFanTable',         array(), 'F5-BIGIP-SYSTEM-MIB');
  $cache_discovery['f5-bigip-system-mib']['chassis']['temp']        = snmpwalk_cache_oid($device, 'sysChassisTempTable',        array(), 'F5-BIGIP-SYSTEM-MIB');
  $cache_discovery['f5-bigip-system-mib']['slot']['voltage']        = snmpwalk_cache_oid($device, 'sysBladeVoltageTable',       array(), 'F5-BIGIP-SYSTEM-MIB');
  $cache_discovery['f5-bigip-system-mib']['slot']['cpu']            = snmpwalk_cache_oid($device, 'sysCpuSensorTable',          array(), 'F5-BIGIP-SYSTEM-MIB');
  $cache_discovery['f5-bigip-system-mib']['slot']['disk']           = snmpwalk_cache_oid($device, 'sysPhysicalDiskTable',       array(), 'F5-BIGIP-SYSTEM-MIB');
  $cache_discovery['f5-bigip-system-mib']['slot']['temp']           = snmpwalk_cache_oid($device, 'sysBladeTempTable',          array(), 'F5-BIGIP-SYSTEM-MIB');
}

$cache_ports = dbFetchRows('SELECT `ifName`,`ifIndex` FROM `ports` WHERE `device_id` = ?', array($device['device_id']));
foreach ($cache_ports as $row => $port)
{
  $cache_ports[$port['ifName']] = $port['ifIndex'];
}

$fru = array(
         'fan' => 'false',
         'powerSupply' => 'true',
         'temp' => 'false',
         'disk' => 'false',
         'port' => 'false',
       );

$class = array(
         'fan' => 'fan',
         'powerSupply' => 'powerSupply',
         'temp' => 'sensor',
         'disk' => 'disk',
         'port' => 'port',
       );

foreach ($cache_discovery['f5-bigip-system-mib']['chassis'] as $type => $cache)
{
  if (!count($cache)) continue;
  $index++; $chassis_pos++;
  $container = $index;
  $inventory[$index] = array(
    'entPhysicalName'         => 'Chassis '.ucfirst($type).' Container',
    'entPhysicalDescr'        => $device['hostname'].' - Chassis '.ucfirst($type).' Container',
    'entPhysicalClass'        => 'container',
    'entPhysicalIsFRU'        => $fru[$type],
    'entPhysicalContainedIn'  => 1,
    'entPhysicalParentRelPos' => $chassis_pos,
    'entPhysicalMfgName'      => 'F5'
  );
  discover_inventory($device, $index, $inventory[$index], $mib);

  $pos = 0;
  foreach ($cache as $id => $entry)
  {
    $index++; $pos++;
    $name = ucfirst($type).' '.$id;
    $serial = NULL;
    $ifindex = NULL;
    if ($type == 'port')
    {
      $ifindex = $cache_ports[$id];
    }
    $inventory[$index] = array(
      'entPhysicalName'         => $name,
      'entPhysicalDescr'        => $device['hostname'].' - '.$name,
      'entPhysicalClass'        => $class[$type],
      'entPhysicalIsFRU'        => $fru[$type],
      'entPhysicalSerialNum'    => $serial,
      'entPhysicalContainedIn'  => $container,
      'entPhysicalParentRelPos' => $pos,
      'entPhysicalMfgName'      => 'F5',
      'ifIndex'                 => $ifindex
    );
    discover_inventory($device, $index, $inventory[$index], $mib);
    unset($ifIndex);
  }
}

// Build and array of stuff by slot
foreach ($cache_discovery['f5-bigip-system-mib']['slot'] as $type => $sensors)
{
  foreach ($sensors as $tmp => $sensor)
  switch ($type)
  {
    case 'cpu':
      $slots[$sensor['sysCpuSensorSlot']]['cpu'] = $sensor;
      break;
    case 'disk':
      $slots[$sensor['sysPhysicalDiskSlotId']]['disk'] = $sensor;
      break;
    case 'temp':
      $slots[$sensor['sysBladeTempSlot']]['temp'][$sensor['sysBladeTempIndex']] = $sensor;
      break;
    case 'voltage':
      $slots[$sensor['sysBladeVoltageSlot']]['voltage'][$sensor['sysBladeVoltageIndex']] = $sensor;
      break;
  }
}

foreach ($slots as $slot => $sensors)
{
  $index++; $chassis_pos++;
  $slot_container = $index;
  $pos = 0;
  $inventory[$index] = array(
    'entPhysicalName'         => 'Blade Slot '.$slot.' Container',
    'entPhysicalDescr'        => $device['hostname'].' - Blade Slot '.$slot.' Container',
    'entPhysicalClass'        => 'container',
    'entPhysicalIsFRU'        => 'false',
    'entPhysicalContainedIn'  => 1,
    'entPhysicalParentRelPos' => $chassis_pos,
    'entPhysicalMfgName'      => 'F5'
  );
  discover_inventory($device, $index, $inventory[$index], $mib);

  foreach ($sensors as $type => $entry)
  {
    $index++;
    if ($type == 'disk')
    {
      $pos++;
      $name   = $entry['sysPhysicalDiskName'];
      $serial = $entry['sysPhysicalDiskSerialNumber'];
      $inventory[$index] = array(
        'entPhysicalName'         => $name,
        'entPhysicalDescr'        => $device['hostname'].' - '.$name,
        'entPhysicalClass'        => $class[$type],
        'entPhysicalIsFRU'        => $fru[$type],
        'entPhysicalSerialNum'    => $serial,
        'entPhysicalContainedIn'  => $slot_container,
        'entPhysicalParentRelPos' => $pos,
        'entPhysicalMfgName'      => 'F5',
      );
      discover_inventory($device, $index, $inventory[$index], $mib);
    }
    else if ($type == 'cpu')
    {
      $pos++;
      $cpu_container = $index;
      $inventory[$index] = array(
        'entPhysicalName'         => 'Blade Slot '.$slot.' CPU Container',
        'entPhysicalDescr'        => $device['hostname'].' - Chassis Slot '.$slot.' CPU Container',
        'entPhysicalClass'        => 'container',
        'entPhysicalIsFRU'        => 'false',
        'entPhysicalContainedIn'  => $slot_container,
        'entPhysicalParentRelPos' => $pos,
        'entPhysicalMfgName'      => 'F5'
      );
      discover_inventory($device, $index, $inventory[$index], $mib);
      $index++;
      $name = $entry['sysCpuSensorName'].' Temperature';
      $inventory[$index] = array(
        'entPhysicalName'         => $name,
        'entPhysicalDescr'        => $device['hostname'].' - '.$name,
        'entPhysicalClass'        => 'sensor',
        'entPhysicalIsFRU'        => 'false',
        'entPhysicalContainedIn'  => $cpu_container,
        'entPhysicalParentRelPos' => 1,
        'entPhysicalMfgName'      => 'F5',
      );
      discover_inventory($device, $index, $inventory[$index], $mib);
      $index++;
      $name = $entry['sysCpuSensorName'].' Fan';
      $inventory[$index] = array(
        'entPhysicalName'         => $name,
        'entPhysicalDescr'        => $device['hostname'].' - '.$name,
        'entPhysicalClass'        => 'fan',
        'entPhysicalIsFRU'        => 'false',
        'entPhysicalContainedIn'  => $cpu_container,
        'entPhysicalParentRelPos' => 2,
        'entPhysicalMfgName'      => 'F5',
      );
      discover_inventory($device, $index, $inventory[$index], $mib);
    }
    else if ($type == 'temp')
    {
      $pos++;
      $temp_container = $index;
      $inventory[$index] = array(
        'entPhysicalName'         => 'Blade Slot '.$slot.' Temp Container',
        'entPhysicalDescr'        => $device['hostname'].' - Chassis Slot '.$slot.' Temp Container',
        'entPhysicalClass'        => 'container',
        'entPhysicalIsFRU'        => 'false',
        'entPhysicalContainedIn'  => $slot_container,
        'entPhysicalParentRelPos' => $pos,
        'entPhysicalMfgName'      => 'F5'
      );
      discover_inventory($device, $index, $inventory[$index], $mib);

      foreach ($entry as $temp_index => $temp_sensor)
      {
        $index++;
        $inventory[$index] = array(
          'entPhysicalName'         => $temp_sensor['sysBladeTempLocation'],
          'entPhysicalDescr'        => $device['hostname'].' - '.$temp_sensor['sysBladeTempLocation'],
          'entPhysicalClass'        => 'sensor',
          'entPhysicalIsFRU'        => 'false',
          'entPhysicalContainedIn'  => $temp_container,
          'entPhysicalParentRelPos' => $temp_index,
          'entPhysicalMfgName'      => 'F5',
        );
        discover_inventory($device, $index, $inventory[$index], $mib);
      }
    }
    else if ($type == 'voltage')
    {
      $pos++;
      $voltage_container = $index;
      $inventory[$index] = array(
        'entPhysicalName'         => 'Blade Slot '.$slot.' Voltage Container',
        'entPhysicalDescr'        => $device['hostname'].' - Chassis Slot '.$slot.' Voltage Container',
        'entPhysicalClass'        => 'container',
        'entPhysicalIsFRU'        => 'false',
        'entPhysicalContainedIn'  => $slot_container,
        'entPhysicalParentRelPos' => $pos,
        'entPhysicalMfgName'      => 'F5'
      );
      discover_inventory($device, $index, $inventory[$index], $mib);

      $volt_pos = 0;
      foreach ($entry as $volt_index => $volt_sensor)
      {
        $index++; $volt_pos++;
        $inventory[$index] = array(
          'entPhysicalName'         => $volt_index,
          'entPhysicalDescr'        => $device['hostname'].' - '.$volt_index,
          'entPhysicalClass'        => 'sensor',
          'entPhysicalIsFRU'        => 'false',
          'entPhysicalContainedIn'  => $voltage_container,
          'entPhysicalParentRelPos' => $volt_pos,
          'entPhysicalMfgName'      => 'F5',
        );
        discover_inventory($device, $index, $inventory[$index], $mib);
      }
    }
  }
}

unset($cache_ports, $slots, $name, $index, $container, $pos, $slot_container, $cpu_container, $temp_container, $voltage_container);
// EOF
