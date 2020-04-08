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

// NETAPP-MIB

if (count($port_stats) == 0)
{
  // If not has standard IF-MIB table, use NETAPP specific tables
  $mib = 'NETAPP-MIB';

  //NETAPP-MIB::netifDescr.11 = STRING: "vega-01:MGMT_PORT_ONLY e0P"
  //NETAPP-MIB::netifDescr.12 = STRING: "vega-01:MGMT_PORT_ONLY e0M"
  //NETAPP-MIB::netifDescr.15 = STRING: "vega-01:a0m"
  //NETAPP-MIB::netifDescr.16 = STRING: "vega-01:a0m-40"
  print_cli($mib.'::netifDescr ');
  $netif_stat = snmpwalk_cache_oid($device, 'netifDescr', array(), $mib);
  if (OBS_DEBUG > 1 && count($netif_stat)) { print_vars($netif_stat); }

  $flags = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
  $netport_stat = snmpwalk_cache_twopart_oid($device, 'netportLinkState',  array(), $mib, NULL, $flags);
  print_cli($mib.'::netportLinkState ');
  $netport_stat = snmpwalk_cache_twopart_oid($device, 'netportType', $netport_stat, $mib, NULL, $flags);
  print_cli($mib.'::netportType ');
  if (OBS_DEBUG > 1 && count($netport_stat)) { print_vars($netport_stat); }

  $mib_config = &$config['mibs'][$mib]['ports']['oids']; // Attach MIB options/translations
  //print_vars($mib_config);

  // Now rewrite to standard IF-MIB array
  foreach ($netif_stat as $ifIndex => $port)
  {
    list($port['netportNode'], $port['netportPort']) = explode(':', $port['netifDescr'], 2);
    $port['netportPort'] = str_ireplace('MGMT_PORT_ONLY ', '', $port['netportPort']);

    if (isset($netport_stat[$port['netportNode']][$port['netportPort']]))
    {
      // ifDescr
      $oid = 'ifDescr';
      $port[$oid] = $port[$mib_config[$oid]['oid']];
      $port_stats[$ifIndex][$oid] = $port[$oid];

      // ifName, ifAlias
      $port_stats[$ifIndex]['ifName']  = $port['netportNode'].':'.$port['netportPort'];
      $port_stats[$ifIndex]['ifAlias'] = ''; // FIXME, I not found

      $netport = &$netport_stat[$port['netportNode']][$port['netportPort']];

      // ifType, ifOperStatus
      foreach (array('ifType', 'ifOperStatus') as $oid)
      {
        $port[$oid] = $netport[$mib_config[$oid]['oid']];
        if (isset($mib_config[$oid]['rewrite'][$port[$oid]]))
        {
          // Translate to standard IF-MIB values
          $port[$oid] = $mib_config[$oid]['rewrite'][$port[$oid]];
        }
        $port_stats[$ifIndex][$oid] = $port[$oid];
      }
    }
  }
  //if (OBS_DEBUG > 1 && count($port_stats)) { print_vars($port_stats); }

  unset($netif_stat, $netport_stat, $netport, $flags, $ifIndex, $port);
}

// EOF
