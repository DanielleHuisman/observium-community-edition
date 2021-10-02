<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

$cache_item = get_cache_item('data');
//print_vars($cache_item->isHit());

if (!ishit_cache_item($cache_item))
{

  // Devices
  $cache['devices'] = array('id'        => array(),
                            'hostname'  => array(),
                            'permitted' => array(),
                            'ignored'   => array(),
                            'disabled'  => array());

  $cache['devices']['stat'] = array('count'    => 0,
                                    'up'       => 0,
                                    'down'     => 0,
                                    'ignored'  => 0,
                                    'disabled' => 0);

  // This code fetches all devices and fills the cache array.
  // This means device_by_id_cache actually never has to do any queries by itself, it'll always get the
  // cached version when running from the web interface. From the commandline obviously we'll need to fetch
  // the data per-device. We pre-fetch the graphs list as well, much faster than a query per device obviously.
  $graphs_array = dbFetchRows("SELECT * FROM `device_graphs` FORCE INDEX (`graph`) ORDER BY `graph`;");

  foreach ($graphs_array as $graph)
  {
    // Cache this per device_id so we can assign it to the correct (cached) device in the for loop below
    if ($graph['enabled'])
    {
      $device_graphs[$graph['device_id']][$graph['graph']] = $graph;
    }
  }
  $cache['graphs'] = array(); // All permitted graphs

  // Cache scheduled maintenance currently active
  $cache['maint'] = cache_alert_maintenance();

  if ($GLOBALS['config']['geocoding']['enable'])
  {
    $devices_array = dbFetchRows("SELECT * FROM `devices` LEFT JOIN `devices_locations` USING (`device_id`) ORDER BY `hostname`;");
  } else {
    $devices_array = dbFetchRows("SELECT * FROM `devices` ORDER BY `hostname`;");
  }
  foreach ($devices_array as $device)
  {
    if (device_permitted($device['device_id']))
    {
      // Process device and add all the human-readable stuff.
      humanize_device($device);

      // Assign device graphs from array created above
      $device['graphs'] = (array)$device_graphs[$device['device_id']];
      $cache['graphs']  = array_unique(array_merge($cache['graphs'], array_keys($device['graphs']))); // Add to global array cache

      $cache['devices']['permitted'][] = (int)$device['device_id']; // Collect IDs for permitted
      $cache['devices']['hostname'][$device['hostname']] = $device['device_id'];
      $cache['devices']['id'][$device['device_id']] = $device;

      if ($device['disabled'])
      {
        $cache['devices']['stat']['disabled']++;
        $cache['devices']['disabled'][] = (int)$device['device_id']; // Collect IDs for disabled
        if (!$config['web_show_disabled']) { continue; }
        // Stat for disabled collect after web_show_disabled
        $cache['devices']['types'][$device['type']]['disabled']++;
      }

      if ($device['ignore'] || (!is_null($device['ignore_until']) && strtotime($device['ignore_until']) > time()) )
      {
        $cache['devices']['stat']['ignored']++;
        $cache['devices']['ignored'][] = (int)$device['device_id']; // Collect IDs for ignored
        $cache['devices']['types'][$device['type']]['ignored']++;
      } else {
        if ($device['status']) {
          $cache['devices']['stat']['up']++;
          $cache['devices']['types'][$device['type']]['up']++;
        } else {
          $cache['devices']['stat']['down']++;
          $cache['devices']['types'][$device['type']]['down']++;
        }
      }

      $cache['devices']['stat']['count']++;

      $cache['devices']['timers']['polling'] += $device['last_polled_timetaken'];
      $cache['devices']['timers']['discovery'] += $device['last_discovered_timetaken'];

      $cache['devices']['types'][$device['type']]['count']++;
      $cache['device_types'][$device['type']]++;
      $cache['device_locations'][$device['location']]++;

      if (isset($config['geocoding']['enable']) && $config['geocoding']['enable'])
      {
        $country_code = $device['location_country'];
        $cache['locations']['entries'][$country_code]['count']++;
        $cache['locations']['entries'][$country_code]['level'] = 'location_country';

        if (isset($config['location_countries_with_counties']) && in_array($country_code, $config['location_countries_with_counties']) )
        {
          $cache['locations']['entries'][$country_code]['entries'][$device['location_county']]['count']++;
          $cache['locations']['entries'][$country_code]['entries'][$device['location_county']]['level'] = 'location_county';

          $cache['locations']['entries'][$country_code]['entries'][$device['location_county']]['entries'][$device['location_city']]['count']++;
          $cache['locations']['entries'][$country_code]['entries'][$device['location_county']]['entries'][$device['location_city']]['level'] = 'location_city';

          $cache['locations']['entries'][$country_code]['entries'][$device['location_county']]['entries'][$device['location_city']]['entries'][$device['location']]['count']++;
          $cache['locations']['entries'][$country_code]['entries'][$device['location_county']]['entries'][$device['location_city']]['entries'][$device['location']]['level'] = 'location';
        } //county only
        else
        { //check for county and states
          if (isset($config['location_countries_with_counties_and_states']) && in_array($country_code, $config['location_countries_with_counties_and_states']) )
          {
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['count']++;
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['level'] = 'location_state';

            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_county']]['count']++;
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_county']]['level'] = 'location_county';

            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_county']]['entries'][$device['location_city']]['count']++;
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_county']]['entries'][$device['location_city']]['level'] = 'location_city';

            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_county']]['entries'][$device['location_city']]['entries'][$device['location']]['count']++;
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_county']]['entries'][$device['location_city']]['entries'][$device['location']]['level'] = 'location';
          } //state and county
          else
          {
            //state only
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['count']++;
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['level'] = 'location_state';
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_city']]['count']++;
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_city']]['level'] = 'location_city';
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_city']]['entries'][$device['location']]['count']++;
            $cache['locations']['entries'][$country_code]['entries'][$device['location_state']]['entries'][$device['location_city']]['entries'][$device['location']]['level'] = 'location';
          } //state only
        } //county only / else
      }
    }
  }
  sort($cache['graphs']);

  // Ports

  $cache['ports'] = array(//'id'              => array(),
                          //'permitted'       => array(),
                          'ignored'         => array(),
                          'errored'         => array(),
                          //'disabled'        => array(),
                          'poll_disabled'   => array(),
                          'device_ignored'  => array(),
                          'device_disabled' => array(),
                          'deleted'         => array());

  $cache['ports']['stat'] = array('count'    => 0,
                                  'up'       => 0,
                                  'down'     => 0,
                                  'ignored'  => 0,
                                  'shutdown' => 0,
                                  'errored'  => 0,
                                  'alerts'   => 0,
                                  'deleted'  => 0);


  $where_permitted = generate_query_permitted(array('device', 'port'));

  $where_hide      = " AND `deleted` = 0";

  // Deleted
  $cache['ports']['deleted']            = dbFetchColumn("SELECT `port_id` FROM `ports` WHERE 1 " . $where_permitted . " AND `deleted` = 1");
  $cache['ports']['stat']['deleted']    = count($cache['ports']['deleted']);

  // Devices disabled
  if (isset($cache['devices']['disabled']) && count($cache['devices']['disabled']) > 0)
  {
    $cache['ports']['device_disabled'] = dbFetchColumn("SELECT `port_id` FROM `ports` WHERE 1 " . $where_permitted . generate_query_values($cache['devices']['disabled'], 'device_id'));
    if (!$config['web_show_disabled'])
    {
      $where_hide  .= generate_query_values($cache['devices']['disabled'], 'device_id', '!=');
    }
  }

  // Devices ignored
  $where_devices_ignored = '';
  if (isset($cache['devices']['ignored']) && count($cache['devices']['ignored']) > 0)
  {
    $cache['ports']['device_ignored'] = dbFetchColumn("SELECT `port_id` FROM `ports` WHERE 1 " . $where_permitted . $where_hide . generate_query_values($cache['devices']['ignored'], 'device_id'));
    $where_hide  .= generate_query_values($cache['devices']['ignored'], 'device_id', '!=');
    $where_devices_ignored = generate_query_values($cache['devices']['ignored'], 'device_id');
  }
  $cache['ports']['stat']['device_ignored']   = count($cache['ports']['device_ignored']);

  // Ports poll disabled
  $cache['ports']['poll_disabled']            = dbFetchColumn("SELECT `port_id` FROM `ports` WHERE 1 " . $where_permitted . $where_hide . " AND `disabled` = '1'");
  $cache['ports']['stat']['poll_disabled']    = count($cache['ports']['poll_disabled']);

  // Ports ignored
  $cache['ports']['ignored'] = dbFetchColumn("SELECT `port_id` FROM `ports` WHERE 1 " . $where_permitted . $where_hide . " AND (`ignore` = '1')");
  $cache['ports']['stat']['ignored']          = count($cache['ports']['ignored']);

  // r("SELECT `port_id` FROM `ports` WHERE 1 " . $where_permitted . $where_hide . " AND (`ignore` = '1'" . $where_devices_ignored . ")");
  //r($cache['ports']['ignored']);

  $where_hide      .= " AND `ignore` = 0";

  // Ports errored
  $cache['ports']['errored'] = dbFetchColumn("SELECT `port_id` FROM `ports` WHERE 1 " . $where_permitted . $where_hide . " AND `ifAdminStatus` = 'up' AND (`ifOperStatus` = 'up' OR `ifOperStatus` = 'testing') AND (`ifOutErrors_delta` > 0 OR `ifInErrors_delta` > 0)");
  //$cache['ports']['errored'] = dbFetchColumn("SELECT `port_id` FROM `ports` LEFT JOIN `ports-state` USING(`port_id`) WHERE 1 " . $where_permitted . $where_hide . " AND `ifAdminStatus` = 'up' AND (`ifOperStatus` = 'up' OR `ifOperStatus` = 'testing') AND (`ifOutErrors_delta` > 0 OR `ifInErrors_delta` > 0)");
  $cache['ports']['stat']['errored']          = count($cache['ports']['errored']);

  // Ports counts
  $cache['ports']['stat']['count']            = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE 1 " . $where_permitted . $where_hide);
  $cache['ports']['stat']['shutdown']         = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE 1 " . $where_permitted . $where_hide . " AND `ifAdminStatus` = ?", array('down'));
  $cache['ports']['stat']['down']             = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE 1 " . $where_permitted . $where_hide . " AND `ifAdminStatus` = ? AND `ifOperStatus` IN (?, ?) AND `ports`.`disabled` = '0' AND `ports`.`deleted` = '0'",    array('up', 'down', 'lowerLayerDown'));
  $cache['ports']['stat']['up']               = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE 1 " . $where_permitted . $where_hide . " AND `ifAdminStatus` = ? AND `ifOperStatus` IN (?, ?, ?)", array('up',   'up', 'testing', 'monitoring'));

  //r($where_hide);
  //r($cache['devices']);
  //r($cache['ports']);
  //r($cache['ports']['stat']);
  //r($ports_db);
  //r($permissions);

  // Sensors
  $cache['sensors']['stat'] = array('count'    => 0,
                                    'ok'       => 0,
                                    'alert'    => 0,
                                    'warning'  => 0,
                                    'ignored'  => 0,
                                    'device_ignored' => 0,
                                    'disabled' => 0,
                                    'deleted'  => 0);
  $cache['sensors']['devices'] = array(); // Stats per device ids
  $cache['sensor_types']       = array(); // FIXME -> $cache['sensors']['types']

  $sensors_array = dbFetchRows('SELECT `device_id`, `sensor_id`, `sensor_class`, `sensor_type`, `sensor_ignore`, `sensor_disable`,
                                       `sensor_value`, `sensor_deleted`, `sensor_event` FROM `sensors` WHERE 1 ' . generate_query_permitted(array('device', 'sensor')));

  foreach ($sensors_array as $sensor)
  {

    //if (!is_entity_permitted($sensor['sensor_id'], 'sensor', $sensor['device_id'])) { continue; } // Check device permitted

    if (!$config['web_show_disabled'])
    {
      if ($cache['devices']['id'][$sensor['device_id']]['disabled']) { continue; }
    }

    if ($sensor['sensor_deleted']) { $cache['sensors']['stat']['deleted']++; continue; }

    $cache['sensors']['stat']['count']++;
    $cache['sensors']['devices'][$sensor['device_id']]['count']++;
    $cache['sensors']['types'][$sensor['sensor_class']]['count']++;
    $cache['sensor_types'][$sensor['sensor_class']]['count']++;

    if ($sensor['sensor_disable'])
    {
      $cache['sensors']['stat']['disabled']++;
      $cache['sensors']['devices'][$sensor['device_id']]['disabled']++;
      $cache['sensors']['types'][$sensor['sensor_class']]['disabled']++;
      continue;
    }

    if ($sensor['sensor_event'] === 'ignore' || $sensor['sensor_ignore'] ||
        in_array($sensor['device_id'], $cache['devices']['ignored']))
    {
      $cache['sensors']['stat']['ignored']++;
      $cache['sensors']['devices'][$sensor['device_id']]['ignored']++;
      $cache['sensors']['types'][$sensor['sensor_class']]['ignored']++;
      continue;
    }

    switch ($sensor['sensor_event'])
    {
      case 'warning':
        $cache['sensors']['stat']['warning']++;
        $cache['sensors']['devices'][$sensor['device_id']]['warning']++;
        $cache['sensors']['types'][$sensor['sensor_class']]['warning']++;
        break;
      case 'ok':
        $cache['sensors']['stat']['ok']++;
        $cache['sensors']['devices'][$sensor['device_id']]['ok']++;
        $cache['sensors']['types'][$sensor['sensor_class']]['ok']++;
        break;
      case 'alert':
        $cache['sensors']['stat']['alert']++;
        $cache['sensors']['devices'][$sensor['device_id']]['alert']++;
        $cache['sensors']['types'][$sensor['sensor_class']]['alert']++;
        $cache['sensor_types'][$sensor['sensor_class']]['alert']++;
        break;
      default:
        $cache['sensors']['stat']['alert']++; // unknown event (empty) also alert
        $cache['sensors']['devices'][$sensor['device_id']]['alert']++;
        $cache['sensors']['types'][$sensor['sensor_class']]['alert']++;
    }
  }
  //r($cache['sensors']);
  //r($cache['sensor_types']);

  // Statuses
  $cache['statuses']['stat'] = array('count'    => 0,
                                     'ok'       => 0,
                                     'alert'    => 0,
                                     'warning'  => 0,
                                     'ignored'  => 0,
                                     'disabled' => 0,
                                     'deleted'  => 0);
  $cache['statuses']['devices'] = array(); // Stats per device id
  $cache['status_classes'] = array();      // FIXME -> $cache['statuses']['classes']

  $status_array = dbFetchRows('SELECT `device_id`, `status_id`, `entPhysicalClass`, `status_ignore`, `status_disable`,
                                      `status_deleted`, `status_event` FROM `status`');

  foreach ($status_array as $status)
  {
    if (!isset($cache['devices']['id'][$status['device_id']])) { continue; } // Check device permitted

    if (!$config['web_show_disabled'])
    {
      if ($cache['devices']['id'][$status['device_id']]['disabled']) { continue; }
    }
    if ($status['status_deleted']) { $cache['statuses']['stat']['deleted']++; continue; }

    $cache['statuses']['stat']['count']++;
    $cache['statuses']['devices'][$status['device_id']]['count']++;
    $cache['status_classes'][$status['entPhysicalClass']]['count']++;

    if ($status['status_disable'])
    {
      $cache['statuses']['stat']['disabled']++;
      $cache['statuses']['devices'][$status['device_id']]['disabled']++;
      continue;
    }

    if ($status['status_event'] === 'ignore' || $status['status_ignore']  ||
        in_array($status['device_id'], $cache['devices']['ignored']))
    {
      $cache['statuses']['stat']['ignored']++;
      $cache['statuses']['devices'][$status['device_id']]['ignored']++;
      continue;
    }

    switch ($status['status_event'])
    {
      case 'warning':
        $cache['statuses']['stat']['warning']++; // 'warning' also 'ok', hrm but now I not sure
        $cache['statuses']['devices'][$status['device_id']]['warning']++;
        break;
      case 'ok':
        $cache['statuses']['stat']['ok']++;
        $cache['statuses']['devices'][$status['device_id']]['ok']++;
        break;
      case 'alert':
        $cache['statuses']['stat']['alert']++;
        $cache['statuses']['devices'][$status['device_id']]['alert']++;
        $cache['status_classes'][$status['entPhysicalClass']]['alert']++;
        break;
      default:
        $cache['statuses']['stat']['alert']++; // unknown event (empty) also alert
        $cache['statuses']['devices'][$status['device_id']]['alert']++;
    }
  }
  //r($cache['statuses']);

  // Counters
  $cache['counters']['stat'] = array('count'    => 0,
                                    'ok'       => 0,
                                    'alert'    => 0,
                                    'warning'  => 0,
                                    'ignored'  => 0,
                                    'disabled' => 0,
                                    'deleted'  => 0);
  $cache['counters']['devices'] = array(); // Stats per device ids

  $counters_array = dbFetchRows('SELECT `device_id`, `counter_id`, `counter_class`, `counter_ignore`, `counter_disable`,
                                       `counter_value`, `counter_deleted`, `counter_event` FROM `counters` WHERE 1 ' . generate_query_permitted(array('device', 'counter')));

  foreach ($counters_array as $counter)
  {

    //if (!is_entity_permitted($counter['counter_id'], 'counter', $counter['device_id'])) { continue; } // Check device permitted

    if (!$config['web_show_disabled'])
    {
      if ($cache['devices']['id'][$counter['device_id']]['disabled']) { continue; }
    }

    if ($counter['counter_deleted']) { $cache['counters']['stat']['deleted']++; continue; }

    $cache['counters']['stat']['count']++;
    $cache['counters']['devices'][$counter['device_id']]['count']++;
    //$cache['counters']['types'][$counter['counter_class']]['count']++;

    if ($counter['counter_disable'])
    {
      $cache['counters']['stat']['disabled']++;
      $cache['counters']['devices'][$counter['device_id']]['disabled']++;
      //$cache['counters']['types'][$counter['counter_class']]['disabled']++;
      continue;
    }

    if ($counter['counter_event'] === 'ignore' || $counter['counter_ignore']  ||
        in_array($counter['device_id'], $cache['devices']['ignored']))
    {
      $cache['counters']['stat']['ignored']++;
      $cache['counters']['devices'][$counter['device_id']]['ignored']++;
      //$cache['counters']['types'][$counter['counter_class']]['ignored']++;
      continue;
    }

    switch ($counter['counter_event'])
    {
      case 'warning':
        $cache['counters']['stat']['warning']++;
        $cache['counters']['devices'][$counter['device_id']]['warning']++;
        //$cache['counters']['types'][$counter['counter_class']]['warning']++;
        break;
      case 'ok':
        $cache['counters']['stat']['ok']++;
        $cache['counters']['devices'][$counter['device_id']]['ok']++;
        //$cache['counters']['types'][$counter['counter_class']]['ok']++;
        break;
      case 'alert':
        $cache['counters']['stat']['alert']++;
        $cache['counters']['devices'][$counter['device_id']]['alert']++;
        //$cache['counters']['types'][$counter['counter_class']]['alert']++;
        break;
      default:
        $cache['counters']['stat']['alert']++; // unknown event (empty) also alert
        $cache['counters']['devices'][$counter['device_id']]['alert']++;
        //$cache['counters']['types'][$counter['counter_class']]['alert']++;
    }
  }
  //r($cache['counters']);

  // Alerts

  $query = 'SELECT `alert_status` FROM `alert_table`';
  $query .= ' WHERE 1' . generate_query_permitted(array('alert'));

  $alert_entries = dbFetchRows($query);
  $cache['alert_entries'] = array('count'    => safe_count($alert_entries),
                                  'up'       => 0,
                                  'down'     => 0,
                                  'unknown'  => 0,
                                  'delay'    => 0,
                                  'suppress' => 0);

  foreach ($alert_entries as $alert_table_id => $alert_entry) {
    switch ($alert_entry['alert_status']) {
      case '0':
        ++$cache['alert_entries']['down'];
        break;
      case '1':
        ++$cache['alert_entries']['up'];
        break;
      case '2':
        ++$cache['alert_entries']['delay'];
        break;
      case '3':
        ++$cache['alert_entries']['suppress'];
        break;
      case '-1': // FIXME, what mean status '-1'?
      default:
        ++$cache['alert_entries']['unknown'];
    }
  }

  // Routing

  // BGP
  if (isset($config['enable_bgp']) && $config['enable_bgp']) {
    // Init variables to 0
    $cache['routing']['bgp']['internal'] = 0;
    $cache['routing']['bgp']['external'] = 0;
    $cache['routing']['bgp']['count'] = 0;
    $cache['routing']['bgp']['up'] = 0;
    $cache['routing']['bgp']['down'] = 0;

    $cache['routing']['bgp']['last_seen'] = $config['time']['now'];
    foreach (dbFetchRows('SELECT `device_id`,`bgpPeer_id`,`local_as`,`bgpPeerState`,`bgpPeerAdminStatus`,`bgpPeerRemoteAs` FROM `bgpPeers`;') as $bgp) {
      if (!$config['web_show_disabled']) {
        if ($cache['devices']['id'][$bgp['device_id']]['disabled']) { continue; }
      }
      if (device_permitted($bgp)) {
        $cache['routing']['bgp']['count']++;
        $cache['bgp']['permitted'][] = (int)$bgp['bgpPeer_id']; // Collect permitted peers
        if ($bgp['bgpPeerAdminStatus'] === 'start' || $bgp['bgpPeerAdminStatus'] === 'running') {
          $cache['routing']['bgp']['up']++;
          $cache['bgp']['start'][] = (int)$bgp['bgpPeer_id']; // Collect START peers (bgpPeerAdminStatus = (start || running))
          if ($bgp['bgpPeerState'] !== 'established') {
            $cache['routing']['bgp']['alerts']++;
          } else {
            $cache['bgp']['up'][] = (int)$bgp['bgpPeer_id']; // Collect UP peers (bgpPeerAdminStatus = (start || running), bgpPeerState = established)
          }
        } else {
          $cache['routing']['bgp']['down']++;
        }
        if ($bgp['local_as'] == $bgp['bgpPeerRemoteAs']) {
          $cache['routing']['bgp']['internal']++;
          $cache['bgp']['internal'][] = (int)$bgp['bgpPeer_id']; // Collect iBGP peers
        } else {
          $cache['routing']['bgp']['external']++;
          $cache['bgp']['external'][] = (int)$bgp['bgpPeer_id']; // Collect eBGP peers
        }
      }
    }
  }

  // OSPF
  if (isset($config['enable_ospf']) && $config['enable_ospf'])
  {
    $cache['routing']['ospf']['last_seen'] = $config['time']['now'];
    foreach (dbFetchRows("SELECT `device_id`, `ospfAdminStat` FROM `ospf_instances`") as $ospf)
    {
      if (!$config['web_show_disabled'])
      {
        if ($cache['devices']['id'][$ospf['device_id']]['disabled']) { continue; }
      }
      if (device_permitted($ospf))
      {
        if ($ospf['ospfAdminStat'] == 'enabled')
        {
          $cache['routing']['ospf']['up']++;
        }
        else if ($ospf['ospfAdminStat'] == 'disabled')
        {
          $cache['routing']['ospf']['down']++;
        } else {
          continue;
        }
        $cache['routing']['ospf']['count']++;
      }
    }
  }

//  if (isset($config['enable_eigrp']) && $config['enable_eigrp']) {
    $cache['routing']['eigrp']['last_seen'] = $config['time']['now'];
    foreach (dbFetchRows("SELECT `device_id` FROM `eigrp_vpns`") as $eigrp)
    {
      if (!$config['web_show_disabled'])
      {
        if ($cache['devices']['id'][$eigrp['device_id']]['disabled'])
        {
          continue;
        }
      }
      if (device_permitted($ospf))
      {
        $cache['routing']['eigrp']['count']++;
      }
    }
 // }

  // Common permission sql query
  //r(range_to_list($cache['devices']['permitted']));
  //unset($cache['devices']['permitted']);
  //$cache['where']['devices_ports_permitted'] = generate_query_permitted(array('device', 'port'));
  $cache['where']['devices_permitted']       = generate_query_permitted(array('device'));
    // This needs to do both, otherwise it only permits permitted ports and not ports on permitted devices.s
  $cache['where']['ports_permitted']         = generate_query_permitted(array('port', 'device'));

  // CEF
  $cache['routing']['cef']['count'] = safe_count(dbFetchColumn("SELECT `cef_switching_id` FROM `cef_switching` WHERE 1 ".$cache['where']['devices_permitted']." GROUP BY `device_id`, `afi`;"));
  // VRF
  if ($config['enable_vrfs']) {
    $cache['routing']['vrf']['count'] = safe_count(dbFetchColumn("SELECT DISTINCT `vrf_rd` FROM `vrfs` WHERE 1 " . $cache['where']['devices_permitted']));
  }

  // Status
  $cache['status']['count'] = $cache['statuses']['stat']['count']; //dbFetchCell("SELECT COUNT(`status_id`) FROM `status` WHERE 1 ".$cache['where']['devices_permitted']);
  // Counter
  $cache['counter']['count'] = $cache['counters']['stat']['count'];

  // Additional common counts
  if ($config['enable_pseudowires'])
  {
    $cache['ports']['pseudowires'] = dbFetchColumn('SELECT DISTINCT `port_id` FROM `pseudowires` WHERE 1 '.$cache['where']['ports_permitted']);
    $cache['pseudowires']['count'] = safe_count($cache['ports']['pseudowires']);
  }
  if ($config['poller_modules']['cisco-cbqos'] || $config['discovery_modules']['cisco-cbqos'])
  {
    $cache['ports']['cbqos'] = dbFetchColumn('SELECT DISTINCT `port_id` FROM `ports_cbqos` WHERE 1 '.$cache['where']['ports_permitted']);
    $cache['cbqos']['count'] = safe_count($cache['ports']['cbqos']);
  }
  if ($config['poller_modules']['mac-accounting'] || $config['discovery_modules']['mac-accounting'])
  {
    $cache['ports']['mac_accounting'] = dbFetchColumn('SELECT DISTINCT `port_id` FROM `mac_accounting` WHERE 1 '.$cache['where']['ports_permitted']);
    $cache['mac_accounting']['count'] = safe_count($cache['ports']['mac_accounting']);
  }

//  if ($config['poller_modules']['unix-agent'])
//  {
    $cache['packages']['count'] = dbFetchCell("SELECT COUNT(*) FROM `packages` WHERE 1 ".$cache['where']['devices_permitted']);
//  }

  if ($config['poller_modules']['applications'])
  {
    $cache['applications']['count'] = dbFetchCell("SELECT COUNT(*) FROM `applications` WHERE 1 ".$cache['where']['devices_permitted']);
  }
  if ($config['poller_modules']['wifi'] || $config['discovery_modules']['wifi'])
  {
    $cache['wifi_sessions']['count'] = dbFetchCell("SELECT COUNT(*) FROM `wifi_sessions` WHERE 1 ".$cache['where']['devices_permitted']);
  }
  if ($config['poller_modules']['printersupplies'] || $config['discovery_modules']['printersupplies'])
  {
    $cache['printersupplies']['count'] = dbFetchCell("SELECT COUNT(*) FROM `printersupplies` WHERE 1 ".$cache['where']['devices_permitted']);
  }

  $cache['neighbours']['count'] = dbFetchCell("SELECT COUNT(*) FROM `neighbours` WHERE `active` = 1 "  . $cache['where']['ports_permitted']);

  //r("SELECT COUNT(*) FROM `neighbours` WHERE `active` = 1 "  . $cache['where']['ports_permitted']);

  $cache['sla']['count']        = dbFetchCell("SELECT COUNT(*) FROM `slas` WHERE `deleted` = 0 "       . $cache['where']['devices_permitted']);
  $cache['p2pradios']['count']  = dbFetchCell("SELECT COUNT(*) FROM `p2p_radios` WHERE `deleted` = 0 " . $cache['where']['devices_permitted']);
  $cache['vm']['count']         = dbFetchCell("SELECT COUNT(*) FROM `vminfo` WHERE 1 "                 . $cache['where']['devices_permitted']);

  // Clean arrays (from DB queries)
  unset($devices_array, $ports_array, $sensors_array, $status_array, $graphs_array, $device_graphs);
  // Clean variables (generated by foreach)
  unset($device, $port, $sensor, $status, $bgp, $ospf);

  // Store $cache in fast caching
  set_cache_item($cache_item, $cache);

  // Clear expired cache
  del_cache_expired();

} // End build cache
else {

  //print_vars(get_cache_data($cache_item));
  $cache = array_merge(get_cache_data($cache_item), $cache);

}
// Clean cache item
unset($cache_item);
//del_cache_expired();
//print_vars(get_cache_items('__wui'));
//print_vars(get_cache_stats());

// EOF


//$a = dbFetchCell("SELECT COUNT(*) FROM `ports` WHERE 1 AND (( (`device_id` != '' AND `device_id` IS NOT NULL)) OR ((`port_id` != '' AND `port_id` IS NOT NULL))) AND `deleted` = 0 AND `ignore` = 0");
//$b = dbFetchCell("SELECT COUNT(*) FROM `ports` INNER JOIN `devices` USING (`device_id`) WHERE `ports`.`ignore` = '0' AND `ports`.`disabled` = '0' AND `ports`.`deleted` = '0'");
//$c = dbFetchCell("SELECT COUNT(*) FROM `ports` INNER JOIN `devices` USING (`device_id`) WHERE 1 AND `ports`.`disabled` = '0' AND `ports`.`deleted` = '0'");


//r($a, $b, $c);
