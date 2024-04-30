<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Include all discovery modules

$include_dir = "includes/discovery/mempools";
include("includes/include-dir-mib.inc.php");

// Detect mempools by simple MIB-based discovery :
// FIXME - this should also be extended to understand multiple entries in a table, and take descr from an OID but this is all I need right now :)
// FIXME. In one day I'll rewrite this ;)
foreach (get_device_mibs_permitted($device) as $mib) {
    if (is_array($config['mibs'][$mib]['mempool'])) {
        print_cli_data_field($mib);
        foreach ($config['mibs'][$mib]['mempool'] as $entry_name => $entry) {
            if (discovery_check_if_type_exist($entry, 'mempool')) {
                continue;
            }

            // Check array requirements list
            if (discovery_check_requires_pre($device, $entry, 'mempool')) {
                continue;
            }

            $entry['found'] = FALSE;

            // Init Precision (scale)
            if (isset($entry['scale']) && is_numeric($entry['scale']) && $entry['scale']) {
                $scale = $entry['scale'];
            } else {
                $scale = 1;
            }

            // Convert strings '3.40 TB' to value
            // See QNAP NAS-MIB or HIK-DEVICE-MIB
            $unit = $entry['unit'] ?? NULL;

            if ($entry['type'] === 'table' || !isset($entry['type'])) {

                /////////////////////
                // Table Discovery //
                /////////////////////

                // If the type is table, walk the table!
                if (!isset($entry['table'])) {
                    $entry['table'] = $entry_name;
                }
                // FIXME - cache this outside the mempools array and then just array_merge it in. Descr OIDs are probably shared a lot

                // Fetch table or Oids
                $table_oids     = ['oid_used', 'oid_total', 'oid_free', 'oid_perc', 'oid_descr', 'extra_oids'];
                $mempools_array = discover_fetch_oids($device, $mib, $entry, $table_oids);

                // FIXME - generify description generation code and just pass it template and OID array.

                $i              = 1; // Used in descr as %i%
                $mempools_count = safe_count($mempools_array);
                foreach ($mempools_array as $index => $mempool_entry) {
                    $oid_num = $entry['oid_num'] . '.' . $index;

                    // Generate mempool description
                    $mempool_entry = array_merge($mempool_entry, entity_index_tags($index, $i));
                    $descr = entity_descr_definition('mempool', $entry, $mempool_entry, $mempools_count);

                    // Check array requirements list
                    if (discovery_check_requires($device, $entry, $mempool_entry, 'mempool')) {
                        continue;
                    }

                    // Init perc/total/used/free
                    $used    = NULL;
                    $total   = NULL;
                    $free    = NULL;
                    $perc    = NULL;
                    $options = [];

                    // Hardcoded total
                    if (!safe_empty($entry['total'])) {
                        $total = $entry['total'];
                    }

                    // Fetch used, total, free and percentage values, if OIDs are defined for them
                    foreach (['oid_used', 'oid_total', 'oid_free'] as $oid) {
                        if (!isset($entry[$oid])) {
                            continue;
                        }

                        $param = str_replace('oid_', '', $oid);
                        if (str_contains($entry[$oid], '.')) {
                            $mempool_entry[$entry[$oid]] = snmp_cache_oid($device, $entry[$oid], $mib);
                        }
                        $$param = snmp_fix_numeric($mempool_entry[$entry[$oid]], $unit);
                    }
                    //if ($entry['oid_used'] != '')      { $used = snmp_fix_numeric($mempool_entry[$entry['oid_used']], $unit); }
                    //if ($entry['oid_free'] != '')      { $free = snmp_fix_numeric($mempool_entry[$entry['oid_free']], $unit); }
                    if (!safe_empty($entry['oid_perc'])) {
                        $perc = snmp_fix_numeric($mempool_entry[$entry['oid_perc']]);
                    }

                    // Prefer hardcoded total over SNMP OIDs
                    //if     ($entry['total'] != '')     { $total = $entry['total']; }
                    //elseif ($entry['oid_total'] != '') { $total = snmp_fix_numeric($mempool_entry[$entry['oid_total']], $unit); }

                    // Extrapolate all values from the ones we have.
                    $mempool = calculate_mempool_properties($scale, $used, $total, $free, $perc, $options);

                    print_debug_vars([$scale, $used, $total, $free, $perc, $options]);
                    print_debug_vars($mempool_entry);
                    print_debug_vars($mempool);

                    print_debug_vars([is_numeric($mempool['used']), is_numeric($mempool['total'])]);

                    // If we have valid used and total, discover the mempool
                    if (is_numeric($mempool['used']) && is_numeric($mempool['total'])) {
                        //print_r(array($valid['mempool'], $device, $index, $mib, $descr, $scale, $mempool['total'], $mempool['used'], $index, array('table' => $entry_name)));

                        $mempool_hc = 0; //  // FIXME mempool_hc = ?? currently keep as 0
                        discover_mempool($valid['mempool'], $device, $index, $mib, $descr, $scale, $mempool['total'], $mempool['used'], $mempool_hc, ['table' => $entry_name]);
                        $entry['found'] = TRUE;
                    }
                    $i++;
                }

            } else {

                ////////////////////
                // Static mempool //
                ////////////////////

                // Init perc/total/used/free
                $used  = NULL;
                $total = NULL;
                $free  = NULL;
                $perc  = NULL;

                $index         = 0; // FIXME. Need use same indexes style as in sensors
                $mempool_entry = ['index' => $index];

                if (isset($entry['oid_descr']) && $entry['oid_descr']) {
                    // Get description from specified OID
                    $mempool_entry[$entry['oid_descr']] = snmp_get_oid($device, $entry['oid_descr'], $mib);
                }
                $descr = entity_descr_definition('mempool', $entry, $mempool_entry);

                // Hardcoded total
                if (!safe_empty($entry['total'])) {
                    $total = $entry['total'];
                }

                // Fetch used, total, free and percentage values, if OIDs are defined for them
                foreach (['oid_used', 'oid_total', 'oid_free'] as $oid) {
                    if (!isset($entry[$oid])) {
                        continue;
                    }

                    $param  = str_replace('oid_', '', $oid);
                    $$param = snmp_fix_numeric(snmp_get_oid($device, $entry[$oid], $mib), $unit);
                }

                // Fetch used, total, free and percentage values, if OIDs are defined for them
                // if ($entry['oid_used'] != '') {
                //    $used = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_used'], $mib), $unit);
                // }

                // Prefer hardcoded total over SNMP OIDs
                // if ($entry['total'] != '') {
                //    $total = $entry['total'];
                // } else {
                //    // No hardcoded total, fetch OID if defined
                //    if ($entry['oid_total'] != '') {
                //       $total = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_total'], $mib), $unit);
                //    }
                // }

                // if ($entry['oid_free'] != '') {
                //    $free = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_free'], $mib), $unit);
                // }

                if (!safe_empty($entry['oid_perc'])) {
                    $perc = snmp_fix_numeric(snmp_get_oid($device, $entry['oid_perc'], $mib));
                }

                $mempool = calculate_mempool_properties($scale, $used, $total, $free, $perc, $entry);

                // If we have valid used and total, discover the mempool
                if (is_numeric($mempool['used']) && is_numeric($mempool['total'])) {
                    // Rename RRD if requested
                    if (isset($entry['rename_rrd'])) {
                        $old_rrd = 'mempool-' . $entry['rename_rrd'];
                        $new_rrd = 'mempool-' . strtolower($mib) . '-' . $index;
                        rename_rrd($device, $old_rrd, $new_rrd);
                        unset($old_rrd, $new_rrd);
                    }

                    $mempool_hc = 0; //  // FIXME mempool_hc = ?? currently keep as 0
                    discover_mempool($valid['mempool'], $device, $index, $mib, $descr, $scale, $mempool['total'], $mempool['used'], $mempool_hc, ['table' => $entry_name]);
                    $entry['found'] = TRUE;
                }
            }

            unset($mempools_array, $mempool, $dot_index, $descr, $i); // Clean up
            if (isset($entry['stop_if_found']) && $entry['stop_if_found'] && $entry['found']) {
                break;
            } // Stop loop if mempool found
        }
        print_cli(PHP_EOL);
    }
}

// Remove memory pools which weren't redetected here
foreach (dbFetchRows('SELECT * FROM `mempools` WHERE `device_id` = ?', [$device['device_id']]) as $test_mempool) {
    $mempool_index = $test_mempool['mempool_index'];
    $mempool_mib   = $test_mempool['mempool_mib'];
    $mempool_descr = $test_mempool['mempool_descr'];
    print_debug($mempool_index . " -> " . $mempool_mib);

    if (!$valid['mempool'][$mempool_mib][$mempool_index]) {
        $GLOBALS['module_stats'][$module]['deleted']++; //echo('-');
        dbDelete('mempools', '`mempool_id` = ?', [$test_mempool['mempool_id']]);
        log_event("Memory pool removed: mib $mempool_mib, index $mempool_index, descr $mempool_descr", $device, 'mempool', $test_mempool['mempool_id']);
    }
}

$GLOBALS['module_stats'][$module]['status'] = safe_count($valid['mempool']);
if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status']) {
    print_vars($valid['mempool']);
}

// EOF
