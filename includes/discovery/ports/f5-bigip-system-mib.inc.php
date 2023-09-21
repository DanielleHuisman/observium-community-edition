<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// F5-BIGIP-SYSTEM-MIB
/*
$mib = 'F5-BIGIP-SYSTEM-MIB';

if (count($port_stats))
{
  // Add F5 own table so we can get 64-bit values. They are checked later on.
  //$flags    = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
  $f5_stats = snmpwalk_cache_oid($device, 'sysIfxStatName',    array(), $mib);

  if ($GLOBALS['snmp_status'])
  {
    $f5_stats = snmpwalk_cache_oid($device, 'sysIfxStatAlias', $f5_stats, $mib);

    $mib_config = &$config['mibs'][$mib]['ports']['oids']; // Attach MIB options/translations

    // Convert F5 specific table to common port table
    foreach ($port_stats as $ifIndex => $port)
    {
      if (isset($f5_stats[$port['ifDescr']]))
      {
        $f5_port = $f5_stats[$port['ifDescr']];

        foreach (array('ifName', 'ifAlias') as $oid)
        {
          $f5_oid = $mib_config[$oid]['oid'];
          if (!isset($port[$oid]) && isset($f5_port[$f5_oid]))
          {
            $port_stats[$ifIndex][$oid] = $f5_port[$f5_oid];
          }
        }
      }
    }
  }

  // Clean
  unset($f5_stats, $f5_port, $f5_oid, $ifIndex, $mib_config);
}

*/
// EOF
