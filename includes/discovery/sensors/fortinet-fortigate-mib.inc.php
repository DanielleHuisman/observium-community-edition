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
  if (str_istarts($descr, 'FAN') && $value > 100)
  {
    $class = 'fanspeed';
  }
  elseif (preg_match('/\d+V(SB)?\d*$/', $descr) || preg_match('/P\d+V\d+/', $descr) ||
          str_icontains($descr, ['VCC', 'VTT', 'VBAT', 'PVSA']))
  {
    if ($value == 0) { continue; }
    $class = 'voltage';
  } else {
    // FIXME, not always?
    $class = 'temperature';
  }

  discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);
}

// EOF
