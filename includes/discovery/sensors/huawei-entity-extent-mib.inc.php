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

//HUAWEI-ENTITY-EXTENT-MIB::hwEntityBomEnDesc.67158029 = STRING: S23&33&53&CX200D,CX7E1FANA,Fan Assembly
//HUAWEI-ENTITY-EXTENT-MIB::hwEntityBomEnDesc.67174413 = STRING: S5300C,CX7M1PWD,DC Power Module
//
//HUAWEI-ENTITY-EXTENT-MIB::hwEntityBomEnDesc.67371017 = STRING: Finished Board,S9700,EH1D2L08QX2E,8-Port 40GE QSFP+ Interface Card(X2E,QSFP+),20M TCAM
//HUAWEI-ENTITY-EXTENT-MIB::hwEntityBomEnDesc.68419593 = STRING: Finished Board,S12700,ET1D2X48SX2S,48-Port 10GE SFP+ Interface Card(X2S,SFP+)

$huawei['sensors_names'] = snmpwalk_cache_oid($device, 'hwEntityBomEnDesc',   array(), 'HUAWEI-ENTITY-EXTENT-MIB');
//$huawei['sensors_names'] = snmpwalk_cache_oid($device, 'entPhysicalName',   $huawei['sensors_names'], 'ENTITY-MIB');
//$huawei['sensors_names'] = snmpwalk_cache_oid($device, 'entPhysicalAlias',  $huawei['sensors_names'], 'ENTITY-MIB');

$huawei['temp']          = snmpwalk_cache_oid($device, 'hwEntityTemperature', array(), 'HUAWEI-ENTITY-EXTENT-MIB');
$huawei['voltage']       = snmpwalk_cache_oid($device, 'hwEntityVoltage',     array(), 'HUAWEI-ENTITY-EXTENT-MIB');
$huawei['fan']           = snmpwalk_cache_oid($device, 'hwFanStatusEntry',    array(), 'HUAWEI-ENTITY-EXTENT-MIB');
print_debug_vars($huawei);

// Temperatures
foreach ($huawei['temp'] as $index => $entry)
{
  $oid_name = 'hwEntityTemperature';
  $oid_num  = ".1.3.6.1.4.1.2011.5.25.31.1.1.1.1.11.$index";

  $value = $entry[$oid_name];
  if ($value > 0 && $value <= 1000)
  {
    $descr = snmp_get_oid($device, "entPhysicalAlias.$index", 'ENTITY-MIB');
    if (empty($descr))
    {
      $descr = snmp_get_oid($device, "entPhysicalName.$index", 'ENTITY-MIB');
    }
    if (empty($descr))
    {
      $descr = end(explode(',', $huawei['sensors_names'][$index]['hwEntityBomEnDesc']));
    }

    $options = array('limit_high' => snmp_get_oid($device, "hwEntityTemperatureThreshold.$index", 'HUAWEI-ENTITY-EXTENT-MIB'));
    $options['rename_rrd'] = "huawei-$index";

    discover_sensor_ng($device,'temperature', $mib,  $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
  }
}

// Voltages
foreach ($huawei['voltage'] as $index => $entry)
{
  $oid_name = 'hwEntityVoltage';
  $oid_num  = ".1.3.6.1.4.1.2011.5.25.31.1.1.1.1.13.$index";

  $value = $entry[$oid_name];
  if ($value != 0 && $value <= 1000)
  {
    if (strlen($huawei['sensors_names'][$index]['hwEntityBomEnDesc']))
    {
      $descr = end(explode(',', $huawei['sensors_names'][$index]['hwEntityBomEnDesc']));
    } else {
      $descr = snmp_get_oid($device, "entPhysicalAlias.$index", 'ENTITY-MIB');
      if (empty($descr))
      {
        $descr = snmp_get_oid($device, "entPhysicalName.$index", 'ENTITY-MIB');
      }
    }

    $options = array('limit_high' => snmp_get_oid($device, "hwEntityVoltageHighThreshold.$index", 'HUAWEI-ENTITY-EXTENT-MIB'),
                     'limit_low'  => snmp_get_oid($device, "hwEntityVoltageLowThreshold.$index",  'HUAWEI-ENTITY-EXTENT-MIB'));

    $options['rename_rrd'] = "huawei-$index";
    discover_sensor_ng($device,'voltage', $mib,  $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
  }
}

foreach ($huawei['fan'] as $index => $entry)
{
  if ($entry['hwEntityFanPresent'] == 'absent') { continue; }

  $descr = 'Slot '.$entry['hwEntityFanSlot'].' Fan '.$entry['hwEntityFanSn'];

  $oid_name = 'hwEntityFanSpeed';
  $oid_num  = '.1.3.6.1.4.1.2011.5.25.31.1.1.10.1.5.'.$index;
  $value    = $entry[$oid_name];

  if ($entry['hwEntityFanSpeed'] > 0)
  {
    $options['rename_rrd'] = "huawei-$index";
    discover_sensor_ng($device,'load', $mib,  $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
  }

  $oid_name = 'hwEntityFanState';
  $oid_num  = '.1.3.6.1.4.1.2011.5.25.31.1.1.10.1.7.'.$index;
  $value    = $entry[$oid_name];
  discover_status($device, $oid_num, $index, 'huawei-entity-ext-mib-fan-state', $descr, $value, array('entPhysicalClass' => 'fan'));
}

// Optical sensors
//$entity_array   = snmpwalk_cache_oid($device, 'HwOpticalModuleInfoEntry', array(), 'HUAWEI-ENTITY-EXTENT-MIB');
$entity_array   = snmpwalk_cache_oid($device, 'hwEntityOpticalTemperature', array(), 'HUAWEI-ENTITY-EXTENT-MIB');
/*
if (snmp_status())
{
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalVoltage',             $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalBiasCurrent',         $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');

  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalRxPower',             $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalRxHighThreshold',     $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalRxHighWarnThreshold', $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalRxLowThreshold',      $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalRxLowWarnThreshold',  $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');

  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalTxPower',             $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalTxHighThreshold',     $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalTxHighWarnThreshold', $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalTxLowThreshold',      $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
  $entity_array = snmpwalk_cache_oid($device, 'hwEntityOpticalTxLowWarnThreshold',  $entity_array, 'HUAWEI-ENTITY-EXTENT-MIB');
}
*/
print_debug_vars($entity_array);
  $rx_limit_oids = array('limit_high'      => 'hwEntityOpticalRxHighThreshold',
                         'limit_high_warn' => 'hwEntityOpticalRxHighWarnThreshold',
                         'limit_low'       => 'hwEntityOpticalRxLowThreshold',
                         'limit_low_warn'  => 'hwEntityOpticalRxLowWarnThreshold');
  $tx_limit_oids = array('limit_high'      => 'hwEntityOpticalTxHighThreshold',
                         'limit_high_warn' => 'hwEntityOpticalTxHighWarnThreshold',
                         'limit_low'       => 'hwEntityOpticalTxLowThreshold',
                         'limit_low_warn'  => 'hwEntityOpticalTxLowWarnThreshold');
foreach ($entity_array as $index => $entry)
{
  $port    = get_port_by_ent_index($device, $index);
  $options = array('entPhysicalIndex' => $index);
  if (is_array($port))
  {
    $entry['ifDescr']            = $port['ifDescr'];
    $options['measured_class']   = 'port';
    $options['measured_entity']  = $port['port_id'];
    $options['entPhysicalIndex_measured'] = $port['ifIndex'];
  } else {
    // Skip?
    continue;
  }

  $temperatureoid = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.5.'.$index;
  $voltageoid     = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.6.'.$index;
  $biascurrentoid = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.7.'.$index;
  $rxpoweroid     = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.8.'.$index;
  $txpoweroid     = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.9.'.$index;

  //Ignore optical sensors with temperature of zero or negative
  if ($entry['hwEntityOpticalTemperature'] > 1)
  {
    $optical_oids = array('hwEntityOpticalVoltage.'             .$index,
                          'hwEntityOpticalBiasCurrent.'         .$index,
                          'hwEntityOpticalRxPower.'             .$index,
                          'hwEntityOpticalRxHighThreshold.'     .$index,
                          'hwEntityOpticalRxHighWarnThreshold.' .$index,
                          'hwEntityOpticalRxLowThreshold.'      .$index,
                          'hwEntityOpticalRxLowWarnThreshold.'  .$index,
                          'hwEntityOpticalTxPower.'             .$index,
                          'hwEntityOpticalTxHighThreshold.'     .$index,
                          'hwEntityOpticalTxHighWarnThreshold.' .$index,
                          'hwEntityOpticalTxLowThreshold.'      .$index,
                          'hwEntityOpticalTxLowWarnThreshold.'  .$index);
    $optical_entry = snmp_get_multi_oid($device, $optical_oids, array(), 'HUAWEI-ENTITY-EXTENT-MIB');
    $entry = array_merge($entry, $optical_entry[$index]);
    print_debug_vars($entry);
    $options['rename_rrd'] = "huawei-$index";
    discover_sensor_ng($device,'temperature', $mib, 'hwEntityOpticalTemperature', $temperatureoid, $index, NULL, $entry['ifDescr'] . ' Temperature',          1, $entry['hwEntityOpticalTemperature'], $options);
    discover_sensor_ng($device,'voltage',     $mib, 'hwEntityOpticalVoltage',     $voltageoid,     $index, NULL, $entry['ifDescr'] . ' Voltage',          0.001, $entry['hwEntityOpticalVoltage'],     $options);
    discover_sensor_ng($device,'current',     $mib, 'hwEntityOpticalBiasCurrent', $biascurrentoid, $index, NULL, $entry['ifDescr'] . ' Bias Current ', 0.000001, $entry['hwEntityOpticalBiasCurrent'], $options);
    // Huawei does not follow their own MIB for some devices and instead reports Rx/Tx Power as dBm converted to mW then multiplied by 1000
    $rxoptions = $options;
    $txoptions = $options;
    if ($entry['hwEntityOpticalRxPower'] >= 0)
    {
      // Derp Huawei.. value reported as W, but Limits as dBm!
      // see: https://jira.observium.org/browse/OBS-2937
      $scale = 0.000001;
      foreach ($rx_limit_oids as $limit => $limit_oid)
      {
        if (isset($entry[$limit_oid]) && $entry[$limit_oid] != -1)
        {
          //$rxoptions[$limit] = $entry[$limit_oid] * $scale;
          $rxoptions[$limit] = value_to_si($entry[$limit_oid] * 0.01, 'dBm', 'power'); // Limit in dBm, convert to W
        }
      }
      $rxoptions['rename_rrd'] = "huawei-hwEntityOpticalRxPower.$index";
      discover_sensor_ng($device, 'power', $mib, 'hwEntityOpticalRxPower', $rxpoweroid, $index, NULL, $entry['ifDescr'] . ' Rx Power', 0.000001, $entry['hwEntityOpticalRxPower'], $rxoptions);
      foreach ($tx_limit_oids as $limit => $limit_oid)
      {
        if (isset($entry[$limit_oid]) && $entry[$limit_oid] != -1)
        {
          //$txoptions[$limit] = $entry[$limit_oid] * $scale;
          $txoptions[$limit] = value_to_si($entry[$limit_oid] * 0.01, 'dBm', 'power'); // Limit in dBm, convert to W
        }
      }
      $txoptions['rename_rrd'] = "huawei-hwEntityOpticalTxPower.$index";
      discover_sensor_ng($device, 'power', $mib, 'hwEntityOpticalTxPower', $txpoweroid, $index, NULL, $entry['ifDescr'] . ' Tx Power', 0.000001, $entry['hwEntityOpticalTxPower'], $txoptions);
    } else {
      $scale = 0.01;
      foreach ($rx_limit_oids as $limit => $limit_oid)
      {
        if (isset($entry[$limit_oid]) && $entry[$limit_oid] != -1)
        {
          $rxoptions[$limit] = $entry[$limit_oid] * $scale;
        }
      }
      $rxoptions['rename_rrd'] = "huawei-hwEntityOpticalRxPower.$index";
      discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalRxPower', $rxpoweroid, $index, NULL, $entry['ifDescr'] . ' Rx Power',     0.01, $entry['hwEntityOpticalRxPower'], $rxoptions);
      foreach ($tx_limit_oids as $limit => $limit_oid)
      {
        if (isset($entry[$limit_oid]) && $entry[$limit_oid] != -1)
        {
          $txoptions[$limit] = $entry[$limit_oid] * $scale;
        }
      }
      $txoptions['rename_rrd'] = "huawei-hwEntityOpticalTxPower.$index";
      discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalTxPower', $txpoweroid, $index, NULL, $entry['ifDescr'] . ' Tx Power',     0.01, $entry['hwEntityOpticalTxPower'], $txoptions);
    }
  }

}

unset($entity_array, $huawei);

// EOF
