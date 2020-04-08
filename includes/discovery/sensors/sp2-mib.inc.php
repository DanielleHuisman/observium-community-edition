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

// FIXME. Here only more hard for convert to definition-based sensors
// Trouble in global Current scale and limits (only)

//SP2-MIB::powerSystemCurrentDecimalSetting.0 = INTEGER: ampere(0)
//SP2-MIB::rectifiersCurrentMajorAlarmLevel.0 = INTEGER: 5000
//SP2-MIB::rectifiersCurrentMinorAlarmLevel.0 = INTEGER: 4000
switch (snmp_get_oid($device, 'powerSystemCurrentDecimalSetting.0', $mib, NULL, OBS_SNMP_ALL_ENUM))
{
  case '1':
    $scale_current = 0.1;
    break;
  case '0':
  default:
    $scale_current = 1;
    break;
}
$oids = snmp_get_multi_oid($device, 'rectifiersCurrentMajorAlarmLevel.0 rectifiersCurrentMinorAlarmLevel.0', array(), $mib);
$limit_high      = $oids[0]['rectifiersCurrentMajorAlarmLevel'] * $scale_current;
$limit_high_warn = $oids[0]['rectifiersCurrentMinorAlarmLevel'] * $scale_current;

//SP2-MIB::rectifierStatus.1 = INTEGER: normal(1)
//SP2-MIB::rectifierStatus.2 = INTEGER: normal(1)
//SP2-MIB::rectifierOutputCurrentValue.1 = INTEGER: 18
//SP2-MIB::rectifierOutputCurrentValue.2 = INTEGER: 20
//SP2-MIB::rectifierInputVoltageValue.1 = INTEGER: 226
//SP2-MIB::rectifierInputVoltageValue.2 = INTEGER: 227
//SP2-MIB::rectifierType.1 = STRING: FLATPACK2 48/2000 HE
//SP2-MIB::rectifierType.2 = STRING: FLATPACK2 48/2000 HE
$oids = snmpwalk_cache_oid($device, 'rectifierEntry', array(), $mib);

foreach ($oids as $index => $entry)
{
  $descr    = "Rectifier $index Output Current (" . $entry['rectifierType'] . ")";
  $oid_name = 'rectifierOutputCurrentValue';
  $oid_num  = ".1.3.6.1.4.1.12148.10.5.6.1.3.$index";
  $type     = $mib . '-' . $oid_name;
  $value    = $entry[$oid_name];
  $scale    = $scale_current;

  // Options
  $options = array('limit_high'      => $limit_high,
                   'limit_high_warn' => $limit_high_warn);

  discover_sensor('current', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

  $descr    = "Rectifier $index Input Voltage (" . $entry['rectifierType'] . ")";
  $oid_name = 'rectifierInputVoltageValue';
  $oid_num  = ".1.3.6.1.4.1.12148.10.5.6.1.4.$index";
  $type     = $mib . '-' . $oid_name;
  $value    = $entry[$oid_name];
  $scale    = 1;

  discover_sensor('voltage', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

  $descr    = "Rectifier $index Status (" . $entry['rectifierType'] . ")";
  $oid_name = 'rectifierStatus';
  $oid_num  = ".1.3.6.1.4.1.12148.10.5.6.1.2.{$index}";
  $type     = 'powerSystemStatus';
  $value    = $entry[$oid_name];

  discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, array('entPhysicalClass' => 'rectifier'));
}

// EOF
