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

if ($device['type'] == 'network' || $device['type'] == 'firewall' || $device['type'] == 'wireless')
{
  print_cli_data_field("MIBs", 2);

  foreach (dbFetchRows("SELECT * FROM `wifi_radios` WHERE `device_id` = ?", array($device['device_id'])) as $radio)
  {
    $GLOBALS['cache']['wifi_radios'][$radio['radio_ap']][$radio['radio_number']] = $radio;
  }

  foreach (dbFetchRows("SELECT * FROM `wifi_wlans` WHERE `device_id` = ?", array($device['device_id'])) as $wlan)
  {
    $GLOBALS['cache']['wifi_wlans'][$wlan['wlan_index']] = $wlan;
  }

  foreach (dbFetchRows("SELECT * FROM `wifi_aps` WHERE `device_id` = ?", array($device['device_id'])) as $aps)
  {
    $GLOBALS['cache']['wifi_aps'][$aps['ap_index']] = $aps;
  }

  $include_dir = "includes/polling/wifi";
  include("includes/include-dir-mib.inc.php");

  /// FIXME : everything below this point is horrible shit that needs to be moved elsewhere. These aren't wireless entities, they're just graphs. They go in graphs.

  /// FIXME. Move this to MIB based includes
  if ($device['os'] == 'trapeze')
  {
    echo("Checking Trapeze Wireless clients... ");
    $wificlients1 = snmp_get($device, "trpzClSessTotalSessions.0", "-OUqnv", "TRAPEZE-NETWORKS-CLIENT-SESSION-MIB");
    echo($wificlients1 . " clients\n");
  }

  # Senao/Engenius
  if ($device['os'] == 'engenius')
  {
    echo("Checking Engenius Wireless clients... ");

    $wificlients1 = count(explode("\n",snmp_walk($device, "entCLInfoIndex", "-OUqnv", "SENAO-ENTERPRISE-INDOOR-AP-CB-MIB")));

    echo(($wificlients1 +0) . " clients\n");
  }

  if ($device['os'] == 'symbol' AND (stristr($device['hardware'],"AP")))
  {
    echo("Checking Symbol Wireless clients... ");

    $wificlients1 = snmp_get($device, ".1.3.6.1.4.1.388.11.2.4.2.100.10.1.18.1", "-Ovq", "\"\"");

    echo(($wificlients1 +0) . " clients on wireless connector, ");
  }

  // RRD Filling Code
  if (isset($wificlients1) && is_numeric($wificlients1))
  {
    rrdtool_update_ng($device, 'wificlients', array('wificlients' => $wificlients1), 'radio1');

    $graphs['wifi_clients'] = TRUE;
  }

  if (isset($wificlients2) && is_numeric($wificlients2))
  {
    rrdtool_update_ng($device, 'wificlients', array('wificlients' => $wificlients2), 'radio2');

    $graphs['wifi_clients'] = TRUE;
  }

  if (isset($wifi_ap_count) && is_numeric($wifi_ap_count))
  {
    rrdtool_update_ng($device, 'wifi_ap_count', array('value' => $wifi_ap_count));

    $graphs['wifi_ap_count'] = TRUE;
  }

  unset($wificlients2, $wificlients1, $wifi_ap_count);
}

// EOF
