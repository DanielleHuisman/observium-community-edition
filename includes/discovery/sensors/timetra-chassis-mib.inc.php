<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

$mib = 'TIMETRA-CHASSIS-MIB';

//TIMETRA-CHASSIS-MIB::tmnxChassisTotalNumber.0 = INTEGER: 1
$chassis_count = snmp_get_oid($device, "tmnxChassisTotalNumber.0", $mib);

$oids = snmp_cache_table($device, 'tmnxHwTable', NULL, 'TIMETRA-CHASSIS-MIB');

//TIMETRA-CHASSIS-MIB::tmnxHwName.1.50331649 = STRING: "chassis"
//TIMETRA-CHASSIS-MIB::tmnxHwName.1.134217729 = STRING: "Slot 1"
//TIMETRA-CHASSIS-MIB::tmnxHwTempSensor.1.50331649 = INTEGER: true(1)
//TIMETRA-CHASSIS-MIB::tmnxHwTempSensor.1.134217729 = INTEGER: true(1)
//TIMETRA-CHASSIS-MIB::tmnxHwTemperature.1.50331649 = INTEGER: 37 degrees celsius
//TIMETRA-CHASSIS-MIB::tmnxHwTemperature.1.134217729 = INTEGER: 37 degrees celsius
foreach ($oids as $index => $entry)
{
  list($chassis, $system_index) = explode('.', $index);
  $chassis_name = $chassis_count > 1 ? ", Chassis $chassis" : "";

    if ($entry['tmnxHwTempSensor'] == 'true' && $entry['tmnxHwTemperature'] != '-1')
    {
      $descr   = rewrite_entity_name($entry['tmnxHwName']).$chassis_name;
      $oid     = ".1.3.6.1.4.1.6527.3.1.2.2.1.8.1.18.$index";
      $options = array('limit_high' => $entry['tmnxHwTempThreshold']);

      discover_sensor('temperature', $device, $oid, $index, 'timetra-chassis-temp', $descr, 1, $entry['tmnxHwTemperature'], $options);
    }

}

$timetra_entity = array(
  //'tmnxChassisPowerSupplyACStatus'     => array('name' => 'Power Supply AC',
  //                                              'class' => 'power',
  //                                              'oid' => '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.2'),
  //'tmnxChassisPowerSupplyDCStatus'     => array('name' => 'Power Supply DC',
  //                                              'class' => 'power',
  //                                              'oid' => '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.3'),
  'tmnxChassisPowerSupplyTempStatus'   => array('name' => 'Power Supply Temperature',
                                                'class' => 'temperature',
                                                'oid' => '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.4'),
  'tmnxChassisPowerSupply1Status'      => array('name' => 'Power Supply 1',
                                                'class' => 'power',
                                                'oid' => '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.6'),
  'tmnxChassisPowerSupply2Status'      => array('name' => 'Power Supply 2',
                                                'class' => 'power',
                                                'oid' => '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.7'),
  //'tmnxChassisPowerSupplyInputStatus'  => array('name'  => 'Power Supply Input',
  //                                              'class' => 'power',
  //                                              'oid'   => '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.9'),
  //'tmnxChassisPowerSupplyOutputStatus' => array('name'  => 'Power Supply Output',
  //                                              'class' => 'power',
  //                                              'oid'   => '.1.3.6.1.4.1.6527.3.1.2.2.1.5.1.10'),
  'tmnxChassisFanOperStatus'           => array('name'  => 'Fans',
                                                'class' => 'fan',
                                                'oid'   => '.1.3.6.1.4.1.6527.3.1.2.2.1.4.1.2'),
);

//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyACStatus.1.1 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyACStatus.1.2 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyDCStatus.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyDCStatus.1.2 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyTempStatus.1.1 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyTempStatus.1.2 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyTempThreshold.1.1 = INTEGER: 58 degrees celsius
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyTempThreshold.1.2 = INTEGER: 58 degrees celsius
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupply1Status.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupply1Status.1.2 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupply2Status.1.1 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupply2Status.1.2 = INTEGER: deviceNotEquipped(2)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyAssignedType.1.1 = INTEGER: dc(1)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyAssignedType.1.2 = INTEGER: dc(1)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyInputStatus.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyInputStatus.1.2 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyOutputStatus.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisPowerSupplyOutputStatus.1.2 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisFanOperStatus.1.1 = INTEGER: deviceStateOk(3)
//TIMETRA-CHASSIS-MIB::tmnxChassisFanSpeed.1.1 = INTEGER: halfSpeed(2)
$cache_discovery['timetra-chassis-state'] = snmpwalk_cache_twopart_oid($device, 'tmnxChassisPowerSupplyEntry', array(), $mib);
$cache_discovery['timetra-chassis-state'] = snmpwalk_cache_twopart_oid($device, 'tmnxChassisFanEntry', $cache_discovery['timetra-chassis-state'], $mib);
print_debug_vars($cache_discovery['timetra-chassis-state']);

foreach ($cache_discovery['timetra-chassis-state'] as $chassis => $entries)
{
  $chassis_name = ($chassis_count > 1 ? ", Chassis $chassis" : "");
  foreach ($entries as $tray => $entry)
  {
    foreach ($entry as $oid_name => $value)
    {
      if (isset($timetra_entity[$oid_name]) && $value != 'deviceNotEquipped')
      {
        $index   = $chassis . "." . $tray;
        $descr   = $timetra_entity[$oid_name]['name'].", Tray $tray".$chassis_name;
        $oid     = $timetra_entity[$oid_name]['oid'].".$chassis.$tray";
        $options = [ 'entPhysicalClass' => $timetra_entity[$oid_name]['class'],
                     'rename_rrd'       => "timetra-chassis-state-tmnxChassisPowerSupply1Status.%index%"];

        discover_status_ng($device, $mib, $oid_name, $oid, $index, 'timetra-chassis-state', $descr, $value, $options);

        //status-timetra-chassis-state-tmnxChassisPowerSupply1Status.1.1.rrd
        //discover_status($device, $oid, $index, 'timetra-chassis-state', $descr, $value, $options);
      }
    }
  }
}

// EOF
