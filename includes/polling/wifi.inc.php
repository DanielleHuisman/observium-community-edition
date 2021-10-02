<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
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

  /// FIXME : everything below this point is horrible shit that needs to be moved elsewhere.
  /// These aren't wireless entities, they're just graphs. They go in graphs.

  // Convert old variables to array
  $poll_wifi = [];
  if (isset($wificlients1) && is_numeric($wificlients1))
  {
    $poll_wifi['wifi_clients1'] = $wificlients1;
  }
  if (isset($wificlients2) && is_numeric($wificlients2))
  {
    $poll_wifi['wifi_clients2'] = $wificlients2;
  }
  if (isset($wifi_ap_count) && is_numeric($wifi_ap_count))
  {
    $poll_wifi['wifi_ap_count'] = $wifi_ap_count;
  }

  // Find MIB-specific SNMP data via OID fetch: wifi_clients (or wifi_clients1, wifi_clients2), wifi_ap_count
  $wifi_metatypes = [ 'wifi_clients', 'wifi_clients1', 'wifi_clients2', 'wifi_ap_count' ];
  foreach (poll_device_mib_metatypes($device, $wifi_metatypes, $poll_wifi) as $metatype => $value)
  {
    if (!is_numeric($value)) { continue; } // Skip not numeric entries

    // RRD Filling Code
    switch ($metatype)
    {
      case 'wifi_clients':
      case 'wifi_clients1':
        rrdtool_update_ng($device, 'wificlients', [ 'wificlients' => $value ], 'radio1');
        $graphs['wifi_clients'] = TRUE;
        break;

      case 'wifi_clients2':
        rrdtool_update_ng($device, 'wificlients', [ 'wificlients' => $value ], 'radio2');
        $graphs['wifi_clients'] = TRUE;
        break;

      case 'wifi_ap_count':
        rrdtool_update_ng($device, 'wifi_ap_count', [ 'value' => $value ]);
        $graphs['wifi_ap_count'] = TRUE;
        break;
    }
  }

  unset($wificlients2, $wificlients1, $wifi_ap_count);
}

// EOF
