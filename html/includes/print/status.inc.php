<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

/**
 * Display status alerts.
 *
 * Display pages with alerts about device troubles.
 * Examples:
 * print_status(array('devices' => TRUE)) - display for devices down
 *
 * Another statuses:
 * devices, uptime, ports, errors, services, bgp
 *
 * @param array $options
 * @return none
 *
 */
function print_status_old($options)
{
  global $config;

  $max_interval = filter_var($options['max']['interval'], FILTER_VALIDATE_INT, array('options' => array('default' => 24,  'min_range' => 1)));
  $max_count    = filter_var($options['max']['count'],    FILTER_VALIDATE_INT, array('options' => array('default' => 200, 'min_range' => 1)));

   $string  = '<table class="table  table-striped table-hover table-condensed">' . PHP_EOL;
 /* $string .= '  <thead>' . PHP_EOL;
  $string .= '  <tr>' . PHP_EOL;
  $string .= '    <th>Device</th>' . PHP_EOL;
  $string .= '    <th>Type</th>' . PHP_EOL;
  $string .= '    <th>Status</th>' . PHP_EOL;
  $string .= '    <th>Entity</th>' . PHP_EOL;
  // $string .= '    <th>Location</th>' . PHP_EOL;
  $string .= '    <th>Information</th>' . PHP_EOL;
  $string .= '  </tr>' . PHP_EOL;
  $string .= '  </thead>' . PHP_EOL;
  $string .= '  <tbody>' . PHP_EOL;
 */

  $query_device_permitted = generate_query_permitted(array('device'), array('device_table' => 'D', 'hide_ignored' => TRUE));
  $query_port_permitted   = generate_query_permitted(array('port'),   array('port_table' => 'I',   'hide_ignored' => TRUE));

  // Show Device Status
  if ($options['devices'])
  {
    $query = 'SELECT * FROM `devices` AS D';
    $query .= ' WHERE D.`status` = 0' . $query_device_permitted;
    $query .= ' ORDER BY D.`hostname` ASC';
    $entries = dbFetchRows($query);
    foreach ($entries as $device)
    {
      $string .= '  <tr>' . PHP_EOL;
      $string .= '    <td class="entity">' . generate_device_link($device, short_hostname($device['hostname'])) . '</td>' . PHP_EOL;
      // $string .= '    <td><span class="badge badge-inverse">Device</span></td>' . PHP_EOL;
      $string .= '    <td><span class="label label-important">Device Down</span></td>' . PHP_EOL;
      $string .= '    <td class="entity"><i class="'.$config['icon']['devices'].'"></i> ' . generate_device_link($device, short_hostname($device['hostname'])) . '</td>' . PHP_EOL;
      // $string .= '    <td style="white-space: nowrap">' . escape_html(truncate($device['location'], 30)) . '</td>' . PHP_EOL;
      $string .= '    <td style="white-space: nowrap">' . deviceUptime($device, 'short') . '</td>' . PHP_EOL;
      $string .= '  </tr>' . PHP_EOL;
    }
  }

  // Uptime
  if ($options['uptime'])
  {
    if (filter_var($config['uptime_warning'], FILTER_VALIDATE_FLOAT) !== FALSE && $config['uptime_warning'] > 0)
    {
      $query = 'SELECT * FROM `devices` AS D';
      // Since reboot event more complicated than just device uptime less than some time
      //$query .= ' WHERE D.`status` = 1 AND D.`uptime` > 0 AND D.`uptime` < ' . $config['uptime_warning'];
      $query .= ' WHERE D.`status` = 1 AND D.`uptime` > 0 AND D.`last_rebooted` > ?';
      $query .= $query_device_permitted;
      $query .= 'ORDER BY D.`hostname` ASC';
      $entries = dbFetchRows($query, array($config['time']['now'] - $config['uptime_warning'] - 10));

      foreach ($entries as $device)
      {
        $string .= '  <tr>' . PHP_EOL;
        $string .= '    <td class="entity">' . generate_device_link($device, short_hostname($device['hostname'])) . '</td>' . PHP_EOL;
        // $string .= '    <td><span class="badge badge-inverse">Device</span></td>' . PHP_EOL;
        $string .= '    <td><span class="label label-success">Device Rebooted</span></td>' . PHP_EOL;
        $string .= '    <td class="entity"><i class="'.$config['icon']['devices'].'"></i> ' . generate_device_link($device, short_hostname($device['hostname'])) . '</td>' . PHP_EOL;
        // $string .= '    <td style="white-space: nowrap">' . escape_html(truncate($device['location'], 30)) . '</td>' . PHP_EOL;
        $string .= '    <td style="white-space: nowrap">Uptime ' . format_uptime($device['uptime'], 'short') . '</td>' . PHP_EOL;
        $string .= '  </tr>' . PHP_EOL;
      }
    }
  }

  // Ports Down
  if ($options['ports'] || $options['neighbours'] || $options['links'])
  {
    $options['neighbours'] = $options['neighbours'] && !$options['ports']; // Disable 'neighbours' if 'ports' already enabled

    $query = 'SELECT * FROM `ports` AS I ';
    if ($options['neighbours'])
    {
      $query .= 'INNER JOIN `neighbours` AS L ON I.`port_id` = L.`port_id` ';
    }
    $query .= 'LEFT JOIN `devices` AS D ON I.`device_id` = D.`device_id` ';
    $query .= "WHERE D.`status` = 1 AND D.ignore = 0 AND I.ignore = 0 AND I.deleted = 0 AND I.`ifAdminStatus` = 'up' AND (I.`ifOperStatus` = 'lowerLayerDown' OR I.`ifOperStatus` = 'down') ";
    if ($options['neighbours'])
    {
      $query .= ' AND L.`active` = 1 ';
    }
    $query .= $query_port_permitted;
    $query .= ' AND I.`ifLastChange` >= DATE_SUB(NOW(), INTERVAL '.$max_interval.' HOUR) ';
    if ($options['neighbours']) { $query .= 'GROUP BY L.`port_id` '; }
    $query .= 'ORDER BY I.`ifLastChange` DESC, D.`hostname` ASC, I.`ifDescr` * 1 ASC ';
    $entries = dbFetchRows($query);
    $i = 1;
    foreach ($entries as $port)
    {
      if ($i > $max_count)
      {
        $string .= '  <tr><td></td><td><span class="badge badge-info">Port</span></td>';
        $string .= '<td><span class="label label-important">Port Down</span></td>';
        $string .= '<td colspan=3>Too many ports down. See <strong><a href="'.generate_url(array('page'=>'ports'), array('state'=>'down')).'">All DOWN ports</a></strong>.</td></tr>' . PHP_EOL;
        break;
      }
      humanize_port($port);
      $string .= '  <tr>' . PHP_EOL;
      $string .= '    <td class="entity">' . generate_device_link($port, short_hostname($port['hostname'])) . '</td>' . PHP_EOL;
      // $string .= '    <td><span class="badge badge-info">Port</span></td>' . PHP_EOL;
      $string .= '    <td><span class="label label-important">Port Down</span></td>' . PHP_EOL;
      $string .= '    <td class="entity">' . get_icon('port') . ' ' . generate_port_link_short($port) . '</td>' . PHP_EOL;
      // $string .= '    <td style="white-space: nowrap">' . escape_html(truncate($port['location'], 30)) . '</td>' . PHP_EOL;
      $string .= '    <td style="white-space: nowrap">Down for ' . format_uptime($config['time']['now'] - strtotime($port['ifLastChange']), 'short'); // This is like deviceUptime()
      if ($options['links']) { $string .= ' ('.nicecase($port['protocol']).': ' .$port['remote_hostname'].' / ' .$port['remote_port'] .')'; }
      $string .= '</td>' . PHP_EOL;
      $string .= '  </tr>' . PHP_EOL;
      $i++;
    }
  }

  // Ports Errors (only deltas)
  if ($options['errors'])
  {
    $query = 'SELECT * FROM `ports` AS I ';
    //$query .= 'LEFT JOIN `ports-state` AS E ON I.`port_id` = E.`port_id` ';
    $query .= 'LEFT JOIN `devices` AS D ON I.`device_id` = D.`device_id` ';
    $query .= "WHERE D.`status` = 1 AND I.`ifOperStatus` = 'up' AND (I.`ifInErrors_delta` > 0 OR I.`ifOutErrors_delta` > 0)";
    $query .= $query_port_permitted;
    $query .= 'ORDER BY D.`hostname` ASC, I.`ifDescr` * 1 ASC';
    $entries = dbFetchRows($query);
    foreach ($entries as $port)
    {
      humanize_port($port);
      $string .= '  <tr>' . PHP_EOL;
      $string .= '    <td class="entity">' . generate_device_link($port, short_hostname($port['hostname'])) . '</td>' . PHP_EOL;
      // $string .= '    <td><span class="badge badge-info">Port</span></td>' . PHP_EOL;
      $string .= '    <td><span class="label label-important">Port Errors</span></td>' . PHP_EOL;
      $string .= '    <td class="entity">' . get_icon('port') . ' ' . generate_port_link_short($port, NULL, 'port_errors') . '</td>' . PHP_EOL;
      // $string .= '    <td style="white-space: nowrap">' . escape_html(truncate($port['location'], 30)) . '</td>' . PHP_EOL;
      $string .= '    <td>Errors ';
      if ($port['ifInErrors_delta']) { $string .= 'In: ' . $port['ifInErrors_delta']; }
      if ($port['ifInErrors_delta'] && $port['ifOutErrors_delta']) { $string .= ', '; }
      if ($port['ifOutErrors_delta']) { $string .= 'Out: ' . $port['ifOutErrors_delta']; }
      $string .= '</td>' . PHP_EOL;
      $string .= '  </tr>' . PHP_EOL;
    }
  }

  // BGP
  if ($options['bgp'])
  {
    if (isset($config['enable_bgp']) && $config['enable_bgp'])
    {
      // Description for BGP states
      $bgpstates = 'IDLE - Router is searching routing table to see whether a route exists to reach the neighbor. &#xA;';
      $bgpstates .= 'CONNECT - Router found a route to the neighbor and has completed the three-way TCP handshake. &#xA;';
      $bgpstates .= 'OPEN SENT - Open message sent, with parameters for the BGP session. &#xA;';
      $bgpstates .= 'OPEN CONFIRM - Router received agreement on the parameters for establishing session. &#xA;';
      $bgpstates .= 'ACTIVE - Router did not receive agreement on parameters of establishment. &#xA;';
      //$bgpstates .= 'ESTABLISHED - Peering is established; routing begins.';

      $query = 'SELECT * FROM `devices` AS D ';
      $query .= 'LEFT JOIN `bgpPeers` AS B ON B.`device_id` = D.`device_id` ';
      $query .= "WHERE D.`status` = 1 AND (`bgpPeerAdminStatus` = 'start' OR `bgpPeerAdminStatus` = 'running') AND `bgpPeerState` != 'established' ";
      $query .= $query_device_permitted;
      $query .= 'ORDER BY D.`hostname` ASC';
      $entries = dbFetchRows($query);
      foreach ($entries as $peer)
      {
        humanize_bgp($peer);
        $peer_ip = generate_entity_link("bgp_peer", $peer, $peer['human_remoteip']);

        $string .= '  <tr>' . PHP_EOL;
        $string .= '    <td class="entity">' . generate_device_link($peer, short_hostname($peer['hostname']), array('tab' => 'routing', 'proto' => 'bgp')) . '</td>' . PHP_EOL;
        // $string .= '    <td><span class="badge badge-warning">BGP</span></td>' . PHP_EOL;
        $string .= '    <td><span class="label label-warning" title="' . $bgpstates . '">BGP ' . nicecase($peer['bgpPeerState']) . '</span></td>' . PHP_EOL;
        $string .= '    <td class="entity" style="white-space: nowrap"><i class="'.$config['icon']['bgp'].'"></i> ' . $peer_ip . '</td>' . PHP_EOL;
        // $string .= '    <td style="white-space: nowrap">' . escape_html(truncate($peer['location'], 30)) . '</td>' . PHP_EOL;
        $string .= '    <td><strong>AS' . $peer['human_remote_as'] . ' :</strong> ' . $peer['astext'] . '</td>' . PHP_EOL;
        $string .= '  </tr>' . PHP_EOL;
      }
    }
  }

  // $string .= '  </tbody>' . PHP_EOL;
  $string .= '</table>';

  // Final print all statuses
  echo($string);
}

function generate_alert_entries($vars)
{

  global $alert_rules;
  global $config;

  // This should be set outside, but do it here if it isn't
  if (!is_array($alert_rules)) { $alert_rules = cache_alert_rules(); }
  /// WARN HERE

  $vars['sort'] = 'alert_last_changed';

  list($query, $param, $query_count) = build_alert_table_query($vars);

  // Fetch alerts
  $count  = dbFetchCell($query_count, $param);
  $alerts = dbFetchRows($query, $param);

  foreach ($alerts as $alert)
  {
    $alert_rule = &$alert_rules[$alert['alert_test_id']];
    $alert['severity'] = $alert_rule['severity'];
    humanize_alert_entry($alert);

    $device = device_by_id_cache($alert['device_id']);

    $array[] = array('sev' => 100,
                     'icon_tag'    => '<i class="' . $config['entities'][$alert['entity_type']]['icon'] . '"></i>',
                     'alert_test_id' => $alert['alert_test_id'],
                     'event'       => '<a href="'. generate_url(array('page' => 'alert_check', 'alert_test_id' => $alert_rule['alert_test_id'])). '">'. escape_html($alert_rule['alert_name']). '</a>',
                     'entity_type' => $alert['entity_type'],
                     'entity_id'   => $alert['entity_id'],
                     'entity_link' => ($alert['entity_type'] != "device" ? generate_entity_link($alert['entity_type'], $alert['entity_id'],NULL, NULL, TRUE, TRUE) : NULL),
                     'device_id' => $device['device_id'],
                     'device_link' => generate_device_link($device, short_hostname($device['hostname'])),
                     'time' => $alert['changed']);
  }

  return $array;

}

/**
 * Display status alerts.
 *
 * Display pages with alerts about device troubles.
 * Examples:
 * print_status(array('devices' => TRUE)) - display for devices down
 *
 * Another statuses:
 * devices, uptime, ports, errors, services, bgp
 *
 * @param array $options
 * @return TRUE
 *
 */
function print_status_boxes($options, $limit = NULL)
{
  if(isset($options['widget_type']) && $options['widget_type'] == 'alert_boxes')
  {
    $status_array = generate_alert_entries(array('status' => 'failed')); //, 'entity_id' => '1'));
  } else
  {
    $status_array = get_status_array($options);
    $status_array = array_sort($status_array, 'sev', 'SORT_DESC');
  }

  $count = count($status_array);

  if ($count == 0)
  {
    echo '<div class="alert statusbox alert-info" style="border-left: 1px; width: 80%; height: 75px; margin-left:10%; float:none; display: block;">';
    echo '<div style="margin: auto; line-height: 75px; text-align: center;">There are currently no ongoing alert events.</div>';
    echo '</div>';

    return;
  }

  $i = 1;
  foreach ($status_array as $entry)
  {
    if ($i >= $limit)
    {
      echo('<div class="alert statusbox alert-danger" style="border-left: 1px;">');
      echo '<div style="margin: auto; line-height: 75px; text-align: center;"><b>' . $count . ' more...</b></div>';
      echo('</div>');

      return;
    }

    if ($entry['entity_link'])
    {
      $entry['entity_link'] = preg_replace('/(<a.*?>)(.{0,20}).*(<\/a>)/', '\\1\\2\\3', $entry['entity_link']);
    }

    if ($entry['sev'] > 51)     { $class = "alert-danger"; }
    elseif ($entry['sev'] > 20) { $class = "alert-warning"; }
    else                        { $class = "alert-info"; }

    //if ($entry['wide']) { $class .= ' statusbox-wide'; }

    echo('<div class="alert statusbox ' . $class . '" style="text-align: center;position: relative;">
            <p style="margin: 0; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">');

    echo '<h4>' . $entry['device_link'] . '</h4>';
    echo '' . $entry['event'] . '<br />';
    echo '<h4 style="margin-bottom: 2px;">' . ($entry['entity_link'] ? $entry['icon_tag'] . $entry['entity_link'] : 'Device') . ' </h4>';
    echo '<small>' . $entry['time'] . '</small>';
    echo('</p></div>');

    $count--;
    $i++;
  }

  return TRUE;

}

function generate_status_table($options, $print = FALSE)
{

  $status_array = get_status_array($options);
  $status_array = array_sort($status_array, 'sev', 'SORT_DESC');
  $i = 1; $string = '';
  $string .= '<table style="" class="table table-striped table-hover table-condensed">' . PHP_EOL;

  foreach ($status_array as $entry)
  {
    if ($entry['sev'] > 51)     { $class = "danger"; $row_class = 'error'; }
    elseif ($entry['sev'] > 20) { $class = "warning"; $row_class = 'warning'; }
    else                        { $class = "info"; }

    $string .= '  <tr class="'.$row_class.'">' . PHP_EOL;
    $string .= '    <td class="state-marker"></td>';
    $string .= '    <td class="entity">' . $entry['device_link'] . '</td>' . PHP_EOL;
    $string .= '    <td><span class="label label-'.$class.'" title="' . $entry['event'] . '">'.$entry['class'].' '.$entry['event'].'</span></td>' . PHP_EOL;
    $string .= '    <td class="entity" style="white-space: nowrap">'. $entry['icon_tag'] . ($entry['entity_link'] ? $entry['entity_link'] : $entry['device_link']) . '</td>' . PHP_EOL;
    $string .= '    <td>' . $entry['time'] . '</td>' . PHP_EOL;
    $string .= '  </tr>' . PHP_EOL;
  }
  $string .= '</table>';
  if($print == TRUE)
  {
    echo $string;
    return TRUE;
  } else {
    return $string;
  }
}


// DOCME needs phpdoc block
function get_status_array($options)
{
  // Mike: I know that there are duplicated variables, but later will remove global
  global $config;

  $max_interval = filter_var($options['max']['interval'], FILTER_VALIDATE_INT, array('options' => array('default' => 24, 'min_range' => 1)));
  $max_count    = filter_var($options['max']['count'],    FILTER_VALIDATE_INT, array('options' => array('default' => 200, 'min_range' => 1)));
  $query_device_permitted = generate_query_permitted(array('device'), array('device_table' => 'D', 'hide_ignored' => TRUE, 'hide_disabled' => TRUE));
  $query_port_permitted   = generate_query_permitted(array('port'),   array('port_table' => 'I',   'hide_ignored' => TRUE));

  // Show Device Status
  if ($options['devices'])
  {
    $query = 'SELECT * FROM `devices` AS D ';
    $query .= 'WHERE D.`status` = 0' . $query_device_permitted;
    $query .= 'ORDER BY D.`hostname` ASC';
    $entries = dbFetchRows($query);
    foreach ($entries as $device)
    {
      $boxes[] = array('sev' => 100,
                       'class' => 'Device',
                       'event' => 'Down',
                       'device_link' => generate_device_link($device, short_hostname($device['hostname'])),
                       'time' => deviceUptime($device, 'short-3'),
                       'icon_tag' => '<i class="' . $config['entities']['device']['icon'] . '"></i>');
    }
  }

  // Uptime
  if ($options['uptime'])
  {
    if (filter_var($config['uptime_warning'], FILTER_VALIDATE_FLOAT) !== FALSE && $config['uptime_warning'] > 0)
    {
      $query = 'SELECT * FROM `devices` AS D ';
      // Since reboot event more complicated than just device uptime less than some time
      //$query .= ' WHERE D.`status` = 1 AND D.`uptime` > 0 AND D.`uptime` < ' . $config['uptime_warning'];
      $query .= ' WHERE D.`status` = 1 AND D.`uptime` > 0 AND D.`last_rebooted` > ?';
      $query .= $query_device_permitted;
      $query .= 'ORDER BY D.`hostname` ASC';
      $entries = dbFetchRows($query, array($config['time']['now'] - $config['uptime_warning'] - 10));

      foreach ($entries as $device)
      {
        $boxes[] = array('sev' => 10,
                         'class' => 'Device',
                         'event' => 'Rebooted',
                         'device_link' => generate_device_link($device, short_hostname($device['hostname'])),
                         'time' => deviceUptime($device, 'short-3'),
                         'location' => $device['location'],
                         'icon_tag' => '<i class="' . $config['entities']['device']['icon'] . '"></i>');
      }
    }
  }

  // Ports Down
  if ($options['ports'] || $options['neighbours'])
  {
    $options['neighbours'] = $options['neighbours'] && !$options['ports']; // Disable 'neighbours' if 'ports' already enabled

    $query = 'SELECT * FROM `ports` AS I ';
    if ($options['neighbours'])
    {
      $query .= 'INNER JOIN `neighbours` as L ON I.`port_id` = L.`port_id` ';
    }
    $query .= 'LEFT JOIN `devices` AS D ON I.`device_id` = D.`device_id` ';
    $query .= "WHERE D.`status` = 1 AND D.ignore = 0 AND I.ignore = 0 AND I.deleted = 0 AND I.`ifAdminStatus` = 'up' AND (I.`ifOperStatus` = 'lowerLayerDown' OR I.`ifOperStatus` = 'down') ";
    if ($options['neighbours'])
    {
      $query .= ' AND L.`active` = 1 ';
    }
    $query .= $query_port_permitted;
    $query .= ' AND I.`ifLastChange` >= DATE_SUB(NOW(), INTERVAL '.$max_interval.' HOUR) ';
    if ($options['neighbours'])
    {
      $query .= 'GROUP BY L.`port_id` ';
    }
    $query .= 'ORDER BY I.`ifLastChange` DESC, D.`hostname` ASC, I.`ifDescr` * 1 ASC ';
    $entries = dbFetchRows($query);
    $i = 1;
    foreach ($entries as $port)
    {
      if ($i > $max_count)
      {
        // Limit to 200 ports on overview page
        break;
      }
      //humanize_port($port);
      $boxes[] = array('sev' => 50,
                       'class' => 'Port',
                       'event' => 'Down',
                       'device_link' => generate_device_link($port, short_hostname($port['hostname'])),
                       'entity_link' => generate_port_link_short($port),
                       'time' => format_uptime($config['time']['now'] - strtotime($port['ifLastChange'])),
                       'location' => $device['location'],
                       'icon_tag' => '<i class="' . $config['entities']['port']['icon'] . '"></i>');
    }
  }


  // Ports Errors (only deltas)
  if ($options['errors'])
  {

    foreach ($GLOBALS['cache']['ports']['errored'] as $port_id)
    {
      if (in_array($port_id, $GLOBALS['cache']['ports']['ignored'])) { continue; } // Skip ignored ports
      if (in_array($port['ifType'], $config['ports']['ignore_errors_iftype'])) { continue; } // Skip iftypes we ignore

      $port   = get_port_by_id($port_id);
      $device = device_by_id_cache($port['device_id']);
      humanize_port($port);

      $port['text'] = [];
      if ($port['ifInErrors_delta'])  { $port['text'][] = 'Rx: ' . format_number($port['ifInErrors_delta']) . ' (' . format_number($port['ifInErrors_rate']) . '/s)'; }
      if ($port['ifOutErrors_delta']) { $port['text'][] = 'Tx: ' . format_number($port['ifOutErrors_delta']) . ' ('  . format_number($port['ifOutErrors_rate']) . '/s)'; }

      $port['string'] = implode(', ', $port['text']);

      if($port['ifInErrors_rate'] > 1 || $port['ifOutErrors_rate'] > 1) { $sev = 70; } else { $sev = 45; }

      $boxes[] = array('sev' => $sev,
                       'class' => 'Port',
                       'event' => 'Errors',
                       'device_link' => generate_device_link($device, short_hostname($device['hostname'])),
                       'entity_link' => generate_port_link_short($port),
                       'time' => $port['string'],
                       'location' => $device['location'],
                       'icon_tag' => '<i class="' . $config['entities']['port']['icon'] . '"></i>');
    }
  }

  // BGP
  if ($options['bgp'])
  {
    if (isset($config['enable_bgp']) && $config['enable_bgp'])
    {
      $query = 'SELECT * FROM `bgpPeers` AS B ';
      $query .= 'LEFT JOIN `devices` AS D ON B.`device_id` = D.`device_id` ';
      //$query .= 'LEFT JOIN `bgpPeers-state` AS BS ON B.`bgpPeer_id` = BS.`bgpPeer_id` ';
      $query .= "WHERE D.`status` = 1 AND (`bgpPeerAdminStatus` = 'start' OR `bgpPeerAdminStatus` = 'running') AND `bgpPeerState` != 'established' ";
      $query .= $query_device_permitted;
      $query .= 'ORDER BY D.`hostname` ASC';
      $entries = dbFetchRows($query);
      foreach ($entries as $peer)
      {
        humanize_bgp($peer);
        $peer_ip = generate_entity_link("bgp_peer", $peer, $peer['human_remoteip']);

        $peer['wide'] = (strstr($peer['bgpPeerRemoteAddr'], ':')) ? TRUE : FALSE;
        $boxes[] = array('sev' => 75,
                         'class' => 'BGP Session',
                         'event' => 'Down',
                         'device_link' => generate_device_link($peer, short_hostname($peer['hostname'])),
                         'entity_link' => $peer_ip,
                         'wide' => $peer['wide'],
                         'time' => format_uptime($peer['bgpPeerFsmEstablishedTime'], 'short-3'),
                         'location' => $device['location'],
                         'icon_tag' => '<i class="' . $config['entities']['bgp_peer']['icon'] . '"></i>');
      }
    }
  }

  // Return boxes array
  return $boxes;
}

// EOF
