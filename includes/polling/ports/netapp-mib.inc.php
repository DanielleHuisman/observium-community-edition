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

$mib = 'NETAPP-MIB';

$port_module = 'netapp';
if ($port_stats_count)
{
  // Check if we're dealing with a retarded ass-backwards OS which feels
  // the need to dump standardized variables in its own MIB, apparently just for shits.
  // Add NetApp's own table so we can get 64-bit values. They are checked later on.
  $port_stats = snmpwalk_cache_oid($device, 'netifEntry', $port_stats, $mib);
  $has_ifXEntry = $GLOBALS['snmp_status']; // Needed for additional check HC counters

  $process_port_functions[$port_module] = $GLOBALS['snmp_status']; // Additionally process port with function
}
else if ($has_ifEntry_error_code == 1000)
{
  // if not exist ifEntry on device, collect and translate netapp specific tables
  print_cli($mib.'::NetIfEntry ');
  $netif_stat = snmpwalk_cache_oid($device, 'netifEntry', array(), $mib);
  if (count($netif_stat))
  {
    $has_ifXEntry = $GLOBALS['snmp_status']; // Needed for additional check HC counters
    if (OBS_DEBUG > 1) { print_vars($netif_stat); }

    $mib_config = &$config['mibs'][$mib]['ports']['oids']; // Attach MIB options/translations
    //print_vars($mib_config);

    /*
    $data_oids_ifEntry = array(
      // ifEntry
      'ifDescr', 'ifType', 'ifMtu', 'ifSpeed', 'ifPhysAddress', 'ifAdminStatus', 'ifOperStatus', 'ifLastChange',
    );
    $data_oids_ifXEntry = array(
      // ifXEntry
      'ifName', 'ifAlias', 'ifHighSpeed', 'ifPromiscuousMode', 'ifConnectorPresent',
    );
    */
    $data_oids_netport = array('ifType', 'ifMtu', 'ifAdminStatus', 'ifOperStatus',
                               'ifHighSpeed', 'ifDuplex', 'ifVlan');
    $flags = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
    $netport_stat = array();
    foreach ($data_oids_netport as $oid)
    {
      $netport_oid = $mib_config[$oid]['oid'];
      print_cli($mib.'::'.$netport_oid.' ');
      $netport_stat = snmpwalk_cache_twopart_oid($device, $netport_oid, $netport_stat, $mib, NULL, $flags);
    }
    // disable hex to string conversion for ifPhysAddress
    $flags = $flags | OBS_SNMP_HEX;
    $netport_oid = $mib_config['ifPhysAddress']['oid'];
    print_cli($mib.'::'.$netport_oid.' ');
    $netport_stat = snmpwalk_cache_twopart_oid($device, $netport_oid, $netport_stat, $mib, NULL, $flags);

    if (OBS_DEBUG > 1 && count($netport_stat)) { print_vars($netport_stat); }
  }

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

      // ifPhysAddress
      $oid = 'ifPhysAddress';
      $port[$oid] = strtolower($netport[$mib_config[$oid]['oid']]);
      $port[$oid] = str_replace(' ', '', $port[$oid]);
      $port_stats[$ifIndex][$oid] = $port[$oid];

      // All other data fields
      foreach ($data_oids_netport as $oid)
      {
        $port[$oid] = $netport[$mib_config[$oid]['oid']];
        if (isset($mib_config[$oid]['rewrite'][$port[$oid]]))
        {
          // Translate to standard IF-MIB values
          $port[$oid] = $mib_config[$oid]['rewrite'][$port[$oid]];
        }
        if ($oid == 'ifVlan' && $port[$oid] < 0)
        {
          $port[$oid] = '';
        }
        $port_stats[$ifIndex][$oid] = $port[$oid];
      }

      // ifEntry fields
      foreach ($stat_oids_ifEntry as $oid)
      {
        $oid = substr($oid, 2); // remove "if"
        // Use only HC counters
        $port_stats[$ifIndex]['ifHC'.$oid] = $port['if64'.$oid];
        $port_stats[$ifIndex]['if'.$oid]   = $port['if64'.$oid];
      }
    }
  }

  // Clean
  unset($netif_stat, $netport_stat, $netport, $netport_oid, $flags, $ifIndex, $port, $data_oids_netport, $oid);
}

// EOF
