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

if (count($port_stats) && $has_ifXEntry === FALSE)
{
  //$port_module = 'f5';

  // Add F5 own table so we can get 64-bit values. They are checked later on.
  //$flags    = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
  $f5_stats = snmpwalk_cache_oid($device, 'sysIfxStat', array(), $mib);
  $has_ifXEntry = $GLOBALS['snmp_status'] && isset($f5_stats[0]['sysIfxStatNumber']) && $f5_stats[0]['sysIfxStatNumber'] > 0; // Needed for additional check HC counters

  if (OBS_DEBUG > 1 && count($f5_stats)) { print_vars($f5_stats); }

  if ($has_ifXEntry)
  {
    $mib_config = &$config['mibs'][$mib]['ports']['oids']; // Attach MIB options/translations

    // Convert F5 specific table to common port table
    foreach ($port_stats as $ifIndex => $port)
    {
      if (isset($f5_stats[$port['ifDescr']]))
      {
        $f5_port = $f5_stats[$port['ifDescr']];
        foreach ($mib_config as $oid => $entry)
        {
          $f5_oid = $entry['oid'];
          if (isset($f5_port[$f5_oid]))
          {
            if (isset($entry['rewrite'][$f5_port[$f5_oid]]))
            {
              // Translate to standard IF-MIB values
              $f5_port[$f5_oid] = $entry['rewrite'][$f5_port[$f5_oid]];
            }
            $port_stats[$ifIndex][$oid] = $f5_port[$f5_oid];
          }
        }
        //$port_stats[$ifIndex] = array_merge($port_stats[$ifIndex], $f5_stats[$ifDescr]);
      }
    }
  }
  //$process_port_functions[$port_module] = $has_ifXEntry; // Additionally process port with function

  // Clean
  unset($f5_stats, $f5_port, $f5_oid, $flags, $ifIndex, $mib_config);
}
*/
// EOF
