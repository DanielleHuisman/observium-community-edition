<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Collect data for non-entity graphs

$include_dir = "includes/polling/graphs/";
include("includes/include-dir-mib.inc.php");

// Merge in mib definitions with $table_defs
foreach (get_device_mibs_permitted($device) as $mib) {
    // Detect sensors by definitions
    if (isset($config['mibs'][$mib]['graphs']) && is_array($config['mibs'][$mib]['graphs'])) {
        if (isset($table_defs[$mib])) {
            $table_defs[$mib] = array_merge($table_defs[$mib], $config['mibs'][$mib]['graphs']);
        } else {
            $table_defs[$mib] = $config['mibs'][$mib]['graphs'];
        }
    }
}

$vrf_contexts = get_device_vrf_contexts($device); // SNMP VRF context discovered for device

foreach ($table_defs as $mib => $mib_tables) {
    print_cli_data_field("$mib", 2);
    foreach ($mib_tables as $table_name => $table_def) {
        // Compat with old table format
        if (!isset($table_def['mib'])) {
            $table_def['mib'] = $mib;
        }

        // Polling NG (with tables and indexed)
        $graphs_ng = FALSE && is_numeric($table_name); // WiP

        if ($graphs_ng) {
            // WIP.
            echo("NEW GRAPHS polling ");
            collect_table_ng($device, $table_def, $graphs);
        } else {
            // CLEANME. remove after migrating to collect_table_ng()
            echo("$table_name ");
            collect_table($device, $table_def, $graphs);
        }

        // Poll same in vrfs if possible
        if ($vrf_contexts) {
            // Keep original device array
            $device_original = $device;

            foreach ($vrf_contexts as $vrf_name => $snmp_virtual) {
                echo("[Virtual Routing $vrf_name] ");
                $device = snmp_virtual_device($device_original, $snmp_virtual);
                //$device['snmp_context'] = $snmp_context;
                if ($graphs_ng) {
                    echo("NEW GRAPHS polling ");
                    collect_table_ng($device, $table_def, $graphs);
                } else {
                    // CLEANME. remove after migrating to collect_table_ng()
                    collect_table($device, $table_def, $graphs);
                }
            }

            // Clean
            $device = $device_original;
            unset($device_original);
        }
    }
    echo PHP_EOL;
}

// EOF
