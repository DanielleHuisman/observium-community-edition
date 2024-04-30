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

function discover_storage_definition($device, $mib, $entry, $object)
{
    $entry['found'] = FALSE;

    $entry['object'] = $object;
    echo($object . ' [');

    // Just append mib name to definition entry, for simple pass to external functions
    if (empty($entry['mib'])) {
        $entry['mib'] = $mib;
    }

    // Check that types listed in skip_if_valid_exist have already been found
    if (discovery_check_if_type_exist($entry, 'storage')) {
        echo '!]';
        return;
    }

    // Check array requirements list
    if (discovery_check_requires_pre($device, $entry, 'storage')) {
        echo '!]';
        return;
    }

    // oid_*_hc and oid_*_high/oid_*_low used with storage_hc flag
    $table_oids    = ['oid_total', 'oid_total_hc', 'oid_total_high', 'oid_total_low',
                      'oid_used', 'oid_used_hc', 'oid_used_high', 'oid_used_low',
                      'oid_free', 'oid_free_hc', 'oid_free_high', 'oid_free_low',
                      'oid_perc', 'oid_descr', 'oid_scale', 'oid_unit',
                      'oid_type', 'oid_online', 'oid_extra',
                      //'oid_limit_low', 'oid_limit_low_warn', 'oid_limit_high_warn', 'oid_limit_high',
                      //'oid_limit_nominal', 'oid_limit_delta_warn', 'oid_limit_delta', 'oid_limit_scale'
    ];
    $storage_array = discover_fetch_oids($device, $mib, $entry, $table_oids);

    // FIXME - generify description generation code and just pass it template and OID array.

    $i             = 1; // Used in descr as %i%
    $storage_count = count($storage_array);
    foreach ($storage_array as $index => $storage_entry) {
        $options = [];
        //$oid_num = $entry['oid_num'] . '.' . $index;

        // Storage Type
        if (isset($entry['oid_type']) && $storage_entry[$entry['oid_type']]) {
            $storage_entry['type'] = $storage_entry[$entry['oid_type']];
        } elseif (isset($entry['type'])) {
            $storage_entry['type'] = $entry['type'];
        } else {
            // Compat (incorrect)
            $storage_entry['type'] = $object;
        }
        $options['storage_type'] = $storage_entry['type'];

        // Generate storage description
        $storage_entry = array_merge($storage_entry, entity_index_tags($index, $i));
        $descr = entity_descr_definition('storage', $entry, $storage_entry, $storage_count);

        // Check valid exist with entity tags
        if (discovery_check_if_type_exist($entry, 'storage', $storage_entry)) {
            continue;
        }

        // Check array requirements list
        if (discovery_check_requires($device, $entry, $storage_entry, 'storage')) {
            continue;
        }

        // Init
        $used  = NULL;
        $total = NULL;
        $free  = NULL;
        $perc  = NULL;
        $hc    = isset($entry['hc']) && $entry['hc'];

        // Convert strings '3.40 TB' to value
        // See QNAP NAS-MIB or HIK-DEVICE-MIB
        $unit = $entry['unit'] ?? NULL;

        // Fetch used, total, free and percentage values, if OIDs are defined for them

        if (!safe_empty($entry['total'])) {
            // Prefer hardcoded total over SNMP OIDs
            $total = $entry['total'];
        } else {
            if (isset($entry['oid_total_high'], $entry['oid_total_low'])) {
                $high = snmp_fix_numeric($storage_entry[$entry['oid_total_high']]);
                $low  = snmp_fix_numeric($storage_entry[$entry['oid_total_low']]);
                if ($total = snmp_size64_high_low($high, $low)) {
                    $hc = TRUE; // set HC flag
                }
            }
            if (!is_numeric($total) && isset($entry['oid_total_hc']) &&
                $total = snmp_fix_numeric($storage_entry[$entry['oid_total_hc']], $unit)) {
                $hc = TRUE; // set HC flag
            }
            if (!is_numeric($total) && isset($entry['oid_total'])) {
                $total = snmp_fix_numeric($storage_entry[$entry['oid_total']], $unit);
            }
        }

        if (isset($entry['oid_used_high'], $entry['oid_used_low'])) {
            $high = snmp_fix_numeric($storage_entry[$entry['oid_used_high']]);
            $low  = snmp_fix_numeric($storage_entry[$entry['oid_used_low']]);
            $used = snmp_size64_high_low($high, $low);
            if (is_numeric($used)) {
                $hc = TRUE; // set HC flag
            }
        }
        if (!is_numeric($used) && isset($entry['oid_used_hc'])) {
            $used = snmp_fix_numeric($storage_entry[$entry['oid_used_hc']], $unit);
            if (is_numeric($used)) {
                $hc = TRUE; // set HC flag
            }
        }
        if (!is_numeric($used) && isset($entry['oid_used'])) {
            $used = snmp_fix_numeric($storage_entry[$entry['oid_used']], $unit);
        }

        if (isset($entry['oid_free_high'], $entry['oid_free_low'])) {
            $high = snmp_fix_numeric($storage_entry[$entry['oid_free_high']]);
            $low  = snmp_fix_numeric($storage_entry[$entry['oid_free_low']]);
            $free = snmp_size64_high_low($high, $low);
            if (is_numeric($free)) {
                $hc = TRUE; // set HC flag
            }
        }
        if (!is_numeric($free) && isset($entry['oid_free_hc'])) {
            $free = snmp_fix_numeric($storage_entry[$entry['oid_free_hc']], $unit);
            if (is_numeric($free)) {
                $hc = TRUE; // set HC flag
            }
        }
        if (!is_numeric($free) && isset($entry['oid_free'])) {
            $free = snmp_fix_numeric($storage_entry[$entry['oid_free']], $unit);
        }

        if (isset($entry['oid_perc'])) {
            $perc = snmp_fix_numeric($storage_entry[$entry['oid_perc']]);
        }

        // Scale
        $scale = entity_scale_definition($device, $entry, $storage_entry);

        // HC
        if ($hc) {
            $options['storage_hc'] = 1;
        }

        // Oper status / Ignore (see NIMBLE-MIB)
        if (isset($entry['oid_online'], $storage_entry[$entry['oid_online']])) {
            $options['storage_ignore'] = get_var_false($storage_entry[$entry['oid_online']]);
        }

        // Extrapolate all values from the ones we have.
        $storage = calculate_mempool_properties($scale, $used, $total, $free, $perc, $entry);

        print_debug_vars([$scale, $used, $total, $free, $perc, $options]);
        print_debug_vars($storage_entry);
        print_debug_vars($storage);
        //print_debug_vars([ is_numeric($storage['used']), is_numeric($storage['total']) ]);

        // If we have valid used and total, discover the storage
        $entry['found'] = discover_storage_ng($device, $mib, $object, $index, $descr, $scale, $storage, $options);
        $i++;
    }

    echo '] ';
}


function discover_storage_ng($device, $storage_mib, $storage_object, $storage_index, $storage_descr, $storage_units, $storage, $options = [])
{
    global $valid;

    // options && limits
    $option  = 'storage_hc';
    $$option = (isset($options[$option]) && $options[$option]) ? 1 : 0;
    $option  = 'storage_ignore';
    $$option = (isset($options[$option]) && $options[$option]) ? 1 : 0;

    if (isset($options['limit_high'])) {
        $storage_crit_limit = $options['limit_high'];
    }
    if (isset($options['limit_high_warn'])) {
        $storage_warn_limit = $options['limit_high_warn'];
    }

    // FIXME. Ignore 0 storage size?
    $storage_size = $storage['total'];
    $storage_used = $storage['used'];
    $storage_free = $storage['free'];
    $storage_perc = $storage['perc'];
    $storage_type = $options['storage_type'] ?? $storage_object;

    print_debug($device['device_id'] . " -> $storage_index, $storage_object, $storage_mib, $storage_descr, $storage_units, $storage_size, $storage_used, $storage_hc");

    if (!is_numeric($storage['total']) || !is_numeric($storage['used'])) {
        print_debug("Skipped by not numeric storage values.");
        return FALSE;
    }
    if (isset($storage['valid']) && !$storage['valid']) {
        print_debug("Skipped by empty storage Size [$storage_size] or invalid Percent [$storage_perc] values.");
        return FALSE;
    }

    // Check storage ignore filters
    if (entity_descr_check($storage_descr, 'storage')) {
        return FALSE;
    }
    // Search duplicates for same mib/descr
    if (in_array($storage_descr, array_values((array)$valid['storage'][$storage_mib]))) {
        print_debug("Skipped by already exist: $storage_descr ");
        return FALSE;
    }

    $params = ['storage_index', 'storage_mib', 'storage_object', 'storage_type', 'storage_descr',
               'storage_hc', 'storage_ignore', 'storage_units', 'storage_crit_limit', 'storage_warn_limit'];
    // This is changeable params, not required for update
    $params_state = ['storage_size', 'storage_used', 'storage_free', 'storage_perc'];

    $device_id = $device['device_id'];

    $storage_db = dbFetchRow("SELECT * FROM `storage` WHERE `device_id` = ? AND `storage_index` = ? AND `storage_mib` = ?", [$device_id, $storage_index, $storage_mib]);
    if (!isset($storage_db['storage_id'])) {

        $update = ['device_id' => $device_id];
        foreach (array_merge($params, $params_state) as $param) {
            $update[$param] = $$param ?? [ 'NULL' ];
        }
        $id = dbInsert($update, 'storage');

        $GLOBALS['module_stats']['storage']['added']++;
        log_event("Storage added: index $storage_index, mib $storage_mib, descr $storage_descr", $device, 'storage', $id);
    } else {
        $update = [];
        foreach ($params as $param) {
            if ($$param != $storage_db[$param]) {
                $update[$param] = $$param ?? [ 'NULL' ];
            }
        }
        if (count($update)) {
            dbUpdate($update, 'storage', '`storage_id` = ?', [$storage_db['storage_id']]);
            $GLOBALS['module_stats']['storage']['updated']++;
            log_event("Storage updated: index $storage_index, mib $storage_mib, descr $storage_descr", $device, 'storage', $storage_db['storage_id']);
        } else {
            $GLOBALS['module_stats']['storage']['unchanged']++;
        }
    }
    print_debug_vars($update);
    if ($storage_ignore) {
        $GLOBALS['module_stats']['storage']['ignored']++;
    }

    $valid['storage'][$storage_mib][$storage_index] = $storage_descr;

    return TRUE;
}

function poll_storage_definition($device, $entry, &$storage, $cache_storage)
{

    // Fetch used, total, free and percentage values, if OIDs are defined for them
    if (!safe_empty($entry['total'])) {
        // Prefer hardcoded total over SNMP OIDs
        $total = $entry['total'];
    } else {
        $total = get_storage_value($device, 'total', $entry, $storage, $cache_storage);
    }

    $used = get_storage_value($device, 'used', $entry, $storage, $cache_storage);
    $free = get_storage_value($device, 'free', $entry, $storage, $cache_storage);
    $perc = get_storage_value($device, 'perc', $entry, $storage, $cache_storage);

    if (isset($entry['oid_online'])) {
        $mib   = $storage['storage_mib'];
        $index = $storage['storage_index'];
        if (isset($cache_storage[$mib][$index][$entry['oid_online']])) {
            $value = $cache_storage[$mib][$index][$entry['oid_online']];
        } else {
            $value = snmp_get_oid($device, $entry['oid_online'] . '.' . $index, $mib);
        }

        // FIXME, probably need additional field for storages like OperStatus up/down
        $ignore = get_var_false($value) ? 1 : 0;
        if ($storage['storage_ignore'] != $ignore) {
            force_discovery($device, 'storage');
        }
    }

    // Merge calculated used/total/free/perc array keys into $storage variable (with additional options)
    $storage         = array_merge($storage, calculate_mempool_properties($storage['storage_units'], $used, $total, $free, $perc, $entry));
    $storage['size'] = $storage['total'];

}

function get_storage_value($device, $param, $entry, $storage, $cache_storage = [])
{
    $mib   = $storage['storage_mib'];
    $index = $storage['storage_index'];
    $hc    = $storage['storage_hc'];

    // Convert strings '3.40 TB' to value
    // See QNAP NAS-MIB or HIK-DEVICE-MIB
    $unit = ($param !== 'perc' && isset($entry['unit'])) ? $entry['unit'] : NULL;

    $value = NULL;
    if (isset($entry['oid_' . $param . '_high'], $entry['oid_' . $param . '_low'])) {
        // High+Low set of values
        if (isset($cache_storage[$mib][$index][$entry['oid_' . $param . '_high']])) {
            // Cached
            $high = $cache_storage[$mib][$index][$entry['oid_' . $param . '_high']];
            $low  = $cache_storage[$mib][$index][$entry['oid_' . $param . '_low']];
        } elseif ($hc) {
            if (isset($entry['oid_' . $param . '_high_num'])) {
                $high = snmp_get_oid($device, $entry['oid_' . $param . '_high_num'] . '.' . $index);
            } elseif (isset($entry['oid_' . $param . '_high'])) {
                $high = snmp_get_oid($device, $entry['oid_' . $param . '_high'] . '.' . $index, $mib);
            }
            if (isset($entry['oid_' . $param . '_low_num'])) {
                $low = snmp_get_oid($device, $entry['oid_' . $param . '_low_num'] . '.' . $index);
            } elseif (isset($entry['oid_' . $param . '_low'])) {
                $low = snmp_get_oid($device, $entry['oid_' . $param . '_low'] . '.' . $index, $mib);
            }
        }
        $high  = snmp_fix_numeric($high);
        $low   = snmp_fix_numeric($low);
        $value = snmp_size64_high_low($high, $low);
    }

    if ($hc && isset($entry['oid_' . $param . '_hc']) && !is_numeric($value)) {
        // Common HC value
        if (isset($cache_storage[$mib][$index][$entry['oid_' . $param . '_hc']])) {
            $value = snmp_fix_numeric($cache_storage[$mib][$index][$entry['oid_' . $param . '_hc']], $unit);
        } else {
            if (isset($entry['oid_' . $param . '_hc_num'])) {
                $value = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_' . $param . '_hc_num'] . '.' . $index), $unit);
            } elseif (isset($entry['oid_' . $param . '_hc'])) {
                $value = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_' . $param . '_hc'] . '.' . $index, $mib), $unit);
            }
        }
    }
    if (!is_numeric($value) && isset($entry['oid_' . $param])) {
        // Common value
        if (isset($cache_storage[$mib][$index][$entry['oid_' . $param]])) {
            $value = snmp_fix_numeric($cache_storage[$mib][$index][$entry['oid_' . $param]], $unit);
        } else {
            if (isset($entry['oid_' . $param . '_num'])) {
                $value = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_' . $param . '_num'] . '.' . $index), $unit);
            } elseif (isset($entry['oid_' . $param])) {
                $value = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_' . $param] . '.' . $index, $mib), $unit);
            }
        }
    }

    return $value;
}

/**
 * Poll and cache entity _NUMERIC_ Oids,
 * need for cross cache between different entities, ie status and sensors
 *
 * @param $device
 * @param $oid_cache
 *
 * @return bool
 */
function poll_cache_storage($device, &$oid_cache)
{
    global $config;

    $mib_walk_option = 'storage_walk'; // ie: $config['mibs'][$mib]['storage_walk']
    //$snmp_flags = OBS_SNMP_ALL_NUMERIC; // Numeric Oids by default


    // CLEANME. Compatibility with old (incorrect) field
    $object_field = get_db_version() > 468 ? 'storage_object' : 'storage_type';

    // Walk query
    $walk_query  = "SELECT `storage_mib`, `$object_field`, `storage_hc`, GROUP_CONCAT(`storage_index` SEPARATOR ?) AS `indexes` FROM `storage` WHERE `device_id` = ? GROUP BY `storage_mib`, `$object_field`, `storage_hc`";
    $walk_params = [',', $device['device_id']];

    $oid_to_cache = [];
    foreach (dbFetchRows($walk_query, $walk_params) as $entry) {
        if (!isset($config['mibs'][$entry['storage_mib']]['storage'][$entry[$object_field]])) {
            // Cache only definition based
            continue;
        }

        $def = $config['mibs'][$entry['storage_mib']]['storage'][$entry[$object_field]];
        $hc  = $entry['storage_hc'];

        // Explode indexes from GROUP_CONCAT()
        $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['indexes'] = explode(',', $entry['indexes']);

        // Storage need only this oids in poller
        $total = FALSE;
        $used  = FALSE;
        $free  = FALSE;
        if ($hc) {
            // HC oids
            if (isset($def['oid_total_high'], $def['oid_total_low'])) {
                $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_total_high'];
                $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_total_low'];
                $total                                                                = TRUE;
            } elseif (isset($def['oid_total_hc'])) {
                $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_total_hc'];
                $total                                                                = TRUE;
            }

            if (isset($def['oid_used_high'], $def['oid_used_low'])) {
                $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_used_high'];
                $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_used_low'];
                $used                                                                 = TRUE;
            } elseif (isset($def['oid_used_hc'])) {
                $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_used_hc'];
                $used                                                                 = TRUE;
            }

            if (isset($def['oid_free_high'], $def['oid_free_low'])) {
                $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_free_high'];
                $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_free_low'];
                $free                                                                 = TRUE;
            } elseif (isset($def['oid_free_hc'])) {
                $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_free_hc'];
                $free                                                                 = TRUE;
            }
        }
        if (!$total && isset($def['oid_total'])) {
            $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_total'];
        }
        if (!$used && isset($def['oid_used'])) {
            $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_used'];
        }
        if (!$free && isset($def['oid_free'])) {
            $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_free'];
        }
        if (isset($def['oid_perc'])) {
            $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_perc'];
        }
        if (isset($def['oid_online'])) {
            $oid_to_cache[$entry['storage_mib']][$entry[$object_field]]['oids'][] = $def['oid_online'];
        }
    }

    foreach ($oid_to_cache as $mib => $object_array) {
        foreach ($object_array as $object => $entry) {
            //$def = $config['mibs'][$mib]['storage'][$type];

            if (isset($config['mibs'][$mib][$mib_walk_option]) &&
                !$config['mibs'][$mib][$mib_walk_option]) {
                // MIB not support walk (by definition)
                $use_walk = FALSE;
            } else {
                // Walk on multiple indexes
                $use_walk = count($entry['indexes']) > 1;
            }

            if ($use_walk) {
                // SNMP walk
                if (isset($GLOBALS['cache']['snmp_object_polled'][$mib][$object])) {
                    print_debug("MIB/Type ($mib::$object) already polled.");
                    continue;
                }

                print_debug("Caching storage snmpwalk by $mib");
                foreach ($entry['oids'] as $oid) {
                    $oid_cache[$mib] = snmpwalk_multipart_oid($device, $oid, $oid_cache[$mib], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
                }
                $GLOBALS['cache']['snmp_object_polled'][$mib][$object] = 1;
            } else {
                // SNMP multiget
                print_debug("Caching storage snmpget by $mib");
                $oids = [];
                foreach ($entry['oids'] as $oid) {
                    foreach ($entry['indexes'] as $index) {
                        $oids[] = $oid . '.' . $index;
                    }
                }
                $oid_cache[$mib] = snmp_get_multi_oid($device, $oids, $oid_cache[$mib], $mib);
            }
        }
    }
    print_debug_vars($oid_to_cache);
    print_debug_vars($oid_cache);
    return !empty($oid_to_cache);
}

// EOF
