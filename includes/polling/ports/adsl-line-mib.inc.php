<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// ADSL-LINE-MIB stats

$port_module = 'adsl';
if ($ports_modules[$port_module] && $port_stats_count)
{
  $dsl_count = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE `device_id` = ? AND `ifType` IN (?, ?)", array($device['device_id'], 'adsl', 'vdsl'));
  if ($dsl_count > 0)
  {
    echo("ADSL ");
    $adsl_oids  = array('adslLineEntry', 'adslAtucPhysEntry', 'adslAturPhysEntry', 'adslAtucChanEntry',
                        'adslAturChanEntry', 'adslAtucPerfDataEntry', 'adslAturPerfDataEntry');
    $port_stats = snmpwalk_cache_oid($device, array_shift($adsl_oids), $port_stats, "ADSL-LINE-MIB");

    $process_port_functions[$port_module] = $GLOBALS['snmp_status'];

    if ($GLOBALS['snmp_status'])
    {
      foreach ($adsl_oids as $oid)
      {
        $port_stats = snmpwalk_cache_oid($device, $oid, $port_stats, "ADSL-LINE-MIB");
      }
    }
    //print_vars($port_stats);
  }
}

// EOF
