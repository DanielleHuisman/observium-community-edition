<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

// If we've been given a hostname, try to retrieve the device_id

if (isset($vars['device']) && !is_numeric($vars['device'])) {
  $vars['hostname'] = $vars['device'];
  unset($vars['device']);
}

if (safe_empty($vars['device']) && !safe_empty($vars['hostname'])) {
  $vars['device'] = get_device_id_by_hostname($vars['hostname']);

  // If device lookup fails, generate an error.
  if (! $vars['device']) {
    print_error('<h4>Invalid Hostname</h4>
                   A device matching the given hostname was not found. Please retype the hostname and try again.');
    return;
  }
}

// If there is no device specified in the URL, generate an error.
if (safe_empty($vars['device'])) {
  print_error('<h4>No device specified</h4>
                   A valid device was not specified in the URL. Please retype and try again.');
  return;
}

// Allow people to see this page if they have permission to see one of the ports, but don't show them tabs.
$permit_tabs = array();
if ($vars['tab'] === "port" && is_numeric($vars['device']) && (isset($vars['port']) || isset($vars['ifdescr']))) {
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

if ($vars['tab'] === "health" && isset($vars['id']) && is_numeric($vars['id']) &&
    is_entity_permitted($vars['id'], 'sensor'))
{
  $permit_tabs['health'] = TRUE;
}

//print_vars($permit_tabs);

// If there is no valid device specified in the URL, generate an error.
if (!isset($cache['devices']['id'][$vars['device']]) && !safe_count($permit_tabs))
{
  print_error('<h4>No valid device specified</h4>
                  A valid device was not specified in the URL. Please retype and try again.');
  return;
}

// Only show if the user has access to the whole device or a single port.
if (isset($cache['devices']['id'][$vars['device']]) || safe_count($permit_tabs))
{
  $selected['iface'] = "active";

  $tab = str_replace(".", "", $vars['tab']);

  if (!$tab)
  {
    $tab = "overview";
  }

  $select[$tab] = "active"; // FIXME. ????

  // Populate device array from pre-populated cache
  $device = device_by_id_cache($vars['device']);

  // Populate the attributes array for this device
  $attribs = get_dev_attribs($device['device_id']);

  // Populate the entityPhysical state array for this device
  $entity_state = get_device_entphysical_state($device['device_id']);

  // Populate the device state array from the serialized entry
  $device_state = safe_unserialize($device['device_state']);

  // Add the device hostname to the page title array
  register_html_title(escape_html($device['hostname']));

  // If the device's OS type has a group, set the device's os_group
  if ($config['os'][$device['os']]['group'])
  {
    $device['os_group'] = $config['os'][$device['os']]['group'];
  }

  //// DEV

  // Start to cache panel
  /* NOTE. Also this panel content can be moved to html/includes/panels/device.inc.php (instead ob_cache() here)
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
    $graphs_enabled = [];
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

    $panel_html = ob_get_clean();

    register_html_panel($panel_html);

    unset($graph_array, $panel_html);

  // End panel */

  // Print the device header

  //echo '<div class="hidden-xl">';
  print_device_header($device, [ 'div-class' => 'hidden-xl' ]);
  //echo '</div>';

  // Show tabs if the user has access to this device

  if (device_permitted($device['device_id'])) {
    if ($config['show_overview_tab']) {
      $navbar['options']['overview'] = array('text' => 'Overview', 'icon' => $config['icon']['overview']);
    }

    $navbar['options']['graphs'] = array('text' => 'Graphs', 'icon' => $config['icon']['graphs']);

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

    if ($health) {
      $navbar['options']['health'] = array('text' => 'Health', 'icon' => $config['icon']['health']);
    }

    // Ports submenu
    $ports_exist = [
      'ports' => dbExist('ports', '`device_id` = ?', array($device['device_id'])),
      'ipv4'  => dbExist('ipv4_addresses', '`device_id` = ?', array($device['device_id'])),
      'ipv6'  => dbExist('ipv6_addresses', '`device_id` = ?', array($device['device_id']))
    ];
    $link_array = [
      'page'    => 'device',
      'device'  => $device['device_id'],
      'tab'     => 'ports'
    ];

    // Print the port tab if there are matching entries in the ports table
    if ($ports_exist['ports']) {
      $navbar['options']['ports'] = array('text' => 'Ports', 'icon' => $config['icon']['port']);

      $navbar['options']['ports']['suboptions']['basic']['text']   = 'Basic';
      $navbar['options']['ports']['suboptions']['details']['text'] = 'Details';

      $navbar['options']['ports']['suboptions']['divider_1']['divider'] = TRUE;
    }
    $option = 'ipv4';
    if ($ports_exist[$option]) {
      // Duplicate, because possible IPs without Ports
      $navbar['options']['ports']['text'] = 'Ports';
      $navbar['options']['ports']['icon'] = $config['icon']['port'];

      $navbar['options']['ports']['suboptions'][$option]['text'] = 'IPv4 addresses';
      //$navbar['options']['ports']['suboptions'][$option]['url']  = generate_url($link_array, [ 'view' => $option ]);
    }
    $option = 'ipv6';
    if ($ports_exist[$option]) {
      // Duplicate, because possible IPs without Ports
      $navbar['options']['ports']['text'] = 'Ports';
      $navbar['options']['ports']['icon'] = $config['icon']['port'];

      $navbar['options']['ports']['suboptions'][$option]['text'] = 'IPv6 addresses';
      //$navbar['options']['ports']['suboptions'][$option]['url']  = generate_url($link_array, [ 'view' => $option ]);
    }
    // Other ports options after Addresses
    if ($ports_exist['ports']) {
      $filters_array = isset($vars['filters']) ? $vars['filters'] : [ 'deleted' => TRUE ];
      $link_array['filters'] = $filters_array;

      // FIXME, need add device_id field into table ip_mac
      if ($ports_exist['arp'] = dbFetchCell("SELECT COUNT(*) FROM `ip_mac` LEFT JOIN `ports` USING(`port_id`) WHERE `device_id` = ?", [ $device['device_id'] ]))
      //if ($ports_exist['arp'] = dbExist('ip_mac', '`device_id` = ?', [ $device['device_id'] ]))
      {
        $navbar['options']['ports']['suboptions']['arp']['text'] = 'ARP/NDP Table';
      }

      if ($ports_exist['fdb'] = dbExist('vlans_fdb', '`device_id` = ?', [ $device['device_id'] ]))
      {
        $navbar['options']['ports']['suboptions']['fdb']['text'] = 'FDB Table';
      }

      if ($health_exist['sensors'] &&
          $ports_exist['sensors'] = dbExist('sensors', '`device_id` = ? AND `measured_class` = ? AND `sensor_deleted` = ?', [ $device['device_id'], 'port', 0 ]))
      {
        $navbar['options']['ports']['suboptions']['sensors']['text'] = 'Sensors';
      }

      if ($ports_exist['neighbours'] = dbExist('neighbours', '`device_id` = ?', [ $device['device_id'] ]))
      {
        $navbar['options']['ports']['suboptions']['neighbours']['text'] = 'Neighbours';
        $navbar['options']['ports']['suboptions']['map']['text']        = 'Map';
      }

      if ($ports_exist['adsl'] = dbExist('ports', '`ifType` = ? AND `device_id` = ?', array('adsl', $device['device_id'])))
      {
        $navbar['options']['ports']['suboptions']['adsl']['text'] = 'ADSL';
      }
    }
    // Store for ports tab
    $ports_exist['navbar'] = $navbar['options']['ports']['suboptions'];

    // Ports options urls and active
    foreach ($navbar['options']['ports']['suboptions'] as $option => $entry) {
      if (!isset($entry['url'])) {
        $navbar['options']['ports']['suboptions'][$option]['url']  = generate_url($link_array, [ 'view' => $option ]);
        if ($vars['tab'] === 'ports' && $vars['view'] === $option) {
          $navbar['options']['ports']['suboptions'][$option]['class'] .= ' active';
        }
      }
    }
    //r($ports_exist);

    // Print vlans tab if there are matching entries in the vlans table
    //if (dbFetchCell('SELECT COUNT(vlan_id) FROM vlans WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('vlans', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['vlans'] = array('text' => 'VLANs', 'icon' => $config['icon']['vlan']);
    }

    // Print the SLAs tab if there are matching entries in the slas table
    //if (dbFetchCell('SELECT COUNT(*) FROM `slas` WHERE `device_id` = ? AND `deleted` = 0', array($device['device_id'])) > '0')
    if (dbExist('slas', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['slas'] = array('text' => 'SLAs', 'icon' => $config['icon']['sla']);
    }

    // Juniper Firewall MIB. Some day generify this stuff.
    if (isset($attribs['juniper-firewall-mib'])) {
      $navbar['options']['juniper-firewall'] = array('text' => 'Firewall', 'icon' => $config['icon']['firewall']);
    }

    // Print the p2p radios tab if there are matching entries in the p2p radios
    //if (dbFetchCell('SELECT COUNT(radio_id) FROM p2p_radios WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('p2p_radios', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['p2pradios'] = array('text' => 'Radios', 'icon' => $config['icon']['p2pradio']);
    }

    // Print the access points tab if there are matching entries in the accesspoints table (Aruba Legacy)
    //if (dbFetchCell('SELECT COUNT(accesspoint_id) FROM accesspoints WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('accesspoints', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['accesspoints'] = array('text' => 'APs (Legacy)', 'icon' => $config['icon']['wifi']);
    }

    // Print the wifi tab if wifi things exist

    //$device_ap_exist    = dbFetchCell('SELECT COUNT(wifi_ap_id)          FROM `wifi_aps`          WHERE `device_id` = ?', array($device['device_id']));
    //$device_radio_exist = dbFetchCell('SELECT COUNT(wifi_radio_id)       FROM `wifi_radios`       WHERE `device_id` = ?', array($device['device_id']));
    //$device_wlan_exist  = dbFetchCell('SELECT COUNT(wlan_id)             FROM `wifi_wlans`        WHERE `device_id` = ?', array($device['device_id']));
    $device_ap_exist    = dbExist('wifi_aps',    '`device_id` = ?', array($device['device_id']));
    $device_radio_exist = dbExist('wifi_radios', '`device_id` = ?', array($device['device_id']));
    $device_wlan_exist  = dbExist('wifi_wlans',  '`device_id` = ?', array($device['device_id']));

    if ($device_ap_exist || $device_radio_exist || $device_wlan_exist) {
      $navbar['options']['wifi'] = array('text' => 'WiFi', 'icon' => $config['icon']['wifi']);
    }

    // Print applications tab if there are matching entries in `applications` table
    //if (dbExist('applications', '`device_id` = ?', array($device['device_id']))) {
    if ($apps = dbFetchColumn('SELECT DISTINCT `app_type` FROM `applications` WHERE `device_id` = ?', [ $device['device_id'] ])) {
      $navbar['options']['apps'] = [ 'text' => 'Apps', 'icon' => $config['icon']['apps'] ];
      $app_array = [ 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'apps' ];
      foreach ($apps as $app_type) {
        $navbar['options']['apps']['suboptions'][$app_type]['text'] = nicecase($app_type);
        //$url = generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'apps', 'app' => $app['app_type'], 'instance' => $app['app_id'] ));
        $navbar['options']['apps']['suboptions'][$app_type]['url']  = generate_url($app_array, [ 'app' => $app_type ]);
        if ($vars['tab'] === 'apps' && $vars['app'] === $app_type) {
          $navbar['options']['apps']['suboptions'][$app_type]['class'] .= ' active';
        }

        // Detect and add application icon
        $icon = $app_type;
        $image = $config['html_dir'].'/images/apps/'.$icon.'.png';
        if (!is_file($image)) {
          list($icon) = explode('-', str_replace('_', '-', $app_type));
          $image = $config['html_dir'].'/images/apps/'.$icon.'.png';
          if ($icon !== $app_type && is_file($image)) {
            // 'postfix_qshape' -> 'postfix'
            // 'exim-mailqueue' -> 'exim'
          } else {
            $icon = 'apps'; // Generic
          }
        }
        $navbar['options']['apps']['suboptions'][$app_type]['image'] = 'images/apps/'.$icon.'.png';
        if (is_file($config['html_dir'].'/images/apps/'.$icon.'_2x.png')) {
          // HiDPI icon
          $navbar['options']['apps']['suboptions'][$app_type]['image_2x'] = 'images/apps/'.$icon.'_2x.png';
        }
      }
    }

    // Print the collectd tab if there is a matching directory
    if (isset($config['collectd_dir']) && is_dir($config['collectd_dir'] . "/" . $device['hostname'] ."/")) {
      $navbar['options']['collectd'] = array('text' => 'Collectd', 'icon' => $config['icon']['collectd']);
    }

    // Print the munin tab if there are matchng entries in the munin_plugins table
    if (dbExist('munin_plugins', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['munin'] = array('text' => 'Munin', 'icon' => $config['icon']['munin']);
    }

    // Build array of smokeping files for use in tab building and smokeping page.
    if (isset($config['smokeping']['dir'])) {
      $smokeping_files = get_smokeping_files();
    }

    // Print latency tab if there are smokeping files with source or destination matching this hostname
    if (safe_count($smokeping_files['incoming'][$device['hostname']]) ||
        safe_count($smokeping_files['outgoing'][$device['hostname']])) {
      $navbar['options']['latency'] = array('text' => 'Ping', 'icon' => $config['icon']['smokeping']);
    }

    // Pring Virtual Machines tab if there are matching entries in the vminfo table
    //if (dbFetchCell('SELECT COUNT(vm_id) FROM vminfo WHERE device_id = ?', array($device['device_id'])) > '0')
    if (dbExist('vminfo', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['vm'] = array('text' => 'VMs', 'icon' => $config['icon']['virtual-machine']);
    }

    // $loadbalancer_tabs is used in device/loadbalancer/ to build the submenu. we do it here to save queries

    // Check for Netscaler vservers and services
    if ($device['os'] === "netscaler") { // Netscaler
      $device_loadbalancer_count['netscaler_vsvr'] = dbFetchCell("SELECT COUNT(*) FROM `netscaler_vservers` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['netscaler_vsvr']) { $loadbalancer_tabs[] = 'netscaler_vsvr'; }

      $device_loadbalancer_count['netscaler_services'] = dbFetchCell("SELECT COUNT(*) FROM `netscaler_services` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['netscaler_services']) { $loadbalancer_tabs[] = 'netscaler_services'; }

      $device_loadbalancer_count['netscaler_servicegroupmembers'] = dbFetchCell("SELECT COUNT(*) FROM `netscaler_servicegroupmembers` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['netscaler_servicegroupmembers']) { $loadbalancer_tabs[] = 'netscaler_servicegroupmembers'; }
    }

    if ($device['os'] === "f5") { // F5
      $device_loadbalancer_count['lb_virtuals'] = dbFetchCell("SELECT COUNT(*) FROM `lb_virtuals` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['lb_virtuals']) { $loadbalancer_tabs[] = 'lb_virtuals'; }

      $device_loadbalancer_count['lb_pools'] = dbFetchCell("SELECT COUNT(*) FROM `lb_pools` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['lb_pools']) { $loadbalancer_tabs[] = 'lb_pools'; }

      $device_loadbalancer_count['lb_snatpools'] = dbFetchCell("SELECT COUNT(*) FROM `lb_snatpools` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['lb_snatpools']) { $loadbalancer_tabs[] = 'lb_snatpools'; }
    }

    // Check for Cisco ACE vservers
    if ($device['os'] === "acsw") { // Cisco ACE
	  /* FIXME. Undone by adama
      $device_loadbalancer_count['slb_vsvrs'] = dbFetchCell("SELECT COUNT(*) FROM `lb_slb_vsvrs` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['slb_vsvrs']) { $loadbalancer_tabs[] = 'slb_vsvrs'; }

      $device_loadbalancer_count['slb_rsvrs'] = dbFetchCell("SELECT COUNT(*) FROM `loadbalancer_rservers` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['slb_rsvrs']) { $loadbalancer_tabs[] = 'slb_rsvrs'; }
	  */
	  $device_loadbalancer_count['loadbalancer_vservers'] = dbFetchCell("SELECT COUNT(*) FROM `loadbalancer_vservers` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['loadbalancer_vservers']) { $loadbalancer_tabs[] = 'loadbalancer_vservers'; }

      $device_loadbalancer_count['loadbalancer_rservers'] = dbFetchCell("SELECT COUNT(*) FROM `loadbalancer_rservers` WHERE `device_id` = ?", array($device['device_id']));
      if ($device_loadbalancer_count['loadbalancer_rservers']) { $loadbalancer_tabs[] = 'loadbalancer_rservers'; }
    }

    // Print the load balancer tab if the loadbalancer_tabs array has entries.
    if (is_array($loadbalancer_tabs)) {
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
    if ($device_routing_count['ospf']) {
      $routing_tabs[] = 'ospf';
      $routing_options[] = array('url'  => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'ospf')),
                                 'text' => 'OSPF',
                                 'icon' => $config['icon']['ospf']);
    }

    $device_routing_count['eigrp'] = dbFetchCell("SELECT COUNT(*) FROM `eigrp_ports` WHERE `device_id` = ?", array($device['device_id']));
    if ($device_routing_count['eigrp']) {
      $routing_tabs[] = 'eigrp';
      $routing_options[] = array('url'  => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'eigrp')),
                                 'text' => 'EIGRP',
                                 'icon' => $config['icon']['eigrp']);
    }

    $device_routing_count['cef'] = dbFetchCell("SELECT COUNT(*) FROM `cef_switching` WHERE `device_id` = ?", array($device['device_id']));
    if ($device_routing_count['cef']) {
      $routing_tabs[] = 'cef';
      $routing_options[] = array('url'  => generate_url(array('page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'cef')),
                                 'text' => 'CEF',
                                 'icon' => $config['icon']['cef']);
    }

    if ($config['enable_vrfs']) {
      $device_routing_count['vrf'] = dbFetchCell("SELECT COUNT(*) FROM `vrfs` WHERE `device_id` = ?", array( $device['device_id'] ));
      if ($device_routing_count['vrf']) {
        $routing_tabs[]    = 'vrf';
        $routing_options[] = array( 'url'  => generate_url(array( 'page' => 'device', 'device' => $device['device_id'], 'tab' => 'routing', 'proto' => 'vrf' )),
                                    'text' => 'VRF',
                                    'icon' => $config['icon']['vrf'] );
      }
    }

    // Print routing tab if any of the routing tables contain matching entries
    if (safe_count($routing_tabs)) {
      $navbar['options']['routing'] = array('text' => 'Routing', 'icon' => $config['icon']['routing'], 'suboptions' => $routing_options);
    }

    // Print the pseudowire tab if any of the routing tables contain matching entries
    //if (dbFetchCell("SELECT COUNT(*) FROM `pseudowires` WHERE `device_id` = ?", array($device['device_id'])))
    if (dbExist('pseudowires', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['pseudowires'] = array('text' => 'Pseudowires', 'icon' => $config['icon']['pseudowire']);
    }

    // Print the packages tab if there are matching entries in the packages table
    //if (dbFetchCell('SELECT COUNT(*) FROM `packages` WHERE `device_id` = ?', array($device['device_id'])) > 0)
    if (dbExist('packages', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['packages'] = array('text' => 'Pkgs', 'icon' => $config['icon']['packages']);
    }

    // Print the Windows Services tab if there are matching entries in the winservices table
    //if (dbFetchCell('SELECT COUNT(*) FROM `winservices` WHERE `device_id` = ?', array($device['device_id'])) > 0)
    if (dbExist('winservices', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['winservices'] = array('text' => 'Windows Services', 'icon' => $config['icon']['processes']);
    }

    // Print the inventory tab if inventory is enabled and either entphysical or hrdevice tables have entries
    if (dbExist('entPhysical', '`device_id` = ? AND `deleted` IS NULL', [ $device['device_id'] ])) {
      $navbar['options']['entphysical'] = [ 'text' => 'Inventory', 'icon' => $config['icon']['inventory'] ];

      if ($device['os_group'] === 'unix' && is_device_mib($device, 'HOST-RESOURCES-MIB') &&
          dbExist('hrDevice', '`device_id` = ?', [ $device['device_id'] ])) {
        // Some unix OS can have both inventory tables, ie VMWare
        $navbar['options']['hrdevice'] = [ 'text' => 'Resources', 'icon' => $config['icon']['inventory'] ];
      }
    } elseif (is_device_mib($device, 'HOST-RESOURCES-MIB') && dbExist('hrDevice', '`device_id` = ?', [ $device['device_id'] ])) {
      $navbar['options']['hrdevice'] = [ 'text' => 'Resources', 'icon' => $config['icon']['inventory'] ];
    }

    if (isset($attribs['ps_list'])) {
      $navbar['options']['processes'] = array('text' => 'Processes', 'icon' => $config['icon']['processes']);
    }

    // Print probes tab if there are entries in the probes table
    if (dbExist('probes', '`device_id` = ?', array($device['device_id']))) {
      $navbar['options']['probes'] = array('text' => 'Probes', 'icon' => $config['icon']['status']);
    }


    // Print printing tab if there are entries in the printersupplies table
    //if (dbFetchCell('SELECT COUNT(*) FROM `printersupplies` WHERE device_id = ?', array($device['device_id'])) > 0)
    if (dbExist('printersupplies', '`device_id` = ?', array($device['device_id']))) {
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
    if ($_SESSION['userlevel'] >= 7) {
      $showconfig = FALSE;
      if ($device_config_file = get_rancid_filename($device)) {
        // Rancid
        $showconfig = TRUE;
      }

      // Print the config tab if we have a device config
      if ($showconfig) {
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
    if ($config['nfsen_enable'] &&
        $nfsen_rrd_file = get_nfsen_filename($device['hostname'])) {
      // Print the netflow tab if we have an nfsen file
      $navbar['options']['nfsen'] = array('text' => 'Netflow', 'icon' => $config['icon']['nfsen']);
    }

    $navbar['options']['notes']                             = array('text' => '', 'icon' => $config['icon']['notes'], 'right' => TRUE, 'class' => "dropdown-toggle");

    if (is_array($config['os'][$device['os']]['remote_access'])) {
      $connect_entries = array();
      foreach ($config['os'][$device['os']]['remote_access'] as $name => $ra_array) {
        if (!is_array($ra_array)) {
          $name = $ra_array;
          $ra_array = array();
        }

        if (isset($config['remote_access'][$name])) {
          $ra_array = array_merge($ra_array, (array)$config['remote_access'][$name]);
        }

        // Check if a device specific attribute has been set by code
        $ra_array['url'] = get_dev_attrib($device, 'ra_url_' . $name);

        // If no attribute set, default to protocol://hostname:port
        if (safe_empty($ra_array['url'])) {
          $ra_array['url'] = $name.'://'.$device['hostname'].':'.$ra_array['port'];
        }
        $connect_entries[] = [ 'text' => 'Connect via '.$ra_array['name'], 'icon' => $ra_array['icon'], 'url' => $ra_array['url'], 'link_opts' => 'target="_blank"' ];
      }
    }

    // If the user has global write permissions, show them the edit tab
    if (is_entity_write_permitted($device['device_id'], 'device')) {
      $navbar['options']['tools']                             = array('text' => '', 'icon' => $config['icon']['device-tools'], 'url' => '#', 'right' => TRUE, 'class' => "dropdown-toggle");
      $navbar['options']['tools']['suboptions']['data']       = array('text' => 'Device Data', 'icon' => $config['icon']['device-data']);
      $navbar['options']['tools']['suboptions']['perf']       = array('text' => 'Performance Data', 'icon' => $config['icon']['device-poller']);
      if ($config['web_enable_showtech']) {
        $navbar['options']['tools']['suboptions']['showtech'] = array('text' => 'Show Tech-Support', 'icon' => $config['icon']['techsupport']);
      }
      $navbar['options']['tools']['suboptions']['divider_1']  = array('divider' => TRUE);

      // Connect menu
      $navbar['options']['tools']['suboptions']['connect']       = array('text' => 'Connect', 'icon' => 'sprite-config', 'url' => '#');
      $navbar['options']['tools']['suboptions']['connect']['entries']       = $connect_entries;
      $navbar['options']['tools']['suboptions']['divider_2']  = array('divider' => TRUE);

      $navbar['options']['tools']['suboptions']['delete']['url']  = "#modal-delete_device";
      $navbar['options']['tools']['suboptions']['delete']['text'] = 'Delete Device';
      $navbar['options']['tools']['suboptions']['delete']['link_opts'] = 'data-toggle="modal"';
      $navbar['options']['tools']['suboptions']['delete']['icon'] = $config['icon']['minus'];

      $navbar['options']['tools']['suboptions']['divider_3']  = array('divider' => TRUE);

      // All properties
      $navbar['options']['tools']['suboptions']['edit']       = array('text' => 'Properties', 'icon' => $config['icon']['device-settings']);

    } elseif (safe_count($connect_entries)) {
      // User-only cog menu
      $navbar['options']['tools']                                     = array('text' => '', 'icon' => $config['icon']['device-tools'], 'url' => '#', 'right' => TRUE, 'class' => "dropdown-toggle");
      $navbar['options']['tools']['suboptions']['connect']            = array('text' => 'Connect', 'icon' => 'sprite-config', 'url' => '#');
      $navbar['options']['tools']['suboptions']['connect']['entries'] = $connect_entries;
      $navbar['options']['tools']['suboptions']['divider_2']          = array('divider' => TRUE);
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

// Make Secondary Dropdown on Hover
    $('.dropdown-submenu a.dropdown-submenu-toggle').hover(function(){
        $('.dropdown-submenu ul').removeAttr('style');
        $(this).next('ul').toggle();
    });


</script>

<?php

    $navbar['class'] = 'navbar-narrow subnav';
    $navbar['brand'] = $device['hostname'];
    $navbar['brand-class'] = 'fixed-only';

    foreach ($navbar['options'] as $option => $array)
    {
      if (!isset($vars['tab'])) { $vars['tab'] = $option; }
      if ($vars['tab'] === $option) { $navbar['options'][$option]['class'] .= " active"; }
      if (!isset($navbar['options'][$option]['url'])) {
        $navbar['options'][$option]['url'] = generate_device_url($device, array('tab' => $option));
      }

      if (isset($navbar['options'][$option]['suboptions'])) {
        foreach ($navbar['options'][$option]['suboptions'] as $sub_option => $sub_array) {
          if (!isset($navbar['options'][$option]['suboptions'][$sub_option]['url'])) {
            $navbar['options'][$option]['suboptions'][$sub_option]['url'] = generate_device_url($device, array('tab' => $sub_option));
          }
        }
      }
    }

    if ($vars['tab'] === 'port')        { $navbar['options']['ports']['class'] .= " active"; }
    if ($vars['tab'] === 'alert')       { $navbar['options']['alerts']['class'] .= " active"; }
    if ($vars['tab'] === 'accesspoint') { $navbar['options']['accesspoints']['class'] .= " active"; }

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
