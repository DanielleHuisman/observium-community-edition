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


/**
 * Poll and cache entity _NUMERIC_ Oids,
 * need for cross cache between different entities, ie status and sensors
 *
 * @param $device
 * @param $entity_type
 * @param $oid_cache
 *
 * @return bool
 */
function poll_cache_oids($device, $entity_type, &$oid_cache)
{
  global $config;

  $use_walk = FALSE; // Use multi get by default
  $mib_walk_option = $entity_type . '_walk'; // ie: $config['mibs'][$mib]['sensors_walk']
  $snmp_flags = OBS_SNMP_ALL_NUMERIC; // Numeric Oids by default

  $translate = entity_type_translate_array($entity_type);
  $table          = $translate['table'];
  $mib_field      = $translate['mib_field'];
  $object_field   = $translate['object_field'];
  $oid_field      = $translate['oid_field'];
  $deleted_field  = $translate['deleted_field'];
  $device_field   = $translate['device_id_field'];

  switch ($entity_type)
  {
    case 'sensor':
    case 'status':
    case 'counter':
      // Device not support walk
      $use_walk = isset($config['os'][$device['os']]['sensors_poller_walk']) &&
                        $config['os'][$device['os']]['sensors_poller_walk'];

      // For sensors and statuses always use same option
      $mib_walk_option = 'sensors_walk';

      // Walk query
      $walk_query = "SELECT DISTINCT `$mib_field`, `$object_field` FROM `$table` WHERE `$device_field` = ? AND `$deleted_field` = ? AND `poller_type` = ?";
      $walk_query .= " AND `$mib_field` != ? AND `$object_field` != ?";
      $walk_params = [$device['device_id'], '0', 'snmp', '', ''];

      // Multi-get query
      $get_query = "SELECT `$oid_field` FROM `$table` WHERE `$device_field` = ? AND `$deleted_field` = ? AND `poller_type` = ? ORDER BY `$oid_field`";
      $get_params = [$device['device_id'], '0', 'snmp'];

      break;

    default:
      print_debug("Unknown Entity $entity_type");
      return FALSE;
  }

  if ($use_walk)
  {
    // Walk by mib & object
    $oid_to_cache = dbFetchRows($walk_query, $walk_params);
    print_debug_vars($oid_to_cache);
    foreach ($oid_to_cache as $entry)
    {
      $mib    = $entry[$mib_field];
      $object = $entry[$object_field];

      // MIB not support walk (by definition)
      if (isset($config['mibs'][$mib][$mib_walk_option]) &&
               !$config['mibs'][$mib][$mib_walk_option])
      {
        continue;
      }
      else if (isset($GLOBALS['cache']['snmp_object_polled'][$mib][$object]))
      {
        print_debug("MIB/Object ($mib::$object)already polled.");
        continue;
      }

      $oid_cache = snmp_walk_multipart_oid($device, $object, $oid_cache, $mib, NULL, $snmp_flags);
      $GLOBALS['cache']['snmp_object_polled'][$mib][$object] = 1;
    }
  } else {
    // Multi get for all others
    $oid_to_cache = dbFetchColumn($get_query, $get_params);
    usort($oid_to_cache, 'compare_numeric_oids'); // correctly sort numeric oids
    print_debug_vars($oid_to_cache);
    $oid_cache = snmp_get_multi_oid($device, $oid_to_cache, $oid_cache, NULL, NULL, $snmp_flags);
  }

  print_debug_vars($oid_cache);

  return TRUE;
}

function poll_device($device, $options)
{
  global $config, $device, $polled_devices, $db_stats, $exec_status, $alert_rules, $alert_table, $graphs, $attribs;

  $device_start = utime();  // Start counting device poll time

  $alert_metrics = array();
  $oid_cache = array();
  $device_state = array();
  //$old_device_state = unserialize($device['device_state']);
  $attribs = get_entity_attribs('device', $device['device_id']);

  print_debug_vars($device);
  print_debug_vars($attribs);

  $pid_info = check_process_run($device); // This just clear stalled DB entries
  add_process_info($device); // Store process info

  $alert_rules = cache_alert_rules();
  $alert_table = cache_device_alert_table($device['device_id']);

  print_debug_vars($alert_rules);
  print_debug_vars($alert_table);

  $status = 0;

  print_cli_heading($device['hostname'] . " [".$device['device_id']."]", 1);

  print_cli_data("OS", $device['os'], 1);

  if ($config['os'][$device['os']]['group'])
  {
    $device['os_group'] = $config['os'][$device['os']]['group'];
    print_cli_data("OS Group", $device['os_group'], 1);
  }

  if (is_numeric($device['last_polled_timetaken']))
  {
    print_cli_data("Last poll duration", $device['last_polled_timetaken']. " seconds", 1);
  }

  print_cli_data("Last Polled", $device['last_polled'], 1);
  print_cli_data("SNMP Version", $device['snmp_version'], 1);

  //unset($poll_update); unset($poll_update_query); unset($poll_separator);
  $update_array = array();

  $host_rrd_dir = $config['rrd_dir'] . "/" . $device['hostname'];
  if (!is_dir($host_rrd_dir)) { mkdir($host_rrd_dir); echo("Created directory : $host_rrd_dir\n"); }

  $flags = OBS_DNS_ALL;
  if ($device['snmp_transport'] == 'udp6' || $device['snmp_transport'] == 'tcp6') // Exclude IPv4 if used transport 'udp6' or 'tcp6'
  {
    $flags = $flags ^ OBS_DNS_A;
  }
  $attribs['ping_skip'] = isset($attribs['ping_skip']) && $attribs['ping_skip'];
  if ($attribs['ping_skip'])
  {
    $flags = $flags | OBS_PING_SKIP; // Add skip ping flag
  }
  $device['pingable'] = isPingable($device['hostname'], $flags);
  if ($device['pingable'])
  {
    $device['snmpable'] = isSNMPable($device);
    if ($device['snmpable'])
    {
      $ping_msg = ($attribs['ping_skip'] ? '' : 'PING (' . $device['pingable'] . 'ms) and ');

      print_cli_data("Device status", "Device is reachable by " . $ping_msg . "SNMP (".$device['snmpable']."ms)", 1);
      $status = "1";
      $status_type = 'ok';
    } else {
      print_cli_data("Device status", "Device is not responding to SNMP requests", 1);
      $status = "0";
      $status_type = 'snmp';
    }
  } else {
    print_cli_data("Device status", "Device is not responding to PINGs", 1);
    $status = "0";
    print_vars(get_status_var('ping_dns'));
    if (isset_status_var('ping_dns') && get_status_var('ping_dns') != 'ok')
    {
      $status_type = 'dns';
    } else {
      $status_type = 'ping';
    }
  }


  if ($device['status'] != $status)
  {
    dbUpdate(array('status' => $status), 'devices', 'device_id = ?', array($device['device_id']));
    // dbInsert(array('importance' => '0', 'device_id' => $device['device_id'], 'message' => "Device is " .($status == '1' ? 'up' : 'down')), 'alerts');

    $event_msg = 'Device status changed to ';
    if ($status == '1')
    {
      // Device Up, Severity Warning (4)
      $event_msg .= 'Up';
      $event_severity = 4;
    } else {
      // Device Down, Severity Error (3)!
      $event_msg .= 'Down';
      $event_severity = 3;
    }
    if ($status_type != 'ok') { $event_msg .= ' (' . $status_type . ')'; }
    log_event($event_msg, $device, 'device', $device['device_id'], $event_severity);
  }
  // Device status type
  if (isset($device['status_type']) && $device['status_type'] != $status_type)
  {
    dbUpdate(array('status_type' => $status_type), 'devices', 'device_id = ?', array($device['device_id']));
    if ($status == '0' && $device['status_type'] != 'ok')
    {
      // Write eventlog entry (only if status Down)
      log_event('Device status changed to Down ('.$device['status_type'].' -> '.$status_type.')', $device, 'device', $device['device_id'], 3);
    }
  }

  rrdtool_update_ng($device, 'status', array('status' => $status));

  if (!$attribs['ping_skip'])
  {
    // Ping response RRD database.
    rrdtool_update_ng($device, 'ping', array('ping' => ($device['pingable'] ? $device['pingable'] : 'U')));
  }

  // SNMP response RRD database.
  rrdtool_update_ng($device, 'ping_snmp', array('ping_snmp' => ($device['snmpable'] ? $device['snmpable'] : 'U')));

  $alert_metrics['device_status'] = $status;
  $alert_metrics['device_status_type'] = $status_type;
  $alert_metrics['device_ping'] = $device['pingable']; // FIXME, when ping skipped, here always 0.001
  $alert_metrics['device_snmp'] = $device['snmpable'];

  if ($status == "1")
  {
    // Arrays for store and check enabled/disabled graphs
    $graphs    = array();
    $graphs_db = array();
    $graphs_insert = array();
    $graphs_delete = array();
    foreach (dbFetchRows("SELECT * FROM `device_graphs` WHERE `device_id` = ?", array($device['device_id'])) as $entry)
    {
      // Not know how empty was here
      if (empty($entry['graph']))
      {
        $graphs_delete[] = $entry['device_graph_id'];
      }

      $graphs_db[$entry['graph']] = $entry;
    }

    $graphs['availability'] = TRUE; // Everything has this!

    if (!$attribs['ping_skip'])
    {
      // Enable Ping graphs
      $graphs['ping'] = TRUE;
    }

    // Enable SNMP graphs
    $graphs['ping_snmp'] = TRUE;

    // Run these base modules always and before all other modules!
    $poll_modules = array('system', 'os');

    if(isset($options['m']) && $options['m'] == 'none')
    {
      unset($poll_modules);
    }

    $mods_disabled_global = array();
    $mods_disabled_device = array();
    $mods_excluded        = array();

    if ($options['m'])
    {
      foreach (explode(',', $options['m']) as $module)
      {
        $module = trim($module);
        if (in_array($module, $poll_modules)) { continue; } // Skip already added modules
        if ($module == 'unix-agent')
        {
          array_unshift($poll_modules, $module);            // Add 'unix-agent' before all
          continue;
        }
        if (is_file($config['install_dir'] . "/includes/polling/$module.inc.php"))
        {
          $poll_modules[] = $module;
        }
      }
    } else {
      foreach ($config['poller_modules'] as $module => $module_status)
      {
        if (in_array($module, $poll_modules)) { continue; } // Skip already added modules
        if ($attribs['poll_'.$module] || ($module_status && !isset($attribs['poll_'.$module])))
        {
          if (poller_module_excluded($device, $module))
          {
            $mods_excluded[] = $module;
            //print_warning("Module [ $module ] excluded for device.");
            continue;
          }
          if ($module == 'unix-agent')
          {
            array_unshift($poll_modules, $module);          // Add 'unix-agent' before all
            continue;
          }
          if (is_file($config['install_dir'] . "/includes/polling/$module.inc.php"))
          {
            $poll_modules[] = $module;
          }
        }
        elseif (isset($attribs['poll_'.$module]) && !$attribs['poll_'.$module])
        {
          $mods_disabled_device[] = $module;
          //print_warning("Module [ $module ] disabled on device.");
        } else {
          $mods_disabled_global[] = $module;
          //print_warning("Module [ $module ] disabled globally.");
        }
      }

    }

    if (count($mods_excluded)) { print_cli_data("Modules Excluded", implode(", ", $mods_excluded), 1); }
    if (count($mods_disabled_global)) { print_cli_data("Disabled Globally", implode(", ", $mods_disabled_global), 1); }
    if (count($mods_disabled_device)) { print_cli_data("Disabled Device", implode(", ", $mods_disabled_global), 1); }
    if (count($poll_modules)) { print_cli_data("Modules Enabled", implode(", ", $poll_modules), 1); }

    echo(PHP_EOL);

    foreach ($poll_modules as $module)
    {
      print_debug(PHP_EOL . "including: includes/polling/$module.inc.php");

      print_cli_heading("Module Start: %R".$module."");

      $m_start = utime();

      include($config['install_dir'] . "/includes/polling/$module.inc.php");

      $m_end   = utime();

      $m_run   = round($m_end - $m_start, 4);
      $device_state['poller_mod_perf'][$module] = $m_run;
      print_cli_data("Module time", number_format($m_run, 4)."s");

      if (!isset($options['m']))
      {
        rrdtool_update_ng($device, 'perf-pollermodule', array('val' => $m_run), $module);
      }

      echo(PHP_EOL);

    }

    print_cli_heading($device['hostname']. " [" . $device['device_id'] . "] completed poller modules at " . date("Y-m-d H:i:s"), 1);

    // Check and update graphs DB
    $graphs_stat = array();

    if (!isset($options['m']))
    {
      // Hardcoded poller performance
      $graphs['poller_perf'] = TRUE;
      $graphs['pollersnmp_count']    = TRUE;
      $graphs['pollersnmp_times']    = TRUE;
      $graphs['pollersnmp_errors_count'] = TRUE;
      $graphs['pollersnmp_errors_times'] = TRUE;
      $graphs['pollerdb_count']    = TRUE;
      $graphs['pollerdb_times']    = TRUE;
      $graphs['pollermemory_perf'] = TRUE;

      // Delete not exists graphs from DB (only if poller run without modules option)
      foreach ($graphs_db as $graph => $entry)
      {
        if (!isset($graphs[$graph]))
        {
          //dbDelete('device_graphs', "`device_id` = ? AND `graph` = ?", array($device['device_id'], $graph));
          $graphs_delete[] = $entry['device_graph_id'];
          unset($graphs_db[$graph]);
          $graphs_stat['deleted'][] = $graph;
        }
      }
    }

    // Add or update graphs in DB
    foreach ($graphs as $graph => $value)
    {
      if (!$graph) { continue; } // Not know how here can empty

      if (!isset($graphs_db[$graph]))
      {
        //dbInsert(array('device_id' => $device['device_id'], 'graph' => $graph, 'enabled' => $value), 'device_graphs');
        $graphs_insert[] = array('device_id' => $device['device_id'], 'graph' => $graph, 'enabled' => $value);
        $graphs_stat['added'][] = $graph;
      }
      else if ($value != $graphs_db[$graph]['enabled'])
      {
        dbUpdate(array('enabled' => $value), 'device_graphs', '`device_graph_id` = ?', array($device['device_id'], $graph));
        $graphs_stat['updated'][] = $graph;
      } else {
        $graphs_stat['checked'][] = $graph;
      }
    }
    if (count($graphs_insert))
    {
      dbInsertMulti($graphs_insert, 'device_graphs');
    }
    if (count($graphs_delete))
    {
      dbDelete('device_graphs', generate_query_values($graphs_delete, 'device_graph_id', NULL, FALSE));
    }

    // Print graphs stats
    foreach ($graphs_stat as $key => $stat)
    {
      if (count($stat)) { print_cli_data('Graphs ['.$key.']', implode(', ', $stat), 1); }
    }

    $device_end  = utime();
    $device_run  = $device_end - $device_start;
    $device_time = round($device_run, 4);

    $update_array['last_polled'] = array('NOW()');
    $update_array['last_polled_timetaken'] = $device_time;

    #echo("$device_end - $device_start; $device_time $device_run");

    print_cli_data("Poller time", $device_time." seconds", 1);
    //print_message(PHP_EOL."Polled in $device_time seconds");

    // Store device stats and history data (only) if we're not doing a single-module poll
    if (!$options['m'])
    {

      // Fetch previous device state (do not use $device array here, for exclude update history collisions)
      $old_device_state = dbFetchCell('SELECT `device_state` FROM `devices` WHERE `device_id` = ?;', array($device['device_id']));
      $old_device_state = unserialize($old_device_state);

      // Add first entry
      $poller_history = array(intval($device_start) => $device_time); // start => duration
      // Add and keep not more than 288 (24 hours with 5min interval) last entries
      if (isset($old_device_state['poller_history']))
      {
        print_debug_vars($old_device_state['poller_history']);
        $poller_history = array_slice($poller_history + $old_device_state['poller_history'], 0, 288, TRUE);
      }
      print_debug_vars($poller_history);

      $device_state['poller_history'] = $poller_history;

      // Keep discovery history and perf too
      if (isset($old_device_state['discovery_history']))
      {
        $device_state['discovery_history'] = $old_device_state['discovery_history'];
      }
      if (isset($old_device_state['discovery_mod_perf']))
      {
        $device_state['discovery_mod_perf'] = $old_device_state['discovery_mod_perf'];
      }
      unset($poller_history, $old_device_state);

      $update_array['device_state'] = serialize($device_state);

      // Also store history in graph
      rrdtool_update_ng($device, 'perf-poller', array('val' => $device_time));
    }

    if (OBS_DEBUG)
    {
      echo("Updating " . $device['hostname'] . " - ");
      print_vars($update_array);
      echo(" \n");
    }

    $updated = dbUpdate($update_array, 'devices', '`device_id` = ?', array($device['device_id']));

    if ($updated) {
      print_cli_data("Updated Data", implode(", ", array_keys($update_array)), 1);
    }

    $alert_metrics['device_la']            = $device_state['la']['5min']; // 5 min as common LA
    $alert_metrics['device_la_1min']       = $device_state['la']['1min'];
    $alert_metrics['device_la_5min']       = $device_state['la']['5min'];
    $alert_metrics['device_la_15min']      = $device_state['la']['15min'];

    $alert_metrics['device_uptime']        = $device['uptime'];
    $alert_metrics['device_rebooted']      = $rebooted; // 0 - not rebooted, 1 - rebooted
    $alert_metrics['device_duration_poll'] = $device['last_polled_timetaken'];

    unset($cache_storage); // Clear cache of hrStorage ** MAYBE FIXME? ** (ok, later)
    unset($cache); // Clear cache (unify all things here?)

  } else if (!$options['m']) {
    // State is 0, also collect poller time for down devices, since it not zero!

    $device_end  = utime();
    $device_run  = $device_end - $device_start;
    $device_time = round($device_run, 4);

    print_cli_data("Poller time", $device_time." seconds", 1);
    // Also store history in graph
    rrdtool_update_ng($device, 'perf-poller', array('val' => $device_time));
  }

  check_entity('device', $device, $alert_metrics);

  echo(PHP_EOL);

  // Clean
  del_process_info($device); // Remove process info
  unset($alert_metrics);
}

///FIXME. It's not a very nice solution, but will approach as temporal.
// Function return FALSE, if poller module allowed for device os (otherwise TRUE).
function poller_module_excluded($device, $module)
{
  global $config;

  ///FIXME. rename module: 'wmi' -> 'windows-wmi'
  if ($module == 'wmi'  && $device['os'] != 'windows') { return TRUE; }

  if ($module == 'ipmi' &&
      (!isset($config['os'][$device['os']]['ipmi']) || $config['os'][$device['os']]['ipmi'] == FALSE)) { return TRUE; }
  if ($module == 'unix-agent' && !($device['os_group'] == 'unix' || $device['os'] == 'generic')) { return TRUE; }

  if (isset($config['os'][$device['os']]['poller_blacklist']))
  {
    if (in_array($module, $config['os'][$device['os']]['poller_blacklist']))
    {
      return TRUE;
    }
  }

  $os_test = explode('-', $module, 2);
  if (count($os_test) === 1) { return FALSE; } // Check modules only with a dash.
  list($os_test) = $os_test;

  ///FIXME. rename module: 'cipsec-tunnels' -> 'cisco-ipsec-tunnels'
  if (($os_test == 'cisco' || $os_test == 'cipsec') && $device['os_group'] != 'cisco') { return TRUE; }
  //$os_groups = array('cisco', 'unix');
  //foreach ($os_groups as $os_group)
  //{
  //  if ($os_test == $os_group && $device['os_group'] != $os_group) { return TRUE; }
  //}

  $oses = array('junose', 'arista_eos', 'netscaler', 'arubaos');
  foreach ($oses as $os)
  {
    if (strpos($os, $os_test) !== FALSE && $device['os'] != $os) { return TRUE; }
  }

  return FALSE;
}

/**
 * Poll a table or oids from SNMP and build an RRD based on an array of arguments.
 *
 * Current limitations:
 *  - single MIB and RRD file for all graphs
 *  - single table per MIB
 *  - if set definition 'call_function', than poll used specific function for snmp walk/get,
 *    else by default used snmpwalk_cache_oid()
 *  - allowed oids only with simple numeric index (oid.0, oid.33), NOT allowed (oid.1.2.23)
 *  - only numeric data
 *
 * Example of (full) args array:
 *  array(
 *   'file'          => 'someTable.rrd',              // [MANDATORY] RRD filename, but if not set used MIB_table.rrd as filename
 *   'call_function' => 'snmpwalk_cache_oid'          // [OPTIONAL] Which function to use for snmp poll, bu default snmpwalk_cache_oid()
 *   'mib'           => 'SOMETHING-MIB',              // [OPTIONAL] MIB or list of MIBs separated by a colon
 *   'mib_dir'       => 'something',                  // [OPTIONAL] OS MIB directory or array of directories
 *   'graphs'        => array('one','two'),           // [OPTIONAL] List of graph_types that this table provides
 *   'table'         => 'someTable',                  // [RECOMENDED] Table name for OIDs
 *   'numeric'       => '.1.3.6.1.4.1.555.4.1.1.48',  // [OPTIONAL] Numeric table OID
 *   'index'         => '1',                          // [OPTIONAL] Force an OID index for the entire table. If not set, equals '0'
 *   'ds_rename'     => array('http' => ''),          // [OPTIONAL] Array for renaming OIDs to DSes
 *   'oids'          => array(                        // List of OIDs you can use as key: full OID name
 *     'someOid' => array(                                 // OID name (You can use OID name, like 'cpvIKECurrSAs')
 *       'descr'     => 'Current IKE SAs',                 // [OPTIONAL] Description of the OID contents
 *       'numeric'   => '.1.3.6.1.4.1.555.4.1.1.48.45',    // [OPTIONAL] Numeric OID
 *       'index'     => '0',                               // [OPTIONAL] OID index, if not set equals '0'
 *       'ds_name'   => 'IKECurrSAs',                      // [OPTIONAL] DS name, if not set used OID name truncated to 19 chars
 *       'ds_type'   => 'GAUGE',                           // [OPTIONAL] DS type, if not set equals 'COUNTER'
 *       'ds_min'    => '0',                               // [OPTIONAL] Min value for DS, if not set equals 'U'
 *       'ds_max'    => '30000'                            // [OPTIONAL] Max value for DS, if not set equals '100000000000'
 *    )
 *  )
 *
 */
function collect_table($device, $oids_def, &$graphs)
{
  $rrd      = array();
  $mib      = NULL;
  $mib_dirs = NULL;
  $use_walk = isset($oids_def['table']) && $oids_def['table']; // Use snmpwalk by default
  $call_function = strtolower($oids_def['call_function']);
  switch ($call_function)
  {
    case 'snmp_get':
    case 'snmp_get_multi':
      $use_walk = FALSE;
      break;
    case 'snmpwalk_cache_bare_oid':
      break;
    case 'snmpwalk_cache_oid':
    default:
      $call_function = 'snmpwalk_cache_oid';
      if (!$use_walk)
      {
        // Break because we should use snmpwalk, but walking table not set
        return FALSE;
      }
  }
  if (isset($oids_def['numeric'])) { $oids_def['numeric'] = '.'.trim($oids_def['numeric'], '. '); } // Remove trailing dot
  if (isset($oids_def['mib']))     { $mib      = $oids_def['mib']; }
  if (isset($oids_def['mib_dir'])) { $mib_dirs = mib_dirs($oids_def['mib_dir']); }
  if (isset($oids_def['file']))
  {
    $rrd_file = $oids_def['file'];
  }
  elseif ($mib && isset($oids_def['table']))
  {
    // Try to use MIB & tableName as rrd_file
    $rrd_file = strtolower(safename($mib.'_'.$oids_def['table'])).'.rrd';
  } else {
    print_debug("  WARNING, not have rrd filename.");
    return FALSE; // Not have RRD filename
  }

  // Get MIBS/Tables/OIDs permissions
  if ($use_walk)
  {
    // if use table walk, than check only this table disabled (not oids)
    $disabled_tables = get_device_objects_disabled($device, $mib);
    if (in_array($oids_def['table'], $disabled_tables))
    {
      print_debug("  WARNING, table '".$oids_def['table']."' for '$mib' disabled and skipped.");
      return FALSE; // table disabled, exit
    }
  } else {
    // if use multi_get, than check all oids disabled
    $disabled_oids = get_device_objects_disabled($device, $mib);
    $oids_ok = array_diff(array_keys($oids_def['oids']), $disabled_oids);
    if (count($oids_ok) == 0)
    {
      print_debug("  WARNING, oids '".implode("', '", array_keys($oids_def['oids']))."' for '$mib' disabled and skipped.");
      return FALSE;  // All oids disabled, exit
    }
  }

  $search  = array();
  $replace = array();
  if (is_array($oids_def['ds_rename']))
  {
    foreach ($oids_def['ds_rename'] as $s => $r)
    {
      $search[]  = $s;
      $replace[] = $r;
    }
  }

  // rrd DS limit is 20 bytes (19 chars + NULL terminator)
  $ds_len = 19;

  $oids       = array();
  $oids_index = array();
  foreach ($oids_def['oids'] as $oid => $entry)
  {
    if (is_numeric($entry['numeric']) && isset($oids_def['numeric']))
    {
      $entry['numeric'] = $oids_def['numeric'] . '.' . $entry['numeric']; // Numeric oid, for future using
    }
    if (!isset($entry['index']) && isset($oids_def['index']))
    {
      $entry['index'] = $oids_def['index'];
    }
    elseif (!isset($entry['index']))
    {
      $entry['index'] = '0';
    }
    if (!isset($entry['ds_type'])) { $entry['ds_type'] = 'COUNTER'; }
    if (!isset($entry['ds_min']))  { $entry['ds_min']  = 'U'; }
    if (!isset($entry['ds_max']))  { $entry['ds_max']  = '100000000000'; }
    if (!isset($entry['ds_name']))
    {
      // Convert OID name to DS name
      $ds_name = $oid;
      if (is_array($oids_def['ds_rename'])) { $ds_name = str_replace($search, $replace, $ds_name); }
    } else {
      $ds_name = $entry['ds_name'];
    }
    if (strlen($ds_name) > $ds_len) { $ds_name = truncate($ds_name, $ds_len, ''); }

    if (isset($oids_def['no_index']) && $oids_def['no_index'] == TRUE)
    {
      $oids[]       = $oid;
    } else {
      $oids[]       = $oid.'.'.$entry['index'];
    }
    $oids_index[] = array('index' => $entry['index'], 'oid' => $oid);

    $rrd['rrd_create'][] = ' DS:'.$ds_name.':'.$entry['ds_type'].':600:'.$entry['ds_min'].':'.$entry['ds_max'];
    if (OBS_DEBUG) { $rrd['ds_list'][] = $ds_name; } // Make DS lists for compare with RRD file in debug
  }

  switch ($call_function)
  {
    case 'snmpwalk_cache_oid':
      $data = snmpwalk_cache_oid($device, $oids_def['table'], array(), $mib, $mib_dirs);
      break;
    case 'snmpwalk_cache_bare_oid':
      $data = snmpwalk_cache_bare_oid($device, $oids_def['table'], array(), $mib, $mib_dirs);
      break;
    case 'snmp_get':
    case 'snmp_get_multi':
    case 'snmp_get_multi_oid':
      $data = snmp_get_multi_oid($device, $oids, array(), $mib, $mib_dirs);
      break;
  }
  if (!snmp_status())
  {
    // Break because latest snmp walk/get return not good exitstatus (wrong mib/timeout/error/etc)
    print_debug("  WARNING, latest snmp walk/get return not good exitstatus for '$mib', RRD update skipped.");
    return FALSE;
  }

  //print_debug_vars($data);

  if (isset($oids_def['no_index']) && $oids_def['no_index'] == TRUE || $call_function == 'snmpwalk_cache_double_oid')
  {
    $data[0] = $data[''];
  }
  elseif ( $call_function == 'snmpwalk_cache_bare_oid')
  {
    $data[0] = $data;
  }

  print_debug_vars($data);

  foreach ($oids_index as $entry)
  {
    $index = $entry['index'];
    $oid   = $entry['oid'];

    if (is_numeric($data[$index][$oid]))
    {
      // The original OID definition from the table.
      $def = $oids_def['oids'][$oid];

      // Apply multiplier value from the entry
      if (isset($def['multiplier']) && $def['multiplier'] != 0)
      {
        $data[$index][$oid] = scale_value($data[$index][$oid], $def['multiplier']);
      }

      $rrd['ok']           = TRUE; // We have any data for current rrd_file
      $rrd['rrd_update'][] = $data[$index][$oid];
    } else {
      $rrd['rrd_update'][] = 'U';
    }
  }

  // Ok, all previous checks done, update RRD, table/oids permissions, $graphs
  if (isset($rrd['ok']) && $rrd['ok'])
  {
    // Create/update RRD file
    $rrd_create = implode('', $rrd['rrd_create']);
    $rrd_update = 'N:'.implode(':', $rrd['rrd_update']);
    rrdtool_create($device, $rrd_file, $rrd_create);
    rrdtool_update($device, $rrd_file, $rrd_update);

    foreach ($oids_def['graphs'] as $graph)
    {
      $graphs[$graph] = TRUE; // Set all graphs to TRUE
    }

    // Compare DSes form RRD file with DSes from array
    if (OBS_DEBUG)
    {
      $graph_template  = "\$config['graph_types']['device']['GRAPH_CHANGE_ME'] = array(\n";
      $graph_template .= "  'file'      => '$rrd_file',\n";
      $graph_template .= "  'ds'        => array(\n";
      $rrd_file_info = rrdtool_file_info(get_rrd_path($device, $rrd_file));
      foreach ($rrd_file_info['DS'] as $ds => $nothing)
      {
        $ds_list[] = $ds;
        $graph_template .= "    '$ds' => array('label' => '$ds'),\n";
      }
      $graph_template .= "  )\n);";
      $in_args = array_diff($rrd['ds_list'], $ds_list);
      if ($in_args)
      {
        print_message("%rWARNING%n, in file '%W".$rrd_file_info['filename']."%n' different DS lists. NOT have: ".implode(', ', $in_args));
      }
      $in_file = array_diff($ds_list, $rrd['ds_list']);
      if ($in_file)
      {
        print_message("%rWARNING%n, in file '%W".$rrd_file_info['filename']."%n' different DS lists. Excess: ".implode(', ', $in_file));
      }

      // Print example for graph template using rrd_file and ds list
      print_message($graph_template);
    }
  }
  else if ($use_walk)
  {
    // Table NOT exist on device!
    // Disable polling table (only if table not enabled manually in DB)

    // This code just disables collection forever after the first failed query!

    /*
    if (!dbFetchCell("SELECT COUNT(*) FROM `devices_mibs` WHERE `device_id` = ? AND `mib` = ?
                     AND `table_name` = ? AND (`oid` = '' OR `oid` IS NULL)", array($device['device_id'], $mib, $oids_def['table'])))
    {
      dbInsert(array('device_id' => $device['device_id'], 'mib' => $mib,
                     'table_name' => $oids_def['table'], 'disabled' => '1'), 'devices_mibs');
    }
    print_debug("  WARNING, table '".$oids_def['table']."' for '$mib' disabled.");
    */
  } else {
    // OIDs NOT exist on device!
    // Disable polling oids (only if table not enabled manually in DB)
    /*
    foreach (array_keys($oids_def['oids']) as $oid)
    {
      if (!dbFetchCell("SELECT COUNT(*) FROM `devices_mibs` WHERE `device_id` = ? AND `mib` = ?
                       AND `oid` = ?", array($device['device_id'], $mib, $oid)))
      {
        dbInsert(array('device_id' => $device['device_id'], 'mib' => $mib,
                       'oid' => $oid, 'disabled' => '1'), 'devices_mibs');
      }
    }
    print_debug("  WARNING, oids '".implode("', '", array_keys($oids_def['oids']))."' for '$mib' disabled.");
    */
  }

  // Return obtained snmp data
  return $data;
}

function poll_p2p_radio($device, $mib, $index, $radio)
{
  $params  = array('radio_tx_freq', 'radio_rx_freq', 'radio_tx_power', 'radio_rx_level', 'radio_name', 'radio_bandwidth', 'radio_modulation', 'radio_total_capacity', 'radio_standard', 'radio_loopback', 'radio_tx_mute', 'radio_eth_capacity', 'radio_e1t1_channels', 'radio_cur_capacity');

  if (is_array($GLOBALS['cache']['p2p_radios'][$mib][$index])) { $radio_db = $GLOBALS['cache']['p2p_radios'][$mib][$index]; }

  // Update the Database

  if (!isset($radio_db['radio_id']))  // If we don't have an entry already, create it
  {
    $insert = array();
    $insert['device_id'] = $device['device_id'];
    $insert['radio_mib'] = $mib;
    $insert['radio_index'] = $index;

    foreach ($params as $param)
    {
      $insert[$param] = $radio[$param];
      if ($radio[$param] == NULL) { $insert[$param] = array('NULL'); }
    }

    $radio_id = dbInsert($insert, 'p2p_radios');
    echo("+");

  } else {  // If we already have an entry, check if it needs updating

    $update = array();
    foreach ($params as $param)
    {
      if ($radio[$param] != $radio_db[$param]) { $update[$param] = $radio[$param]; }
    }
    if (count($update)) // If there have been changes, update it
    {
      dbUpdate($update, 'p2p_radios', '`radio_id` = ?', array($radio_db['radio_id']));
      echo('U');
    } else {
      echo('.');
    }
  }

  rrdtool_update_ng($device, 'p2p_radio', array(
    'tx_power'     => $radio['radio_tx_power'],
    'rx_level'     => $radio['radio_rx_level'],
    'rmse'         => $radio['radio_rmse'],
    'agc_gain'     => $radio['radio_agc_gain'],
    'cur_capacity' => $radio['radio_cur_capacity'],
    'sym_rate_tx'  => $radio['radio_sym_rate_tx'],
    'sym_rate_rx'  => $radio['radio_sym_rate_tx'],
  ), "$mib-$index");

  $GLOBALS['valid']['p2p_radio'][$mib][$index] = 1; // FIXME. What? How it passed there?
}

function update_application($app_id, $app_data)
{

  $update_array = array('app_json' => json_encode($app_data), 'app_lastpolled' => time());

  dbUpdate($update_array, 'applications', '`app_id` = ?', array($app_id));

}

// EOF
