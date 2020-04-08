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

// This could probably do with a rewrite, I suspect there's 1 table that can be walked for all the info below instead of 4.
// Also, all types should be equal, not brocade-dom, brocade-dom-tx and brocade-dom-rx (requires better indexes too)

$oids = snmpwalk_cache_oid($device, "snIfOpticalMonitoringTxBiasCurrent", array(), "FOUNDRY-SN-SWITCH-GROUP-MIB");

$scale = si_to_scale('milli');
foreach ($oids as $index => $entry)
{
  $value   = $entry['snIfOpticalMonitoringTxBiasCurrent'];
  if (!preg_match("|N/A|", $value))
  {
    //$descr   = snmp_get($device, "ifDescr.$index", "-Oqv", "IF-MIB") . " DOM TX Bias Current";
    $oid     = ".1.3.6.1.4.1.1991.1.1.3.3.6.1.4.$index";
    $options = array('entPhysicalIndex' => $index);
    $port    = get_port_by_index_cache($device['device_id'], $index);

    if (is_array($port))
    {
      $descr = ($port["ifDescr"] ? $port["ifDescr"] : $port["ifName"]);
      $options['measured_class']  = 'port';
      $options['measured_entity'] = $port['port_id'];
    } else {
      $descr = snmp_get($device, "ifDescr.$index", "-Oqv", "IF-MIB");
    }
    $descr  .= " DOM TX Bias Current";

    $options['rename_rrd'] = "brocade-dom-$index";
    discover_sensor_ng($device, 'current', $mib, 'snIfOpticalMonitoringTxBiasCurrent', $oid, $index, NULL, $descr, $scale, $value, $options);
  }
}

$oids = snmpwalk_cache_oid($device, "snIfOpticalMonitoringTxPower", array(), "FOUNDRY-SN-SWITCH-GROUP-MIB");

foreach ($oids as $index => $entry)
{
  $value   =  $entry['snIfOpticalMonitoringTxPower'];
  if (!preg_match("|N/A|", $value))
  {
    //$descr   = snmp_get($device, "ifDescr.$index", "-Oqv", "IF-MIB") . " DOM TX Power";
    $oid     = ".1.3.6.1.4.1.1991.1.1.3.3.6.1.2.$index";
    $options = array('entPhysicalIndex' => $index);
    $port    = get_port_by_index_cache($device['device_id'], $index);

    if (is_array($port))
    {
      $descr = ($port["ifDescr"] ? $port["ifDescr"] : $port["ifName"]);
      $options['measured_class']  = 'port';
      $options['measured_entity'] = $port['port_id'];
    } else {
      $descr = snmp_get($device, "ifDescr.$index", "-Oqv", "IF-MIB");
    }
    $descr  .= " DOM TX Power";

    $options['rename_rrd'] = "brocade-dom-tx-$index";
    discover_sensor_ng($device,'dbm', $mib, 'snIfOpticalMonitoringTxPower', $oid, $index, NULL, $descr, 1, $value, $options);
  }
}

$oids = snmpwalk_cache_oid($device, "snIfOpticalMonitoringRxPower", array(), "FOUNDRY-SN-SWITCH-GROUP-MIB");

foreach ($oids as $index => $entry)
{
  $value   = $entry['snIfOpticalMonitoringRxPower'];
  if (!preg_match("|N/A|", $value))
  {
    //$descr   = snmp_get($device, "ifDescr.$index", "-Oqv", "IF-MIB") . " DOM RX Power";
    $oid     = ".1.3.6.1.4.1.1991.1.1.3.3.6.1.3.$index";
    $options = array('entPhysicalIndex' => $index);
    $port    = get_port_by_index_cache($device['device_id'], $index);

    if (is_array($port))
    {
      $descr = ($port["ifDescr"] ? $port["ifDescr"] : $port["ifName"]);
      $options['measured_class']  = 'port';
      $options['measured_entity'] = $port['port_id'];
    } else {
      $descr = snmp_get($device, "ifDescr.$index", "-Oqv", "IF-MIB");
    }
    $descr  .= " DOM RX Power";

    $options['rename_rrd'] = "brocade-dom-rx-$index";
    discover_sensor_ng($device,'dbm', $mib, 'snIfOpticalMonitoringRxPower', $oid, $index, NULL, $descr, 1, $value, $options);
  }
}

$oids = snmpwalk_cache_oid($device, "snIfOpticalMonitoringTemperature", array(), "FOUNDRY-SN-SWITCH-GROUP-MIB");

foreach ($oids as $index => $entry)
{
  $value   = $entry['snIfOpticalMonitoringTemperature'];
  if (!preg_match("|N/A|", $value))
  {
    //$descr   = snmp_get($device, "ifDescr.$index", "-Oqv", "IF-MIB") . " DOM Temperature";
    $oid     = ".1.3.6.1.4.1.1991.1.1.3.3.6.1.1.$index";
    $options = array('entPhysicalIndex' => $index);
    $port    = get_port_by_index_cache($device['device_id'], $index);

    if (is_array($port))
    {
      $descr = ($port["ifDescr"] ? $port["ifDescr"] : $port["ifName"]);
      $options['measured_class']  = 'port';
      $options['measured_entity'] = $port['port_id'];
    } else {
      $descr = snmp_get($device, "ifDescr.$index", "-Oqv", "IF-MIB");
    }
    $descr  .= " DOM Temperature";

    $options['rename_rrd'] = "brocade-dom-$index";
    discover_sensor_ng($device, 'temperature', $mib, 'snIfOpticalMonitoringTemperature', $oid, $index, NULL, $descr, 1, $value, $options);
  }
}

/*
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringTemperature.201334784.1 = STRING: "57 C Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringTxPower.201334784.1 = STRING: "566.4 uWatts Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringRxPower.201334784.1 = STRING: "451.1 uWatts Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringTxBiasCurrent.201334784.1 = STRING: "6.184 mAmps Normal"
*/

$oids = snmpwalk_cache_twopart_oid($device, "snIfOpticalLaneMonitoringTable", array(), "FOUNDRY-SN-SWITCH-GROUP-MIB");

//print_r($oids);

foreach ($oids as $ifIndex => $lanes)
{

  $options = array();
  $port = get_port_by_index_cache($device['device_id'], $ifIndex);

  if (is_array($port))
  {
    $port_descr = ($port["ifDescr"] ? $port["ifDescr"] : $port["ifName"]);
    $options['measured_class'] = 'port';
    $options['measured_entity'] = $port['port_id'];
  } else
  {
    $port_descr = snmp_get($device, "ifDescr.$index", "-Oqv", "IF-MIB");
  }

  foreach($lanes as $lane => $entry)
  {

    $lane_descr = "$port_descr Lane $lane";
    $index = "$ifIndex.$lane";

    if ($entry['snIfOpticalLaneMonitoringTemperature'] != "NA")
    {
      $value = $entry['snIfOpticalLaneMonitoringTemperature'];
      $oid_name = 'snIfOpticalLaneMonitoringTemperature';
      $oid_num = '.1.3.6.1.4.1.1991.1.1.3.3.10.1.2.'.$index;
      discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $lane_descr, 1, $value, $options);
    }

    if ($entry['snIfOpticalLaneMonitoringTxPower'] != "NA")
    {
      $value = $entry['snIfOpticalLaneMonitoringTxPower'];
      $oid_name = 'snIfOpticalLaneMonitoringTxPower';
      $oid_num = '.1.3.6.1.4.1.1991.1.1.3.3.10.1.3.'.$index;
      $descr = "$lane_descr TX Power";

      //discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.000001, $value, $options);
      discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.000001, $value, $options);
    }

    if ($entry['snIfOpticalLaneMonitoringRxPower'] != "NA")
    {
      $value = $entry['snIfOpticalLaneMonitoringRxPower'];
      $oid_name = 'snIfOpticalLaneMonitoringRxPower';
      $oid_num = '.1.3.6.1.4.1.1991.1.1.3.3.10.1.4.'.$index;
      $descr = "$lane_descr RX Power";

      //discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.000001, $value, $options);
      discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.000001, $value, $options);
    }

    if ($entry['snIfOpticalLaneMonitoringTxBiasCurrent'] != "NA")
    {
      $value = $entry['snIfOpticalLaneMonitoringTxBiasCurrent'];
      $oid_name = 'snIfOpticalLaneMonitoringTxBiasCurrent';
      $oid_num = '.1.3.6.1.4.1.1991.1.1.3.3.10.1.5.'.$index;
      $descr = "$lane_descr TX Bias Current";

      discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
    }

  }
}


// EOF
