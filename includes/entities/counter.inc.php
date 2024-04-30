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

function discover_counter_definition($device, $mib, $entry)
{

    echo($entry['oid'] . ' [');

    // Just append mib name to definition entry, for simple pass to external functions
    if (empty($entry['mib'])) {
        $entry['mib'] = $mib;
    }

    // Check that types listed in skip_if_valid_exist have already been found
    if (discovery_check_if_type_exist($entry, 'counter')) {
        echo '!]';
        return;
    }

    // Check array requirements list
    if (discovery_check_requires_pre($device, $entry, 'counter')) {
        echo '!]';
        return;
    }

    // Validate if Oid exist for current mib (in case when used generic definitions, ie edgecore)
    if (empty($entry['oid_num'])) {
        // Use snmptranslate if oid_num not set
        $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
        if (empty($entry['oid_num'])) {
            echo("]");
            print_debug("Oid [" . $entry['oid'] . "] not exist for mib [$mib]. Counter skipped.");
            return;
        }
    } else {
        $entry['oid_num'] = rtrim($entry['oid_num'], '.');
    }

    // Fetch table or Oids
    $table_oids    = ['oid', 'oid_descr', 'oid_scale', 'oid_unit', 'oid_class', 'oid_add',
                      'oid_limit_low', 'oid_limit_low_warn', 'oid_limit_high_warn', 'oid_limit_high', 'oid_limit_warn',
                      'oid_limit_nominal', 'oid_limit_delta_warn', 'oid_limit_delta', 'oid_limit_scale',
                      'oid_extra', 'oid_entPhysicalIndex'];
    $counter_array = discover_fetch_oids($device, $mib, $entry, $table_oids);

    $counters = []; // Reset per-class counters for each MIB

    $counters_count = count($counter_array);
    foreach ($counter_array as $index => $counter) {
        $options = [];

        $counter = array_merge($counter, entity_index_tags($index));

        // Determine the counter class
        $class = entity_class_definition($device, $entry, $counter, 'counter');
        if (!$class) {
            continue; // Break foreach. Proceed to next counter!
        }

        $dot_index = strlen($index) ? '.' . $index : '';
        $oid_num   = $entry['oid_num'] . $dot_index;

        // echo PHP_EOL; print_vars($entry); echo PHP_EOL; print_vars($counter); echo PHP_EOL; print_vars($descr); echo PHP_EOL;

        // %i% can be used in description, a counter is kept per counter class
        $counters[$class]++;

        // Generate specific keys used during rewrites

        $counter['class'] = nicecase($class);  // Class in descr
        $counter['i'] = $counters[$class]; // i++ counter in descr (per counter class)

        // Check valid exist with entity tags
        if (discovery_check_if_type_exist($entry, 'counter', $counter)) {
            continue;
        }

        // Check array requirements list
        if (discovery_check_requires($device, $entry, $counter, 'counter')) {
            continue;
        }

        $value = snmp_fix_numeric($counter[$entry['oid']]);
        if (!discovery_check_value_valid($device, $value, $entry, 'counter')) {
            continue;
        }

        // Scale
        $scale = entity_scale_definition($device, $entry, $counter);

        // Limits
        $options = array_merge($options, entity_limits_definition($device, $entry, $counter, $scale));

        // Unit
        if (isset($entry['unit'])) {
            $options['counter_unit'] = $entry['unit'];
        }
        if (isset($entry['oid_unit']) && isset($counter[$entry['oid_unit']])) {
            // Translate unit from specific Oid
            $unit = $counter[$entry['oid_unit']];
            if (isset($entry['map_unit'][$unit])) {
                $options['counter_unit'] = $entry['map_unit'][$unit];
            }
        }

        // Rule-based entity linking.
        if ($measured = entity_measured_match_definition($device, $entry, $counter, 'counter')) {
            $options = array_merge($options, $measured);
            $counter = array_merge($counter, $measured); // append to $counter for %descr% tags, ie %port_label%
        } // End rule-based entity linking
        elseif (isset($entry['entPhysicalIndex'])) {
            // Just set physical index
            $options['entPhysicalIndex'] = array_tag_replace($counter, $entry['entPhysicalIndex']);
        }

        // Add extra value, dirty hack for  DANTHERM-COOLING-MIB:
        // heaterOpertdurHour.0 = INTEGER: 13 Hours
        // heaterOpertdurMin.0 = INTEGER: 59
        if (isset($entry['oid_add']) && isset($counter[$entry['oid_add']])) {
            $options['value_add'] = snmp_fix_numeric($counter[$entry['oid_add']]);
            $options['oid_add']   = snmp_translate($entry['oid_add'] . $dot_index, $mib);
            if (isset($entry['scale_add'])) {
                $options['scale_add'] = $entry['scale_add'];
            }
        }

        // Generate Description
        $descr = entity_descr_definition('counter', $entry, $counter, $counters_count);

        // Rename old (converted) RRDs to definition format
        if (isset($entry['rename_rrd'])) {
            $options['rename_rrd'] = $entry['rename_rrd'];
        } elseif (isset($entry['rename_rrd_full'])) {
            $options['rename_rrd_full'] = $entry['rename_rrd_full'];
        }

        discover_counter($device, $class, $mib, $entry['oid'], $oid_num, $index, $descr, $scale, $value, $options);
    }

    echo '] ';

}

// TESTME needs unit testing
/**
 * Discover a new counter on a device
 *
 * This function adds a status counter to a device, if it does not already exist.
 * Data on the counter is updated if it has changed, and an event is logged with regards to the changes.
 *
 * Status counters are handed off to discover_status().
 * Current counter values are rectified in case they are broken (added spaces, etc).
 *
 * @param array  $device        Device array counter is being discovered on
 * @param string $class         Class of counter (voltage, temperature, etc.)
 * @param string $mib           SNMP MIB name
 * @param string $object        SNMP Named Oid of counter (without index)
 * @param string $oid           SNMP Numeric Oid of counter (without index)
 * @param string $index         SNMP index of counter
 * @param string $counter_descr Description of counter
 * @param int    $scale         Scale of counter (0.1 for 1:10 scale, 10 for 10:1 scale, etc)
 * @param string $value         Current counter value
 * @param array  $options       Options (counter_unit, limit_auto, limit*, poller_type, scale, measured_*)
 *
 * @return bool
 */
function discover_counter($device, $class, $mib, $object, $oid, $index, $counter_descr, $scale = 1, $value = NULL, $options = [])
{

    //echo 'MIB:'; print_vars($mib);

    $poller_type = isset($options['poller_type']) ? $options['poller_type'] : 'snmp';
    // Class for counter is free text string (not limited by known classes)
    $class = safe_empty($class) ? 'counter' : strtolower($class);
    // Use MIB & Object or Numeric Oid?
    $use_mib_object = $mib && $object;

    $counter_deleted = 0;

    // Init main
    $param_main = ['oid'             => 'counter_oid', 'counter_descr' => 'counter_descr', 'scale' => 'counter_multiplier',
                   'counter_deleted' => 'counter_deleted', 'mib' => 'counter_mib', 'object' => 'counter_object'];

    // Init numeric values
    if (!is_numeric($scale) || $scale == 0) {
        $scale = 1;
    }

    // Skip discovery counter if value not numeric or null (default)
    if (!safe_empty($value)) {
        // Some retarded devices report data with spaces and commas
        // STRING: "  20,4"
        $value = snmp_fix_numeric($value);
    }

    if (is_numeric($value)) {
        $value = scale_value($value, $scale);
        // $value *= $scale; // Scale before unit conversion
        $value = value_to_si($value, $options['counter_unit'], $class); // Convert if not SI unit

        // Extra add value
        if (isset($options['value_add']) && is_numeric($options['value_add'])) {
            $value += scale_value($options['value_add'], $options['scale_add']);
        }
    } else {
        print_debug("Counter skipped by not numeric value: '$value', '$counter_descr'");
        if (strlen($value)) {
            print_debug("Perhaps this is named status, use discover_status() instead.");
        }
        return FALSE;
    }

    $param_limits = ['limit_high' => 'counter_limit', 'limit_high_warn' => 'counter_limit_warn',
                     'limit_low'  => 'counter_limit_low', 'limit_low_warn' => 'counter_limit_low_warn'];
    foreach ($param_limits as $key => $column) {
        // Set limits vars and unit convert if required
        $$key = (is_numeric($options[$key]) ? value_to_si($options[$key], $options['counter_unit'], $class) : NULL);
    }
    // Set by which param use limits
    switch (strtolower($options['limit_by'])) {
        case 's':
        case 'sec':
        case 'second':
            $limit_by = 'sec';
            break;
        case 'm':
        case 'min':
        case 'minute':
            $limit_by = 'min';
            break;
        case 'h':
        case 'hour':
            $limit_by = 'hour';
            break;
        case 'val':
        case 'value':
            $limit_by = 'value';
            break;
        default:
            //case 'poll':
            //case '5min':
            $limit_by = '5min';
            break;
    }
    // Auto calculate high/low limits if not passed (for counter must be explicit passed)
    //$limit_auto = !isset($options['limit_auto']) || (bool)$options['limit_auto'];
    $limit_auto = isset($options['limit_auto']) && (bool)$options['limit_auto'];

    // Init optional
    $param_opt = ['entPhysicalIndex', 'entPhysicalClass', 'entPhysicalIndex_measured', 'measured_class', 'measured_entity', 'counter_unit'];
    foreach ($param_opt as $key) {
        $$key = ($options[$key] ? $options[$key] : NULL);
    }

    print_debug("Discover counter: [class: $class, device: " . $device['hostname'] . ", oid: $oid, index: $index, descr: $counter_descr, scale: $scale, limits: ($limit_low, $limit_low_warn, $limit_high_warn, $limit_high), CURRENT: $value, $entPhysicalIndex, $entPhysicalClass");

    // Check counter ignore filters
    if (entity_descr_check($counter_descr, 'counter')) {
        return FALSE;
    }
    //foreach ($config['ignore_counter'] as $bi)        { if (strcasecmp($bi, $counter_descr) == 0)   { print_debug("Skipped by equals: $bi, $counter_descr "); return FALSE; } }
    //foreach ($config['ignore_counter_string'] as $bi) { if (stripos($counter_descr, $bi) !== FALSE) { print_debug("Skipped by strpos: $bi, $counter_descr "); return FALSE; } }
    //foreach ($config['ignore_counter_regexp'] as $bi) { if (preg_match($bi, $counter_descr) > 0)    { print_debug("Skipped by regexp: $bi, $counter_descr "); return FALSE; } }

    if (!is_null($limit_low_warn) && !is_null($limit_high_warn) && ($limit_low_warn > $limit_high_warn)) {
        // Fix high/low thresholds (i.e. on negative numbers)
        [$limit_high_warn, $limit_low_warn] = [$limit_low_warn, $limit_high_warn];
    }
    print_debug_vars($limit_high);
    print_debug_vars($limit_high_warn);
    print_debug_vars($limit_low_warn);
    print_debug_vars($limit_low);

    if ($use_mib_object) {
        $where  = '`device_id` = ? AND `counter_class` = ? AND `counter_mib` = ? AND `counter_object` = ? AND `counter_index` = ? AND `poller_type`= ?';
        $params = [$device['device_id'], $class, $mib, $object, $index, $poller_type];
    } else {
        // Rare case, when MIB and Object unknown
        $where  = '`device_id` = ? AND `counter_class` = ? AND `counter_oid` = ? AND `counter_index` = ? AND `poller_type`= ?';
        $params = [$device['device_id'], $class, $oid, $index, $poller_type];
    }

    if (!dbExist('counters', $where, $params)) {
        if (!$limit_high) {
            $limit_high = sensor_limit_high($class, $value, $limit_auto);
        }
        if (!$limit_low) {
            $limit_low = sensor_limit_low($class, $value, $limit_auto);
        }

        if (!is_null($limit_low) && !is_null($limit_high) && ($limit_low > $limit_high)) {
            // Fix high/low thresholds (i.e. on negative numbers)
            [$limit_high, $limit_low] = [$limit_low, $limit_high];
            print_debug("High/low limits swapped.");
        }

        $counter_insert = [
          'poller_type'   => $poller_type, 'device_id' => $device['device_id'],
          'counter_class' => $class, 'counter_index' => $index
        ];

        foreach ($param_main as $key => $column) {
            $counter_insert[$column] = $$key;
        }

        foreach ($param_limits as $key => $column) {
            // Convert strings/numbers to (float) or to array('NULL')
            $$key                    = is_numeric($$key) ? (float)$$key : ['NULL'];
            $counter_insert[$column] = $$key;
        }
        $counter_insert['counter_limit_by'] = $limit_by;

        foreach ($param_opt as $key) {
            if (is_null($$key)) {
                $$key = ['NULL'];
            }
            $counter_insert[$key] = $$key;
        }

        $counter_insert['counter_value']  = $value;
        $counter_insert['counter_polled'] = time();

        $counter_id = dbInsert($counter_insert, 'counters');

        // Extra oid add
        if ($counter_id && isset($options['oid_add'])) {
            set_entity_attrib('counter', $counter_id, 'oid_add', $options['oid_add'], $device['device_id']);
            if (isset($options['scale_add'])) {
                set_entity_attrib('counter', $counter_id, 'scale_add', $options['scale_add'], $device['device_id']);
            }
        }

        print_debug("( $counter_id inserted )");
        echo('+');

        log_event("Counter added: $class $mib::$object.$index $counter_descr", $device, 'counter', $counter_id);
    } else {
        $counter_entry = dbFetchRow("SELECT * FROM `counters` WHERE " . $where, $params);
        $counter_id    = $counter_entry['counter_id'];

        // Limits
        if (!$counter_entry['counter_custom_limit']) {
            if (!is_numeric($limit_high)) {
                if ($counter_entry['counter_limit'] !== '') {
                    // Calculate a reasonable limit
                    $limit_high = sensor_limit_high($class, $value, $limit_auto);
                } else {
                    // Use existing limit. (this is wrong! --mike)
                    $limit_high = $counter_entry['counter_limit'];
                }
            }

            if (!is_numeric($limit_low)) {
                if ($counter_entry['counter_limit_low'] !== '') {
                    // Calculate a reasonable limit
                    $limit_low = sensor_limit_low($class, $value, $limit_auto);
                } else {
                    // Use existing limit. (this is wrong! --mike)
                    $limit_low = $counter_entry['counter_limit_low'];
                }
            }

            // Fix high/low thresholds (i.e. on negative numbers)
            if (!is_null($limit_low) && !is_null($limit_high) && ($limit_low > $limit_high)) {
                [$limit_high, $limit_low] = [$limit_low, $limit_high];
                print_debug("High/low limits swapped.");
            }

            // Update limits
            $update     = [];
            $update_msg = [];
            $debug_msg  = 'Current counter value: "' . $value . '", scale: "' . $scale . '"' . PHP_EOL;
            foreach ($param_limits as $key => $column) {
                // $key - param name, $$key - param value, $column - column name in DB for $key
                $debug_msg .= '  ' . $key . ': "' . $counter_entry[$column] . '" -> "' . $$key . '"' . PHP_EOL;
                //convert strings/numbers to identical type (float) or to array('NULL') for correct comparison
                $$key                   = is_numeric($$key) ? (float)$$key : ['NULL'];
                $counter_entry[$column] = is_numeric($counter_entry[$column]) ? (float)$counter_entry[$column] : ['NULL'];
                if (float_cmp($$key, $counter_entry[$column], 0.01) !== 0) // FIXME, need compare autogenerated and hard passed limits by different ways
                {
                    $update[$column] = $$key;
                    $update_msg[]    = $key . ' -> "' . (is_array($$key) ? 'NULL' : $$key) . '"';
                }
            }
            if ($counter_entry['counter_limit_by'] != $limit_by) {
                $update['counter_limit_by'] = $limit_by;
                $update_msg[]               = 'limit_by -> "' . $limit_by . '"';
            }
            if (count($update)) {
                echo("L");
                print_debug($debug_msg);
                log_event('Counter updated (limits): ' . implode(', ', $update_msg), $device, 'counter', $counter_entry['counter_id']);
                $updated = dbUpdate($update, 'counters', '`counter_id` = ?', [$counter_entry['counter_id']]);
            }
        }

        $update = [];
        foreach ($param_main as $key => $column) {
            if (float_cmp($$key, $counter_entry[$column]) !== 0) {
                $update[$column] = $$key;
            }
        }

        foreach ($param_opt as $key) {
            if ($$key != $counter_entry[$key]) {
                $update[$key] = $$key;
            }
        }

        if (count($update)) {
            $updated = dbUpdate($update, 'counters', '`counter_id` = ?', [$counter_entry['counter_id']]);

            // Extra oid add
            if (isset($options['oid_add'])) {
                set_entity_attrib('counter', $counter_entry['counter_id'], 'oid_add', $options['oid_add'], $device['device_id']);
                if (isset($options['scale_add'])) {
                    set_entity_attrib('counter', $counter_entry['counter_id'], 'scale_add', $options['scale_add'], $device['device_id']);
                }
            }

            echo('U');
            log_event("Counter updated: $class $mib::$object.$index $counter_descr", $device, 'counter', $counter_entry['counter_id']);
        } else {
            if (isset($options['oid_add']) &&
                !dbExist('entity_attribs', '`entity_type` = ? AND `entity_id` = ? AND `attrib_type` = ?', ['counter', $counter_entry['counter_id'], 'oid_add'])) {
                set_entity_attrib('counter', $counter_entry['counter_id'], 'oid_add', $options['oid_add'], $device['device_id']);
                if (isset($options['scale_add'])) {
                    set_entity_attrib('counter', $counter_entry['counter_id'], 'scale_add', $options['scale_add'], $device['device_id']);
                }
            }
            echo('.');
        }
    }

    // Rename old (converted) RRDs to definition format
    // Allow with changing class or without
    if (isset($options['rename_rrd']) || isset($options['rename_rrd_full'])) {
        $rrd_tags = ['index' => $index, 'mib' => $mib, 'object' => $object, 'oid' => $object];
        if (isset($options['rename_rrd'])) {
            $options['rename_rrd'] = array_tag_replace($rrd_tags, $options['rename_rrd']);
            $old_rrd               = 'counter-' . $class . '-' . $options['rename_rrd'];
        } elseif (isset($options['rename_rrd_full'])) {
            $options['rename_rrd_full'] = array_tag_replace($rrd_tags, $options['rename_rrd_full']);
            $old_rrd                    = 'counter-' . $options['rename_rrd_full'];
        }
        $new_rrd = "counter-" . $class . "-" . $mib . "-" . $object . "-" . $index . ".rrd";
        rename_rrd($device, $old_rrd, $new_rrd);
    }

    $GLOBALS['valid']['counter'][$class][$mib][$object][$index] = 1;

    return $counter_id;
    //return TRUE;
}

// Poll a counter
function poll_counter($device, &$oid_cache)
{
    global $config, $agent_sensors, $ipmi_counters, $graphs, $table_rows;

    $sql = "SELECT * FROM `counters`";
    $sql .= " WHERE `device_id` = ? AND `counter_deleted` = ?";
    $sql .= ' ORDER BY `counter_oid`'; // This fix polling some OIDs (when not ordered)

    //print_debug_vars($GLOBALS['cache']['entity_attribs']);
    foreach (dbFetchRows($sql, [$device['device_id'], '0']) as $counter_db) {
        $counter_poll    = [];
        $counter_attribs = get_entity_attribs('counter', $counter_db);
        //print_debug_vars($GLOBALS['cache']['entity_attribs']);
        //print_debug_vars($counter_attribs);
        $class = $counter_db['counter_class'];
        // Counter not have type attribute, this need for compat with agent or ipmi
        $type = $counter_db['counter_mib'] . '-' . $counter_db['counter_object'];

        //print_cli_heading("Counter: ".$counter_db['counter_descr'], 3);

        if (OBS_DEBUG) {
            echo("Checking (" . $counter_db['poller_type'] . ") $class " . $counter_db['counter_descr'] . " ");
            print_debug_vars($counter_db, 1);
        }

        if ($counter_db['poller_type'] === "snmp") {
            $counter_db['counter_oid'] = '.' . ltrim($counter_db['counter_oid'], '.'); // Fix first dot in oid for caching

            // Take value from $oid_cache if we have it, else snmp_get it
            if (isset($oid_cache[$counter_db['counter_oid']])) {
                $oid_cache_tmp                         = $oid_cache[$counter_db['counter_oid']]; // keep original value, while cache entry can reused
                $oid_cache[$counter_db['counter_oid']] = snmp_fix_numeric($oid_cache[$counter_db['counter_oid']], $counter_db['counter_unit']);
            }
            if (is_numeric($oid_cache[$counter_db['counter_oid']])) {
                print_debug("value taken from oid_cache");
                $counter_poll['counter_value']         = $oid_cache[$counter_db['counter_oid']];
                $oid_cache[$counter_db['counter_oid']] = $oid_cache_tmp; // restore original cached value
            } else {
                // Get by numeric oid
                $counter_poll['counter_value'] = snmp_get_oid($device, $counter_db['counter_oid'], 'SNMPv2-MIB');
                $counter_poll['counter_value'] = snmp_fix_numeric($counter_poll['counter_value'], $counter_db['counter_unit']);
            }
            $unit = $counter_db['counter_unit'];

            // Extra add
            if (isset($counter_attribs['oid_add'])) {
                $counter_poll['counter_value_add'] = snmp_fix_numeric(snmp_get_oid($device, $counter_attribs['oid_add'], 'SNMPv2-MIB'));
            }
        } elseif ($counter_db['poller_type'] === "agent") {
            if (isset($agent_sensors)) {
                $counter_poll['counter_value'] = $agent_sensors[$class][$type][$counter_db['counter_index']]['current'];
            } else {
                print_warning("No agent counter data available.");
                continue;
            }
        } elseif ($counter_db['poller_type'] === "ipmi") {
            if (isset($ipmi_counters)) {
                $counter_poll['counter_value'] = snmp_fix_numeric($ipmi_counters[$class][$type][$counter_db['counter_index']]['current']);
                $unit                          = $ipmi_counters[$class][$type][$counter_db['counter_index']]['unit'];
            } else {
                print_warning("No IPMI counter data available.");
                continue;
            }
        } else {
            print_warning("Unknown counter poller type.");
            continue;
        }

        $counter_polled_time   = time(); // Store polled time for current counter
        $counter_polled_period = $counter_polled_time - $counter_db['counter_polled'];

        if (isset($counter_db['counter_multiplier']) && $counter_db['counter_multiplier'] != 0) {
            //$counter_poll['counter_value'] *= $counter_db['counter_multiplier'];
            $counter_poll['counter_value'] = scale_value($counter_poll['counter_value'], $counter_db['counter_multiplier']);
        }

        // Unit conversion to SI (if required)
        $counter_poll['counter_value'] = value_to_si($counter_poll['counter_value'], $counter_db['counter_unit'], $class);

        // Extra add
        if (isset($counter_attribs['oid_add']) && is_numeric($counter_poll['counter_value_add'])) {
            print_debug("Extra value add: " . $counter_poll['counter_value'] . " + (" . $counter_poll['counter_value_add'] . " * " . $counter_attribs['scale_add'] . ") =");
            $counter_poll['counter_value'] += scale_value($counter_poll['counter_value_add'], $counter_attribs['scale_add']);
            print_debug($counter_poll['counter_value']);
        }

        // Rate /s
        $value_diff                        = int_sub($counter_poll['counter_value'], $counter_db['counter_value']);
        $counter_poll['counter_rate']      = float_div($value_diff, $counter_polled_period);
        $counter_poll['counter_rate_min']  = float_div($value_diff, ($counter_polled_period / 60));
        $counter_poll['counter_rate_5min'] = float_div($value_diff, ($counter_polled_period / 300)); // This is mostly same as count per poll period
        print_debug('Rate /sec: (' . $counter_poll['counter_value'] . ' - ' . $counter_db['counter_value'] . '(=' . $value_diff . ')) / ' . $counter_polled_period . ' = ' . $counter_poll['counter_rate']);
        print_debug('Rate /min: ' . $counter_poll['counter_rate_min']);
        print_debug('Rate /5min: ' . $counter_poll['counter_rate_5min']);
        // Rate /h (more complex since counters grow up very rarely
        $counter_poll['counter_history'] = $counter_db['counter_history'] != '' ? json_decode($counter_db['counter_history'], TRUE) : [];
        // Now find first polled time around 3600s (1h)
        foreach ($counter_poll['counter_history'] as $polled_time => $value) {
            $diff = $counter_polled_time - $polled_time;
            if ($diff < (3600 + ($config['rrd']['step'] / 2))) // 3600 + 150 (possible change step in future)
            {
                if ($diff < 3300) {
                    // If not have full hour history, use approximate to hour rate
                    $period                            = $diff / 3600; // Period in hours (around 1)
                    $counter_poll['counter_rate_hour'] = int_sub($counter_poll['counter_value'], $value) / $period;
                    print_debug("Hour rate by approximate: " . $counter_poll['counter_value'] . " - $value / $period");
                } else {
                    $counter_poll['counter_rate_hour'] = int_sub($counter_poll['counter_value'], $value); // Keep this value as integer, since we keep in history only 1 hour
                    print_debug("Hour rate by history: " . $counter_poll['counter_value'] . " - $value");
                }
                break;
            }

            // Clear old entries
            unset($counter_poll['counter_history'][$polled_time]);
        }
        // Just if initially not exist history
        if (!isset($counter_poll['counter_rate_hour'])) {
            $counter_poll['counter_rate_hour'] = $counter_poll['counter_rate_5min'] * 12;
            print_debug("Hour rate initially: " . $counter_poll['counter_rate_5min'] . " * 12");
        }
        print_debug('Rate /hour: ' . $counter_poll['counter_rate_hour']);

        // Append last value to history and json it
        $counter_poll['counter_history'][$counter_polled_time] = $counter_poll['counter_value'];
        print_debug_vars($counter_poll['counter_history']);
        $counter_poll['counter_history'] = json_encode($counter_poll['counter_history']);

        print_debug_vars($counter_poll, 1);

        //print_cli_data("Value", $counter_poll['counter_value'] . "$unit ", 3);

        // FIXME this block and the other block below it are kinda retarded. They should be merged and simplified.

        if ($counter_db['counter_disable']) {
            $counter_poll['counter_event']  = 'ignore';
            $counter_poll['counter_status'] = 'Counter disabled.';
        } else {
            // Select param for calculate limit events
            switch ($counter_db['counter_limit_by']) {
                case 'sec':
                    $limit_by   = 'counter_rate';
                    $limit_unit = "$unit/sec";
                    break;
                case 'min':
                    $limit_by   = 'counter_rate_min';
                    $limit_unit = "$unit/min";
                    break;
                case '5min':
                    $limit_by   = 'counter_rate_5min';
                    $limit_unit = "$unit/5min";
                    break;
                case 'hour':
                    $limit_by   = 'counter_rate_hour';
                    $limit_unit = "$unit/hour";
                    break;
                case 'value':
                    $limit_by   = 'counter_value';
                    $limit_unit = $unit;
                    break;
            }

            $counter_poll['counter_event'] = check_thresholds($counter_db['counter_limit_low'], $counter_db['counter_limit_low_warn'],
                                                              $counter_db['counter_limit_warn'], $counter_db['counter_limit'],
                                                              $counter_poll[$limit_by]);
            if ($counter_poll['counter_event'] === 'alert') {
                $counter_poll['counter_status'] = 'Counter critical thresholds exceeded.';
            } elseif ($counter_poll['counter_event'] === 'warning') {
                $counter_poll['counter_status'] = 'Counter warning thresholds exceeded.';
            } else {
                $counter_poll['counter_status'] = '';
            }

            // Reset Alert if counter ignored
            if ($counter_poll['counter_event'] !== 'ok' && $counter_db['counter_ignore']) {
                $counter_poll['counter_event']  = 'ignore';
                $counter_poll['counter_status'] = 'Counter thresholds exceeded, but ignored.';
            }
        }

        // If last change never set, use current time
        if (empty($counter_db['counter_last_change'])) {
            $counter_db['counter_last_change'] = $counter_polled_time;
        }

        if ($counter_poll['counter_event'] != $counter_db['counter_event']) {
            // Counter event changed, log and set counter_last_change
            $counter_poll['counter_last_change'] = $counter_polled_time;

            if ($counter_db['counter_event'] === 'ignore') {
                print_message("[%yCounter Ignored%n]", 'color');
            } elseif (is_numeric($counter_db['counter_limit_low']) &&
                      $counter_db[$limit_by] >= $counter_db['counter_limit_low'] &&
                      $counter_poll[$limit_by] < $counter_db['counter_limit_low']) {
                // If old value greater than low limit and new value less than low limit
                $msg = ucfirst($class) . " Alarm: " . $device['hostname'] . " " . $counter_db['counter_descr'] . " is under threshold: " . $counter_poll[$limit_by] . "$limit_unit (< " . $counter_db['counter_limit_low'] . "$limit_unit)";
                log_event(ucfirst($class) . ' ' . $counter_db['counter_descr'] . " under threshold: " . $counter_poll[$limit_by] . " $limit_unit (< " . $counter_db['counter_limit_low'] . " $limit_unit)", $device, 'counter', $counter_db['counter_id'], 'warning');
            } elseif (is_numeric($counter_db['counter_limit']) &&
                      $counter_db[$limit_by] <= $counter_db['counter_limit'] &&
                      $counter_poll[$limit_by] > $counter_db['counter_limit']) {
                // If old value less than high limit and new value greater than high limit
                $msg = ucfirst($class) . " Alarm: " . $device['hostname'] . " " . $counter_db['counter_descr'] . " is over threshold: " . $counter_poll[$limit_by] . "$limit_unit (> " . $counter_db['counter_limit'] . "$limit_unit)";
                log_event(ucfirst($class) . ' ' . $counter_db['counter_descr'] . " above threshold: " . $counter_poll[$limit_by] . " $limit_unit (> " . $counter_db['counter_limit'] . " $limit_unit)", $device, 'counter', $counter_db['counter_id'], 'warning');
            }
        } else {
            // If counter not changed, leave old last_change
            $counter_poll['counter_last_change'] = $counter_db['counter_last_change'];
        }

        // Send statistics array via AMQP/JSON if AMQP is enabled globally and for the ports module
        if ($config['amqp']['enable'] == TRUE && $config['amqp']['modules']['counters']) {
            $json_data = ['value' => $counter_poll['counter_value']];
            messagebus_send(['attribs' => ['t'         => $counter_polled_time,
                                           'device'    => $device['hostname'],
                                           'device_id' => $device['device_id'],
                                           'e_type'    => 'counter',
                                           'e_class'   => $counter_db['counter_class'],
                                           'e_type'    => $type,
                                           'e_index'   => $counter_db['counter_index']],
                             'data'    => $json_data]);
        }

        // Add table row

        $type         = $counter_db['counter_mib'] . '::' . $counter_db['counter_object'] . '.' . $counter_db['counter_index'];
        $format       = (string)$config['counter_types'][$counter_db['counter_class']]['format'];
        $table_rows[] = [$counter_db['counter_descr'],
                         $counter_db['counter_class'],
                         $type,
                         $counter_poll['counter_value'] . $unit,
                         format_value($counter_poll['counter_rate'], $format) . '/s | ' . format_value($counter_poll['counter_rate_min'], $format) . '/min | ' .
                         format_value($counter_poll['counter_rate_5min'], $format) . '/5min | ' . format_value($counter_poll['counter_rate_hour'], $format) . '/h',
                         $counter_poll['counter_event'],
                         format_unixtime($counter_poll['counter_last_change']),
                         $counter_db['poller_type']];

        // Update StatsD/Carbon
        if ($config['statsd']['enable'] == TRUE) {
            StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'counter' . '.' . $counter_db['counter_class'] . '.' . $type . '.' . $counter_db['counter_index'], $counter_poll['counter_value']);
        }

        // Update RRD (Counter store both rate(counter) and value(sensor)
        //$rrd_file = get_counter_rrd($device, $counter_db);
        //rrdtool_create($device, $rrd_file, "DS:counter:GAUGE:600:-20000:U");
        //rrdtool_update($device, $rrd_file, "N:" . $counter_poll['counter_value']);
        $ds = [
          'sensor'  => $counter_poll['counter_value'],
          // RRD COUNTER must be integer
          'counter' => (is_numeric($counter_poll['counter_value']) ? round($counter_poll['counter_value'], 0) : 0)
        ];
        rrdtool_update_ng($device, 'counter', $ds, $counter_db['counter_id']);

        // Enable graph
        $graphs[$counter_db['counter_class']] = TRUE;

        // Check alerts
        $metrics = [];

        $metrics['counter_value']        = $counter_poll['counter_value'];
        $metrics['counter_rate']         = $counter_poll['counter_rate'];
        $metrics['counter_rate_min']     = $counter_poll['counter_rate_min'];
        $metrics['counter_rate_5min']    = $counter_poll['counter_rate_5min'];
        $metrics['counter_rate_hour']    = $counter_poll['counter_rate_hour'];
        $metrics['counter_event']        = $counter_poll['counter_event'];
        $metrics['counter_event_uptime'] = $counter_polled_time - $counter_poll['counter_last_change'];
        $metrics['counter_status']       = $counter_poll['counter_status'];

        check_entity('counter', $counter_db, $metrics);

        // Add to MultiUpdate SQL State

        $GLOBALS['multi_update_db'][] = [
          'counter_id'          => $counter_db['counter_id'], // UNIQUE index
          //'device_id'           => $counter_db['device_id'],  // Required
          'counter_value'       => $counter_poll['counter_value'],
          'counter_rate'        => $counter_poll['counter_rate'],
          'counter_rate_5min'   => $counter_poll['counter_rate_5min'],
          'counter_rate_hour'   => $counter_poll['counter_rate_hour'],
          'counter_history'     => $counter_poll['counter_history'],
          'counter_event'       => $counter_poll['counter_event'],
          'counter_status'      => $counter_poll['counter_status'],
          'counter_last_change' => $counter_poll['counter_last_change'],
          'counter_polled'      => $counter_polled_time];
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function check_valid_counter($device, $poller_type = 'snmp')
{
    $valid = &$GLOBALS['valid']['counter'];

    $entries = dbFetchRows("SELECT * FROM `counters` WHERE `device_id` = ? AND `poller_type` = ? AND `counter_deleted` = '0'", [$device['device_id'], $poller_type]);

    foreach ($entries as $entry) {
        $index  = $entry['counter_index'];
        $class  = $entry['counter_class'];
        $object = $entry['counter_object'];
        $mib    = strlen($entry['counter_mib']) ? $entry['counter_mib'] : '__';
        if (!$valid[$class][$mib][$object][$index] ||
            $valid[$class][$mib][$object][$index] > 1) {// Duplicate entry
            echo("-");
            print_debug("Status deleted: $mib::$object.$index");
            //dbDelete('counter',       "`counter_id` = ?", array($entry['counter_id']));

            dbUpdate(['counter_deleted' => '1'], 'counters', '`counter_id` = ?', [$entry['counter_id']]);

            foreach (get_entity_attribs('counter', $entry['counter_id']) as $attrib_type => $value) {
                del_entity_attrib('counter', $entry['counter_id'], $attrib_type);
            }
            log_event("Counter deleted: " . $entry['counter_class'] . " " . $entry['counter_index'] . " " . $entry['counter_descr'], $device, 'counter', $entry['counter_id']);
        } else {
            // Increase counter as hint for find duplicates
            $valid[$class][$mib][$object][$index]++;
        }
    }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_counter_rrd($device, $counter)
{
    global $config;

    # For IPMI/agent, counters tend to change order, and there is no index, so we prefer to use the description as key here.
    if ((isset($config['os'][$device['os']]['sensor_descr']) && $config['os'][$device['os']]['sensor_descr']) ||                         // per os definition
        (isset($config['mibs'][$counter['counter_mib']]['sensor_descr']) && $config['mibs'][$counter['counter_mib']]['sensor_descr']) || // per mib definition
        ($counter['poller_type'] != "snmp" && $counter['poller_type'] != '')) {
        $index = $counter['counter_descr'];
    } else {
        $index = $counter['counter_index'];
    }

    if ($counter['poller_type'] != "snmp" && $counter['poller_type'] != '') {
        $rrd_file = "counter-" . $counter['counter_class'] . "-" . $counter['poller_type'] . "-" . $index . ".rrd";
    } elseif ($counter['counter_mib'] == '' || $counter['counter_object'] == '') {
        // Seems as numeric Oid polling (not known MIB & Oid)
        // counter_oid is full numeric oid with index
        $rrd_file = "counter-" . $counter['counter_class'] . "-" . $counter['counter_oid'] . ".rrd";
    } else {
        $rrd_file = "counter-" . $counter['counter_class'] . "-" . $counter['counter_mib'] . "-" . $counter['counter_object'] . "-" . $index . ".rrd";
    }

    return ($rrd_file);
}

// EOF
