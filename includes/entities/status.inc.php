<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/// MOVEME. states.inc.php
function get_bits_state_array($hex, $mib = NULL, $object = NULL, $bits_def = NULL)
{
    global $config;

    // Fetch validate BITS definition
    if (!empty($bits_def) && is_array($bits_def)) {
        // Use passed bits definitions
        $def = $bits_def;
    } elseif (!safe_empty($mib) && !safe_empty($object) &&
              isset($config['mibs'][$mib]['states_bits'][$object])) {
        $def = $config['mibs'][$mib]['states_bits'][$object];
    }

    if (empty($def) || !is_array($def)) {
        print_debug("Incorrect BITS state definition passed.");
        return [];
    }
    //print_debug_vars($def);

    //$bit_array = array_reverse(str_split(hex2binmap($hex)));
    $bit_array = str_split(hex2binmap($hex));
    //print_debug_vars($bit_array);

    $state_array = [];
    foreach ($bit_array as $bit => $set) {
        if ($set) {
            $state_array[$bit] = $def[$bit]['name'];
        }
    }

    return $state_array;
}

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

    $state_array = ['value' => FALSE];

    switch ($poller_type) {
        case 'agent':
        case 'ipmi':
            $state = state_string_to_numeric($type, $value, $mib, $poller_type);
            if ($state !== FALSE) {
                $state_array['value'] = $state; // Numeric value
                if (isset(array_values($config[$poller_type]['states'][$type])[0]['match'])) {
                    // String value
                    $match_events         = [
                      0 => 'exclude',
                      1 => 'ok',
                      2 => 'warning',
                      3 => 'alert',
                      4 => 'ignore',
                    ];
                    $state_array['name']  = trim($value);
                    $state_array['event'] = $match_events[$state];
                } else {
                    $state_array['name']  = $config[$poller_type]['states'][$type][$state]['name'];  // Named value
                    $state_array['event'] = $config[$poller_type]['states'][$type][$state]['event']; // Event type
                }
                $state_array['mib'] = $mib;
            }
            break;

        default: // SNMP
            $value = trim($value);
            $state = state_string_to_numeric($type, $value, $mib);
            if ($state !== FALSE) {
                if (safe_empty($mib)) {
                    $mib = state_type_to_mib($type);
                }
                $state_array['value'] = $state; // Numeric value
                $state_def            = $config['mibs'][$mib]['states'][$type];
                $def_key              = array_key_first($state_def);
                if (isset($state_def[$def_key]['match'])) {
                    // String value
                    $match_events        = [
                      0 => 'exclude',
                      1 => 'ok',
                      2 => 'warning',
                      3 => 'alert',
                      4 => 'ignore',
                    ];
                    $state_array['name'] = $value;
                    if (isset($state_def[$def_key]['name'])) {
                        // Rare case, for convert numeric states to named, see: GEIST-MIB-V3::waterSensorDampness
                        foreach ($state_def as $index => $content) {
                            if (preg_match($content['match'], $value)) {
                                //return $match_events[$content['event']];
                                $state_array['name'] = $content['name'];
                            }
                        }
                    }
                    $state_array['event'] = $match_events[$state];
                } else {
                    // Named value
                    $state_array['name'] = $state_def[$state]['name'];

                    if (isset($state_def[$state]['event_map'])) {
                        // For events based on additional Oid value, see:
                        // PowerNet-MIB::emsInputContactStatusInputContactState.1 = INTEGER: contactOpenEMS(2)
                        // PowerNet-MIB::emsInputContactStatusInputContactNormalState.1 = INTEGER: normallyOpenEMS(2)

                        // Find event associated event with event_value
                        $state_array['event'] = $state_def[$state]['event_map'][$event_map];

                    } else {
                        // Normal static events
                        $state_array['event'] = $state_def[$state]['event']; // Event type
                    }
                }

                // Force discovery by event
                if (isset($state_def[$state]['discovery'])) {
                    $state_array['discovery'] = $state_def[$state]['discovery'];
                }

                $state_array['mib'] = $mib; // MIB name
            }
    }

    return $state_array;
}

/**
 * Converts named oid values to numerical interpretation based on oid descriptions and stored in definitions
 *
 * @param string $type        Sensor type which has definitions in $config['mibs'][$mib]['states'][$type]
 * @param mixed  $value       Value which must be converted
 * @param string $mib         MIB name
 * @param string $poller_type Poller type
 *
 * @return integer Note, if definition not found or incorrect value, returns FALSE
 */
function state_string_to_numeric($type, $value, $mib = '', $poller_type = 'snmp')
{
    switch ($poller_type) {
        case 'agent':
        case 'ipmi':
            if (!isset($GLOBALS['config'][$poller_type]['states'][$type])) {
                return FALSE;
            }
            $state_def = $GLOBALS['config'][$poller_type]['states'][$type];
            break;

        default:
            if (safe_empty($mib)) {
                $mib = state_type_to_mib($type);
            }
            $state_def = $GLOBALS['config']['mibs'][$mib]['states'][$type];
    }

    if (!is_array($state_def)) {
        return FALSE;
    }

    //$state_match = isset(array_values($state_def)[0]['match']);
    if (isset($state_def[array_key_first($state_def)]['match'])) {
        // different type of statuses - string statuses, not enum
        // Match by regex
        // See QSAN-SNMP-MIB definitions and others
        // 0 -> exclude, 1 -> ok, 2 -> warning, 3 -> alert, 4 -> ignore
        $match_events = [
          'exclude' => 0,
          'ok'      => 1,
          'warning' => 2,
          'alert'   => 3,
          'ignore'  => 4,
        ];
        foreach ($state_def as $index => $content) {
            if (preg_match($content['match'], trim($value))) {
                return $match_events[$content['event']];
            }
        }
        return 1; // by default ok
    }

    if (is_intnum($value)) {
        // Return value if already numeric
        if (isset($state_def[$value])) {
            return (int)$value;
        }
        return FALSE;
    }

    foreach ($state_def as $index => $content) {
        if (strcasecmp($content['name'], trim($value)) == 0) {
            return $index;
        }
    }

    // 0x01
    if (str_starts($value, '0x')) {
        $int = (int)hexdec($value);
        if (isset($state_def[$int])) {
            return $int;
        }
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
    if (!isset($GLOBALS['cache']['state_type_mib'])) {
        $GLOBALS['cache']['state_type_mib'] = [];
        // $config['mibs'][$mib]['states']['dskf-mib-hum-state'][0] = array('name' => 'error',    'event' => 'alert');
        foreach ($GLOBALS['config']['mibs'] as $mib => $entries) {
            if (!isset($entries['states'])) {
                continue;
            }
            foreach ($entries['states'] as $type => $entry) {
                if (isset($GLOBALS['cache']['state_type_mib'][$type])) {
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

    echo($entry['oid'] . ' [');

    // Just append mib name to definition entry, for simple pass to external functions
    if (empty($entry['mib'])) {
        $entry['mib'] = $mib;
    }

    // Check that types listed in skip_if_valid_exist have already been found
    if (discovery_check_if_type_exist($entry, 'status')) {
        echo '!]';
        return;
    }

    // Check array requirements list
    if (discovery_check_requires_pre($device, $entry, 'status')) {
        echo '!]';
        return;
    }

    // Validate if Oid exist for current mib (in case when used generic definitions, ie edgecore)
    if (empty($entry['oid_num'])) {
        // Use snmptranslate if oid_num not set
        $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
        if (empty($entry['oid_num'])) {
            echo("]");
            print_debug("Oid [" . $entry['oid'] . "] not exist for mib [$mib]. Status skipped.");
            return;
        }
    } else {
        $entry['oid_num'] = rtrim($entry['oid_num'], '.');
    }

    // Fetch table or Oids
    $table_oids   = ['oid', 'oid_descr', 'oid_class', 'oid_map', 'oid_extra', 'oid_entPhysicalIndex'];
    $status_array = discover_fetch_oids($device, $mib, $entry, $table_oids);

    $i            = 0; // Used in descr as $i++
    $status_count = count($status_array);
    foreach ($status_array as $index => $status) {
        $options = [];

        $dot_index = strlen($index) ? '.' . $index : '';
        $oid_num   = $entry['oid_num'] . $dot_index;

        //echo PHP_EOL; print_vars($entry); echo PHP_EOL; print_vars($status); echo PHP_EOL; print_vars($descr); echo PHP_EOL;

        // %i% can be used in description
        $i++;

        $status = array_merge($status, entity_index_tags($index, $i));

        $class = entity_class_definition($device, $entry, $status, 'status');
        if ($class === 'exclude' || $class === FALSE) {
            continue; // trigger for exclude statuses
        }
        if (empty($class)) {
            $class = $entry['measured'];
        }

        // Generate specific keys used during rewrites
        $status['class'] = nicecase($class); // Class in descr

        // Check valid exist with entity tags
        if (discovery_check_if_type_exist($entry, 'status', $status)) {
            continue;
        }

        // Check array requirements list
        if (discovery_check_requires($device, $entry, $status, 'status')) {
            continue;
        }

        $value = $status[$entry['oid']];
        if (!discovery_check_value_valid($device, $value, $entry, 'status')) {
            continue;
        }

        $options = ['entPhysicalClass' => $class];

        // Definition based events
        if (isset($entry['oid_map']) && $status[$entry['oid_map']]) {
            $options['status_map'] = $status[$entry['oid_map']];
        }

        // Rule-based entity linking.
        if ($measured = entity_measured_match_definition($device, $entry, $status, 'status')) {
            $options = array_merge($options, $measured);
            $status  = array_merge($status, $measured); // append to $status for %descr% tags, ie %port_label%
            if (empty($class)) {
                $options['entPhysicalClass'] = $measured['measured'];
            }
        } elseif (isset($entry['entPhysicalIndex'])) {
            // Just set physical index
            $options['entPhysicalIndex'] = array_tag_replace($status, $entry['entPhysicalIndex']);
        }

        // Generate Description
        $descr = entity_descr_definition('status', $entry, $status, $status_count);

        // Rename old (converted) RRDs to definition format
        if (isset($entry['rename_rrd'])) {
            $options['rename_rrd'] = $entry['rename_rrd'];
        } elseif (isset($entry['rename_rrd_full'])) {
            $options['rename_rrd_full'] = $entry['rename_rrd_full'];
        }

        discover_status_ng($device, $mib, $entry['oid'], $oid_num, $index, $entry['type'], $descr, $value, $options);

    }

    echo '] ';

}

// Compatibility wrapper!
function discover_status($device, $numeric_oid, $index, $type, $status_descr, $value = NULL, $options = [], $poller_type = NULL)
{
    if (isset($poller_type)) {
        $options['poller_type'] = $poller_type;
    }

    return discover_status_ng($device, '', '', $numeric_oid, $index, $type, $status_descr, $value, $options);
}

// TESTME needs unit testing
/**
 * Discover a new status sensor on a device - called from discover_sensor()
 *
 * This function adds a status sensor to a device, if it does not already exist.
 * Data on the sensor is updated if it has changed, and an event is logged with regards to the changes.
 *
 * @param array  $device       Device array status sensor is being discovered on
 * @param string $mib          SNMP MIB name
 * @param string $object       SNMP Named Oid of sensor (without index)
 * @param string $oid          SNMP Numeric Oid of sensor (without index)
 * @param string $index        SNMP index of status sensor
 * @param string $type         Type of status sensor (used as key in $config['status_states'])
 * @param string $status_descr Description of status sensor
 * @param string $value        Current value of status sensor
 * @param array  $options      Options
 *
 * @return bool
 */
function discover_status_ng($device, $mib, $object, $oid, $index, $type, $status_descr, $value = NULL, $options = [])
{
    global $config;

    $poller_type = (isset($options['poller_type']) ? $options['poller_type'] : 'snmp');

    $status_deleted = 0;

    // Init main
    $param_main = [
      'oid'   => 'status_oid', 'status_descr' => 'status_descr', 'status_deleted' => 'status_deleted',
      'index' => 'status_index', 'mib' => 'status_mib', 'object' => 'status_object'
    ];

    // Init optional
    $param_opt = [
      'entPhysicalIndex', 'entPhysicalClass', 'entPhysicalIndex_measured',
      'measured_class', 'measured_entity', 'measured_entity_label', 'status_map'
    ];
    foreach ($param_opt as $key) {
        $$key = $options[$key] ?: NULL;
    }

    $state_array = get_state_array($type, $value, $mib, $status_map, $poller_type);
    $state       = $state_array['value'];
    if ($state === FALSE) {
        print_debug("Skipped by unknown state value: $value, $status_descr ");
        return FALSE;
    }
    if ($state_array['event'] === 'exclude') {
        print_debug("Skipped by 'exclude' event value: " . $config['status_states'][$type][$state]['name'] . ", $status_descr ");
        return FALSE;
    }
    $value = $state;
    $index = (string)$index; // Convert to string, for correct compare

    print_debug("Discover status: [device: " . $device['hostname'] . ", oid: $oid, index: $index, type: $type, descr: $status_descr, CURRENT: $value, $entPhysicalIndex, $entPhysicalClass]");

    // Check status ignore filters
    if (entity_descr_check($status_descr, 'status')) {
        return FALSE;
    }
    //foreach ($config['ignore_sensor'] as $bi)        { if (strcasecmp($bi, $status_descr) == 0)   { print_debug("Skipped by equals: $bi, $status_descr "); return FALSE; } }
    //foreach ($config['ignore_sensor_string'] as $bi) { if (stripos($status_descr, $bi) !== FALSE) { print_debug("Skipped by strpos: $bi, $status_descr "); return FALSE; } }
    //foreach ($config['ignore_sensor_regexp'] as $bi) { if (preg_match($bi, $status_descr) > 0)    { print_debug("Skipped by regexp: $bi, $status_descr "); return FALSE; } }

    $new_definition = $poller_type === 'snmp' && !safe_empty($mib) && !safe_empty($object);
    if ($new_definition) {
        $where        = ' WHERE `device_id` = ? AND `status_mib` = ? AND `status_object` = ? AND `status_type` = ? AND `status_index` = ? AND `poller_type`= ?';
        $params       = [$device['device_id'], $mib, $object, $type, $index, $poller_type];
        $status_exist = dbExist('status', $where, $params);

        // Check if old format of status was exist, then rename rrd
        if (!$status_exist) {
            $old_where  = ' WHERE `device_id` = ? AND `status_type` = ? AND `status_index` = ? AND `poller_type`= ?';
            $old_index  = $object . '.' . $index;
            $old_params = [$device['device_id'], $type, $old_index, $poller_type];

            if ($status_exist = dbExist('status', $old_where, $old_params)) {
                $where  = $old_where;
                $params = $old_params;

                // Rename old rrds without mib & object to new rrd name style
                if (!isset($options['rename_rrd'])) {
                    $options['rename_rrd'] = $type . "-" . $old_index;
                }
            }
        }
    } else {
        // Old format of definitions
        $where        = ' WHERE `device_id` = ? AND `status_type` = ? AND `status_index` = ? AND `poller_type`= ?';
        $params       = [$device['device_id'], $type, $index, $poller_type];
        $status_exist = dbExist('status', $where, $params);
    }
    //if (dbFetchCell('SELECT COUNT(*) FROM `status`' . $where, $params) == '0')
    if (!$status_exist) {
        $status_insert = [
          'poller_type'   => $poller_type,
          'device_id'     => $device['device_id'],
          'status_index'  => $index,
          'status_type'   => $type,
          //'status_id'    => $status_id,
          'status_value'  => $value,
          'status_polled' => time(), //array('NOW()'), // this field is INT(11)
          'status_event'  => $state_array['event'],
          'status_name'   => $state_array['name']
        ];

        foreach ($param_main as $key => $column) {
            $status_insert[$column] = $$key;
        }

        foreach ($param_opt as $key) {
            if (is_null($$key)) {
                $$key = ['NULL'];
            } // If param null, convert to array(NULL)
            $status_insert[$key] = $$key;
        }

        $status_id = dbInsert($status_insert, 'status');

        print_debug("( $status_id inserted )");
        echo('+');
        log_event("Status added: $entPhysicalClass $type $index $status_descr", $device, 'status', $status_id);
    } else {
        $status_entry = dbFetchRow('SELECT * FROM `status`' . $where, $params);
        $status_id    = $status_entry['status_id'];

        print_debug_vars($status_entry);
        $update = [];
        foreach ($param_main as $key => $column) {
            if ($$key != $status_entry[$column]) {
                $update[$column] = $$key;
            }
        }
        foreach ($param_opt as $key) {
            if ($$key != $status_entry[$key]) {
                $update[$key] = !is_null($$key) ? $$key : ['NULL'];
            }
        }
        print_debug_vars($update);

        if (count($update)) {
            $updated = dbUpdate($update, 'status', '`status_id` = ?', [$status_entry['status_id']]);
            echo('U');
            log_event("Status updated: $entPhysicalClass $type $index $status_descr", $device, 'status', $status_entry['status_id']);
        } else {
            echo('.');
        }
    }

    // Rename old (converted) RRDs to definition format
    // Allow with changing class or without
    if (isset($options['rename_rrd_full'])) {
        // Compatibility with sensor option
        $options['rename_rrd'] = $options['rename_rrd_full'];
    }
    if (isset($options['rename_rrd'])) {
        $rrd_tags              = ['index' => $index, 'type' => $type, 'mib' => $mib, 'object' => $object, 'oid' => $object];
        $options['rename_rrd'] = array_tag_replace($rrd_tags, $options['rename_rrd']);
        $old_rrd               = 'status-' . $options['rename_rrd'];

        //$new_rrd = 'status-'.$type.'-'.$index;
        $new_entry = ['status_descr' => $status_descr, 'status_mib' => $mib, 'status_object' => $object,
                      'status_type'  => $type, 'status_index' => $index, 'poller_type' => $poller_type];
        $new_rrd   = get_status_rrd($device, $new_entry);
        rename_rrd($device, $old_rrd, $new_rrd);
    }

    if ($new_definition) {
        $GLOBALS['valid']['status'][$mib][$object][$index] = 1;
    } else {
        // without $mib/$object
        $GLOBALS['valid']['status']['__'][$type][$index] = 1;
    }

    return $status_id;
    //return TRUE;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function check_valid_status($device, $valid, $poller_type = 'snmp')
{
    $entries = dbFetchRows("SELECT * FROM `status` WHERE `device_id` = ? AND `poller_type` = ? AND `status_deleted` = ?", [$device['device_id'], $poller_type, 0]);

    if (!safe_empty($entries)) {
        foreach ($entries as $entry) {
            $index = $entry['status_index'];
            $type  = $entry['status_type'];
            if ($poller_type === 'snmp') {
                if (!safe_empty($entry['status_mib']) && !safe_empty($entry['status_object'])) {
                    // New definitions use Object instead type
                    $mib  = $entry['status_mib'];
                    $type = $entry['status_object'];
                } else {
                    $mib = '__';
                }
            } else {
                // For ipmi and unix-agent
                $mib = 'state';
            }

            if (!$valid[$mib][$type][$index]) {
                echo("-");
                print_debug("Status deleted: $index -> $type");
                //dbDelete('status',       "`status_id` = ?", array($entry['status_id']));

                dbUpdate(['status_deleted' => '1'], 'status', '`status_id` = ?', [$entry['status_id']]);

                foreach (get_entity_attribs('status', $entry['status_id']) as $attrib_type => $value) {
                    del_entity_attrib('status', $entry['status_id'], $attrib_type);
                }
                log_event("Status deleted: " . $entry['status_class'] . " " . $entry['status_type'] . " " . $entry['status_index'] . " " . $entry['status_descr'], $device, 'status', $entry['status_id']);
            }
        }
    }
}

function poll_status($device, &$oid_cache)
{
    global $config, $agent_sensors, $ipmi_sensors, $graphs, $table_rows;

    $sql = "SELECT * FROM `status`";
    //$sql .= " LEFT JOIN `status-state` USING(`status_id`)";
    $sql .= " WHERE `device_id` = ? AND `status_deleted` = ?";
    $sql .= ' ORDER BY `status_oid`'; // This fix polling some OIDs (when not ordered)

    foreach (dbFetchRows($sql, [$device['device_id'], '0']) as $status_db) {
        //print_cli_heading("Status: ".$status_db['status_descr']. "(".$status_db['poller_type'].")", 3);

        print_debug("Checking (" . $status_db['poller_type'] . ") " . $status_db['status_descr'] . " ");

        // $status_poll = $status_db;    // Cache non-humanized status array for use as new status state

        if ($status_db['poller_type'] === "snmp") {
            $status_db['status_oid'] = '.' . ltrim($status_db['status_oid'], '.'); // Fix first dot in oid for caching

            // Check if a specific poller file exists for this status, else collect via SNMP.
            $file = $config['install_dir'] . "/includes/polling/status/" . $status_db['status_type'] . ".inc.php";

            if (is_file($file)) {
                include($file);
            } else {
                // Take value from $oid_cache if we have it, else snmp_get it
                if (isset($oid_cache[$status_db['status_oid']])) {
                    print_debug("value taken from oid_cache");
                    $status_value = $oid_cache[$status_db['status_oid']];
                } else {
                    $status_value = snmp_get_oid($device, $status_db['status_oid'], 'SNMPv2-MIB');
                }
                //$status_value = snmp_fix_numeric($status_value); // Do not use fix, this broke not-enum (string) statuses
            }
        } elseif ($status_db['poller_type'] === "agent") {
            if (isset($agent_sensors['state'])) {
                $status_value = $agent_sensors['state'][$status_db['status_type']][$status_db['status_index']]['current'];
            } else {
                print_warning("No agent status data available.");
                continue;
            }
        } elseif ($status_db['poller_type'] === "ipmi") {
            if (isset($ipmi_sensors['state'])) {
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
        $state_array                 = get_state_array($status_db['status_type'], $status_value, $status_db['status_mib'], $status_db['status_map'], $status_db['poller_type']);
        $status_value                = $state_array['value']; // Override status_value by numeric for "pseudo" (string) statuses
        $status_poll['status_value'] = $state_array['value'];
        $status_poll['status_name']  = $state_array['name'];
        if ($status_db['status_ignore'] || $status_db['status_disable']) {
            $status_poll['status_event'] = 'ignore';
        } else {
            $status_poll['status_event'] = $state_array['event'];
        }

        // Force ignore state if measured entity is in Shutdown state
        $measured_class = $status_db['measured_class'];
        if ($status_poll['status_event'] === 'alert' && is_numeric($status_db['measured_entity']) &&
            isset($config['sensors'][$measured_class]['ignore_shutdown']) && $config['sensors'][$measured_class]['ignore_shutdown']) {
            $measured_entity = get_entity_by_id_cache($measured_class, $status_db['measured_entity']);
            print_debug_vars($measured_entity);
            // Currently only for ports
            if (isset($measured_entity['ifAdminStatus']) && $measured_entity['ifAdminStatus'] === 'down') {
                $status_poll['status_event'] = 'ignore';
            }
        }

        // If last change never set, use current time
        if (empty($status_db['status_last_change'])) {
            $status_db['status_last_change'] = $status_polled_time;
        }

        if ($status_poll['status_event'] != $status_db['status_event']) {
            // Status event changed, log and set status_last_change
            $status_poll['status_last_change'] = $status_polled_time;

            if ($status_poll['status_event'] === 'ignore') {
                print_message("[%ystatus Ignored%n]", 'color');
            } elseif ($status_db['status_event'] != '') {
                // If old state not empty and new state not equals to new state
                $msg = 'Status ' . ucfirst($status_poll['status_event']) . ': ' . $device['hostname'] . ' ' . $status_db['status_descr'] .
                       ' entered ' . strtoupper($status_poll['status_event']) . ' state: ' . $status_poll['status_name'] .
                       ' (previous: ' . $status_db['status_name'] . ')';

                if (isset($config['entity_events'][$status_poll['status_event']])) {
                    $severity = $config['entity_events'][$status_poll['status_event']]['severity'];
                } else {
                    $severity = 'informational';
                }
                log_event($msg, $device, 'status', $status_db['status_id'], $severity);

                // Trick for fast rediscover sensors if associated status changed
                // See in MIKROTIK-MIB::mtxrPOEStatus definition
                $old_state_array = get_state_array($status_db['status_type'], $status_db['status_value'], $status_db['status_mib'], $status_db['status_map'], $status_db['poller_type']);
                if (isset($old_state_array['discovery']) && is_module_enabled($device, $old_state_array['discovery'], 'discovery')) {
                    force_discovery($device, $old_state_array['discovery']);
                    print_debug("Module {$old_state_array['discovery']} force for discovery by changed status type {$status_db['status_mib']}::{$status_db['status_object']}");
                }
            }
        } else {
            // If status not changed, leave old last_change
            $status_poll['status_last_change'] = $status_db['status_last_change'];
        }

        print_debug_vars($status_poll);

        // Send statistics array via AMQP/JSON if AMQP is enabled globally and for the ports module
        if ($config['amqp']['enable'] == TRUE && $config['amqp']['modules']['status']) {
            $json_data = ['value' => $status_value];
            messagebus_send(['attribs' => ['t'      => time(), 'device' => $device['hostname'], 'device_id' => $device['device_id'],
                                           'e_type' => 'status', 'e_type' => $status_db['status_type'], 'e_index' => $status_db['status_index']], 'data' => $json_data]);
        }

        // Update StatsD/Carbon
        if ($config['statsd']['enable'] == TRUE) {
            StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'status' . '.' . $status_db['status_class'] . '.' . $status_db['status_type'] . '.' . $status_db['status_index'], $status_value);
        }

        // Update RRD - FIXME - can't convert to NG because filename is dynamic! new function should return index instead of filename.
        $rrd_file = get_status_rrd($device, $status_db);
        rrdtool_create($device, $rrd_file, "DS:status:GAUGE:600:-20000:U");
        rrdtool_update($device, $rrd_file, "N:$status_value");

        // Enable graph
        $graphs[$status_db['status']] = TRUE;

        // Check alerts
        $metrics = [];

        $metrics['status_value']       = $status_value;
        $metrics['status_name']        = $status_poll['status_name'];
        $metrics['status_name_uptime'] = $status_polled_time - $status_poll['status_last_change'];
        $metrics['status_event']       = $status_poll['status_event'];

        //print_cli_data("Event (State)", $status_poll['status_event'] ." (".$status_poll['status_name'].")", 3);

        $table_rows[] = [$status_db['status_descr'], $status_db['status_type'], $status_db['status_index'], $status_db['poller_type'],
                         $status_poll['status_name'], $status_poll['status_event'], format_unixtime($status_poll['status_last_change'])];

        check_entity('status', $status_db, $metrics);

        // Add to MultiUpdate SQL State

        $GLOBALS['multi_update_db'][] = [
          'status_id'          => $status_db['status_id'], // UNIQUE index
          'status_value'       => $status_value,
          'status_name'        => $status_poll['status_name'],
          'status_event'       => $status_poll['status_event'],
          'status_last_change' => $status_poll['status_last_change'],
          'status_polled'      => $status_polled_time];
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
    if ((isset($config['os'][$device['os']]['sensor_descr']) && $config['os'][$device['os']]['sensor_descr']) ||                     // per os definition
        (isset($config['mibs'][$status['status_mib']]['sensor_descr']) && $config['mibs'][$status['status_mib']]['sensor_descr']) || // per mib definition
        ($status['poller_type'] != "snmp" && $status['poller_type'] != '')) {
        $index = $status['status_descr'];
    } else {
        $index = $status['status_index'];
    }

    if (strlen($status['status_mib']) && strlen($status['status_object'])) {
        // for discover_status_ng(), note here is just status index
        $rrd_file = "status-" . $status['status_mib'] . "-" . $status['status_object'] . "-" . $index . ".rrd";
    } else {
        // for discover_status(), note index == "%object%.%index%"
        $rrd_file = "status-" . $status['status_type'] . "-" . $index . ".rrd";
    }

    return ($rrd_file);
}

// DOCME needs phpdoc block
// TESTME needs unit testing

function get_status_by_id($status_id)
{
    if (is_numeric($status_id)) {
        $status = dbFetchRow("SELECT * FROM `status` WHERE `status_id` = ?", [$status_id]);
    }
    if (is_array($status)) {
        return $status;
    } else {
        return FALSE;
    }
}

// EOF
