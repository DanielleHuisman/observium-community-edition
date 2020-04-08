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

$oids  = snmpwalk_cache_multi_oid($device, 'nbsDevPSTable',  array(), 'DEV-CFG-MIB');
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  if ($entry['nbsDevPSAdminStatus'] == 'notActive') // && $entry['nbsDevPSRedundantMode'] == 'none')
  {
    // skip non redundant and non active
    continue;
  }
  $descr    = strlen($entry['nbsDevPSDescription']) ? $entry['nbsDevPSDescription'] : 'Power Supply ' . $index;
  $oid_name = 'nbsDevPSOperStatus';
  $oid_num  = '.1.3.6.1.4.1.629.1.50.11.1.8.2.1.5.'.$index;
  $type     = 'nbsDevOperStatus';
  $value    = $entry[$oid_name];

  discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, array('entPhysicalClass' => 'powerSupply'));
}

$oids  = snmpwalk_cache_multi_oid($device, 'nbsDevFANTable',  array(), 'DEV-CFG-MIB');
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  if ($entry['nbsDevFANAdminStatus'] == 'notActive') // && $entry['nbsDevFANType'] == 'none')
  {
    // skip non redundant and non active
    continue;
  }
  $descr    = strlen($entry['nbsDevFANDescription']) ? $entry['nbsDevFANDescription'] : 'Fan ' . $index;
  $oid_name = 'nbsDevFANOperStatus';
  $oid_num  = '.1.3.6.1.4.1.629.1.50.11.1.11.2.1.5.'.$index;
  $type     = 'nbsDevOperStatus';
  $value    = $entry[$oid_name];

  discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, array('entPhysicalClass' => 'fan'));
}

// Yah, wee have too old MRV mibs, that why here used "known" numeric oids
// .1.3.6.1.4.1.629.1.50.11.1.13.1.0 - nbsDevPhParamCpuTempC.0
// .1.3.6.1.4.1.629.1.50.11.1.13.2.0 - nbsDevPhParamDevAmbientTempC.0
// .1.3.6.1.4.1.629.1.50.11.1.13.3.0 - nbsDevPhParamPackProcTempC.0

$oids = snmp_get_multi_oid($device, array('.1.3.6.1.4.1.629.1.50.11.1.13.1.0',
                                           '.1.3.6.1.4.1.629.1.50.11.1.13.2.0',
                                           '.1.3.6.1.4.1.629.1.50.11.1.13.3.0'), array(), 'DEV-CFG-MIB', NULL, OBS_SNMP_ALL_NUMERIC);
print_debug_vars($oids);

  // nbsDevPhParamCpuTempC
  $index    = 0;
  $descr    = 'CPU Temperature';
  $oid_name = 'nbsDevPhParamCpuTempC';
  $oid_num  = ".1.3.6.1.4.1.629.1.50.11.1.13.1.{$index}";
  $type     = $mib . '-' . $oid_name;
  $scale    = 1;
  $value    = $oids[$oid_num];
  if ($value != 0)
  {
    discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);
  }

/* All this skipped anyway, since data too same
  // DEV-CFG-MIB::nbDevGen.13.1.0 = Gauge32: 40
  // DEV-CFG-MIB::nbDevGen.13.2.0 = Gauge32: 40
  // DEV-CFG-MIB::nbDevGen.13.3.0 = Gauge32: 40

  // nbsDevPhParamDevAmbientTempC
  $index    = 0;
  $descr    = 'Device Temperature';
  $oid_name = 'nbsDevPhParamDevAmbientTempC';
  $oid_num  = ".1.3.6.1.4.1.629.1.50.11.1.13.2.{$index}";
  $type     = $mib . '-' . $oid_name;
  $scale    = 1;
  $value    = $oids[$oid_num];
  if ($value != 0)
  {
    discover_sensor('temperature', $device, $oid_num, $index, $type, $descr, $scale, $value);
  }

  // nbsDevPhParamPackProcTempC
  $index    = 0;
  $descr    = 'CPU Temperature';
  $oid_name = 'nbsDevPhParamPackProcTempC';
  $oid_num  = ".1.3.6.1.4.1.629.1.50.11.1.13.3.{$index}";
  $type     = $mib . '-' . $oid_name;
  $scale    = 1;
  $value    = $oids[$oid_num];
  if ($value != 0)
  {
    discover_sensor('temperature', $device, $oid_num, $index, $type, $descr, $scale, $value);
  }
*/

// EOF
