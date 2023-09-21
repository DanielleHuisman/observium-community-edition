<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// This script checks all "fortigate" devices for support for fgProcessorTable, and if so, renames the fgSysCpuUsage.0 RRD to fgProcessorTable.1
// Needs to be a separate script because the rename_rrd setting on a 'table' type can only rename to identical indexes, so not 0 -> 1.

$devices = dbFetchRows("SELECT * FROM `devices` WHERE os='fortigate'");

foreach ($devices as $device)
{
  // Try to retrieve the new table OID
  $cpu = snmp_get($device, 'fgProcessorUsage.1', '-OQUvs', 'FORTINET-FORTIGATE-MIB');
  if (is_numeric($cpu))
  {
    // OID supported, so will be discovered as CPU table, excluding the old one. Rename RRD to keep data in Processor 1.
    rename_rrd($device, 'processor-fgSysCpuUsage-0.rrd', 'processor-fgProcessorTable-fgProcessorUsage.1.rrd');
    echo ('.');
   
    // Force rediscovery of processors, to actually use the new table
    force_discovery($device, array('processors'));
  } else {
    // No support for OID (not sure if this is even possible, but let's play it safe), keep the old RRD where it is.
    echo ("X");
  }
}

// EOF
