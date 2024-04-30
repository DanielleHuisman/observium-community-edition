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

// New Definition Discovery

function discover_sensor_definition($device, $mib, $entry)
{

    echo($entry['oid'] . ' [');

    // Just append mib name to definition entry, for simple pass to external functions
    if (empty($entry['mib'])) {
        $entry['mib'] = $mib;
    }

    // Check that types listed in skip_if_valid_exist have already been found
    if (discovery_check_if_type_exist($entry, 'sensor')) {
        echo '!]';
        return;
    }

    // Check array requirements list
    if (discovery_check_requires_pre($device, $entry, 'sensor')) {
        echo '!]';
        return;
    }

    // Validate if Oid exist for current mib (in case when used generic definitions, ie edgecore)
    if (empty($entry['oid_num'])) {
        // Use snmptranslate if oid_num not set
        $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
        if (empty($entry['oid_num'])) {
            echo("]");
            print_debug("Oid [" . $entry['oid'] . "] not exist for mib [$mib]. Sensor skipped.");
            return;
        }
    } else {
        $entry['oid_num'] = rtrim($entry['oid_num'], '.');
    }

    // Fetch table or Oids
    $table_oids = ['oid', 'oid_descr', 'oid_scale', 'oid_precision', 'oid_unit', 'oid_class',
                   'oid_limit_low', 'oid_limit_low_warn', 'oid_limit_high_warn', 'oid_limit_high', 'oid_limit_warn',
                   'oid_limit_nominal', 'oid_limit_delta_warn', 'oid_limit_delta', 'oid_limit_scale',
                   'oid_extra', 'oid_entPhysicalIndex'];

    $sensor_array = discover_fetch_oids($device, $mib, $entry, $table_oids);

    $entry['type'] = $mib . '-' . $entry['oid'];

    $counters = []; // Reset per-class counters for each MIB

    $sensors_count = count($sensor_array);
    foreach ($sensor_array as $index => $sensor) {
        $options = [];

        $sensor = array_merge($sensor, entity_index_tags($index));

        $dot_index = strlen($index) ? '.' . $index : '';
        $oid_num   = $entry['oid_num'] . $dot_index;

        // Determine the sensor class
        $class = entity_class_definition($device, $entry, $sensor, 'sensor');
        if (!$class) {
            continue; // Break foreach. Proceed to next sensor!
        }

        // %i% can be used in description, a counter is kept per sensor class
        $counters[$class]++;

        // Generate specific keys used during rewrites
        $sensor['class'] = nicecase($class);  // Class in descr
        $sensor['i']     = $counters[$class]; // i++ counter in descr (per sensor class)
        $options['i']    = $sensor['i'] - 1;

        // Rule-based entity linking (associate before discovery_check_requires()).
        if ($measured = entity_measured_match_definition($device, $entry, $sensor, 'sensor')) {
            $options = array_merge($options, $measured);
            $sensor  = array_merge($sensor, $measured); // append to $sensor for %descr% tags, ie %port_label%
        } elseif (isset($entry['measured_match'])) {
            // In case when measured entity not found
            //$sensor['port_label'] = 'Port ' . $counters[$class];
            $sensor['port_label'] = $index;
            // End rule-based entity linking
        } elseif (isset($entry['entPhysicalIndex'])) {
            // Just set physical index
            $options['entPhysicalIndex'] = array_tag_replace($sensor, $entry['entPhysicalIndex']);
        }

        // Check valid exist with entity tags
        if (discovery_check_if_type_exist($entry, 'sensor', $sensor)) {
            continue;
        }

        // Check array requirements list
        if (discovery_check_requires($device, $entry, $sensor, 'sensor')) {
            continue;
        }

        // Addition & convert (before scale)
        if (isset($entry['addition'])) {
            // Static unit definition
            $options['sensor_addition'] = $entry['addition'];
        }
        if (isset($entry['convert'])) {
            // Static unit definition
            $options['sensor_convert'] = $entry['convert'];
        }

        // Scale
        $scale = entity_scale_definition($device, $entry, $sensor);

        // Limits
        $options = array_merge($options, entity_limits_definition($device, $entry, $sensor, $scale));

        // Add scale poller option, see: BLUECOAT-SG-SENSOR-MIB, SL-OTN-MIB
        if (isset($entry['scale_poll'], $entry['oid_scale']) && $entry['scale_poll']) {
            // walk this oid on every poll
            if (isset($entry['scale_si']) && $entry['scale_si']) {
                $options['oid_scale_si'] = $entry['oid_scale'];
            }
            print_debug_vars($options);
        }

        // Unit
        if (isset($entry['unit'])) {
            // Static unit definition
            $options['sensor_unit'] = $entry['unit'];
        }
        if (isset($entry['oid_unit'])) {
            if (isset($sensor[$entry['oid_unit']])) {
                // Unit in same table
                $unit = $sensor[$entry['oid_unit']];
            } elseif (str_contains_array($entry['oid_unit'], '.')) {
                // Unit is outside from table with single index, see VERTIV-V5-MIB
                $unit = snmp_cache_oid($device, $entry['oid_unit'], $mib);
            }
            // Translate unit from specific Oid
            if (isset($entry['map_unit'][$unit])) {
                $options['sensor_unit'] = $entry['map_unit'][$unit];
            }
        }

        $value = snmp_fix_numeric($sensor[$entry['oid']], $options['sensor_unit']);
        if (!discovery_check_value_valid($device, $value, $entry, 'sensor')) {
            continue;
        }

        // Generate Description
        $descr = entity_descr_definition('sensor', $entry, $sensor, $sensors_count);

        // Rename old (converted) RRDs to definition format
        if (isset($entry['rename_rrd'])) {
            $options['rename_rrd'] = $entry['rename_rrd'];
        } elseif (isset($entry['rename_rrd_full'])) {
            $options['rename_rrd_full'] = $entry['rename_rrd_full'];
        }

        print_debug_vars($options);
        discover_sensor_ng($device, $class, $mib, $entry['oid'], $oid_num, $index, $entry['type'], $descr, $scale, $value, $options);
    }

    echo '] ';

}


// Compatibility wrapper!
function discover_sensor($class, $device, $numeric_oid, $index, $type, $sensor_descr, $scale = 1, $value = NULL, $options = [], $poller_type = NULL)
{
    if (!safe_empty($poller_type)) {
        $options['poller_type'] = $poller_type;
    }

    return discover_sensor_ng($device, $class, '', '', $numeric_oid, $index, $type, $sensor_descr, $scale, $value, $options);
}


// TESTME needs unit testing
/**
 * Discover a new sensor on a device
 *
 * This function adds a status sensor to a device, if it does not already exist.
 * Data on the sensor is updated if it has changed, and an event is logged with regards to the changes.
 *
 * Status sensors are handed off to discover_status().
 * Current sensor values are rectified in case they are broken (added spaces, etc).
 *
 * @param array        $device       Device array sensor is being discovered on
 * @param string       $class        Class of sensor (voltage, temperature, etc.)
 * @param string       $mib          SNMP MIB name
 * @param string       $object       SNMP Named Oid of sensor (without index)
 * @param string       $oid          SNMP Numeric Oid of sensor (without index)
 * @param string       $index        SNMP index of sensor
 * @param string       $type         Type of sensor
 * @param string       $sensor_descr Description of sensor
 * @param int          $scale        Scale of sensor (0.1 for 1:10 scale, 10 for 10:1 scale, etc)
 * @param string|float $value        Current sensor value
 * @param array        $options      Options (sensor_unit, limit_auto, limit*, poller_type, scale, measured_*)
 *
 * @return bool
 */
function discover_sensor_ng($device, $class, $mib, $object, $oid, $index, $type, $sensor_descr, $scale = 1, $value = NULL, $options = [])
{
    global $config;

    //echo 'MIB:'; print_vars($mib);

    $poller_type = $options['poller_type'] ?? 'snmp';

    $sensor_deleted = 0;

    // If this is actually a status indicator, pass it off to discover_status() then return.
    if ($class === 'state' || $class === 'status') {
        print_debug("Redirecting call to discover_status().");
        return discover_status_ng($device, $mib, $object, $oid, $index, $type, $sensor_descr, $value, $options);
    }
    if ($class && isset($config['counter_types'][$class])) {
        print_debug("Redirecting call to discover_counter().");
        return discover_counter($device, $class, $mib, $object, $oid, $index, $sensor_descr, $scale, $value, $options);
    }
    if ($class === 'power' && $options['measured_class'] === 'port' &&                   // Power sensor with measured port entity
        $config['sensors']['port']['power_to_dbm'] &&                                    // Convert option set to TRUE
        $options['sensor_unit'] !== 'W' && !str_icontains_array($sensor_descr, 'PoE')) { // Not forced W unit, not PoE
        // DOM Power sensors convert to dBm
        print_debug("DOM power sensor forced to dBm sensor.");
        $options['sensor_unit'] = 'W';
        return discover_sensor_ng($device, 'dbm', $mib, $object, $oid, $index, $type, $sensor_descr, $scale, $value, $options);
    }

    // Init main
    $param_main = [
      'oid'            => 'sensor_oid',
      'type'           => 'sensor_type', // anyway compare type on update, while db query is case-insensitive
      'sensor_descr'   => 'sensor_descr',
      'scale'          => 'sensor_multiplier',
      'sensor_deleted' => 'sensor_deleted',
      'mib'            => 'sensor_mib',
      'object'         => 'sensor_object'
    ];

    // Init numeric values
    if (!is_numeric($scale) || $scale == 0) {
        $scale = 1;
    }

    // Generate a type if it's not provided
    if (safe_empty($type)) {
        $type = $mib . '-' . $object;
    }
    // Another hack for FIBERSTORE-MIB/FS-SWITCH-MIB multi lane DOM sensors
    // Append unit as sensor type part
    if (isset($options['sensor_unit']) && str_starts($options['sensor_unit'], 'split')) {
        $type .= '-' . $options['sensor_unit'];
    }

    // Skip discovery sensor if value not numeric or null (default)
    if (strlen($value)) {
        // Some retarded devices report data with spaces and commas
        // STRING: "  20,4"
        $value = snmp_fix_numeric($value, $options['sensor_unit']);
    }

    if (is_numeric($value)) {
        // $attrib_type = 'sensor_addition';
        // if (isset($options[$attrib_type]) && is_numeric($options[$attrib_type])) {
        //     // See in FOUNDRY-POE-MIB
        //     $value += $options[$attrib_type];
        // }
        $value = sensor_addition($device, $value, $options,
                                 [ 'poller_type' => $poller_type, 'device_id' => $device['device_id'],
                                   'sensor_class' => $class, 'sensor_index' => $index,
                                   'sensor_type' => $type, 'sensor_mib' => $mib ]);

        $value = scale_value($value, $scale);
        // $value *= $scale; // Scale before unit conversion
        $value = value_to_si($value, $options['sensor_unit'], $class); // Convert if not SI unit
    } else {
        print_debug("Sensor skipped by not numeric value: '$value', '$sensor_descr'");
        if (!safe_empty($value)) {
            print_debug("Perhaps this is named sensor, use discover_status() instead.");
        }
        return FALSE;
    }

    // Check sensor ignore filters
    if (entity_descr_check($sensor_descr, 'sensor')) {
        print_debug("Sensor skipped by ignored description: '$sensor_descr'");
        return FALSE;
    }

    $param_limits = [ 'limit_high' => 'sensor_limit', 'limit_high_warn' => 'sensor_limit_warn',
                      'limit_low'  => 'sensor_limit_low', 'limit_low_warn' => 'sensor_limit_low_warn' ];
    foreach ($param_limits as $key => $column) {
        // Set limits vars and unit convert if required
        if (is_numeric($options[$key])) {
            $$key = value_to_si($options[$key], $options['sensor_unit'], $class);
            // Force disable limit auto if any limit passed
            if (!isset($options['limit_auto']) && is_numeric($$key)) {
                $options['limit_auto'] = FALSE;
            }
        } else {
            $$key = NULL;
        }
        //$$key = (is_numeric($options[$key]) ? value_to_si($options[$key], $options['sensor_unit'], $class) : NULL);
    }
    // Auto calculate high/low limits if not passed
    $limit_auto = !isset($options['limit_auto']) || (bool)$options['limit_auto'];

    if (!$limit_auto) {
        // reset incorrect warning limits, see:
        // https://jira.observium.org/browse/OBS-3818
        if ($limit_high_warn === 0 && $limit_high_warn < $limit_high &&
            ($limit_high_warn < $limit_low_warn || $limit_high_warn < $limit_low)) {
            print_debug("High Warning Limit was reset to NULL");
            $limit_high_warn = NULL;
        }
        if ($limit_low_warn === 0 && $limit_low_warn > $limit_low &&
            ($limit_low_warn > $limit_high_warn || $limit_low_warn > $limit_high)) {
            print_debug("Low Warning Limit was reset to NULL");
            $limit_low_warn = NULL;
        }
        // another case with incorrect warnings:
        // -30,   0, 0, 3.008
        // -30, -30, 0, 3.008
        //   0, -30, 0, 1.99
        // https://jira.observium.org/browse/OBS-3597
        if (is_numeric($limit_low_warn) && is_numeric($limit_low) && $limit_low > $limit_low_warn) {
            print_debug("Low Warning Limit was swapped $limit_low (low) <> $limit_low_warn (warn).");
            // swap low warn & low
            $tmp            = $limit_low_warn;
            $limit_low_warn = $limit_low;
            $limit_low      = $tmp;
            unset($tmp);
        }
        if (is_numeric($limit_low_warn) &&
            (($limit_high_warn === $limit_low_warn) || ($limit_low === $limit_low_warn))) {
            print_debug("Low Warning Limit was reset to NULL");
            // reset incorrect low warning
            $limit_low_warn = NULL;
        }
    }
    if (!is_null($limit_low_warn) && !is_null($limit_high_warn) && ($limit_low_warn > $limit_high_warn)) {
        print_debug("High/low warning limits swapped.");
        // Fix high/low thresholds (i.e. on negative numbers)
        [ $limit_high_warn, $limit_low_warn ] = [ $limit_low_warn, $limit_high_warn ];
    }

    // Init optional
    $param_opt = ['entPhysicalIndex', 'entPhysicalClass', 'entPhysicalIndex_measured',
                  'measured_class', 'measured_entity', 'measured_entity_label', 'sensor_unit'];
    foreach ($param_opt as $key) {
        $$key = $options[$key] ?: NULL;
    }

    print_debug("Discover sensor: [class: $class, device: " . $device['hostname'] . ", oid: $oid, index: $index, type: $type, descr: $sensor_descr, scale: $scale, limits: ($limit_low, $limit_low_warn, $limit_high_warn, $limit_high), CURRENT: $value, $entPhysicalIndex, $entPhysicalClass");

    // print_debug_vars($limit_auto);
    // print_debug_vars($limit_high);
    // print_debug_vars($limit_high_warn);
    // print_debug_vars($limit_low_warn);
    // print_debug_vars($limit_low);

    if (!dbExist('sensors', '`poller_type`= ? AND `sensor_class` = ? AND `device_id` = ? AND `sensor_type` = ? AND `sensor_index` = ?',
                 [ $poller_type, $class, $device['device_id'], $type, $index ])) {
        if (!is_numeric($limit_high)) {
            $limit_high = sensor_limit_high($class, $value, $limit_auto);
        }
        if (!is_numeric($limit_low)) {
            $limit_low = sensor_limit_low($class, $value, $limit_auto);
        }

        if (!is_null($limit_low) && !is_null($limit_high) && ($limit_low > $limit_high)) {
            // Fix high/low thresholds (i.e. on negative numbers)
            [ $limit_high, $limit_low ] = [ $limit_low, $limit_high ];
            print_debug("High/low limits swapped.");
        }

        if (OBS_DEBUG) {
            $limit_rows = [];
            $limit_rows[] = [ 'Value', $value ];
            $limit_rows[] = [ '-----', '-----' ];
            $limit_rows[] = [ 'Auto',  $limit_auto ? 'TRUE' : 'FALSE' ];
            $limit_rows[] = [ 'High',         is_numeric($limit_high) ? $limit_high : '--' ];
            $limit_rows[] = [ 'High Warning', is_numeric($limit_high_warn) ? $limit_high_warn : '--' ];
            $limit_rows[] = [ 'Low Warning',  is_numeric($limit_low_warn) ? $limit_low_warn : '--' ];
            $limit_rows[] = [ 'Low',          is_numeric($limit_low) ? $limit_low : '--' ];
            print_cli_table($limit_rows, [ 'Limit Param', 'Value' ]);
        }

        $sensor_insert = ['poller_type'  => $poller_type, 'sensor_class' => $class, 'device_id' => $device['device_id'],
                          'sensor_index' => $index, 'sensor_type' => $type];

        foreach ($param_main as $key => $column) {
            $sensor_insert[$column] = $$key;
        }

        foreach ($param_limits as $key => $column) {
            // Convert strings/numbers to (float) or to array('NULL')
            $$key                   = is_numeric($$key) ? (float)$$key : ['NULL'];
            $sensor_insert[$column] = $$key;
        }
        foreach ($param_opt as $key) {
            if (is_null($$key)) {
                $$key = ['NULL'];
            }
            $sensor_insert[$key] = $$key;
        }

        $sensor_insert['sensor_value']  = $value;
        $sensor_insert['sensor_polled'] = time(); // array('NOW()'); // this field is INT(11)

        $sensor_id = dbInsert($sensor_insert, 'sensors');

        // Extra (rare) params
        foreach ([ 'sensor_addition', 'sensor_convert', 'oid_scale_si' ] as $attrib_type) {
            if (isset($options[$attrib_type]) && !safe_empty($options[$attrib_type])) {
                // Add sensor attrib for use in poller
                set_entity_attrib('sensor', $sensor_id, $attrib_type, $options[$attrib_type]);
            }
        }

        print_debug("( $sensor_id inserted )");
        echo('+');

        log_event("Sensor added: $class $type $index $sensor_descr", $device, 'sensor', $sensor_id);
    } else {
        $sensor_entry = dbFetchRow("SELECT * FROM `sensors` WHERE `sensor_class` = ? AND `device_id` = ? AND `sensor_type` = ? AND `sensor_index` = ?", [$class, $device['device_id'], $type, $index]);
        $sensor_id    = $sensor_entry['sensor_id'];

        // Limits
        if (!$sensor_entry['sensor_custom_limit']) {
            if (!is_numeric($limit_high)) {
                if ($sensor_entry['sensor_limit'] !== '') {
                    // Calculate a reasonable limit
                    $limit_high = sensor_limit_high($class, $value, $limit_auto);
                } else {
                    // Use existing limit. (this is wrong! --mike)
                    $limit_high = $sensor_entry['sensor_limit'];
                }
            }

            if (!is_numeric($limit_low)) {
                if ($sensor_entry['sensor_limit_low'] !== '') {
                    // Calculate a reasonable limit
                    $limit_low = sensor_limit_low($class, $value, $limit_auto);
                } else {
                    // Use existing limit. (this is wrong! --mike)
                    $limit_low = $sensor_entry['sensor_limit_low'];
                }
            }

            // Fix high/low thresholds (i.e. on negative numbers)
            if (!is_null($limit_low) && !is_null($limit_high) && ($limit_low > $limit_high)) {
                [ $limit_high, $limit_low ] = [ $limit_low, $limit_high ];
                print_debug("High/low limits swapped.");
            }

            // Update limits
            $update     = [];
            $update_msg = [];

            $limit_rows = [];
            $limit_rows[] = [ 'Value', $value, '' ];
            $limit_rows[] = [ 'Scale', $scale, '' ];
            $limit_rows[] = [ '-----', '-----', '-----' ];
            $limit_rows[] = [ 'Auto',  $limit_auto ? 'TRUE' : 'FALSE', '' ];
            foreach ($param_limits as $key => $column) {
                // $key - param name, $$key - param value, $column - column name in DB for $key
                $limit_row = [ $key, is_numeric($$key) ? $$key : '--' ];
                $debug_msg = '';
                //convert strings/numbers to identical type (float) or to array('NULL') for correct comparison
                $$key                  = is_numeric($$key) ? (float)$$key : [ 'NULL' ];
                $sensor_entry[$column] = is_numeric($sensor_entry[$column]) ? (float)$sensor_entry[$column] : [ 'NULL' ];
                if (float_cmp($$key, $sensor_entry[$column], $limit_auto ? 0.01 : 0.0000001) !== 0) {
                    // Auto generated limits epsilon 0.01, default is 0.0000001
                    $update[$column] = $$key;
                    $update_msg[]    = $key . ' -> "' . (is_array($$key) ? 'NULL' : $$key) . '"';
                    $debug_msg       = $limit_auto ? ' (AUTO)' : ' (CHANGED)';
                }
                $limit_row[] = (is_numeric($sensor_entry[$column]) ? $sensor_entry[$column] : '--') . $debug_msg;
                $limit_rows[] = $limit_row;
            }
            if (count($update)) {
                echo("L");
                //print_debug($debug_msg);
                if (OBS_DEBUG) {
                    print_cli_table($limit_rows, [ 'Limit Param', 'Value', 'Previous' ]);
                }
                if ($config['sensors']['limits_events']) {
                    log_event('Sensor updated (limits): ' . implode(', ', $update_msg), $device, 'sensor', $sensor_entry['sensor_id']);
                }
                $updated = dbUpdate($update, 'sensors', '`sensor_id` = ?', [$sensor_entry['sensor_id']]);
            }
        }

        $update = [];
        foreach ($param_main as $key => $column) {
            if (float_cmp($$key, $sensor_entry[$column]) !== 0) {
                $update[$column] = $$key;
            }
        }

        foreach ($param_opt as $key) {
            if ($$key != $sensor_entry[$key]) {
                $update[$key] = !is_null($$key) ? $$key : ['NULL'];
            }
        }

        // Extra (rare) params
        $attribs = get_entity_attribs('sensor', $sensor_entry['sensor_id']);
        foreach ([ 'sensor_addition', 'sensor_convert', 'oid_scale_si' ] as $attrib_type) {
            //$attrib = get_entity_attrib('sensor', $sensor_entry['sensor_id'], $attrib_type);
            if (!safe_empty($options[$attrib_type])) {
                // Add sensor attrib for use in poller
                if ($attribs[$attrib_type] != $options[$attrib_type]) {
                    set_entity_attrib('sensor', $sensor_id, $attrib_type, $options[$attrib_type]);
                }
            } elseif (!safe_empty($attribs[$attrib_type])) {
                del_entity_attrib('sensor', $sensor_entry['sensor_id'], $attrib_type);
            }
        }

        if (count($update)) {
            $updated = dbUpdate($update, 'sensors', '`sensor_id` = ?', [$sensor_entry['sensor_id']]);
            echo('U');
            log_event("Sensor updated: $class $type $index $sensor_descr", $device, 'sensor', $sensor_entry['sensor_id']);
        } else {
            echo('.');
        }
    }

    // Rename old (converted) RRDs to definition format
    // Allow with changing class or without
    if (isset($options['rename_rrd']) || isset($options['rename_rrd_full'])) {
        $rrd_tags = ['index' => $index, 'type' => $type, 'mib' => $mib, 'object' => $object, 'oid' => $object, 'i' => $options['i']];
        if (isset($options['rename_rrd'])) {
            $options['rename_rrd'] = array_tag_replace($rrd_tags, $options['rename_rrd']);
            $old_rrd               = 'sensor-' . $class . '-' . $options['rename_rrd'];
        } elseif (isset($options['rename_rrd_full'])) {
            $options['rename_rrd_full'] = array_tag_replace($rrd_tags, $options['rename_rrd_full']);
            $old_rrd                    = 'sensor-' . $options['rename_rrd_full'];
        }
        $new_rrd = 'sensor-' . $class . '-' . $type . '-' . $index;
        rename_rrd($device, $old_rrd, $new_rrd);
    }

    $GLOBALS['valid']['sensor'][$class][$type][$index] = 1;

    return $sensor_id;
    //return TRUE;

}

// TESTME needs unit testing
/**
 * Calculate lower limit on a sensor
 *
 * @param string $class Sensor class (voltage, temperature, ...)
 * @param string $value Current sensor value to use as base
 * @param bool   $auto  Set to false to not set an automatic limit
 *
 * @return string
 */
function sensor_limit_low($class, $value, $auto = TRUE)
{

    if (!$auto || $value == 0) {
        return NULL;
    } // Do not calculate limit

    $limit = NULL;
    switch ($class) {
        case 'temperature':
            if ($value > 0) {
                $limit = 0; // Freezing cold should be enough of a lower limit.
            }
            break;
        case 'voltage':
            if ($value < 0) {
                $limit = $value * (1 + (sgn($value) * 0.15));
            } else {
                $limit = $value * (1 - (sgn($value) * 0.15));
            }
            break;
        case 'humidity':
            $limit = 20;
            break;
        case 'frequency':
            $limit = $value * 0.95;
            break;
        case 'current':
            $limit = NULL;
            break;
        case 'fanspeed':
            $limit = $value * 0.80;
            break;
        case 'power':
            $limit = NULL;
            break;
    }

    return $limit;
}

function sensor_limit_low_warn($class, $value, $auto = TRUE)
{

    if (!$auto || $value == 0) {
        return NULL;
    } // Do not calculate limit

    $limit = NULL;
    switch ($class) {
        case 'temperature':
            $limit = NULL;
            break;
        case 'voltage':
            if ($value < 0) {
                $limit = $value * (1 + (sgn($value) * 0.10));
            } else {
                $limit = $value * (1 - (sgn($value) * 0.10));
            }
            break;
        case 'humidity':
            $limit = 25;
            break;
        case 'frequency':
            $limit = $value * 0.97;
            break;
        case 'current':
            $limit = NULL;
            break;
        case 'fanspeed':
            $limit = $value * 0.85;
            break;
        case 'power':
            $limit = NULL;
            break;
    }

    return $limit;
}

/**
 * Calculate upper limit on a sensor
 *
 * @param string $class Sensor class (voltage, temperature, ...)
 * @param string $value Current sensor value to use as base
 * @param bool   $auto  Set to false to not set an automatic limit
 *
 * @return string
 */
function sensor_limit_high($class, $value, $auto = TRUE)
{

    if (!$auto || $value == 0) {
        return NULL;
    } // Do not calculate limit

    $limit = NULL;
    switch ($class) {
        case 'temperature':
            if ($value < 0) {
                // Negative temperatures are usually used for "Thermal margins",
                // indicating how far from the critical point we are.
                $limit = 0;
            } else {
                $limit = $value * 1.60;
            }
            break;
        case 'voltage':
            if ($value < 0) {
                $limit = $value * (1 - (sgn($value) * 0.15));
            } else {
                $limit = $value * (1 + (sgn($value) * 0.15));
            }
            break;
        case 'humidity':
            $limit = 70;
            break;
        case 'frequency':
            $limit = $value * 1.05;
            break;
        case 'current':
            $limit = $value * 1.50;
            break;
        case 'fanspeed':
            $limit = $value * 1.80;
            break;
        case 'power':
            $limit = $value * 1.50;
            break;
    }

    return $limit;
}

function sensor_limit_high_warn($class, $value, $auto = TRUE)
{

    if (!$auto || $value == 0) {
        return NULL;
    } // Do not calculate limit

    $limit = NULL;
    switch ($class) {
        case 'temperature':
            if ($value < 0) {
                // Negative temperatures are usually used for "Thermal margins",
                // indicating how far from the critical point we are.
                //$limit = 0;
            } else {
                $limit = $value * 1.50;
            }
            break;
        case 'voltage':
            if ($value < 0) {
                $limit = $value * (1 - (sgn($value) * 0.10));
            } else {
                $limit = $value * (1 + (sgn($value) * 0.10));
            }
            break;
        case 'humidity':
            $limit = 65;
            break;
        case 'frequency':
            $limit = $value * 1.03;
            break;
        case 'current':
            $limit = $value * 1.30;
            break;
        case 'fanspeed':
            $limit = $value * 1.50;
            break;
        case 'power':
            $limit = $value * 1.30;
            break;
    }

    return $limit;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function check_valid_sensors($device, $class, $valid, $poller_type = 'snmp')
{
    $entries = dbFetchRows("SELECT * FROM `sensors` WHERE `device_id` = ? AND `sensor_class` = ? AND `poller_type` = ? AND `sensor_deleted` = '0'", [$device['device_id'], $class, $poller_type]);

    if (safe_count($entries)) {
        foreach ($entries as $entry) {
            $index = $entry['sensor_index'];
            $type  = $entry['sensor_type'];
            if (!$valid[$class][$type][$index]) {
                echo("-");
                print_debug("Sensor deleted: $index -> $type");
                //dbDelete('sensors',       "`sensor_id` = ?", array($entry['sensor_id']));

                dbUpdate(['sensor_deleted' => '1'], 'sensors', '`sensor_id` = ?', [$entry['sensor_id']]);
                //dbDelete('sensors-state', "`sensor_id` = ?", array($entry['sensor_id']));

                foreach (get_entity_attribs('sensor', $entry['sensor_id']) as $attrib_type => $value) {
                    del_entity_attrib('sensor', $entry['sensor_id'], $attrib_type);
                }
                log_event("Sensor deleted: " . $entry['sensor_class'] . " " . $entry['sensor_type'] . " " . $entry['sensor_index'] . " " . $entry['sensor_descr'], $device, 'sensor', $entry['sensor_id']);
            }
        }
    }
}

// Poll a sensor
function poll_sensor($device, $class, $unit, &$oid_cache)
{
    global $config, $graphs, $table_rows;

    $sql = "SELECT * FROM `sensors`";
    //$sql .= " LEFT JOIN `sensors-state` USING(`sensor_id`)";
    $sql .= " WHERE `sensor_class` = ? AND `device_id` = ? AND `sensor_deleted` = ?";
    $sql .= ' ORDER BY `sensor_oid`'; // This fix polling some OIDs (when not ordered)

    //print_vars($GLOBALS['cache']['entity_attribs']);
    foreach (dbFetchRows($sql, [$class, $device['device_id'], '0']) as $sensor_db) {
        $sensor_poll = [];

        //print_cli_heading("Sensor: ".$sensor_db['sensor_descr'], 3);

        if (OBS_DEBUG) {
            echo("Checking (" . $sensor_db['poller_type'] . ") $class " . $sensor_db['sensor_descr'] . " ");
            print_debug_vars($sensor_db, 1);
        }

        $sensor_poll['sensor_value'] = get_sensor_cached_value($device, $oid_cache, $sensor_db);
        if ($sensor_poll['sensor_value'] === FALSE) {
            // no agent/ipmi values
            continue;
        }
        $sensor_polled_time = time(); // Store polled time for current sensor

        print_debug_vars($sensor_poll, 1);

        // Addition, Scale and Unit conversion
        $sensor_poll['sensor_value'] = sensor_value_scale($device, $sensor_poll['sensor_value'], $sensor_db);

        //print_cli_data("Value", $sensor_poll['sensor_value'] . "$unit ", 3);

        // FIXME this block and the other block below it are kinda retarded. They should be merged and simplified.

        if ($sensor_db['sensor_disable']) {
            $sensor_poll['sensor_event']  = 'ignore';
            $sensor_poll['sensor_status'] = 'Sensor disabled.';
        } else {
            $sensor_poll['sensor_event'] = check_thresholds($sensor_db['sensor_limit_low'], $sensor_db['sensor_limit_low_warn'],
                                                            $sensor_db['sensor_limit_warn'], $sensor_db['sensor_limit'],
                                                            $sensor_poll['sensor_value']);
            // Percent based classes, ignore invalid values.
            // See: CPQIDA-MIB::cpqDaLogDrvPercentRebuild
            if (in_array($class, ["progress", "load", "capacity"], TRUE) && ($sensor_poll['sensor_value'] < 0 || $sensor_poll['sensor_value'] > 100)) {
                $sensor_poll['sensor_event']  = 'ignore';
                $sensor_poll['sensor_status'] = 'Sensor beyond normal values.';
            }

            if ($sensor_poll['sensor_event'] === 'alert') {


                // Force ignore state if measured entity is in Shutdown state
                $measured_class = $sensor_db['measured_class'];
                if (is_numeric($sensor_db['measured_entity']) &&
                    isset($config['sensors'][$measured_class]['ignore_shutdown']) && $config['sensors'][$measured_class]['ignore_shutdown']) {
                    $measured_entity = get_entity_by_id_cache($measured_class, $sensor_db['measured_entity']);
                    print_debug_vars($measured_entity);
                    // Currently only for ports
                    if (isset($measured_entity['ifAdminStatus']) && $measured_entity['ifAdminStatus'] === 'down') {
                        $sensor_poll['sensor_event']  = 'ignore';
                        $sensor_poll['sensor_status'] = 'Sensor critical thresholds exceeded, but ignored.';
                    }
                }
            } elseif ($sensor_poll['sensor_event'] === 'warning') {
                $sensor_poll['sensor_status'] = 'Sensor warning thresholds exceeded.';
            } else {
                $sensor_poll['sensor_status'] = '';
            }

            // Reset Alert if sensor ignored
            if ($sensor_poll['sensor_event'] !== 'ok' && $sensor_db['sensor_ignore']) {
                $sensor_poll['sensor_event']  = 'ignore';
                $sensor_poll['sensor_status'] = 'Sensor thresholds exceeded, but ignored.';
            }
        }

        // If last change never set, use current time
        if (empty($sensor_db['sensor_last_change'])) {
            $sensor_db['sensor_last_change'] = $sensor_polled_time;
        }

        if ($sensor_poll['sensor_event'] != $sensor_db['sensor_event']) {
            // Sensor event changed, log and set sensor_last_change
            $sensor_poll['sensor_last_change'] = $sensor_polled_time;

            if ($sensor_db['sensor_event'] === 'ignore') {
                print_message("[%ySensor Ignored%n]", 'color');
            } elseif (is_numeric($sensor_db['sensor_limit_low']) &&
                      $sensor_db['sensor_value'] >= $sensor_db['sensor_limit_low'] &&
                      $sensor_poll['sensor_value'] < $sensor_db['sensor_limit_low']) {
                // If old value greater than low limit and new value less than low limit
                $msg = ucfirst($class) . " Alarm: " . $device['hostname'] . " " . $sensor_db['sensor_descr'] . " is under threshold: " . $sensor_poll['sensor_value'] . "$unit (< " . $sensor_db['sensor_limit_low'] . "$unit)";
                log_event(ucfirst($class) . ' ' . $sensor_db['sensor_descr'] . " under threshold: " . $sensor_poll['sensor_value'] . " $unit (< " . $sensor_db['sensor_limit_low'] . " $unit)", $device, 'sensor', $sensor_db['sensor_id'], 'warning');
            } elseif (is_numeric($sensor_db['sensor_limit']) &&
                      $sensor_db['sensor_value'] <= $sensor_db['sensor_limit'] &&
                      $sensor_poll['sensor_value'] > $sensor_db['sensor_limit']) {
                // If old value less than high limit and new value greater than high limit
                $msg = ucfirst($class) . " Alarm: " . $device['hostname'] . " " . $sensor_db['sensor_descr'] . " is over threshold: " . $sensor_poll['sensor_value'] . "$unit (> " . $sensor_db['sensor_limit'] . "$unit)";
                log_event(ucfirst($class) . ' ' . $sensor_db['sensor_descr'] . " above threshold: " . $sensor_poll['sensor_value'] . " $unit (> " . $sensor_db['sensor_limit'] . " $unit)", $device, 'sensor', $sensor_db['sensor_id'], 'warning');
            }
        } else {
            // If sensor not changed, leave old last_change
            $sensor_poll['sensor_last_change'] = $sensor_db['sensor_last_change'];
        }

        // Send statistics array via AMQP/JSON if AMQP is enabled globally and for the ports module
        if ($config['amqp']['enable'] == TRUE && $config['amqp']['modules']['sensors']) {
            $json_data = ['value' => $sensor_poll['sensor_value']];
            messagebus_send(['attribs' => ['t'      => time(), 'device' => $device['hostname'], 'device_id' => $device['device_id'],
                                           'e_type' => 'sensor', 'e_class' => $sensor_db['sensor_class'], 'e_type' => $sensor_db['sensor_type'], 'e_index' => $sensor_db['sensor_index']], 'data' => $json_data]);
        }

        // Add table row

        $table_rows[] = [$sensor_db['sensor_descr'], $sensor_db['sensor_class'], $sensor_db['sensor_type'], $sensor_db['poller_type'],
                         $sensor_poll['sensor_value'] . $unit, $sensor_poll['sensor_event'], format_unixtime($sensor_poll['sensor_last_change'])];

        // Update StatsD/Carbon
        if ($config['statsd']['enable'] == TRUE) {
            StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'sensor' . '.' . $sensor_db['sensor_class'] . '.' . $sensor_db['sensor_type'] . '.' . $sensor_db['sensor_index'], $sensor_poll['sensor_value']);
        }

        // Update RRD - FIXME - can't convert to NG because filename is dynamic! new function should return index instead of filename.
        $rrd_file = get_sensor_rrd($device, $sensor_db);
        rrdtool_create($device, $rrd_file, "DS:sensor:GAUGE:600:-20000:U");
        rrdtool_update($device, $rrd_file, "N:" . $sensor_poll['sensor_value']);

        // Enable graph
        $graphs[$sensor_db['sensor_class']] = TRUE;

        // Check alerts
        $metrics = [];

        $metrics['sensor_value']        = $sensor_poll['sensor_value'];
        $metrics['sensor_event']        = $sensor_poll['sensor_event'];
        $metrics['sensor_event_uptime'] = $sensor_polled_time - $sensor_poll['sensor_last_change'];
        $metrics['sensor_status']       = $sensor_poll['sensor_status'];

        check_entity('sensor', $sensor_db, $metrics);

        // Add to MultiUpdate SQL State

        $GLOBALS['multi_update_db'][] = [
          'sensor_id'          => $sensor_db['sensor_id'], // UNIQUE index
          'sensor_value'       => $sensor_poll['sensor_value'],
          'sensor_event'       => $sensor_poll['sensor_event'],
          'sensor_status'      => $sensor_poll['sensor_status'],
          'sensor_last_change' => $sensor_poll['sensor_last_change'],
          'sensor_polled'      => $sensor_polled_time
        ];
    }
}

/**
 * Parse output of ipmitool sensor
 *
 * @param        $device
 * @param        $results
 * @param string $source
 *
 * @return mixed
 */
function parse_ipmitool_sensor($device, $results, $source = 'ipmi')
{
    global $valid, $config;

    $index = 0;

    $ipmi_sensors = [];
    foreach (explode("\n", $results) as $row) {
        $index++;

        # BB +1.1V IOH     | 1.089      | Volts      | ok    | na        | 1.027     | 1.054     | 1.146     | 1.177     | na
        # Fan5             | 0.000      | RPM        | nr    | 200.000   | 300.000   | 400.000   | na        | na        | na
        [$desc, $current, $unit, $state, $low_nonrecoverable, $limit_low, $limit_low_warn, $limit_high_warn, $limit_high, $high_nonrecoverable] = explode('|', $row);

        // Trim values
        $current = trim($current);
        $state   = trim($state);
        $unit    = trim($unit);
        $desc    = trim($desc);
        if ($current !== "na" && $state !== "nr") {
            if ($unit === 'discrete') {
                // Statuses
                if (!$config['ipmi_unit']['discrete']) {
                    print_debug("Discrete statuses disabled.");
                    continue;
                }
                print_warning("Discrete statuses support is very unstable!");

                $options    = [];
                $ipmi_state = hexdec($state) + 0;
                print_debug("Descr: $desc, Current: $current, State: $state ($ipmi_state), Unit: $unit");

                // 0 - fail, 1 - ok
                switch ($current) {
                    // Intrusion        | 0x0        | discrete   | 0x0100| na        | na        | na        | na        | na        | na
                    // Power Supply     | 0x0        | discrete   | 0x0000| na        | na        | na        | na        | na        | na
                    // PS1 Status       | 0x1        | discrete   | 0x0100| na        | na        | na        | na        | na        | na
                    // PS2 Status       | 0x1        | discrete   | 0x0100| na        | na        | na        | na        | na        | na

                    case '0x0':
                        $state = in_array($state, ['0x0000', '0x01ff']) ? 1 : 0;
                        break;
                    case '0x1':
                        $state = in_array($state, ['0x0000', '0x01ff']) ? 0 : 1;
                        break;
                }

                if (str_istarts($desc, ['chassis', 'intrusion'])) {
                    // Chassis
                    $options = ['entPhysicalClass' => 'chassis'];
                    //$state = ($state === '0x0000') ? 1 : 0;
                } elseif (str_istarts($desc, ['ps', 'power supply'])) {
                    // Power Supply
                    $options = ['entPhysicalClass' => 'powersupply'];
                    //$state = in_array($state, [ '0x0000', '0x01ff' ]) ? 1 : 0;
                } else {
                    // All others
                    $options = ['entPhysicalClass' => 'other'];
                    //$state = ($state === '0x0000') ? 1 : 0;
                }

                $state_type = $source === 'ipmi' ? 'ipmi-state' : 'unix-agent-state';
                discover_status($device, '', $index, $state_type, $desc, $state, $options, $source);
                $ipmi_sensors['state'][$state_type][$index] = ['description' => $desc, 'current' => $state, 'index' => $index];
            } elseif (isset($config['ipmi_unit'][$unit])) {
                print_debug("Descr: $desc, Current: $current, State: $state, Unit: $unit");
                // Numeric sensors
                $class = $config['ipmi_unit'][$unit];
                if (($class === 'capacity') && str_istarts($desc, [ 'fan' ])) {
                    $class = 'load';
                }
                $options = [];
                foreach (['limit_high', 'limit_low', 'limit_high_warn', 'limit_low_warn'] as $limit) {
                    $limit_value = trim($$limit);
                    if (is_numeric($limit_value)) {
                        $options[$limit] = $limit_value;
                    }
                }

                discover_sensor($class, $device, '', $index, $source, $desc, 1, $current, $options, $source);

                $ipmi_sensors[$class][$source][$index] = ['description' => $desc, 'current' => $current, 'index' => $index, 'unit' => $unit];
            }
        }
    }

    return $ipmi_sensors;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_sensor_rrd($device, $sensor)
{
    global $config;

    # For IPMI/agent, sensors tend to change order, and there is no index, so we prefer to use the description as key here.
    if ((isset($config['os'][$device['os']]['sensor_descr']) && $config['os'][$device['os']]['sensor_descr']) ||                     // per os definition
        (isset($config['mibs'][$sensor['sensor_mib']]['sensor_descr']) && $config['mibs'][$sensor['sensor_mib']]['sensor_descr']) || // per mib definition
        ($sensor['poller_type'] != "snmp" && $sensor['poller_type'] != '')) {
        $index = $sensor['sensor_descr'];
    } else {
        $index = $sensor['sensor_index'];
    }

    // note, in discover_sensor_ng() sensor_type == %mib%-%object%
    $rrd_file = "sensor-" . $sensor['sensor_class'] . "-" . $sensor['sensor_type'] . "-" . $index . ".rrd";


    return $rrd_file;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_sensor_by_id($sensor_id)
{
    if (is_numeric($sensor_id)) {
        $sensor = dbFetchRow("SELECT * FROM `sensors` WHERE `sensor_id` = ?", [$sensor_id]);
    }
    if (is_array($sensor)) {
        return $sensor;
    }

    return FALSE;
}

function get_sensor_cached_value($device, $oid_cache, &$sensor_db) {
    global $agent_sensors, $ipmi_sensors;

    $class = $sensor_db['sensor_class'];

    if ($sensor_db['poller_type'] === "snmp") {

        if (!str_starts_with($sensor_db['sensor_oid'], '.')) {
            // Fix first dot in oid for caching
            $sensor_db['sensor_oid'] = '.' . $sensor_db['sensor_oid'];
        }
        $sensor_oid = $sensor_db['sensor_oid'];

        // Take the value from $oid_cache if we have it, else snmp_get it
        if (isset($oid_cache[$sensor_oid]) && !safe_empty($oid_cache[$sensor_oid])) {
            $sensor_value = $oid_cache[$sensor_oid];
            $cached = TRUE;
        } else {
            $sensor_value = snmp_get_oid($device, $sensor_oid, 'SNMPv2-MIB');
            $cached = FALSE;
        }
        // Compat with runtime sensors without set unit
        // Use timetick conversion only when snmpdata is formatted as timetick 0:0:21:00.00
        if ($class === "runtime" && empty($sensor_db['sensor_unit']) && str_contains($sensor_value, ':')) {
            $sensor_db['sensor_unit'] = 'timeticks';
        }
        $sensor_value = snmp_fix_numeric($sensor_value, $sensor_db['sensor_unit']);

        // Papouch TME hack, was added in r2337 by @adama
        if (sensor_value_retry($device, $sensor_value, $sensor_db)) {
            $cached = FALSE;
        }

        if ($cached) {
            print_debug("value taken from oid_cache: $sensor_oid = $sensor_value");
        }

    } elseif ($sensor_db['poller_type'] === "agent") {
        if (!safe_empty($agent_sensors)) {
            $sensor_value = snmp_fix_numeric($agent_sensors[$class][$sensor_db['sensor_type']][$sensor_db['sensor_index']]['current']);
        } else {
            print_warning("No agent sensor data available.");
            return FALSE;
        }
    } elseif ($sensor_db['poller_type'] === "ipmi") {
        if (!safe_empty($ipmi_sensors)) {
            $sensor_value = snmp_fix_numeric($ipmi_sensors[$class][$sensor_db['sensor_type']][$sensor_db['sensor_index']]['current']);
            $sensor_db['sensor_unit'] = $ipmi_sensors[$class][$sensor_db['sensor_type']][$sensor_db['sensor_index']]['unit'];
        } else {
            print_warning("No IPMI sensor data available.");
            return FALSE;
        }
    } else {
        print_error("Unknown sensor poller type.");
        return FALSE;
    }

    if ($sensor_value == -32768) {
        // FIXME. Not know why? Was added in r2493 by @adama
        print_debug("Reset invalid {$sensor_db['sensor_class']} sensor value '-32768' to 0.");
        return 0;
    }
    if ($sensor_db['sensor_unit'] === 'W' && $sensor_value < 0) {
        // See: https://jira.observium.org/browse/OBS-3200
        // -9999 is exclude for Extreme devices, which report incorrect RX power when no power received
        print_debug("Reset invalid {$sensor_db['sensor_class']} sensor value '$sensor_value' to 0.");
        return 0;
    }

    return $sensor_value;
}

function sensor_value_retry($device, &$sensor_value, $sensor_db) {
    // Ie $config['os'][$os]['sensors_temperature_invalid'] = 9999;
    $os_sensor_def = 'sensors_' . $sensor_db['sensor_class'] . '_invalid';
    if (!isset($GLOBALS['config']['os'][$device['os']][$os_sensor_def])) {
        return FALSE;
    }

    $value_invalid = (string)$GLOBALS['config']['os'][$device['os']][$os_sensor_def];

    // Papouch TME hack, was added in r2337 by @adama
    // Papouch TME sometimes sends 999.9 when it is right in the middle of an update
    // Unified for Fortinet

    // Try 5 times to get a valid temp reading
    $i = 0;
    while ((string)$sensor_value === $value_invalid || !is_numeric($sensor_value)) {
        sleep(1); // Give the TME some time to reset
        $i++;
        print_debug("Retry ($i) get {$sensor_db['sensor_class']} sensor value..");
        $sensor_value = snmp_fix_numeric(snmp_get_oid($device, $sensor_db['sensor_oid'], 'SNMPv2-MIB'), $sensor_db['sensor_unit']);
        if ($i === 4) {
            break;
        }
    }
    // If we received 999.9 degrees still, reset to 0.
    if ($sensor_value === $value_invalid) {
        print_debug("Reset invalid {$sensor_db['sensor_class']} sensor value '$value_invalid' to 0.");
        $sensor_value = 0;
    }

    return (bool)$i;
}

function sensor_value_scale($device, $sensor_value, &$sensor_db) {
    // Sensor attribs, by first must be cached
    $attribs = $GLOBALS['cache']['entity_attribs']['sensor'][$sensor_db['sensor_id']] ?? [];

    // Addition & Conversion & Scale
    if (is_numeric($sensor_value)) {
        $mib = $sensor_db['sensor_mib'];

        $sensor_value = sensor_addition($device, $sensor_value, $attribs, $sensor_db);

        // See BLUECOAT-SG-SENSOR-MIB
        if (isset($attribs['oid_scale_si']) && !safe_empty($attribs['oid_scale_si'])) {
            if (str_contains($attribs['oid_scale_si'], '.')) {
                // single oid
                if ($scale_si = snmp_cache_oid($device, $attribs['oid_scale_si'], $mib)) {
                    $sensor_db['sensor_multiplier'] = si_to_scale($scale_si);
                    print_debug("Sensor set scale by get oid " . $mib . '::' . $attribs['oid_scale_si'] . ": " . $sensor_db['sensor_multiplier'] . " ($scale_si).");
                }
            } elseif (isset($config['mibs'][$mib]['sensors_walk']) && !$config['mibs'][$mib]['sensors_walk']) {
                // single oid by index
                if ($scale_si = snmp_cache_oid($device, $attribs['oid_scale_si'] . '.' . $sensor_db['sensor_index'], $mib)) {
                    $sensor_db['sensor_multiplier'] = si_to_scale($scale_si);
                    print_debug("Sensor set scale by get oid " . $mib . '::' . $attribs['oid_scale_si'] . '.' . $sensor_db['sensor_index'] . ": " . $sensor_db['sensor_multiplier'] . " ($scale_si).");
                }
            } else {
                // table walk
                $scale_table = snmp_cache_table($device, $attribs['oid_scale_si'], [], $mib);
                if (isset($scale_table[$sensor_db['sensor_index']][$attribs['oid_scale_si']])) {
                    $scale_si = $scale_table[$sensor_db['sensor_index']][$attribs['oid_scale_si']];
                    $sensor_db['sensor_multiplier'] = si_to_scale($scale_si);
                    print_debug("Sensor set scale by walk table " . $mib . '::' . $attribs['oid_scale_si'] . ": " . $sensor_db['sensor_multiplier'] . " ($scale_si).");
                }
            }
        }
    }

    if (isset($sensor_db['sensor_multiplier']) && $sensor_db['sensor_multiplier'] != 0) {
        $sensor_value = scale_value($sensor_value, $sensor_db['sensor_multiplier']);
    }

    // Unit conversion to SI (if required)
    return value_to_si($sensor_value, $sensor_db['sensor_unit'], $sensor_db['sensor_class']);
}

function sensor_addition($device, $sensor_value, $attribs = [], $sensor_db = []) {
    $mib = $sensor_db['sensor_mib'];

    if (isset($attribs['sensor_convert'])) {
        switch ($attribs['sensor_convert']) {
            case 'tmnx_rx_power':
                $oids = [
                    'tmnxDDMExtCalRxPower0.' . $sensor_db['sensor_index'],
                    'tmnxDDMExtCalRxPower1.' . $sensor_db['sensor_index'],
                    'tmnxDDMExtCalRxPower2.' . $sensor_db['sensor_index'],
                    'tmnxDDMExtCalRxPower3.' . $sensor_db['sensor_index'],
                    'tmnxDDMExtCalRxPower4.' . $sensor_db['sensor_index'],
                ];
                $entry = snmp_get_multi_oid($device, $oids, [], $mib);
                $entry = $entry[$sensor_db['sensor_index']];
                $sensor_value = value_unit_tmnx_rx_power($sensor_value, $entry['tmnxDDMExtCalRxPower0'], $entry['tmnxDDMExtCalRxPower1'],
                                                         $entry['tmnxDDMExtCalRxPower2'], $entry['tmnxDDMExtCalRxPower3'], $entry['tmnxDDMExtCalRxPower4']);
                break;

            case 'sysuptime':
                //r($sensor_value);
                $sensor_value = timeticks_to_sec(snmp_cache_oid($device, "sysUpTime.0", "SNMPv2-MIB"), TRUE) - $sensor_value;
                //r($sensor_value);
                break;
        }
    }

    if (isset($attribs['sensor_addition']) && is_numeric($attribs['sensor_addition'])) {
        $sensor_value += $attribs['sensor_addition'];
    }

    return $sensor_value;
}

/**
 * Custom value unit conversion functions for some vendors,
 * who do not know how use snmp float conversions,
 * do not know physics, mathematics and in general badly studied at school
 */
function value_unit_ieee32float($value)
{
    return ieeeint2float($value);
}

// DEPRECATED. Same as value_unit_ieee32float()
function value_unit_accuenergy($value)
{
    return hex2float(dechex($value));
}

// See: https://jira.observium.org/browse/OBS-2941
// Oids "pm10010mpMesrlineNetRxInputPwrPortn" and "pm10010mpMesrlineNetTxLaserOutputPwrPortn" in EKINOPS-Pm10010mp-MIB
// If AV<32768:  Tx_Pwr(dBm) = AV/100
// If AV>=32768: Tx_Pwr(dBm) = (AV-65536)/100
function value_unit_ekinops_dbm1($value)
{
    if ($value >= 32768 && $value <= 65536) {
        return ($value - 65536) / 100;
    }
    if ($value > 65536 || $value < 0) {
        return FALSE;
    }

    return $value / 100;
}

// See: https://jira.observium.org/browse/OBS-2941
// oids "pm10010mpMesrclientNetTxPwrPortn" and "pm10010mpMesrclientNetRxPwrPortn" in EKINOPS-Pm10010mp-MIB
// Power = 10*log(AV)-40) (Unit = dBm)
function value_unit_ekinops_dbm2($value)
{
    //return 10 * log10($value) + 30; // this is how watts converted to dbm
    return 10 * log10($value) - 40; // BUT this how convert it EKINOPS.... WHY??????
}

// Just quadruple-fucking rage.. to Nokia TimOS
function value_unit_tmnx_rx_power($rx, $rx0 = 0, $rx1 = 0, $rx2 = 0, $rx3 = 0, $rx4 = 0)
{
    return ieeeint2float($rx0) + ieeeint2float($rx1) * $rx**1 + ieeeint2float($rx2) * $rx**2 + ieeeint2float($rx3) * $rx**3 + ieeeint2float($rx4) * $rx**4;
}

// EOF
