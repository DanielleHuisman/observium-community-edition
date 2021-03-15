<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// If we've been given a hostname, try to retrieve the device_id

if (isset($vars['device']) && !is_numeric($vars['device']))
{
  $vars['hostname'] = $vars['device'];
  unset($vars['device']);
}

if (empty($vars['device']) && !empty($vars['hostname']))
{
  $vars['device'] = get_device_id_by_hostname($vars['hostname']);

  // If device lookup fails, generate an error.
  if (empty($vars['device']))
  {
    print_error('<h4>Invalid Hostname</h4>
                   A device matching the given hostname was not found. Please retype the hostname and try again.');
    return;
  }
}

// If there is no device specified in the URL, generate an error.
if (empty($vars['device']))
{
  print_error('<h4>No device specified</h4>
                   A valid device was not specified in the URL. Please retype and try again.');
  return;
}

// Allow people to see this page if they have permission to see one of the ports, but don't show them tabs.
$permit_tabs = array();
if ($vars['tab'] == "port" && is_numeric($vars['device']) && (isset($vars['port']) || isset($vars['ifdescr'])))
{
  // If we've been given a 'ifdescr' variable, try to work out the port_id from this
  if (!is_numeric($vars['port']) && !empty($vars['ifdescr']))
  {
    $ifdescr = base64_decode($vars['ifdescr']);
    if (!$ifdescr) { $ifdescr = $vars['ifdescr']; }
    $vars['port'] = get_port_id_by_ifDescr($vars['device'], $ifdescr);
  }

  if (port_permitted($vars['port']) && $vars['device'] == get_device_id_by_port_id($vars['port']))
  {
    $permit_tabs['ports'] = TRUE;
  }
}

if ($vars['tab'] == "health" && is_numeric($vars['id']) && (isset($vars['id'])))
{
  if (is_entity_permitted($vars['id'], 'sensor'))
  // && $vars['device'] == get_device_id_by_port_id($vars['port']))
  {
    $permit_tabs['health'] = TRUE;
  }
}

// print_vars($permit_tabs);

// If there is no valid device specified in the URL, generate an error.
if (!isset($cache['devices']['id'][$vars['device']]) && !count($permit_tabs))
{
  print_error('<h4>No valid device specified</h4>
                  A valid device was not specified in the URL. Please retype and try again.');
  return;
}

// Only show if the user has access to the whole device or a single port.
if (isset($cache['devices']['id'][$vars['device']]) || count($permit_tabs))
{
  $selected['iface'] = "active";

  $tab = str_replace(".", "", $vars['tab']);

  if (!$tab)
  {
    $tab = "overview";
  }

  $select[$tab] = "active";

  // Populate device array from pre-populated cache
  $device = device_by_id_cache($vars['device']);

  // Populate the attributes array for this device
  $attribs = get_dev_attribs($device['device_id']);

  // Populate the entityPhysical state array for this device
  $entity_state = get_device_entphysical_state($device['device_id']);

  // Populate the device state array from the serialized entry
  $device_state = unserialize($device['device_state']);

  // Add the device hostname to the page title array
  register_html_title(escape_html($device['hostname']));

  // If the device's OS type has a group, set the device's os_group
  if ($config['os'][$device['os']]['group']) { $device['os_group'] = $config['os'][$device['os']]['group']; }

  //// DEV

  // Start to cache panel
  // NOTE. Also this panel content can be moved to html/includes/panels/device.inc.php (instead ob_cache() here)
    ob_start();

    print_device_header($device, array('no_graphs' => TRUE));

    include("pages/device/overview/information_extended.inc.php");

    echo generate_box_open();

    // Only show graphs for device_permitted(), don't show device graphs to users who can only see a single entity.

    if (isset($config['os'][$device['os']]['graphs']))
    {
      $graphs = $config['os'][$device['os']]['graphs'];
    }
    else if (isset($device['os_group']) && isset($config['os'][$device['os_group']]['graphs']))
    {
      $graphs = $config['os'][$device['os_group']]['graphs'];
    } else {
      // Default group
      $graphs = $config['os_group']['default']['graphs'];
    }

    $graph_array = array();
    $graph_array['height'] = "100";
    $graph_array['width']  = "213";
    $graph_array['to']     = $config['time']['now'];
    $graph_array['device'] = $device['device_id'];
    $graph_array['type']   = "device_bits";
    $graph_array['from']   = $config['time']['day'];
    $graph_array['legend'] = "no";
    $graph_array['bg']     = "FFFFFF00";

    // Preprocess device graphs array
    foreach ($device['graphs'] as $graph)
    {
      $graphs_enabled[] = $graph['graph'];
    }

    echo '<div class="row">';

    foreach ($graphs as $entry)
    {
      if ($entry && in_array(str_replace('device_', '', $entry), $graphs_enabled) !== FALSE)
      {
        $graph_array['type'] = $entry;

        preg_match('/^(?P<type>[a-z0-9A-Z-]+)_(?P<subtype>[a-z0-9A-Z-_]+)/', $entry, $graphtype);

        if (isset($graphtype['type']) && isset($graphtype['subtype']))
        {
          $type = $graphtype['type'];
          $subtype = $graphtype['subtype'];

          $text = $config['graph_types'][$type][$subtype]['descr'];
        } else {
          $text = nicecase($subtype); // Fallback to the type itself as a string, should not happen!
        }

        echo('<div class="col-xl-6">');
        echo("<div style='padding: 0px; text-align: center;'><h4>$text</h4></div>");
        print_graph_popup($graph_array);
        echo("</div>");
      }
    }
    echo '</div>';

    echo generate_box_close();

    $panel_html = ob_get_contents();
    ob_end_clean();

    register_html_panel($panel_html);

    unset($graph_array, $panel_html);

  // End panel

  // Print the device header

  echo '<div class="hidden-xl">';
  print_device_header($device);
  echo '</div>';

  // Show tabs if the user has access to this device

  if (device_permitted($device['device_id']))
  {
    if ($config['show_overview_tab'])
    {
      $navbar['options']['overview'] = array('text' => 'Overview', 'icon' => $config['icon']['overview']);
    }

    $navbar['options']['graphs'] = array('text' => 'Graphs', 'icon' => $config['icon']['graphs']);

    //$health = dbFetchCell('SELECT COUNT(*) FROM `storage`    WHERE device_id = ?', array($device['device_id'])) +
    //          dbFetchCell('SELECT COUNT(*) FROM `sensors`    WHERE device_id = ?', array($device['device_id'])) +
    //          dbFetchCell('SELECT COUNT(*) FROM `mempools`   WHERE device_id = ?', array($device['device_id'])) +
    //          dbFetchCell('SELECT COUNT(*) FROM `status`     WHERE device_id = ?', array($device['device_id'])) +
    //          dbFetchCell('SELECT COUNT(*) FROM `processors` WHERE device_id = ?', array($device['device_id']));
    $health_exist = [
      'storage'    => dbExist('storage',    '`device_id` = ?', array($device['device_id'])),
      'diskio'     => dbExist('ucd_diskio', '`device_id` = ?', array($device['device_id'])),
      'mempools'   => dbExist('mempools',   '`device_id` = ?', array($device['device_id'])),
      'processors' => dbExist('processors', '`device_id` = ?', array($device['device_id'])),
      'sensors'    => dbExist('sensors',    '`device_id` = ? AND `sensor_deleted` = ?', array($device['device_id'], 0)),
      'status'     => dbExist('status',     '`device_id` = ? AND `status_deleted` = ?', array($device['device_id'], 0)),
      'counter'    => dbExist('counters',   '`device_id` = ? AND `counter_deleted` = ?', array($device['device_id'], 0)),
    ];
    //r($health_exist);

    $health = $health_exist['storage'] || $health_exist['diskio'] ||
              $health_exist['sensors'] || $health_exist['status'] || $health_exist['counter'] ||
              $health_exist['mempools'] || $health_exist['processors'];

    if ($health)
    {
      $navbar['options']['health'] = array('text' => 'Health', 'icon' => $config['icon']['health']);
    }

    // Print applications tab if there are matching entries in `applications` table
    //if (dbFetchCell('SELECT COUNT(app_id) FROM applications WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('applications', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['apps'] = array('text' => 'Apps', 'icon' => $config['icon']['apps']);
    }
    
    // Print the collectd tab if there is a matching directory
    if (isset($config['collectd_dir']) && is_dir($config['collectd_dir'] . "/" . $device['hostname'] ."/"))
    {
      $navbar['options']['collectd'] = array('text' => 'Collectd', 'icon' => $config['icon']['collectd']);
    }

    // Print the munin tab if there are matchng entries in the munin_plugins table
    //if (dbFetchCell('SELECT COUNT(mplug_id) FROM munin_plugins WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('munin_plugins', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['munin'] = array('text' => 'Munin', 'icon' => $config['icon']['munin']);
    }

    // Print the port tab if there are matching entries in the ports table
    //if (dbFetchCell('SELECT COUNT(port_id) FROM ports WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('ports', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['ports'] = array('text' => 'Ports', 'icon' => $config['icon']['port']);
    }

    // Juniper Firewall MIB. Some day generify this stuff.
    if(isset($attribs['juniper-firewall-mib']))
    {
      $navbar['options']['juniper-firewall'] = array('text' => 'Firewall', 'icon' => $config['icon']['firewall']);
    }

    // Print the SLAs tab if there are matching entries in the slas table
    //if (dbFetchCell('SELECT COUNT(*) FROM `slas` WHERE `device_id` = ? AND `deleted` = 0', array($device['device_id'])) > '0')
    if (dbExist('slas', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['slas'] = array('text' => 'SLAs', 'icon' => $config['icon']['sla']);
    }

    // Print the p2p radios tab if there are matching entries in the p2p radios
    //if (dbFetchCell('SELECT COUNT(radio_id) FROM p2p_radios WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('p2p_radios', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['p2pradios'] = array('text' => 'Radios', 'icon' => $config['icon']['p2pradio']);
    }

    // Print the access points tab if there are matching entries in the accesspoints table (Aruba Legacy)
    //if (dbFetchCell('SELECT COUNT(accesspoint_id) FROM accesspoints WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('accesspoints', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['accesspoints'] = array('text' => 'APs (Legacy)', 'icon' => $config['icon']['wifi']);
    }

    // Print the wifi tab if wifi things exist

    //$device_ap_exist    = dbFetchCell('SELECT COUNT(wifi_ap_id)          FROM `wifi_aps`          WHERE `device_id` = ?', array($device['device_id']));
    //$device_radio_exist = dbFetchCell('SELECT COUNT(wifi_radio_id)       FROM `wifi_radios`       WHERE `device_id` = ?', array($device['device_id']));
    //$device_wlan_exist  = dbFetchCell('SELECT COUNT(wlan_id)             FROM `wifi_wlans`        WHERE `device_id` = ?', array($device['device_id']));
    $device_ap_exist    = dbExist('wifi_aps',    '`device_id` = ?', array($device['device_id']));
    $device_radio_exist = dbExist('wifi_radios', '`device_id` = ?', array($device['device_id']));
    $device_wlan_exist  = dbExist('wifi_wlans',  '`device_id` = ?', array($device['device_id']));

    if ($device_ap_exist || $device_radio_exist || $device_wlan_exist)
    {
      $navbar['options']['wifi'] = array('text' => 'WiFi', 'icon' => $config['icon']['wifi']);
    }

    // Build array of smokeping files for use in tab building and smokeping page.
    if (isset($config['smokeping']['dir']))
    {
      $smokeping_files = get_smokeping_files();
    }

    // Print latency tab if there are smokeping files with source or destination matching this hostname
    if (count($smokeping_files['incoming'][$device['hostname']]) || count($smokeping_files['outgoing'][$device['hostname']]))
    {
      $navbar['options']['latency'] = array('text' => 'Ping', 'icon' => $config['icon']['smokeping']);
    }

    // Print vlans tab if there are matching entries in the vlans table
    //if (dbFetchCell('SELECT COUNT(vlan_id) FROM vlans WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('vlans', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['vlans'] = array('text' => 'VLANs', 'icon' => $config['icon']['vlan']);
    }

    // Pring Virtual Machines tab if there are matching entries in the vminfo table
    //if (dbFetchCell('SELECT COUNT(vm_id) FROM vminfo WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('vminfo', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['vm'] = array('text' => 'VMs', 'icon' => $config['icon']['virtual-machine']);
    }

    // $loadbalancer_tabs is used in device/loadbalancer/ to build the submenu. we do it here to save queries

    // Check for Netscaler vservers and services
    if ($device['os'] == "netscaler") // Netscaler
    {
      $device_loadbalancer_count['netscaler_vsvr'] = dbFetchCell("SELECT COUNT(*) FROM `netscaler_vservers` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['netscaler_vsvr']) { $loadbalancer_tabs[] = 'netscaler_vsvr'; }

      $device_loadbalancer_count['netscaler_services'] = dbFetchCell("SELECT COUNT(*) FROM `netscaler_services` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['netscaler_services']) { $loadbalancer_tabs[] = 'netscaler_services'; }

      $device_loadbalancer_count['netscaler_servicegroupmembers'] = dbFetchCell("SELECT COUNT(*) FROM `netscaler_servicegroupmembers` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['netscaler_servicegroupmembers']) { $loadbalancer_tabs[] = 'netscaler_servicegroupmembers'; }
    }

    if ($device['os'] == "f5")  // F5
    {
      $device_loadbalancer_count['lb_virtuals'] = dbFetchCell("SELECT COUNT(*) FROM `lb_virtuals` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['lb_virtuals']) { $loadbalancer_tabs[] = 'lb_virtuals'; }

      $device_loadbalancer_count['lb_pools'] = dbFetchCell("SELECT COUNT(*) FROM `lb_pools` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['lb_pools']) { $loadbalancer_tabs[] = 'lb_pools'; }

      $device_loadbalancer_count['lb_snatpools'] = dbFetchCell("SELECT COUNT(*) FROM `lb_snatpools` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['lb_snatpools']) { $loadbalancer_tabs[] = 'lb_snatpools'; }
    }

    // Check for Cisco ACE vservers
    if ($device['os'] == "acsw")  // Cisco ACE
    {
      $device_loadbalancer_count['loadbalancer_vservers'] = dbFetchCell("SELECT COUNT(*) FROM `loadbalancer_vservers` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['loadbalancer_vservers']) { $loadbalancer_tabs[] = 'loadbalancer_vservers'; }

      $device_loadbalancer_count['loadbalancer_rservers'] = dbFetchCell("SELECT COUNT(*) FROM `loadbalancer_rservers` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['loadbalancer_rservers']) { $loadbalancer_tabs[] = 'loadbalancer_rservers'; }
    }

    // Print the load balancer tab if the loadbalancer_tabs array has entries.
    if (is_array($loadbalancer_tabs))
    {
      $navbar['options']['loadbalancer'] = array('text' => 'Load Balancer', 'icon' => $config['icon']['loadbalancer'], 'community' => FALSE);
    }

    // $routing_tabs is used in device/routing/ to build the tabs menu. we build it here to save some queries

    $device_routing_count['ipsec_tunnels'] = dbFetchCell("SELECT COUNT(*) FROM `ipsec_tunnels` WHERE `device_id` = ?", array($device['device_id']));
    if ($device_routing_count['ipsec_tunnels']) { $routing_tabs[] = 'ipsec_tunnels';
      $routing_options[] = array('url'  => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'ipsec_tunnels')),
                                 'text' => 'IPSEC Tunnels',
                                 'icon' => $config['icon']['ipsec_tunnel']);
    }

    $device_routing_count['bgp'] = dbFetchCell("SELECT COUNT(*) FROM `bgpPeers` WHERE `device_id` = ?", array($device['device_id']));
    if ($device_routing_count['bgp']) {
      $routing_tabs[] = 'bgp';
      $routing_options[] = array('url'  => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'bgp')),
                                 'text' => 'BGP',
                                 'icon' => $config['icon']['bgp']);
    }

    //$device_routing_count['ospf'] = dbFetchCell("SELECT COUNT(*) FROM `ospf_instances` WHERE `ospfAdminStat` = 'enabled' AND `device_id` = ?", array($device['device_id']));
    $device_routing_count['ospf'] = dbFetchCell("SELECT COUNT(*) FROM `ospf_instances` WHERE `device_id` = ?", array($device['device_id']));
    if ($device_routing_count['ospf']) { $routing_tabs[] = 'ospf';
      $routing_options[] = array('url'  => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'ospf')),
                                 'text' => 'OSPF',
                                 'icon' => $config['icon']['ospf']);
    }

    $device_routing_count['eigrp'] = dbFetchCell("SELECT COUNT(*) FROM `eigrp_ports` WHERE `device_id` = ?", array($device['device_id']));
    if ($device_routing_count['eigrp']) { $routing_tabs[] = 'eigrp';
      $routing_options[] = array('url'  => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'eigrp')),
                                 'text' => 'EIGRP',
                                 'icon' => $config['icon']['eigrp']);}

    $device_routing_count['cef'] = dbFetchCell("SELECT COUNT(*) FROM `cef_switching` WHERE `device_id` = ?", array($device['device_id']));
    if ($device_routing_count['cef']) { $routing_tabs[] = 'cef';
      $routing_options[] = array('url'  => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'cef')),
                                 'text' => 'CEF',
                                 'icon' => $config['icon']['cef']);}

    $device_routing_count['vrf'] = dbFetchCell("SELECT COUNT(*) FROM `vrfs` WHERE `device_id` = ?", array($device['device_id']));
    if ($device_routing_count['vrf']) { $routing_tabs[] = 'vrf';
      $routing_options[] = array('url'  => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'vrf')),
                                 'text' => 'VRF',
                                 'icon' => $config['icon']['vrf']);}

    // Print routing tab if any of the routing tables contain matching entries
    if (is_array($routing_tabs))
    {
      $navbar['options']['routing'] = array('text' => 'Routing', 'icon' => $config['icon']['routing'], 'suboptions' => $routing_options);
    }

    // Print the pseudowire tab if any of the routing tables contain matching entries
    //if (dbFetchCell("SELECT COUNT(*) FROM `pseudowires` WHERE `device_id` = ?", array($device['device_id'])))
    if (dbExist('pseudowires', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['pseudowires'] = array('text' => 'Pseudowires', 'icon' => $config['icon']['pseudowire']);
    }

    // Print the packages tab if there are matching entries in the packages table
    //if (dbFetchCell('SELECT COUNT(*) FROM `packages` WHERE `device_id` = ?', array($device['device_id'])) > 0)
    if (dbExist('packages', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['packages'] = array('text' => 'Pkgs', 'icon' => $config['icon']['packages']);
    }

    // Print the Windows Services tab if there are matching entries in the winservices table
    //if (dbFetchCell('SELECT COUNT(*) FROM `winservices` WHERE `device_id` = ?', array($device['device_id'])) > 0)
    if (dbExist('winservices', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['winservices'] = array('text' => 'Windows Services', 'icon' => $config['icon']['processes']);
    }

    // Print the inventory tab if inventory is enabled and either entphysical or hrdevice tables have entries
    //if (dbFetchCell('SELECT COUNT(*) FROM `entPhysical` WHERE `device_id` = ?', array($device['device_id'])) > 0)
    if (dbExist('entPhysical', '`device_id` = ? AND `deleted` IS NULL', [ $device['device_id'] ]))
    {
      $navbar['options']['entphysical'] = array('text' => 'Inventory', 'icon' => $config['icon']['inventory']);
    }
    //elseif (dbFetchCell('SELECT COUNT(*) FROM `hrDevice` WHERE `device_id` = ?', array($device['device_id'])) > 0)
    elseif (dbExist('hrDevice', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['hrdevice'] = array('text' => 'Inventory', 'icon' => $config['icon']['inventory']);
    }

    if (isset($attribs['ps_list']))
    {
      $navbar['options']['processes'] = array('text' => 'Processes', 'icon' => $config['icon']['processes']);
    }

    // Print probes tab if there are entries in the probes table
    if (
        dbExist('probes', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['probes'] = array('text' => 'Probes', 'icon' => $config['icon']['status']);
    }


    // Print printing tab if there are entries in the printersupplies table
    //if (dbFetchCell('SELECT COUNT(*) FROM `printersupplies` WHERE device_id = ?', array($device['device_id'])) > 0)
    if (dbExist('printersupplies', '`device_id` = ?', array($device['device_id'])))
    {
      $navbar['options']['printing'] = array('text' => 'Printing', 'icon' => $config['icon']['printersupply']);

      // $printing_tabs is used in device/printing/ to build the tabs menu. we build it here to save some queries
      /// FIXME. sid3windr, I not see what this query "save" here, must be moved to device/printing.inc.php
      $printing_tabs = dbFetchColumn("SELECT DISTINCT `supply_type` FROM `printersupplies` WHERE `device_id` = ?", array($device['device_id']));
    }

    // Always print logs tab
    $navbar['options']['logs'] = array('text' => 'Logs', 'icon' => $config['icon']['logs']);

    // Print alerts tab
    $navbar['options']['alerts'] = array('text' => 'Alerts', 'icon' => $config['icon']['alert']);

    // If the user has secure global read privileges, check for a device config.
    if ($_SESSION['userlevel'] >= 7)
    {
      $device_config_file = get_rancid_filename($device['hostname']);

      // Print the config tab if we have a device config
      if ($device_config_file)
      {
        $navbar['options']['showconfig'] = array('text' => 'Config', 'icon' => $config['icon']['config']);
      }
    }

    /*
    // If the user has global read privileges, check for device notes.
    if ($_SESSION['userlevel'] >= 5)
    {
      // Promoted to real tab when notes is filled in, otherwise demoted to icon on the right
      $navbar['options']['notes'] = array('text' => ($attribs['notes'] ? 'Notes' : NULL), 'icon' => 'sprite-notes', 'right' => ($attribs['notes'] == ''));
    }
    */

    // If nfsen is enabled, check for an nfsen file
    if ($config['nfsen_enable'])
    {
      $nfsen_rrd_file = get_nfsen_filename($device['hostname']);
    }

    // Print the netflow tab if we have an nfsen file
    if ($nfsen_rrd_file)
    {
      $navbar['options']['nfsen'] = array('text' => 'Netflow', 'icon' => $config['icon']['nfsen']);
    }

    $navbar['options']['notes']                             = array('text' => '', 'icon' => $config['icon']['eventlog'], 'right' => TRUE, 'class' => "dropdown-toggle");


    // If the user has global write permissions, show them the edit tab
    if (is_entity_write_permitted($device['device_id'], 'device'))
    {
      $navbar['options']['tools']                             = array('text' => '', 'icon' => $config['icon']['tools'], 'url' => '#', 'right' => TRUE, 'class' => "dropdown-toggle");
      $navbar['options']['tools']['suboptions']['data']       = array('text' => 'Device Data', 'icon' => $config['icon']['device-data']);
      $navbar['options']['tools']['suboptions']['perf']       = array('text' => 'Performance Data', 'icon' => $config['icon']['device-poller']);
      if($config['web_enable_showtech']) { $navbar['options']['tools']['suboptions']['showtech']       = array('text' => 'Show Tech-Support', 'icon' => $config['icon']['techsupport']); }
      $navbar['options']['tools']['suboptions']['divider_1']  = array('divider' => TRUE);

      if (is_array($config['os'][$device['os']]['remote_access']))
      {
        foreach ($config['os'][$device['os']]['remote_access'] as $name => $ra_array)
        {
          if (!is_array($ra_array)) { $name = $ra_array; $ra_array = array(); }

          $ra_array = array_merge($ra_array, $config['remote_access'][$name]);

          // Check if a device specific attribute has been set by code
          $ra_array['url'] = get_dev_attrib($device, 'ra_url_' . $name);

          // If no attribute set, default to protocol://hostname:port
          if ($ra_array['url'] == '')
          {
            $ra_array['url'] = $name.'://'.$device['hostname'].':'.$ra_array['port'];
          }

          $navbar['options']['tools']['suboptions'][$name]       = array('text' => 'Connect via '.$ra_array['name'], 'icon' => $ra_array['icon'], 'url' => $ra_array['url'], 'link_opts' => 'target="_blank"');
        }
        $navbar['options']['tools']['suboptions']['divider_2']  = array('divider' => TRUE);
      }

      $navbar['options']['tools']['suboptions']['delete']['url']  = "#modal-delete_device";
      $navbar['options']['tools']['suboptions']['delete']['text'] = 'Delete Device';
      $navbar['options']['tools']['suboptions']['delete']['link_opts'] = 'data-toggle="modal"';
      $navbar['options']['tools']['suboptions']['delete']['icon'] = $config['icon']['minus'];

      $navbar['options']['tools']['suboptions']['divider_3']  = array('divider' => TRUE);

      $navbar['options']['tools']['suboptions']['edit']       = array('text' => 'Properties', 'icon' => $config['icon']['tools']);

    }
?>

<script>

(function ($) {

        $(function() {

                // fix sub nav on scroll
                var $win = $(window),
                                $body = $('body'),
                                $nav = $('.subnav'),
                                navHeight = $('.navbar').first().height(),
                                subnavHeight = $('.subnav').first().height(),
                                subnavTop = $('.subnav').length && $('.subnav').offset().top,
                                marginTop = parseInt($body.css('margin-top'), 10);
                                isFixed = 0;

//                                 subnavTop = $('.subnav').length && $('.subnav').offset().top - navHeight,

                processScroll();

                $win.on('scroll', processScroll);

                function processScroll() {
                        var i, scrollTop = $win.scrollTop();

                        if (scrollTop >= subnavTop && !isFixed) {
                                isFixed = 1;
                                $nav.addClass('subnav-fixed');
                                $body.css('margin-top', marginTop + subnavHeight + 'px');
                        } else if (scrollTop <= subnavTop && isFixed) {
                                isFixed = 0;
                                $nav.removeClass('subnav-fixed');
                                $body.css('margin-top', marginTop + 'px');
                        }
                }

        });

})(window.jQuery);
</script>

<?php

    $navbar['class'] = 'navbar-narrow subnav';
    $navbar['brand'] = $device['hostname'];
    $navbar['brand-class'] = 'fixed-only';

    foreach ($navbar['options'] as $option => $array)
    {
      if (!isset($vars['tab'])) { $vars['tab'] = $option; }
      if ($vars['tab'] == $option) { $navbar['options'][$option]['class'] .= " active"; }
      if (!isset($navbar['options'][$option]['url'])) { $navbar['options'][$option]['url'] = generate_device_url($device, array('tab' => $option)); }

      if (isset($navbar['options'][$option]['suboptions']))
      {
        foreach ($navbar['options'][$option]['suboptions'] as $sub_option => $sub_array)
        {
          if (!isset($navbar['options'][$option]['suboptions'][$sub_option]['url'])) { $navbar['options'][$option]['suboptions'][$sub_option]['url'] = generate_device_url($device, array('tab' => $sub_option)); }
        }
      }
    }

    if ($vars['tab'] == 'port')        { $navbar['options']['ports']['class'] .= " active"; }
    if ($vars['tab'] == 'alert')       { $navbar['options']['alerts']['class'] .= " active"; }
    if ($vars['tab'] == 'accesspoint') { $navbar['options']['accesspoints']['class'] .= " active"; }

    print_navbar($navbar);
    unset($navbar);
  }

  // Delete device modal

      $form = array('type'      => 'horizontal',
                    'entity_write_permit' => array('entity_type' => 'device', 'entity_id' => $device['device_id']),
                    'id'        => 'modal-delete_device',
                    'title'      => 'Delete Device "' . $device['hostname'] . '"',
                    'icon'      => $config['icon']['device-delete'],
                    'url'       => 'delhost/'
                    );

      $form['row'][0]['id'] = array(
                                      'type'        => 'hidden',
                                      'fieldset'    => 'body',
                                      'value'       => $device['device_id']);
      $form['row'][4]['confirm'] = array(
                                      'type'        => 'checkbox',
                                      'fieldset'    => 'body',
                                      'name'        => 'Confirm Deletion',
                                      'onchange'    => "javascript: toggleAttrib('disabled', 'delete_modal');",
                                      'value'       => 'confirm');
      /*
      $form['row'][5]['deleterrd'] = array(
                                      'type'        => 'checkbox',
                                      'fieldset'    => 'body',
                                      'name'        => 'Delete RRDs',
                                      'onchange'    => "javascript: showDiv(this.checked, 'warning_".$device['device_id']."_div');",
                                      'value'       => 'confirm');
      $form['row'][7]['warning_'.$device['device_id']] = array(
                                      'type'        => 'html',
                                      'fieldset'    => 'body',
                                      'html'        => '<h4 class="alert-heading"><i class="icon-warning-sign"></i> Warning!</h4>' .
                                                       ' This will delete this device from Observium including all logging entries, but will not delete the RRDs.',
                                      //'div_style'   => 'display: none', // hide initially
                                      'div_class'   => 'alert alert-warning');
      */

      $form['row'][9]['close'] = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'footer',
                                      'div_class'   => '', // Clean default form-action class!
                                      'name'        => 'Close',
                                      'icon'        => '',
                                      'attribs'     => array('data-dismiss' => 'modal',  // dismiss modal
                                                             'aria-hidden'  => 'true')); // do not sent any value
      $form['row'][9]['delete_modal'] = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'footer',
                                      'div_class'   => '', // Clean default form-action class!
                                      'name'        => 'Delete device',
                                      'icon'        => 'icon-remove icon-white',
                                      //'right'       => TRUE,
                                      'class'       => 'btn-danger',
                                      'disabled'    => TRUE);

      echo generate_form_modal($form);
      unset($form);



  // Check that the user can view the device, or is viewing a permitted port on the device
  if (isset($cache['devices']['id'][$device['device_id']]) || $permit_tabs[$tab])
  {
    // If this device has never been polled, print a warning here
    if (!$device['last_polled'] || $device['last_polled'] == '0000-00-00 00:00:00')
    {
      print_warning('<h4>Device not yet polled</h4>
This device has not yet been successfully polled. System information and statistics will not be populated and graphs will not draw.
Please wait 5-10 minutes for graphs to draw correctly.');

      // Poller info displayed only if device never polled
      if ($_SESSION['userlevel'] >= 7)
      {
        $poller_start = dbFetchCell("SELECT `process_start` FROM `observium_processes` WHERE `device_id` = ? AND `process_name` = ?", array($device['device_id'], 'poller.php'));
        //r($poller_start);
        if ($poller_start)
        {
          print_success('<h4>Device poller in progress</h4>
This device is being polled now. Poller started '.format_unixtime($poller_start).' ('.format_uptime(time() - $poller_start).' ago).');
        }
      }
    }

    // If this device has never been discovered, print a warning here
    if (!$device['last_discovered'] || $device['last_discovered'] == '0000-00-00 00:00:00')
    {
      print_warning('<h4>Device not yet discovered</h4>
This device has not yet been successfully discovered. System information and statistics will not be populated and graphs will not draw.
This device should be automatically discovered within 10 minutes.');
    }
    if ($_SESSION['userlevel'] >= 7)
    {
      $discovery_start = dbFetchCell("SELECT `process_start` FROM `observium_processes` WHERE `device_id` = ? AND `process_name` = ?", array($device['device_id'], 'discovery.php'));
      //r($discovery_start);
      if ($discovery_start)
      {
        print_success('<h4>Device discovery in progress</h4>
This device is being discovered now. Discovery started '.format_unixtime($discovery_start).' ('.format_uptime(time() - $discovery_start).' ago).');
      }
    }

    if (is_file($config['html_dir']."/pages/device/".basename($tab).".inc.php"))
    {
      include($config['html_dir']."/pages/device/".basename($tab).".inc.php");
    } else {
      print_error('<h4>Tab does not exist</h4>
The requested tab does not exist. Please correct the URL and try again.');
    }

  } else {
    print_error_permission();
  }
}

// EOF
