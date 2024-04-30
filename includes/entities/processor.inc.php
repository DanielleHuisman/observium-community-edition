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

function discover_processor_definition($device, $mib, $entry) {

    // Just append mib name to definition entry, for simple pass to external functions
    if (empty($entry['mib'])) {
        $entry['mib'] = $mib;
    }

    // Check that types listed in skip_if_valid_exist have already been found
    if (discovery_check_if_type_exist($entry, 'processor')) {
        echo '!]';
        return FALSE;
    }

    // Check array requirements list
    if (discovery_check_requires_pre($device, $entry, 'processor')) {
        echo '!]';
        return FALSE;
    }

    // Units, see: LANCOM-GS2310PPLUS-MIB
    $unit = $entry['unit'] ?? NULL;

    // CLEANME, Compat, remove when converted
    if ($entry['type'] === 'table' && !isset($entry['table'])) {
        $entry['table'] = $entry['object'];
    }
    // elseif (!isset($entry['rename_rrd']) && OBSERVIUM_REV < 13500) {
    //     // Add rename by default
    //     $entry['rename_rrd'] = $entry['object'] . '-%index%';
    // }

    // Fetch table or Oids
    $table_oids = [
        'oid', 'oid_descr', 'oid_scale', 'oid_precision', 'oid_count',
        //'oid_limit_low', 'oid_limit_low_warn', 'oid_limit_high_warn', 'oid_limit_high', 'oid_limit_warn',
        //'oid_limit_nominal', 'oid_limit_delta_warn', 'oid_limit_delta', 'oid_limit_scale',
        'oid_extra', //'oid_unit', 'oid_entPhysicalIndex'
    ];
    $processors_array = discover_fetch_oids($device, $mib, $entry, $table_oids);

    if (empty($entry['oid_num'])) {
        // Use snmptranslate if oid_num not set
        $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
    }

    $found = FALSE;
    $i = 1; // Used in descr as $i++
    $processors_count = count($processors_array);
    foreach ($processors_array as $index => $processor) {
        $dot_index = '.' . $index;
        $oid_num   = $entry['oid_num'] . $dot_index;

        // Check valid exist with entity tags
        if (discovery_check_if_type_exist($entry, 'processor', $processor)) {
            continue;
        }

        // Rewrite specific keys
        $index_tags = entity_index_tags($index, $i);
        $options = [ 'i' => $i, 'count' => $processors_count, 'oid' => $entry['oid'] ];
        if ($processors_count === 1 && isset($entry['indexes'])) {
            // Compat with old static definitions
            $options['indexes'] = count($entry['indexes']);
        }

        $descr = entity_descr_definition('processor', $entry, array_merge($index_tags, $processor), $processors_count);

        if (isset($entry['oid_count'])) {
            // See F5-PLATFORM-STATS-MIB
            if (str_contains($entry['oid_count'], '.')) {
                $oid = array_tag_replace($index_tags, $entry['oid_count']);
                // Get processors count if exist for MIB
                $processor_count = snmp_get_oid($device, $oid, $mib);
            } else {
                $processor_count = $processor[$entry['oid_count']];
            }
            if (is_numeric($processor_count) && $processor_count > 1) {
                $descr .= ' x' . $processor_count;
            }
            $options['count'] = $processor_count;
        }

        // Options

        if (isset($entry['idle'])) {
            $options['idle'] = $entry['idle'] ? 1 : 0;
        }
        // FIXME. entPhysicalIndex and hrDeviceIndex

        // Rename old (converted) RRDs to definition format
        if (isset($entry['rename_rrd'])) {
            $options['rename_rrd'] = $entry['rename_rrd'];
        }

        // Precision (scale)
        // FIXME, currently we support only int precision, need convert all to float scale!
        $scale     = entity_scale_definition($device, $entry, $processor, 'processor');
        $precision = $scale !== 1 ? round(float_div(1, $scale)) : 1;

        $usage = snmp_fix_numeric($processor[$entry['oid']], $unit);
        if (discovery_check_value_valid($device, $usage, $entry, 'processor')) {
            discover_processor_ng($device, $mib, $entry['object'], $oid_num, $index, NULL, $descr, $precision, $usage, $options);
            $found = TRUE;
        }
        $i++;
    }

    return $found;
}

// Compatibility wrapper!
function discover_processor(&$valid, $device, $processor_oid, $processor_index, $processor_type, $processor_descr, $processor_precision = 1, $value = NULL, $entPhysicalIndex = NULL, $hrDeviceIndex = NULL, $processor_returns_idle = 0) {

    $options = [ 'idle' => $processor_returns_idle ? 1 : 0 ];
    if (!safe_empty($entPhysicalIndex)) {
        $options['entPhysicalIndex'] = $entPhysicalIndex;
    }
    if (!safe_empty($hrDeviceIndex)) {
        $options['hrDeviceIndex'] = $hrDeviceIndex;
    }

    return discover_processor_ng($device, '', '', $processor_oid, $processor_index, $processor_type, $processor_descr, $processor_precision, $value, $options);
}

function discover_processor_ng($device, $processor_mib, $processor_object, $processor_oid, $processor_index,
                               $processor_type, $processor_descr, $processor_precision = 1, $value = NULL, $options = []) {

    print_debug($device['device_id'] . " -> $processor_oid, $processor_index, $processor_type, $processor_descr, $processor_precision, $value");

    // Check processor ignore filters
    if (entity_descr_check($processor_descr, 'processor')) {
        return FALSE;
    }

    // Skip discovery processor if value not numeric or null(default)
    if ($value !== NULL) {
        $value = snmp_fix_numeric($value);
    }

    if (!(is_numeric($value) || $value === NULL)) {
        print_debug("Skipped by not numeric value: $value, $processor_descr ");
        return FALSE;
    }

    // Old: processor_type
    // New: mib-object
    if ($discovery_ng = empty($processor_type)) {
        if (!empty($processor_object)) {
            $processor_type = $processor_object;
        }
        if (!empty($processor_mib)) {
            $processor_type = $processor_mib . '-' . $processor_type;
        }
    }
    $processor_returns_idle = isset($options['idle']) && $options['idle'];

    // Main params
    $params = [ 'processor_index', 'processor_mib', 'processor_object', 'processor_oid', 'processor_type', 'processor_descr', 'processor_precision' ];
    $params_opt = [ 'entPhysicalIndex' => 'entPhysicalIndex', 'hrDeviceIndex' => 'hrDeviceIndex', 'idle' => 'processor_returns_idle' ];

    $processor_db = dbFetchRow("SELECT * FROM `processors` WHERE `device_id` = ? AND `processor_index` = ? AND `processor_type` = ?", [ $device['device_id'], $processor_index, $processor_type ]);

    // Compat with old discovery (update instead delete/add)
    if ($discovery_ng && !$processor_db) {
        if (isset($options['indexes'])) {
            // Old static converted to indexes
            $processor_db = dbFetchRow("SELECT * FROM `processors` WHERE `device_id` = ? AND `processor_index` = ? AND `processor_oid` = ? AND `processor_mib` IS NULL",
                                       [ $device['device_id'], $processor_index, $processor_oid ]);
        } else {
            $old_index1 = $processor_object . '.' . $processor_index;
            $old_index2 = $options['oid'] . '.' . $processor_index;
            $processor_db = dbFetchRow("SELECT * FROM `processors` WHERE `device_id` = ? AND `processor_index` IN (?, ?, ?) AND `processor_type` = ? AND `processor_mib` IS NULL",
                                       [ $device['device_id'], $processor_index, $old_index1, $old_index2, $processor_object ]);
        }
        if (!isset($options['rename_rrd']) && isset($processor_db['processor_index'])) {
            // Derp old table indexes..
            if ($processor_db['processor_index'] === $old_index1) {
                $options['rename_rrd'] = $processor_object . '-' . $old_index1;
            } elseif ($processor_db['processor_index'] === $old_index2) {
                $options['rename_rrd'] = $processor_object . '-' . $old_index2;
            }
        }
    }

    if (!isset($processor_db['processor_id'])) {
        $insert = [ 'device_id' => $device['device_id'] ];
        if (!$processor_precision) {
            $processor_precision = 1;
        }
        foreach ($params as $param) {
            $insert[$param] = $$param ?? [ 'NULL' ];
        }
        foreach ($params_opt as $opt => $param) {
            if (isset($options[$opt])) {
                $insert[$param] = $options[$opt];
            }
        }

        if ($processor_precision != 1) {
            $value = round(float_div($value, $processor_precision), 2);
        }
        // The OID returns idle value, so we subtract it from 100.
        if ($processor_returns_idle) {
            $value = 100 - $value;
        }

        $insert['processor_usage'] = $value;
        $id = dbInsert($insert, 'processors');

        $GLOBALS['module_stats']['processors']['added']++;
        log_event("Processor added: index $processor_index, type $processor_type, descr $processor_descr", $device, 'processor', $id);
    } else {
        $update = [];
        foreach ($params as $param) {
            if ($$param != $processor_db[$param]) {
                $update[$param] = $$param ?? [ 'NULL' ];
            }
        }
        foreach ($params_opt as $opt => $param) {
            if (isset($options[$opt]) && $options[$opt] != $processor_db[$param]) {
                print_debug_vars($options);
                print_debug_vars($processor_db);
                $update[$param] = $options[$opt];
            }
        }

        // Skip WMI processor description update, this is done in poller
        if (isset($update['processor_descr']) && $processor_type === 'hr' &&
            ($update['processor_descr'] === 'Unknown Processor Type' || $update['processor_descr'] === 'Intel') &&
            is_module_enabled($device, 'wmi', 'poller')) {
            unset($update['processor_descr']);
        }

        if (count($update)) {
            dbUpdate($update, 'processors', '`processor_id` = ?', [ $processor_db['processor_id'] ]);
            $GLOBALS['module_stats']['processors']['updated']++;
            log_event("Processor updated: index $processor_index, type $processor_type, descr $processor_descr", $device, 'processor', $processor_db['processor_id']);
        } else {
            $GLOBALS['module_stats']['processors']['unchanged']++;
        }
        $id = $processor_db['processor_id'];
    }

    // Rename old (converted) RRDs to definition format
    if (isset($options['rename_rrd'])) {
        $rrd_tags = [
            'index'  => $processor_index,
            'type'   => $processor_type,
            'mib'    => $processor_mib,
            'object' => $processor_object,
            'oid'    => $processor_object,
            'count'  => $options['count'],
            'i'      => $options['i'],
            // for rrd
            'processor_index'  => $processor_index,
            'processor_type'   => $processor_type,
            'processor_mib'    => $processor_mib,
            'processor_object' => $processor_object,
        ];

        $options['rename_rrd'] = array_tag_replace($rrd_tags, $options['rename_rrd']);

        $old_rrd = 'processor-' . $options['rename_rrd'];
        $new_rrd = get_processor_rrd($device, $rrd_tags);
        rename_rrd($device, $old_rrd, $new_rrd);
    }

    $GLOBALS['valid']['processor'][$processor_type][$processor_index] = 1;

    return $id;
}

function get_processor_rrd($device, $processor, $full = TRUE) {
    $index = $processor['processor_index'];

    if (!empty($processor['processor_mib']) && !empty($processor['processor_object'])) {
        // for discover_processor_ng(), note here is just status index
        if ($processor['processor_type'] === $processor['processor_mib'] . '-' . $processor['processor_object']) {
            // FIXME. Use old style of rrd naming, because rename rrd impossible for remote rrd
            $rrd_file = $processor['processor_object'] . "-" . $index;
        } else {
            $rrd_file = $processor['processor_mib'] . "-" . $processor['processor_object'] . "-" . $index;
        }
    } else {
        // for discover_processor(), note index == "%object%.%index%"
        $rrd_file = $processor['processor_type'] . "-" . $index;
    }

    if ($full) {
        // Prepend processor
        return 'processor-' . $rrd_file . '.rrd';
    }
    return $rrd_file;
}

// EOF
