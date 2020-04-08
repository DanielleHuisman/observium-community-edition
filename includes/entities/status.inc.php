<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 *
 */

/**
 * Return normalized state array by type, mib and value (numeric or string)
 *
 * @param string $type        State type, in MIBs definition array $config['mibs'][$mib]['states'][$type]
 * @param string $value       Polled value for status
 * @param string $mib         MIB name
 * @param string $event_map   Polled value for event (this used only for statuses with event condition values)
 * @param string $poller_type Poller type (snmp, ipmi, agent)
 *
 * @return array State array
 */
function get_state_array($type, $value, $mib = '', $event_map = NULL, $poller_type = 'snmp')
{
  global $config;

  $state_array = array('value' => FALSE);

  switch ($poller_type)
  {
    case 'agent':
    case 'ipmi':
      $state = state_string_to_numeric($type, $value, $mib, $poller_type);
      if ($state !== FALSE)
      {
        $state_array['value'] = $state; // Numeric value
        $state_array['name']  = $config[$poller_type]['states'][$type][$state]['name'];  // Named value
        $state_array['event'] = $config[$poller_type]['states'][$type][$state]['event']; // Event type
        $state_array['mib']   = $mib;
      }
      break;

    default: // SNMP
      $state = state_string_to_numeric($type, $value, $mib);
      if ($state !== FALSE)
      {
        if (!strlen($mib))
        {
          $mib = state_type_to_mib($type);
        }
        $state_array['value'] = $state; // Numeric value
        $state_array['name']  = $config['mibs'][$mib]['states'][$type][$state]['name'];  // Named value

        if (isset($config['mibs'][$mib]['states'][$type][$state]['event_map']))
        {
          // For events based on additional Oid value, see:
          // PowerNet-MIB::emsInputContactStatusInputContactState.1 = INTEGER: contactOpenEMS(2)
          // PowerNet-MIB::emsInputContactStatusInputContactNormalState.1 = INTEGER: normallyOpenEMS(2)

          // Find event associated event with event_value
          $state_array['event'] = $config['mibs'][$mib]['states'][$type][$state]['event_map'][$event_map];

        } else {
          // Normal static events
          $state_array['event'] = $config['mibs'][$mib]['states'][$type][$state]['event']; // Event type
        }
        $state_array['mib']   = $mib; // MIB name
      }
  }

  return $state_array;
}

/**
 * Converts named oid values to numerical interpretation based on oid descriptions and stored in definitions
 *
 * @param string $type Sensor type which has definitions in $config['mibs'][$mib]['states'][$type]
 * @param mixed $value Value which must be converted
 * @param string $mib MIB name
 * @param string $poller_type Poller type
 *
 * @return integer Note, if definition not found or incorrect value, returns FALSE
 */
function state_string_to_numeric($type, $value, $mib = '', $poller_type = 'snmp')
{
  switch ($poller_type)
  {
    case 'agent':
    case 'ipmi':
      if (!isset($GLOBALS['config'][$poller_type]['states'][$type]))
      {
        return FALSE;
      }
      $state_def = $GLOBALS['config'][$poller_type]['states'][$type];
      break;

    default:
      if (!strlen($mib))
      {
        $mib = state_type_to_mib($type);
      }
      $state_def = $GLOBALS['config']['mibs'][$mib]['states'][$type];
  }

  if (is_numeric($value))
  {
    // Return value if already numeric
    if ($value == (int)$value && isset($state_def[(int)$value]))
    {
      return (int)$value;
    } else {
      return FALSE;
    }
  }
  foreach ($state_def as $index => $content)
  {
    if (strcasecmp($content['name'], trim($value)) == 0) { return $index; }
  }

  return FALSE;
}

/**
 * Helper function for get MIB name by status type.
 * Currently we use unique status types over all MIBs
 *
 * @param string $state_type Unique status type
 *
 * @return string MIB name corresponding to this type
 */
function state_type_to_mib($state_type)
{
  // By first cache all type -> mib from definitions
  if (!isset($GLOBALS['cache']['state_type_mib']))
  {
    $GLOBALS['cache']['state_type_mib'] = array();
    // $config['mibs'][$mib]['states']['dskf-mib-hum-state'][0] = array('name' => 'error',    'event' => 'alert');
    foreach ($GLOBALS['config']['mibs'] as $mib => $entries)
    {
      if (!isset($entries['states'])) { continue; }
      foreach ($entries['states'] as $type => $entry)
      {
        if (isset($GLOBALS['cache']['state_type_mib'][$type]))
        {
          // Disabling because it's annoying for now - pending some rewriting.
          //print_warning('Warning, status type name "'.$type.'" for MIB "'.$mib.'" also exist in MIB "'.$GLOBALS['cache']['state_type_mib'][$type].'". Type name MUST be unique!');
        }
        $GLOBALS['cache']['state_type_mib'][$type] = $mib;
      }
    }
  }

  //print_vars($GLOBALS['cache']['state_type_mib']);
  return $GLOBALS['cache']['state_type_mib'][$state_type];
}

function discover_status_definition($device, $mib, $entry)
{

  echo($entry['oid']. ' [');

  // Check that types listed in skip_if_valid_exist have already been found
  if (discovery_check_if_type_exist($GLOBALS['valid'], $entry, 'status')) { echo '!]'; return; }

  // Check array requirements list
  if (discovery_check_requires_pre($device, $entry, 'status')) { echo '!]'; return; }

  // Fetch table or Oids
  $table_oids = array('oid', 'oid_descr', 'oid_class', 'oid_map', 'oid_extra', 'oid_entPhysicalIndex');
  $status_array = discover_fetch_oids($device, $mib, $entry, $table_oids);

  if (empty($entry['oid_num']))
  {
    // Use snmptranslate if oid_num not set
    $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
  } else {
    $entry['oid_num'] = rtrim($entry['oid_num'], '.');
  }

  $i = 0; // Used in descr as $i++
  $status_count = count($status_array);
  foreach ($status_array as $index => $status)
  {
    $options = array();

    $dot_index = '.' . $index;
    $oid_num   = $entry['oid_num'] . $dot_index;

    //echo PHP_EOL; print_vars($entry); echo PHP_EOL; print_vars($status); echo PHP_EOL; print_vars($descr); echo PHP_EOL;

    // %i% can be used in description
    $i++;

    if (isset($entry['oid_class']) && strlen($status[$entry['oid_class']]))
    {
      if (isset($entry['map_class'][$status[$entry['oid_class']]]))
      {
        // Try override measured class by map
        $class = $entry['map_class'][$status[$entry['oid_class']]];
        if ($class == 'exclude' || !$class)
        {
          print_debug("Excluded by empty class map value.");
          continue; // trigger for exclude statuses
        }
      } else {
        $class = $status[$entry['oid_class']];
      }
    } else {
      $class = $entry['measured'];
    }
    // Generate specific keys used during rewrites

    $status['class'] = nicecase($class); // Class in descr
    $status['index'] = $index;           // Index in descr
    $status['i']     = $i;               // i++ counter in descr

    // Check array requirements list
    if (discovery_check_requires($device, $entry, $status, 'status')) { continue; }

    $value = $status[$entry['oid']];
    if (!strlen($value))
    {
      print_debug("Excluded by empty current value.");
      continue;
    }

    $options = array('entPhysicalClass' => $class);

    // Definition based events
    if (isset($entry['oid_map']) && $status[$entry['oid_map']])
    {
      $options['status_map'] = $status[$entry['oid_map']];
    }

    // Rule-based entity linking.
    if ($measured = entity_measured_match_definition($device, $entry, $status, 'status'))
    {
      $options = array_merge($options, $measured);
      $status  = array_merge($status, $measured); // append to $status for %descr% tags, ie %port_label%
      if (empty($class))
      {
        $options['entPhysicalClass'] = $measured['measured'];
      }
    }
    // End rule-based entity linking
    else if (isset($entry['entPhysicalIndex']))
    {
      // Just set physical index
      $options['entPhysicalIndex'] = array_tag_replace($status, $entry['entPhysicalIndex']);
    }

    // Generate Description
    $descr = entity_descr_definition('status', $entry, $status, $status_count);

    // Rename old (converted) RRDs to definition format
    if (isset($entry['rename_rrd']))
    {
      $options['rename_rrd'] = $entry['rename_rrd'];
    }
    else if (isset($entry['rename_rrd_full']))
    {
      $options['rename_rrd_full'] = $entry['rename_rrd_full'];
    }

    discover_status_ng($device, $mib, $entry['oid'], $oid_num, $index, $entry['type'], $descr, $value, $options);

  }

  echo '] ';

}

// Compatibility wrapper!
function discover_status($device, $numeric_oid, $index, $type, $status_descr, $value = NULL, $options = array(), $poller_type = NULL)
{
  if (isset($poller_type)) { $options['poller_type'] = $poller_type; }

  return discover_status_ng($device, '', '', $numeric_oid, $index, $type, $status_descr, $value, $options);
}

// TESTME needs unit testing
/**
 * Discover a new status sensor on a device - called from discover_sensor()
 *
 * This function adds a status sensor to a device, if it does not already exist.
 * Data on the sensor is updated if it has changed, and an event is logged with regards to the changes.
 *
 * @param array $device        Device array status sensor is being discovered on
 * @param string $mib          SNMP MIB name
 * @param string $object       SNMP Named Oid of sensor (without index)
 * @param string $oid          SNMP Numeric Oid of sensor (without index)
 * @param string $index        SNMP index of status sensor
 * @param string $type         Type of status sensor (used as key in $config['status_states'])
 * @param string $status_descr Description of status sensor
 * @param string $value        Current value of status sensor
 * @param array $options       Options
 *
 * @return bool
 */
function discover_status_ng($device, $mib, $object, $oid, $index, $type, $status_descr, $value = NULL, $options = array())
{
  global $config;

  $poller_type   = (isset($options['poller_type']) ? $options['poller_type'] : 'snmp');

  $status_deleted = 0;

  // Init main
  $param_main = array('oid' => 'status_oid', 'status_descr' => 'status_descr', 'status_deleted' => 'status_deleted',
                      'index' => 'status_index', 'mib' => 'status_mib', 'object' => 'status_object');

  // Init optional
  $param_opt = array('entPhysicalIndex', 'entPhysicalClass', 'entPhysicalIndex_measured', 'measured_class', 'measured_entity', 'status_map');
  foreach ($param_opt as $key)
  {
    $$key = ($options[$key] ? $options[$key] : NULL);
  }

  $state_array = get_state_array($type, $value, $mib, $status_map, $poller_type);
  $state = $state_array['value'];
  if ($state === FALSE)
  {
    print_debug("Skipped by unknown state value: $value, $status_descr ");
    return FALSE;
  }
  else if ($state_array['event'] == 'exclude')
  {
    print_debug("Skipped by 'exclude' event value: ".$config['status_states'][$type][$state]['name'].", $status_descr ");
    return FALSE;
  }
  $value = $state;
  $index = strval($index); // Convert to string, for correct compare

  print_debug("Discover status: [device: ".$device['hostname'].", oid: $oid, index: $index, type: $type, descr: $status_descr, CURRENT: $value, $entPhysicalIndex, $entPhysicalClass]");

  // Check sensor ignore filters
  foreach ($config['ignore_sensor'] as $bi)        { if (strcasecmp($bi, $status_descr) == 0)   { print_debug("Skipped by equals: $bi, $status_descr "); return FALSE; } }
  foreach ($config['ignore_sensor_string'] as $bi) { if (stripos($status_descr, $bi) !== FALSE) { print_debug("Skipped by strpos: $bi, $status_descr "); return FALSE; } }
  foreach ($config['ignore_sensor_regexp'] as $bi) { if (preg_match($bi, $status_descr) > 0)    { print_debug("Skipped by regexp: $bi, $status_descr "); return FALSE; } }

  $new_definition = $poller_type == 'snmp' && strlen($mib) && strlen($object);
  if ($new_definition)
  {
    $where  = ' WHERE `device_id` = ? AND `status_mib` = ? AND `status_object` = ? AND `status_type` = ? AND `status_index` = ? AND `poller_type`= ?';
    $params = array($device['device_id'], $mib, $object, $type, $index, $poller_type);
    $status_exist = dbExist('status', $where, $params);

    // Check if old format of status was exist, than rename rrd
    if (!$status_exist)
    {
      $old_where  = ' WHERE `device_id` = ? AND `status_type` = ? AND `status_index` = ? AND `poller_type`= ?';
      $old_index  = $object . '.' . $index;
      $old_params = array($device['device_id'], $type, $old_index, $poller_type);

      if ($status_exist = dbExist('status', $old_where, $old_params))
      {
        $where        = $old_where;
        $params       = $old_params;

        // Rename old rrds without mib & object to new rrd name style
        if (!isset($options['rename_rrd']))
        {
          $options['rename_rrd'] = $type . "-" . $old_index;
        }
      }
    }
  } else {
    // Old format of definitions
    $where  = ' WHERE `device_id` = ? AND `status_type` = ? AND `status_index` = ? AND `poller_type`= ?';
    $params = array($device['device_id'], $type, $index, $poller_type);
    $status_exist = dbExist('status', $where, $params);
  }
  //if (dbFetchCell('SELECT COUNT(*) FROM `status`' . $where, $params) == '0')
  if (!$status_exist)
  {
    $status_insert = array('poller_type'  => $poller_type,
                           'device_id'    => $device['device_id'],
                           'status_index' => $index,
                           'status_type'  => $type,
                           //'status_id'    => $status_id,
                           'status_value' => $value,
                           'status_polled' => time(), //array('NOW()'), // this field is INT(11)
                           'status_event' => $state_array['event'],
                           'status_name'  => $state_array['name']
    );

    foreach ($param_main as $key => $column)
    {
      $status_insert[$column] = $$key;
    }

    foreach ($param_opt as $key)
    {
      if (is_null($$key)) { $$key = array('NULL'); } // If param null, convert to array(NULL)
      $status_insert[$key] = $$key;
    }

    $status_id = dbInsert($status_insert, 'status');

    print_debug("( $status_id inserted )");
    echo('+');
    log_event("Status added: $entPhysicalClass $type $index $status_descr", $device, 'status', $status_id);
  } else {
    $status_entry = dbFetchRow('SELECT * FROM `status`' . $where, $params);
    $status_id = $status_entry['status_id'];

    print_debug_vars($status_entry);
    $update = array();
    foreach ($param_main as $key => $column)
    {
      if ($$key != $status_entry[$column])
      {
        $update[$column] = $$key;
      }
    }
    foreach ($param_opt as $key)
    {
      if ($$key != $status_entry[$key])
      {
        $update[$key] = $$key;
      }
    }
    print_debug_vars($update);

    if (count($update))
    {
      $updated = dbUpdate($update, 'status', '`status_id` = ?', array($status_entry['status_id']));
      echo('U');
      log_event("Status updated: $entPhysicalClass $type $index $status_descr", $device, 'status', $status_entry['status_id']);
    } else {
      echo('.');
    }
  }

  // Rename old (converted) RRDs to definition format
  // Allow with changing class or without
  if (isset($options['rename_rrd_full']))
  {
    // Compatibility with sensor option
    $options['rename_rrd'] = $options['rename_rrd_full'];
  }
  if (isset($options['rename_rrd']))
  {
    $rrd_tags = array('index' => $index, 'type' => $type, 'mib' => $mib, 'object' => $object, 'oid' => $object);
    $options['rename_rrd'] = array_tag_replace($rrd_tags, $options['rename_rrd']);
    $old_rrd               = 'status-' . $options['rename_rrd'];

    //$new_rrd = 'status-'.$type.'-'.$index;
    $new_entry = ['status_descr' => $status_descr, 'status_mib' => $mib, 'status_object' => $object,
                  'status_type' => $type, 'status_index' => $index, 'poller_type' => $poller_type];
    $new_rrd = get_status_rrd($device, $new_entry);
    rename_rrd($device, $old_rrd, $new_rrd);
  }

  if (strlen($mib))
  {
    $GLOBALS['valid']['status'][$mib][$type][$index] = 1;
  } else {
    $GLOBALS['valid']['status']['__'][$type][$index] = 1;
  }

  return $status_id;
  //return TRUE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function check_valid_status($device, $valid, $poller_type = 'snmp')
{
  $entries = dbFetchRows("SELECT * FROM `status` WHERE `device_id` = ? AND `poller_type` = ? AND `status_deleted` = '0'", array($device['device_id'], $poller_type));

  if (is_array($entries) && count($entries))
  {
    foreach ($entries as $entry)
    {
      $index = $entry['status_index'];
      $type  = $entry['status_type'];
      $mib   = strlen($entry['status_mib']) ? $entry['status_mib'] : '__';
      if (!$valid[$mib][$type][$index])
      {
        echo("-");
        print_debug("Status deleted: $index -> $type");
        //dbDelete('status',       "`status_id` = ?", array($entry['status_id']));
        //dbDelete('status-state', "`status_id` = ?", array($entry['status_id']));

        dbUpdate(array('status_deleted' => '1'), 'status', '`status_id` = ?', array($entry['status_id']));

        foreach (get_entity_attribs('status', $entry['status_id']) as $attrib_type => $value)
        {
          del_entity_attrib('status', $entry['status_id'], $attrib_type);
        }
        log_event("Status deleted: ".$entry['status_class']." ".$entry['status_type']." ". $entry['status_index']." ".$entry['status_descr'], $device, 'status', $entry['status_id']);
      }
    }
  }
}

function poll_status($device, &$oid_cache)
{
  global $config, $agent_sensors, $ipmi_sensors, $graphs, $table_rows;

  $sql  = "SELECT * FROM `status`";
  //$sql .= " LEFT JOIN `status-state` USING(`status_id`)";
  $sql .= " WHERE `device_id` = ? AND `status_deleted` = ?";
  $sql .= ' ORDER BY `status_oid`'; // This fix polling some OIDs (when not ordered)

  foreach (dbFetchRows($sql, array($device['device_id'], '0')) as $status_db)
  {
    //print_cli_heading("Status: ".$status_db['status_descr']. "(".$status_db['poller_type'].")", 3);

    print_debug("Checking (" . $status_db['poller_type'] . ") " . $status_db['status_descr'] . " ");

    // $status_poll = $status_db;    // Cache non-humanized status array for use as new status state

    if ($status_db['poller_type'] == "snmp")
    {
      $status_db['status_oid'] = '.' . ltrim($status_db['status_oid'], '.'); // Fix first dot in oid for caching

      // Check if a specific poller file exists for this status, else collect via SNMP.
      $file = $config['install_dir']."/includes/polling/status/".$status_db['status_type'].".inc.php";

      if (is_file($file))
      {
        include($file);
      } else {
        // Take value from $oid_cache if we have it, else snmp_get it
        if (isset($oid_cache[$status_db['status_oid']]))
        {
          print_debug("value taken from oid_cache");
          $status_value = $oid_cache[$status_db['status_oid']];
        } else {
          $status_value = snmp_get_oid($device, $status_db['status_oid'], 'SNMPv2-MIB');
        }
        //$status_value = snmp_fix_numeric($status_value); // Do not use fix, this broke not-enum (string) statuses
      }
    }
    else if ($status_db['poller_type'] == "agent")
    {
      if (isset($agent_sensors['state']))
      {
        $status_value = $agent_sensors['state'][$status_db['status_type']][$status_db['status_index']]['current'];
      } else {
        print_warning("No agent status data available.");
        continue;
      }
    }
    else if ($status_db['poller_type'] == "ipmi")
    {
      if (isset($ipmi_sensors['state']))
      {
        $status_value = $ipmi_sensors['state'][$status_db['status_type']][$status_db['status_index']]['current'];
      } else {
        print_warning("No IPMI status data available.");
        continue;
      }
    } else {
      print_warning("Unknown status poller type.");
      continue;
    }

    $status_polled_time = time(); // Store polled time for current status

    // Write new value and humanize (for alert checks)
    $state_array = get_state_array($status_db['status_type'], $status_value, $status_db['status_mib'], $status_db['status_map'], $status_db['poller_type']);
    $status_value                = $state_array['value']; // Override status_value by numeric for "pseudo" (string) statuses
    $status_poll['status_value'] = $state_array['value'];
    $status_poll['status_name']  = $state_array['name'];
    if ($status_db['status_ignore'] || $status_db['status_disable'])
    {
      $status_poll['status_event'] = 'ignore';
    } else {
      $status_poll['status_event'] = $state_array['event'];
    }

    // If last change never set, use current time
    if (empty($status_db['status_last_change']))
    {
      $status_db['status_last_change'] = $status_polled_time;
    }

    if ($status_poll['status_event'] != $status_db['status_event'])
    {
      // Status event changed, log and set status_last_change
      $status_poll['status_last_change'] = $status_polled_time;

      if ($status_poll['status_event'] == 'ignore')
      {
        print_message("[%ystatus Ignored%n]", 'color');
      }
      else if ($status_db['status_event'] != '')
      {
        // If old state not empty and new state not equals to new state
        $msg = 'Status ' . ucfirst($status_poll['status_event']) . ': ' . $device['hostname'] . ' ' . $status_db['status_descr'] .
          ' entered ' . strtoupper($status_poll['status_event']) . ' state: ' . $status_poll['status_name'] .
          ' (previous: ' . $status_db['status_name'] . ')';

        if (isset($config['entity_events'][$status_poll['status_event']]))
        {
          $severity = $config['entity_events'][$status_poll['status_event']]['severity'];
        } else {
          $severity = 'informational';
        }
        log_event($msg, $device, 'status', $status_db['status_id'], $severity);

      }
    } else {
      // If status not changed, leave old last_change
      $status_poll['status_last_change'] = $status_db['status_last_change'];
    }

    print_debug_vars($status_poll);

    // Send statistics array via AMQP/JSON if AMQP is enabled globally and for the ports module
    if ($config['amqp']['enable'] == TRUE && $config['amqp']['modules']['status'])
    {
      $json_data = array('value' => $status_value);
      messagebus_send(array('attribs' => array('t' => time(), 'device' => $device['hostname'], 'device_id' => $device['device_id'],
                                               'e_type' => 'status', 'e_type' => $status_db['status_type'], 'e_index' => $status_db['status_index']), 'data' => $json_data));
    }

    // Update StatsD/Carbon
    if ($config['statsd']['enable'] == TRUE)
    {
      StatsD::gauge(str_replace(".", "_", $device['hostname']).'.'.'status'.'.'.$status_db['status_class'].'.'.$status_db['status_type'].'.'.$status_db['status_index'], $status_value);
    }

    // Update RRD - FIXME - can't convert to NG because filename is dynamic! new function should return index instead of filename.
    $rrd_file = get_status_rrd($device, $status_db);
    rrdtool_create($device, $rrd_file, "DS:status:GAUGE:600:-20000:U");
    rrdtool_update($device, $rrd_file,"N:$status_value");

    // Enable graph
    $graphs[$status_db['status']] = TRUE;

    // Check alerts
    $metrics = array();

    $metrics['status_value'] = $status_value;
    $metrics['status_name']  = $status_poll['status_name'];
    $metrics['status_name_uptime'] = $status_polled_time - $status_poll['status_last_change'];
    $metrics['status_event'] = $status_poll['status_event'];

    //print_cli_data("Event (State)", $status_poll['status_event'] ." (".$status_poll['status_name'].")", 3);

    $table_rows[] = array($status_db['status_descr'], $status_db['status_type'], $status_db['status_index'] ,$status_db['poller_type'],
                          $status_poll['status_name'], $status_poll['status_event'], format_unixtime($status_poll['status_last_change']));

    check_entity('status', $status_db, $metrics);

    // Add to MultiUpdate SQL State

    $GLOBALS['multi_update_db'][] = array(
      'status_id'          => $status_db['status_id'], // UNIQUE index
      'status_value'       => $status_value,
      'status_name'        => $status_poll['status_name'],
      'status_event'       => $status_poll['status_event'],
      'status_last_change' => $status_poll['status_last_change'],
      'status_polled'      => $status_polled_time);
    //dbUpdate(array('status_value'  => $status_value,
    //               'status_name'   => $status_poll['status_name'],
    //               'status_event'  => $status_poll['status_event'],
    //               'status_last_change' => $status_poll['status_last_change'],
    //               'status_polled' => $status_polled_time),
    //               'status', '`status_id` = ?', array($status_db['status_id']));
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_status_rrd($device, $status)
{
  global $config;

  # For IPMI, sensors tend to change order, and there is no index, so we prefer to use the description as key here.
  if ($config['os'][$device['os']]['status_descr'] || ($status['poller_type'] != "snmp" && $status['poller_type'] != ''))
  {
    $rrd_file = "status-" . $status['status_type'] . "-" . $status['status_descr'] . ".rrd";
  }
  elseif (strlen($status['status_mib']) && strlen($status['status_object']))
  {
    // for discover_status_ng(), note here is just status index
    $rrd_file = "status-" . $status['status_mib'] . "-" . $status['status_object'] . "-" . $status['status_index'] . ".rrd";
  } else {
    // for discover_status(), note index == "%object%.%index%"
    $rrd_file = "status-".$status['status_type']."-".$status['status_index'] . ".rrd";
  }

  return($rrd_file);
}

// EOF
