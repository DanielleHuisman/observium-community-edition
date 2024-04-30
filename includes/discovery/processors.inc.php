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

// Detect processors by simple MIB-based discovery
foreach (get_device_mibs_permitted($device) as $mib) {
    if (!is_array($config['mibs'][$mib]['processor'])) {
        continue;
    }

    print_cli_data_field("$mib");

    foreach ($config['mibs'][$mib]['processor'] as $entry_name => $entry) {
        $entry['found'] = FALSE;
        $entry['object'] = $entry_name;

        // CLEANME. Remove compat when all static convert!
        if ($entry['type'] === 'static') {
            print_debug("Static processor discovery: $entry_name\n");
            print_debug_vars($entry);

            // Get index
            $index = explode('.', $entry['oid'], 2)[1];
            foreach (array_keys($entry) as $key) {
                if (str_starts_with($key, 'oid_') && str_ends_with($entry[$key], '.' . $index)) {
                    $entry[$key] = explode('.', $entry[$key], 2)[0];
                }
            }
            $entry['indexes'] = [ $index => [ 'descr' => $entry['descr'] ] ];
            unset($entry['type']);
            print_warning("Convert static processor definition to common!");
            print_debug_vars($entry);
        } elseif (isset($entry['indexes'])) {
            print_debug("Forced snmpget by indexes");
            $entry['type'] = 'indexes';
        }

        $entry['found'] = discover_processor_definition($device, $mib, $entry);

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
    unset($processor_oid, $processor_type);
}

$GLOBALS['module_stats'][$module]['status'] = safe_count($valid['processor']);
if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status']) {
    print_vars($valid['processor']);
}

// EOF
