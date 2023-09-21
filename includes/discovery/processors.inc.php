<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Include all discovery modules by supported MIB

$include_dir = "includes/discovery/processors";
include("includes/include-dir-mib.inc.php");

// Detect processors by simple MIB-based discovery :
// FIXME - this should also be extended to understand multiple entries in a table, and take descr from an OID but this is all I need right now :)
foreach (get_device_mibs_permitted($device) as $mib) {
    if (!is_array($config['mibs'][$mib]['processor'])) {
        continue;
    }

    print_cli_data_field("$mib");

    foreach ($config['mibs'][$mib]['processor'] as $entry_name => $entry) {
        $entry['found'] = FALSE;

        // Check duplicate processors by $valid['processor'] array
        if (discovery_check_if_type_exist($entry, 'processor')) {
            continue 2;
        }

        // Precision (scale)
        $precision = 1;
        if (isset($entry['scale']) && is_numeric($entry['scale']) && $entry['scale'] != 1) {
            // FIXME, currently we support only int precision, need convert all to float scale!
            $precision = round(1 / $entry['scale'], 0);
        }

        // Units, see: LANCOM-GS2310PPLUS-MIB
        $unit = $entry['unit'] ?? NULL;

        if ($entry['type'] === 'table') {

            // Use the type as the table if no table is set.
            if (!isset($entry['table'])) {
                $entry['table'] = $entry_name;
            }

            // Fetch table or Oids
            $table_oids       = [
              'oid', 'oid_descr', 'oid_scale', 'oid_precision', 'oid_count',
              //'oid_limit_low', 'oid_limit_low_warn', 'oid_limit_high_warn', 'oid_limit_high', 'oid_limit_warn',
              //'oid_limit_nominal', 'oid_limit_delta_warn', 'oid_limit_delta', 'oid_limit_scale',
              'oid_extra', //'oid_unit', 'oid_entPhysicalIndex'
            ];
            $processors_array = discover_fetch_oids($device, $mib, $entry, $table_oids);
            // $processors_array = snmpwalk_cache_oid($device, $entry['table'], [], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
            // if ($entry['table_descr']) {
            //   // If descr in separate table with same indexes
            //   $processors_array = snmpwalk_cache_oid($device, $entry['table_descr'], $processors_array, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
            // }
            if (empty($entry['oid_num'])) {
                // Use snmptranslate if oid_num not set
                $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
            }

            $i                = 1; // Used in descr as $i++
            $processors_count = count($processors_array);
            foreach ($processors_array as $index => $processor) {
                $dot_index = '.' . $index;
                $oid_num   = $entry['oid_num'] . $dot_index;

                // Rewrite specific keys
                $replace_array = [
                  'index' => $index,    // Index in descr
                  'i'     => $i,        // i++ counter in descr
                ];

                $descr = entity_descr_definition('processor', $entry, array_merge($replace_array, $processor), $processors_count);

                if (isset($entry['oid_count'])) {
                    // See F5-PLATFORM-STATS-MIB
                    if (str_contains($entry['oid_count'], '.')) {
                        $oid = array_tag_replace($replace_array, $entry['oid_count']);
                        // Get processors count if exist for MIB
                        $processor_count = snmp_get_oid($device, $oid, $mib);
                    } else {
                        $processor_count = $processor[$entry['oid_count']];
                    }
                    if (is_numeric($processor_count) && $processor_count > 1) {
                        $descr .= ' x' . $processor_count;
                    }
                }

                $idle = (isset($entry['idle']) && $entry['idle'] ? 1 : 0);

                $usage = snmp_fix_numeric($processor[$entry['oid']], $unit);
                if (is_numeric($usage)) {
                    if (isset($entry['rename_rrd'])) {
                        $old_rrd = 'processor-' . $entry['rename_rrd'] . '-' . $index;
                        $new_rrd = 'processor-' . $entry_name . '-' . $entry['table'] . $dot_index;
                        rename_rrd($device, $old_rrd, $new_rrd);
                        unset($old_rrd, $new_rrd);
                    }
                    discover_processor($valid['processor'], $device, $oid_num, $entry['oid'] . $dot_index, $entry_name, $descr, $precision, $usage, NULL, NULL, $idle);
                    $entry['found'] = TRUE;
                }
                $i++;
            }
        } else {
            // Static processor
            $index = 0; // FIXME. Need use same indexes style as in sensors

            // Fetch description from oid if specified
            $replace_array = ['index' => $index];
            if (isset($entry['oid_descr'])) {
                $replace_array[$entry['oid_descr']] = snmp_get_oid($device, $entry['oid_descr'], $mib);
            }
            $descr = entity_descr_definition('processor', $entry, $replace_array);

            if (isset($entry['oid_count']) && $entry['oid_count']) {
                $oid = array_tag_replace($replace_array, $entry['oid_count']);
                // Get processors count if exist for MIB
                $processor_count = snmp_get_oid($device, $oid, $mib);
                if ($processor_count > 1) {
                    $descr .= ' x' . $processor_count;
                }
            }

            if (empty($entry['oid_num'])) {
                // Use snmptranslate if oid_num not set
                $entry['oid_num'] = snmp_translate($entry['oid'], $mib);
            }

            // Idle
            $idle = (isset($entry['idle']) && $entry['idle'] ? 1 : 0);

            $usage = snmp_fix_numeric(snmp_get_oid($device, $entry['oid'], $mib), $unit);

            // If we have valid usage, discover the processor
            if (is_numeric($usage) && $usage != '4294967295') {
                // Rename RRD if requested
                if (isset($entry['rename_rrd'])) {
                    $old_rrd = 'processor-' . $entry['rename_rrd'];
                    $new_rrd = 'processor-' . $entry_name . '-' . $index;
                    rename_rrd($device, $old_rrd, $new_rrd);
                    unset($old_rrd, $new_rrd);
                }
                discover_processor($valid['processor'], $device, $entry['oid_num'], $index, $entry_name, $descr, $precision, $usage, NULL, NULL, $idle);
                $entry['found'] = TRUE;
            }
        }
        unset($processors_array, $processor, $dot_index, $descr, $i); // Clean up
        if (isset($entry['stop_if_found']) && $entry['stop_if_found'] && $entry['found']) {
            break;
        } // Stop loop if processor found
    }
    print_cli(PHP_EOL); // Close MIB entry line.
}

// Remove processors which weren't redetected here
foreach (dbFetchRows('SELECT * FROM `processors` WHERE `device_id` = ?', [$device['device_id']]) as $test_processor) {
    $processor_index = $test_processor['processor_index'];
    $processor_type  = $test_processor['processor_type'];
    $processor_descr = $test_processor['processor_descr'];
    print_debug($processor_index . " -> " . $processor_type);

    if (!$valid['processor'][$processor_type][$processor_index]) {
        $GLOBALS['module_stats'][$module]['deleted']++; //echo('-');
        dbDelete('processors', '`processor_id` = ?', [$test_processor['processor_id']]);
        log_event("Processor removed: type " . $processor_type . " index " . $processor_index . " descr " . $processor_descr, $device, 'processor', $test_processor['processor_id']);
    }
    unset($processor_oid);
    unset($processor_type);
}

$GLOBALS['module_stats'][$module]['status'] = safe_count($valid['processor']);
if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status']) {
    print_vars($valid['processor']);
}

// EOF
