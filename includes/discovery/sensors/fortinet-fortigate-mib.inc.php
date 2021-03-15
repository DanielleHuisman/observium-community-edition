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

// Currently not possible convert to definitions because type detection is hard, based on descriptions

$oids = snmp_walk_multipart_oid($device, "fgHwSensorTable", array(), "FORTINET-FORTIGATE-MIB");
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  $descr    = $entry['fgHwSensorEntName'];

  $oid_name = 'fgHwSensorEntValue';
  $oid_num  = '.1.3.6.1.4.1.12356.101.4.3.2.1.3.'.$index;
  $scale    = 1;
  $value    = $entry[$oid_name];

  // Detect class based on descr anv value (this is derp, table not have other data for detect class
  if (str_iexists($descr, 'Fan') && ($value > 100 || $value == 0))
  {
    if ($value == 0) { continue; }
    $class = 'fanspeed';
  }
  elseif (preg_match('/\d+V(SB|DD)?\d*$/', $descr) || preg_match('/P\d+V\d+/', $descr) ||
          str_iexists($descr, [ 'VCC', 'VTT', 'VDD', 'VDQ', 'VBAT', 'VSA', 'Vcore', 'VIN', 'VOUT', 'Vbus', 'Vsht' ]))
  {
    if ($value == 0) { continue; }
    $class = 'voltage';
  }
  elseif (str_iexists($descr, 'Status'))
  {
    // FORTINET-FORTIGATE-MIB::fgHwSensorEntName.45 = STRING: PS1 Status
    // FORTINET-FORTIGATE-MIB::fgHwSensorEntName.50 = STRING: PS2 Status
    // FORTINET-FORTIGATE-MIB::fgHwSensorEntValue.45 = STRING: 0
    // FORTINET-FORTIGATE-MIB::fgHwSensorEntValue.50 = STRING: 9
    // FORTINET-FORTIGATE-MIB::fgHwSensorEntAlarmStatus.45 = INTEGER: false(0)
    // FORTINET-FORTIGATE-MIB::fgHwSensorEntAlarmStatus.50 = INTEGER: true(1)
    $descr    = str_ireplace('Status', 'Alarm Status', $descr);
    $oid_name = 'fgHwSensorEntAlarmStatus';
    $oid_num  = '.1.3.6.1.4.1.12356.101.4.3.2.1.4.'.$index;
    $type     = 'fgHwSensorEntAlarmStatus';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, array('entPhysicalClass' => 'powersupply'));
    continue;
  } else {
    // FIXME, not always?
    $class = 'temperature';
  }

  discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);
}

// EOF
