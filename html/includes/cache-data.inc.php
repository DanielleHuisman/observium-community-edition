<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

$cache_data_start = microtime(TRUE);
$cache_item       = get_cache_item('data');
//print_vars($cache_item->isHit());

if (!ishit_cache_item($cache_item)) {

    // Devices
    $cache['devices'] = [
        'id'        => [],
        'hostname'  => [],
        'permitted' => [],
        'ignored'   => [],
        'down'      => [],
        'up'        => [],
        'disabled'  => []
    ];

    $cache['devices']['stat'] = [
        'count'    => 0,
        'up'       => 0,
        'down'     => 0,
        'ignored'  => 0,
        'disabled' => 0
    ];

    // This code fetches all devices and fills the cache array.
    // This means device_by_id_cache actually never has to do any queries by itself, it'll always get the
    // cached version when running from the web interface. From the commandline obviously we'll need to fetch
    // the data per-device. We pre-fetch the graphs list as well, much faster than a query per device obviously.
    //$graphs_array = dbFetchRows("SELECT * FROM `device_graphs` FORCE INDEX (`graph`) ORDER BY `graph`;");

    $cache['graphs'] = [];
    foreach (dbFetchRows("SELECT `graph` FROM `device_graphs` GROUP BY `graph` ORDER BY `graph`;") as $entry) {
        $cache['graphs'][$entry['graph']] = $entry['graph'];
    }

    // Cache scheduled maintenance currently active
    $cache['maint'] = cache_alert_maintenance();


    $select = "`device_id`, `hostname`, `status`, `disabled`, `type`, `ignore`, `ignore_until`, `last_polled_timetaken`, `last_discovered_timetaken`, `devices`.`location`";

    if ($GLOBALS['config']['geocoding']['enable']) {
        $select .= ', `devices_locations`.`location_country`, `devices_locations`.`location_state`, `devices_locations`.`location_county`, `devices_locations`.`location_city`';
        $devices_query = "SELECT " . $select . " FROM `devices` LEFT JOIN `devices_locations` USING (`device_id`) ORDER BY `hostname`;";
    } else {
        $devices_query = "SELECT " . $select . " FROM `devices` ORDER BY `hostname`;";
    }

    foreach (dbFetchRows($devices_query) as $device) {
        if (!device_permitted($device['device_id'])) {
            continue;
        }

        // Initialize types and device_locations if not already set
        if (!isset($cache['devices']['types'][$device['type']])) {
            $cache['devices']['types'][$device['type']] = [ 'up' => 0, 'down' => 0, 'disabled' => 0, 'ignored' => 0, 'count' => 0 ];
        }

        $cache['devices']['permitted'][]                        = (int)$device['device_id']; // Collect IDs for permitted
        $cache['devices']['hostname'][$device['hostname']]      = (int)$device['device_id'];
        $cache['devices']['hostname_map'][$device['device_id']] = $device['hostname'];

        //if (empty($device['device_id'])) { r($device); }

        if ($device['disabled']) {
            $cache['devices']['stat']['disabled']++;
            $cache['devices']['disabled'][] = (int)$device['device_id']; // Collect IDs for disabled
            if (!$GLOBALS['config']['web_show_disabled']) {
                continue;
            }
            // Stat for disabled collect after web_show_disabled
            $cache['devices']['types'][$device['type']]['disabled']++;
        }

        if ($device['ignore'] || (!is_null($device['ignore_until']) && strtotime($device['ignore_until']) > time())) {
            $cache['devices']['stat']['ignored']++;
            $cache['devices']['ignored'][] = (int)$device['device_id']; // Collect IDs for ignored
            $cache['devices']['types'][$device['type']]['ignored']++;
        } else {
            if ($device['status']) {
                $cache['devices']['stat']['up']++;
                $cache['devices']['types'][$device['type']]['up']++;
                $cache['devices']['up'][] = (int)$device['device_id'];
            } else {
                $cache['devices']['stat']['down']++;
                $cache['devices']['types'][$device['type']]['down']++;
                $cache['devices']['down'][] = (int)$device['device_id'];
            }
        }

        $cache['devices']['stat']['count']++;

        $cache['devices']['timers']['polling']   += $device['last_polled_timetaken'];
        $cache['devices']['timers']['discovery'] += $device['last_discovered_timetaken'];

        $cache['devices']['types'][$device['type']]['count']++;

        if (!isset($cache['device_locations'][$device['location']])) {
            $cache['device_locations'][$device['location']] = 0;
        }
        $cache['device_locations'][$device['location']]++;

        if (isset($config['geocoding']['enable']) && $config['geocoding']['enable']) {
            $country_code = (string)$device['location_country'];
            $state        = (string)$device['location_state'];
            $county       = (string)$device['location_county'];
            $city         = (string)$device['location_city'];
            $location     = (string)$device['location'];

            $location_levels = [
                'location_country' => &$cache['locations']['entries'][$country_code],
            ];

            // Unknown locations without city & state
            $no_city = in_array($country_code, [ 'Unknown', '' ], TRUE) || ($city === 'Unknown' && $state === 'Unknown');

            if (!empty($config['location_countries_with_counties']) && in_array($country_code, $config['location_countries_with_counties'])) {
                $location_levels['location_county'] = &$cache['locations']['entries'][$country_code]['entries'][$county];
                $location_levels['location_city']   = &$cache['locations']['entries'][$country_code]['entries'][$county]['entries'][$city];
                $location_levels['location']        = &$cache['locations']['entries'][$country_code]['entries'][$county]['entries'][$city]['entries'][$location];
            } elseif (!empty($config['location_countries_with_counties_and_states']) && in_array($country_code, $config['location_countries_with_counties_and_states'])) {
                $location_levels['location_state']  = &$cache['locations']['entries'][$country_code]['entries'][$state];
                $location_levels['location_county'] = &$cache['locations']['entries'][$country_code]['entries'][$state]['entries'][$county];
                $location_levels['location_city']   = &$cache['locations']['entries'][$country_code]['entries'][$state]['entries'][$county]['entries'][$city];
                $location_levels['location']        = &$cache['locations']['entries'][$country_code]['entries'][$state]['entries'][$county]['entries'][$city]['entries'][$location];
            } elseif (!$no_city) {
                $location_levels['location_state'] = &$cache['locations']['entries'][$country_code]['entries'][$state];
                $location_levels['location_city']  = &$cache['locations']['entries'][$country_code]['entries'][$state]['entries'][$city];
                $location_levels['location']       = &$cache['locations']['entries'][$country_code]['entries'][$state]['entries'][$city]['entries'][$location];
            } else {
                // Simple menu with Country -> Location
                $location_levels['location']       = &$cache['locations']['entries'][$country_code]['entries'][$location];
            }

            foreach ($location_levels as $level => &$entry) {
                if (!isset($entry['count'])) {
                    $entry['count'] = 0;
                }
                $entry['count']++;
                $entry['level'] = $level;
            }
            unset($entry);
        }
    }

    //dump($cache['device_locations']);
    //dump($cache['locations']['entries']);

    sort($cache['graphs']);

    // Ports

    $microtime_start = microtime(TRUE);

    $cache['ports'] = [
        //'id'              => [],
        //'permitted'       => [],
        'ignored'         => [],
        'errored'         => [],
        //'disabled'        => [],
        'poll_disabled'   => [],
        'device_ignored'  => [],
        'device_disabled' => [],
        //'deleted'         => []
    ];

    $cache['ports']['stat'] = [
        'count'    => 0,
        'up'       => 0,
        'down'     => 0,
        'ignored'  => 0,
        'shutdown' => 0,
        'errored'  => 0,
        'alerts'   => 0,
        'deleted'  => 0
    ];

    $where_permitted = generate_query_permitted_ng(['device', 'port']);

    $where_permitted_hide   = [ $where_permitted ];
    $where_permitted_hide[] = "`deleted` = 0";

    // Deleted
    $cache['ports']['deleted']         = dbFetchColumn("SELECT `port_id` FROM `ports`" . generate_where_clause($where_permitted, "`deleted` = 1"));
    $cache['ports']['stat']['deleted'] = safe_count($cache['ports']['deleted']);

    // Devices disabled
    if (isset($cache['devices']['disabled']) && !safe_empty($cache['devices']['disabled'])) {
        $cache['ports']['device_disabled'] = dbFetchColumn("SELECT `port_id` FROM `ports` " . generate_where_clause($where_permitted, generate_query_values($cache['devices']['disabled'], 'device_id')));
        if (!$config['web_show_disabled']) {
            $where_permitted_hide[] = generate_query_values($cache['devices']['disabled'], 'device_id', '!=');
        }
    }

    // Devices ignored
    $where_devices_ignored = '';
    if (isset($cache['devices']['ignored']) && !safe_empty($cache['devices']['ignored'])) {
        $cache['ports']['device_ignored'] = dbFetchColumn("SELECT `port_id` FROM `ports`" . generate_where_clause($where_permitted_hide, generate_query_values($cache['devices']['ignored'], 'device_id')));
        $where_permitted_hide[]           = generate_query_values($cache['devices']['ignored'], 'device_id', '!=');
        $where_devices_ignored            = generate_query_values($cache['devices']['ignored'], 'device_id');
    }
    $cache['ports']['stat']['device_ignored'] = safe_count($cache['ports']['device_ignored']);

    // Ports poll disabled
    $cache['ports']['poll_disabled']         = dbFetchColumn("SELECT `port_id` FROM `ports`" . generate_where_clause($where_permitted_hide, "`disabled` = '1'"));
    $cache['ports']['stat']['poll_disabled'] = safe_count($cache['ports']['poll_disabled']);

    // Ports ignored
    $cache['ports']['ignored']         = dbFetchColumn("SELECT `port_id` FROM `ports`" . generate_where_clause($where_permitted_hide, "`ignore` = '1'"));
    $cache['ports']['stat']['ignored'] = safe_count($cache['ports']['ignored']);

    $where_permitted_hide[] = "`ignore` = 0";

    // Ports errored
    $cache['ports']['errored']         = dbFetchColumn("SELECT `port_id` FROM `ports`" . generate_where_clause($where_permitted_hide, "`ifAdminStatus` = 'up' AND (`ifOperStatus` = 'up' OR `ifOperStatus` = 'testing') AND (`ifOutErrors_delta` > 0 OR `ifInErrors_delta` > 0)"));
    $cache['ports']['stat']['errored'] = safe_count($cache['ports']['errored']);

    // Ports counts
    $cache['ports']['stat']['count']    = dbFetchCell("SELECT COUNT(*) FROM `ports`" . generate_where_clause($where_permitted_hide));
    $cache['ports']['stat']['shutdown'] = dbFetchCell("SELECT COUNT(*) FROM `ports`" . generate_where_clause($where_permitted_hide, "`ifAdminStatus` = 'down'"));
    $cache['ports']['stat']['down']     = dbFetchCell("SELECT COUNT(*) FROM `ports`" . generate_where_clause($where_permitted_hide, "`ifAdminStatus` = 'up' AND `ifOperStatus` IN ('down', 'lowerLayerDown') AND `ports`.`disabled` = '0' AND `ports`.`deleted` = '0'"));
    $cache['ports']['stat']['up']       = dbFetchCell("SELECT COUNT(*) FROM `ports`" . generate_where_clause($where_permitted_hide, "`ifAdminStatus` = 'up' AND `ifOperStatus` IN ('up', 'testing', 'monitoring')"));

    $time_elapsed = elapsed_time($microtime_start);
    bdump("Port Cache: " . round($time_elapsed, 5) . "s");


    // Sensors

    /*

    $microtime_start = microtime(true);

      $cache['sensors']['stat'] = array('count'          => 0,
                                        'ok'             => 0,
                                        'alert'          => 0,
                                        'warning'        => 0,
                                        'ignored'        => 0,
                                        'device_ignored' => 0,
                                        'disabled'       => 0,
                                        'deleted'        => 0);
      $cache['sensors']['devices'] = array(); // Stats per device ids

      $sensors_array = dbFetchRows('SELECT `device_id`, `sensor_id`, `sensor_class`, `sensor_type`, `sensor_ignore`, `sensor_disable`,
                                         `sensor_value`, `sensor_deleted`, `sensor_event` FROM `sensors` WHERE 1 ' . generate_query_permitted(array('device', 'sensor')));

      foreach ($sensors_array as $sensor) {

        //if (!is_entity_permitted($sensor['sensor_id'], 'sensor', $sensor['device_id'])) { continue; } // Check device permitted

        // Initialize devices and types for this sensor if not already set
        if (!isset($cache['sensors']['devices'][$sensor['device_id']])) {
          $cache['sensors']['devices'][$sensor['device_id']] = ['count' => 0, 'disabled' => 0, 'ignored' => 0, 'warning' => 0, 'ok' => 0, 'alert' => 0];
        }
        if (!isset($cache['sensors']['types'][$sensor['sensor_class']])) {
          $cache['sensors']['types'][$sensor['sensor_class']] = ['count' => 0, 'disabled' => 0, 'ignored' => 0, 'warning' => 0, 'ok' => 0, 'alert' => 0];
        }

        if (!$config['web_show_disabled']) {
          if ($cache['devices']['id'][$sensor['device_id']]['disabled']) {
            continue;
          }
        }

        if ($sensor['sensor_deleted']) {
          $cache['sensors']['stat']['deleted']++;
          continue;
        }

        $cache['sensors']['stat']['count']++;
        $cache['sensors']['devices'][$sensor['device_id']]['count']++;
        $cache['sensors']['types'][$sensor['sensor_class']]['count']++;

        if ($sensor['sensor_disable']) {
          $cache['sensors']['stat']['disabled']++;
          $cache['sensors']['devices'][$sensor['device_id']]['disabled']++;
          $cache['sensors']['types'][$sensor['sensor_class']]['disabled']++;
          continue;
        }

        if ($sensor['sensor_event'] === 'ignore' || $sensor['sensor_ignore'] ||
          in_array($sensor['device_id'], $cache['devices']['ignored'])) {
          $cache['sensors']['stat']['ignored']++;
          $cache['sensors']['devices'][$sensor['device_id']]['ignored']++;
          $cache['sensors']['types'][$sensor['sensor_class']]['ignored']++;
          continue;
        }

        switch ($sensor['sensor_event']) {
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
            break;
          default:
            $cache['sensors']['stat']['alert']++; // unknown event (empty) also alert
            $cache['sensors']['devices'][$sensor['device_id']]['alert']++;
            $cache['sensors']['types'][$sensor['sensor_class']]['alert']++;
        }
      }
      //r($cache['sensors']);
      //r($cache['sensor_types']);

    $time_elapsed = elapsed_time($microtime_start);
    bdump("Sensor Cache v1: " . round($time_elapsed, 5) . "s");

    $microtime_start = microtime(true);

    $query_where = [];
      $query_where[] = generate_query_permitted_ng(['device', 'sensor']);
      $query_where[] = "`sensor_deleted` = 0";

      if (!$config['web_show_disable']) {
        $query_where[] = "`device_id` NOT IN (SELECT `device_id` FROM `devices` WHERE `disabled` = 1)";
        //$query_where[] = "`disable` = 0";
      }

      $where = generate_where_clause($query_where);

      $sensorStatsQuery = "
      SELECT
          device_id,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0) THEN 1 ELSE 0 END) AS count,
          SUM(sensor_deleted) AS deleted_count,
          SUM(sensor_disable) AS disabled_count,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0 AND sensor_event = 'ok') THEN 1 ELSE 0 END) AS ok_count,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0 AND sensor_event = 'warning') THEN 1 ELSE 0 END) AS warning_count,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0 AND sensor_event = 'alert') THEN 1 ELSE 0 END) AS alert_count,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0 AND sensor_event = 'ignore') THEN 1 ELSE 0 END) AS ignored_count
      FROM `sensors`
      " . $where . "
      GROUP BY
          device_id
  ";

      $sensor_results = dbFetchRows($sensorStatsQuery);

      //r($sensorStatsQuery);

      $cache['sensors']['stat'] = array('count' => 0, 'ok' => 0, 'alert' => 0, 'warning' => 0, 'ignored' => 0, 'disabled' => 0, 'deleted' => 0);
      $cache['sensors']['devices'] = array();
      $cache['sensor_types'] = array();

      foreach ($sensor_results as $row) {
        $deviceId = $row['device_id'];

        $cache['sensors']['stat']['count'] += $row['count'];
        $cache['sensors']['stat']['deleted'] += $row['deleted_count'];
        $cache['sensors']['stat']['disabled'] += $row['disabled_count'];
        $cache['sensors']['stat']['ignored'] += $row['ignored_count'];
        $cache['sensors']['stat']['ok'] += $row['ok_count'];
        $cache['sensors']['stat']['warning'] += $row['warning_count'];
        $cache['sensors']['stat']['alert'] += $row['alert_count'];

        $cache['sensors']['devices'][$deviceId]['count'] = $row['count'];
        $cache['sensors']['devices'][$deviceId]['deleted'] = $row['deleted_count'];
        $cache['sensors']['devices'][$deviceId]['disabled'] = $row['disabled_count'];
        $cache['sensors']['devices'][$deviceId]['ignored'] = $row['ignored_count'];
        $cache['sensors']['devices'][$deviceId]['ok'] = $row['ok_count'];
        $cache['sensors']['devices'][$deviceId]['warning'] = $row['warning_count'];
        $cache['sensors']['devices'][$deviceId]['alert'] = $row['alert_count'];
      }

      $sensorStatsQuery = "
      SELECT
          sensor_class,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0) THEN 1 ELSE 0 END) AS count,
          SUM(sensor_deleted) AS deleted_count,
          SUM(sensor_disable) AS disabled_count,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0 AND sensor_event = 'ok') THEN 1 ELSE 0 END) AS ok_count,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0 AND sensor_event = 'warning') THEN 1 ELSE 0 END) AS warning_count,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0 AND sensor_event = 'alert') THEN 1 ELSE 0 END) AS alert_count,
          SUM(CASE WHEN (sensor_deleted = 0 AND sensor_disable = 0 AND sensor_event = 'ignore') THEN 1 ELSE 0 END) AS ignored_count
      FROM `sensors`
      " . $where . "
      GROUP BY
          sensor_class
  ";

      $sensor_results = dbFetchRows($sensorStatsQuery);

      //r($sensorStatsResults);

      foreach ($sensor_results as $row) {
        $sensorClass = $row['sensor_class'];
        $cache['sensors']['types'][$sensorClass]['count'] = $row['count'];
        $cache['sensors']['types'][$sensorClass]['deleted'] = $row['deleted_count'];
        $cache['sensors']['types'][$sensorClass]['disabled'] = $row['disabled_count'];
        $cache['sensors']['types'][$sensorClass]['ignored'] = $row['ignored_count'];
        $cache['sensors']['types'][$sensorClass]['ok'] = $row['ok_count'];
        $cache['sensors']['types'][$sensorClass]['warning'] = $row['warning_count'];
        $cache['sensors']['types'][$sensorClass]['alert'] = $row['alert_count'];
      }

      //r($cache['sensors']);

    $time_elapsed = elapsed_time($microtime_start);
    bdump("Sensor Cache v2: " . round($time_elapsed, 5) . "s");

    */

    $microtime_start = microtime(TRUE);

    $query_where   = [];
    $query_where[] = generate_query_permitted_ng([ 'device', 'sensor' ]);
    $query_where[] = "`sensor_deleted` = 0";

    if (!$config['web_show_disable']) {
        $query_where[] = "`device_id` NOT IN (SELECT `device_id` FROM `devices` WHERE `disabled` = 1)";
        //$query_where[] = "`disable` = 0";
    }

    $where = generate_where_clause($query_where);

    $sensor_query = "
    SELECT
        device_id,
        sensor_class,
        COUNT(*) AS count,
        SUM(sensor_deleted) AS deleted_count,
        SUM(sensor_disable) AS disabled_count,
        COUNT(sensor_event = 'ok' OR NULL) AS ok_count,
        COUNT(sensor_event = 'warning' OR NULL) AS warning_count,
        COUNT(sensor_event = 'alert' OR NULL) AS alert_count,
        COUNT(sensor_event = 'ignore' OR NULL) AS ignored_count
    FROM `sensors`    
    " . $where . "
    GROUP BY
        device_id,
        sensor_class
";

    $sensor_results = dbFetchRows($sensor_query);

    //r($sensorStatsQuery);

    $cache['sensors']['stat']    = ['count' => 0, 'ok' => 0, 'alert' => 0, 'warning' => 0, 'ignored' => 0, 'disabled' => 0, 'deleted' => 0];
    $cache['sensors']['devices'] = [];
    $cache['sensor_types']       = [];

    foreach ($sensor_results as $row) {
        $device_id    = $row['device_id'];
        $sensor_class = $row['sensor_class'];

        // Initialize devices and types for this sensor if not already set
        if (!isset($cache['sensors']['devices'][$device_id])) {
            $cache['sensors']['devices'][$device_id] = ['count' => 0, 'deleted' => 0, 'disabled' => 0, 'ignored' => 0, 'ok' => 0, 'warning' => 0, 'alert' => 0];
        }
        if (!isset($cache['sensors']['types'][$sensor_class])) {
            $cache['sensors']['types'][$sensor_class] = ['count' => 0, 'deleted' => 0, 'disabled' => 0, 'ignored' => 0, 'ok' => 0, 'warning' => 0, 'alert' => 0];
        }

        // Update device stats
        $cache['sensors']['devices'][$device_id]['count']    += $row['count'];
        $cache['sensors']['devices'][$device_id]['deleted']  += $row['deleted_count'];
        $cache['sensors']['devices'][$device_id]['disabled'] += $row['disabled_count'];
        $cache['sensors']['devices'][$device_id]['ignored']  += $row['ignored_count'];
        $cache['sensors']['devices'][$device_id]['ok']       += $row['ok_count'];
        $cache['sensors']['devices'][$device_id]['warning']  += $row['warning_count'];
        $cache['sensors']['devices'][$device_id]['alert']    += $row['alert_count'];

        // Update sensor class stats
        $cache['sensors']['types'][$sensor_class]['count']    += $row['count'];
        $cache['sensors']['types'][$sensor_class]['deleted']  += $row['deleted_count'];
        $cache['sensors']['types'][$sensor_class]['disabled'] += $row['disabled_count'];
        $cache['sensors']['types'][$sensor_class]['ignored']  += $row['ignored_count'];
        $cache['sensors']['types'][$sensor_class]['ok']       += $row['ok_count'];
        $cache['sensors']['types'][$sensor_class]['warning']  += $row['warning_count'];
        $cache['sensors']['types'][$sensor_class]['alert']    += $row['alert_count'];

        // Update overall stats
        $cache['sensors']['stat']['count']    += $row['count'];
        $cache['sensors']['stat']['deleted']  += $row['deleted_count'];
        $cache['sensors']['stat']['disabled'] += $row['disabled_count'];
        $cache['sensors']['stat']['ignored']  += $row['ignored_count'];
        $cache['sensors']['stat']['ok']       += $row['ok_count'];
        $cache['sensors']['stat']['warning']  += $row['warning_count'];
        $cache['sensors']['stat']['alert']    += $row['alert_count'];
    }

    $time_elapsed = elapsed_time($microtime_start);
    bdump("Sensor Cache v3: " . round($time_elapsed, 5) . "s");

    $microtime_start = microtime(TRUE);

    // Statuses
    $cache['statuses']['stat']    = ['count'    => 0,
                                     'ok'       => 0,
                                     'alert'    => 0,
                                     'warning'  => 0,
                                     'ignored'  => 0,
                                     'disabled' => 0,
                                     'deleted'  => 0];
    $cache['statuses']['devices'] = [];      // Stats per device id
    $cache['status_classes']      = [];      // FIXME -> $cache['statuses']['classes']

    $status_array = dbFetchRows('SELECT `device_id`, `status_id`, `entPhysicalClass`, `status_ignore`, `status_disable`,
                                      `status_deleted`, `status_event` FROM `status`' . generate_where_clause(generate_query_permitted_ng(['device', 'status'])));


    //r($cache['devices']);

    foreach ($status_array as $status) {

        if (!$config['web_show_disabled']) {
            if (in_array($status['device_id'], $cache['devices']['disabled'])) {
                continue;
            }
        }
        if ($status['status_deleted']) {
            $cache['statuses']['stat']['deleted']++;
            continue;
        }

        // Initialize devices and status_classes for this status if not already set
        if (!isset($cache['statuses']['devices'][$status['device_id']])) {
            $cache['statuses']['devices'][$status['device_id']] = ['count' => 0, 'disabled' => 0, 'ignored' => 0, 'ok' => 0, 'warning' => 0, 'alert' => 0];
        }
        if (!isset($cache['status_classes'][$status['entPhysicalClass']])) {
            $cache['status_classes'][$status['entPhysicalClass']] = ['count' => 0, 'alert' => 0];
        }

        $cache['statuses']['stat']['count']++;
        $cache['statuses']['devices'][$status['device_id']]['count']++;
        $cache['status_classes'][$status['entPhysicalClass']]['count']++;

        if ($status['status_disable']) {
            $cache['statuses']['stat']['disabled']++;
            $cache['statuses']['devices'][$status['device_id']]['disabled']++;
            continue;
        }

        if ($status['status_event'] === 'ignore' || $status['status_ignore'] ||
            in_array($status['device_id'], $cache['devices']['ignored'])) {
            $cache['statuses']['stat']['ignored']++;
            $cache['statuses']['devices'][$status['device_id']]['ignored']++;
            continue;
        }

        switch ($status['status_event']) {
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


    $time_elapsed = elapsed_time($microtime_start);
    bdump("Status Cache v1: " . round($time_elapsed, 5) . "s");


    $microtime_start = microtime(TRUE);

    // Counters
    $cache['counters']['stat']    = ['count'    => 0,
                                     'ok'       => 0,
                                     'alert'    => 0,
                                     'warning'  => 0,
                                     'ignored'  => 0,
                                     'disabled' => 0,
                                     'deleted'  => 0];
    $cache['counters']['devices'] = [];                              // Stats per device ids

    $counters_array = dbFetchRows('SELECT `device_id`, `counter_id`, `counter_class`, `counter_ignore`, `counter_disable`,
                                       `counter_value`, `counter_deleted`, `counter_event` FROM `counters`' . generate_where_clause(generate_query_permitted_ng(['device', 'counter'])));

    foreach ($counters_array as $counter) {

        if (!$config['web_show_disabled'] &&
            in_array($counter['device_id'], (array)$cache['devices']['disabled'])) {
            continue;
        }

        if ($counter['counter_deleted']) {
            $cache['counters']['stat']['deleted']++;
            continue;
        }

        $cache['counters']['stat']['count']++;
        $cache['counters']['devices'][$counter['device_id']]['count']++;
        //$cache['counters']['types'][$counter['counter_class']]['count']++;

        if ($counter['counter_disable']) {
            $cache['counters']['stat']['disabled']++;
            $cache['counters']['devices'][$counter['device_id']]['disabled']++;
            //$cache['counters']['types'][$counter['counter_class']]['disabled']++;
            continue;
        }

        if ($counter['counter_event'] === 'ignore' || $counter['counter_ignore'] ||
            in_array($counter['device_id'], $cache['devices']['ignored'])) {
            $cache['counters']['stat']['ignored']++;
            $cache['counters']['devices'][$counter['device_id']]['ignored']++;
            //$cache['counters']['types'][$counter['counter_class']]['ignored']++;
            continue;
        }

        switch ($counter['counter_event']) {
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

    $time_elapsed = elapsed_time($microtime_start);
    bdump("Counter Cache v1: " . round($time_elapsed, 5) . "s");


    $microtime_start = microtime(TRUE);

    // Alerts

    $query = "
SELECT 
  SUM(CASE WHEN alert_status = 0 THEN 1 ELSE 0 END) as down,
  SUM(CASE WHEN alert_status = 1 THEN 1 ELSE 0 END) as up,
  SUM(CASE WHEN alert_status = 2 THEN 1 ELSE 0 END) as delay,
  SUM(CASE WHEN alert_status = 3 THEN 1 ELSE 0 END) as suppress,
  SUM(CASE WHEN alert_status NOT IN (0, 1, 2, 3) THEN 1 ELSE 0 END) as unknown
FROM alert_table
";
    $query .= generate_where_clause(generate_query_permitted_ng(['alert']));

    $alert_entries = dbFetchRow($query);

    $cache['alert_entries'] = [
      'count'    => array_sum($alert_entries),
      'up'       => $alert_entries['up'],
      'down'     => $alert_entries['down'],
      'unknown'  => $alert_entries['unknown'],
      'delay'    => $alert_entries['delay'],
      'suppress' => $alert_entries['suppress']
    ];

    /*
    $query = 'SELECT `alert_status` FROM `alert_table`';
    $query .= generate_where_clause(generate_query_permitted_ng( ['alert'] ));

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
    */


    // Routing

    // BGP

    /*
    if (isset($config['enable_bgp']) && $config['enable_bgp']) {
      // Init variables to 0
      $cache['routing']['bgp']['internal'] = 0;
      $cache['routing']['bgp']['external'] = 0;
      $cache['routing']['bgp']['count'] = 0;
      $cache['routing']['bgp']['up'] = 0;
      $cache['routing']['bgp']['down'] = 0;

      $cache['routing']['bgp']['last_seen'] = get_time();
      foreach (dbFetchRows('SELECT `device_id`,`bgpPeer_id`,`local_as`,`bgpPeerState`,`bgpPeerAdminStatus`,`bgpPeerRemoteAs` FROM `bgpPeers`'  . generate_where_clause(generate_query_permitted_ng(array('device')))) as $bgp) {
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
    */

    if (isset($config['enable_bgp']) && $config['enable_bgp']) {
        $query = "
    SELECT
        COUNT(*) as count,
        SUM(CASE WHEN bgpPeerAdminStatus IN ('start', 'running') THEN 1 ELSE 0 END) as up,
        SUM(CASE WHEN bgpPeerAdminStatus NOT IN ('start', 'running') THEN 1 ELSE 0 END) as down,
        SUM(CASE WHEN local_as = bgpPeerRemoteAs THEN 1 ELSE 0 END) as internal,
        SUM(CASE WHEN local_as <> bgpPeerRemoteAs THEN 1 ELSE 0 END) as external,
        SUM(CASE WHEN bgpPeerAdminStatus IN ('start', 'running') AND bgpPeerState != 'established' THEN 1 ELSE 0 END) as alerts
    FROM bgpPeers
    " . generate_where_clause(generate_query_permitted_ng(['device']));

        if (!$config['web_show_disabled']) {
            $query .= " AND device_id NOT IN (SELECT device_id FROM devices WHERE disabled = 1)";
        }

        $bgp_data = dbFetchRow($query);

        $cache['routing']['bgp']['count']    = $bgp_data['count'];
        $cache['routing']['bgp']['up']       = $bgp_data['up'];
        $cache['routing']['bgp']['down']     = $bgp_data['down'];
        $cache['routing']['bgp']['internal'] = $bgp_data['internal'];
        $cache['routing']['bgp']['external'] = $bgp_data['external'];
        $cache['routing']['bgp']['alerts']   = $bgp_data['alerts'];
    }

    // OSPF

    if (isset($config['enable_ospf']) && $config['enable_ospf']) {
        $query = "
    SELECT
        COUNT(*) as count,
        SUM(CASE WHEN ospfAdminStat = 'enabled' THEN 1 ELSE 0 END) as up,
        SUM(CASE WHEN ospfAdminStat = 'disabled' THEN 1 ELSE 0 END) as down
    FROM ospf_instances" . generate_where_clause(generate_query_permitted_ng(['device']));

        if (!$config['web_show_disabled']) {
            $query .= " AND device_id NOT IN (SELECT device_id FROM devices WHERE disabled = 1)";
        }

        $ospf_data = dbFetchRow($query);

        $cache['routing']['ospf']['count']     = $ospf_data['count'];
        $cache['routing']['ospf']['up']        = $ospf_data['up'];
        $cache['routing']['ospf']['down']      = $ospf_data['down'];
        $cache['routing']['ospf']['last_seen'] = get_time();
    }

    /*
    if (isset($config['enable_ospf']) && $config['enable_ospf'])
    {
      $cache['routing']['ospf']['last_seen'] = get_time();
      foreach (dbFetchRows("SELECT `device_id`, `ospfAdminStat` FROM `ospf_instances`"  . generate_where_clause(generate_query_permitted_ng(['device']))) as $ospf)
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
    */

//  if (isset($config['enable_eigrp']) && $config['enable_eigrp']) {
    $cache['routing']['eigrp']['last_seen'] = get_time();
    foreach (dbFetchRows("SELECT `device_id` FROM `eigrp_vpns`" . generate_where_clause(generate_query_permitted_ng([ 'device', 'eigrp' ]))) as $eigrp) {
        if (!$config['web_show_disabled'] &&
            in_array($eigrp['device_id'], (array)$cache['devices']['disabled'])) {
            continue;
        }
        if (device_permitted($eigrp)) {
            $cache['routing']['eigrp']['count']++;
        }
    }
    // }

    // Common permission sql query
    //r(range_to_list($cache['devices']['permitted']));
    //unset($cache['devices']['permitted']);

    //$cache['where']['devices_ports_permitted'] = generate_query_permitted(array('device', 'port'));
    $cache['where']['devices_permitted'] = generate_query_permitted_ng(['device']);

    // This needs to do both, otherwise it only permits permitted ports and not ports on permitted devices.s
    $cache['where']['ports_permitted'] = generate_query_permitted_ng(['port', 'device']);

    // CEF
    $cache['routing']['cef']['count'] = dbFetchCell("SELECT COUNT(`cef_switching_id`) FROM `cef_switching`" . generate_where_clause($cache['where']['devices_permitted']) . " GROUP BY `device_id`, `afi`;");

    // VRF
    if ($config['enable_vrfs']) {
        //$cache['routing']['vrf']['count'] = safe_count(dbFetchColumn("SELECT DISTINCT `vrf_rd` FROM `vrfs`" . $cache['where']['devices_permitted']));
        $cache['routing']['vrf']['count'] = dbFetchCell("SELECT COUNT(DISTINCT `vrf_rd`) FROM `vrfs`" . generate_where_clause($cache['where']['devices_permitted']));
    }

    // Status
    $cache['status']['count'] = $cache['statuses']['stat']['count']; //dbFetchCell("SELECT COUNT(`status_id`) FROM `status` WHERE 1 ".$cache['where']['devices_permitted']);
    // Counter
    $cache['counter']['count'] = $cache['counters']['stat']['count'];

    // Additional common counts
    if ($config['enable_pseudowires']) {
        $cache['ports']['pseudowires'] = dbFetchColumn('SELECT DISTINCT `port_id` FROM `pseudowires`' . generate_where_clause($cache['where']['ports_permitted']));
        $cache['pseudowires']['count'] = safe_count($cache['ports']['pseudowires']);
    }
    if ($config['poller_modules']['cisco-cbqos'] || $config['discovery_modules']['cisco-cbqos']) {
        $cache['ports']['cbqos'] = dbFetchColumn('SELECT DISTINCT `port_id` FROM `ports_cbqos`' . generate_where_clause($cache['where']['ports_permitted']));
        $cache['cbqos']['count'] = safe_count($cache['ports']['cbqos']);
    }
    if ($config['poller_modules']['mac-accounting'] || $config['discovery_modules']['mac-accounting']) {
        $cache['ports']['mac_accounting'] = dbFetchColumn('SELECT DISTINCT `port_id` FROM `mac_accounting`' . generate_where_clause($cache['where']['ports_permitted']));
        $cache['mac_accounting']['count'] = safe_count($cache['ports']['mac_accounting']);
    }

//  if ($config['poller_modules']['unix-agent'])
//  {
    $cache['packages']['count'] = dbFetchCell("SELECT COUNT(*) FROM `packages`" . generate_where_clause($cache['where']['devices_permitted']));
//  }

    if ($config['poller_modules']['applications']) {
        $cache['applications']['count'] = dbFetchCell("SELECT COUNT(*) FROM `applications`" . generate_where_clause($cache['where']['devices_permitted']));
    }
    if ($config['poller_modules']['wifi'] || $config['discovery_modules']['wifi']) {
        $cache['wifi_sessions']['count'] = dbFetchCell("SELECT COUNT(*) FROM `wifi_sessions`" . generate_where_clause($cache['where']['devices_permitted']));
    }
    if ($config['poller_modules']['printersupplies'] || $config['discovery_modules']['printersupplies']) {
        $cache['printersupplies']['count'] = dbFetchCell("SELECT COUNT(*) FROM `printersupplies`" . generate_where_clause($cache['where']['devices_permitted']));
    }

    $cache['neighbours']['count'] = dbFetchCell("SELECT COUNT(*) FROM `neighbours`" . generate_where_clause($cache['where']['ports_permitted'], "`active` = 1"));

    //r("SELECT COUNT(*) FROM `neighbours` WHERE `active` = 1 "  . $cache['where']['ports_permitted']);

    $cache['sla']['count']       = dbFetchCell("SELECT COUNT(*) FROM `slas`" . generate_where_clause($cache['where']['devices_permitted'], "`deleted` = 0"));
    $cache['p2pradios']['count'] = dbFetchCell("SELECT COUNT(*) FROM `p2p_radios`" . generate_where_clause($cache['where']['devices_permitted'], "`deleted` = 0"));
    $cache['vm']['count']        = dbFetchCell("SELECT COUNT(*) FROM `vminfo`" . generate_where_clause($cache['where']['devices_permitted']));

    // Clean arrays (from DB queries)
    unset($ports_array, $sensors_array, $status_array, $graphs_array, $device_graphs);
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

$cache_data_time = elapsed_time($cache_data_start);

// EOF

