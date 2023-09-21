<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Include all discovery modules

$include_dir = "includes/discovery/storage";
include("includes/include-dir-mib.inc.php");

foreach (get_device_mibs_permitted($device) as $mib) {

    if (!is_array($config['mibs'][$mib]['storage'])) {
        continue;
    }

    print_cli_data_field($mib);
    foreach ($config['mibs'][$mib]['storage'] as $entry_name => $entry) {
        discover_storage_definition($device, $mib, $entry, $entry_name);

    }
    print_cli(PHP_EOL);
}

print_debug_vars($valid['storage']);

// Remove storage which weren't redetected here
$query = 'SELECT * FROM `storage` WHERE `device_id` = ?';

foreach (dbFetchRows($query, [$device['device_id']]) as $test_storage) {
    $storage_index = $test_storage['storage_index'];
    $storage_mib   = $test_storage['storage_mib'];
    $storage_descr = $test_storage['storage_descr'];
    print_debug($storage_index . " -> " . $storage_mib);

    if (!$valid['storage'][$storage_mib][$storage_index]) {
        $GLOBALS['module_stats']['storage']['deleted']++; //echo('-');
        dbDelete('storage', 'storage_id = ?', [$test_storage['storage_id']]);
        log_event("Storage removed: index $storage_index, mib $storage_mib, descr $storage_descr", $device, 'storage', $test_storage['storage_id']);
    }
}

$GLOBALS['module_stats'][$module]['status'] = safe_count($valid[$module]);
if (OBS_DEBUG && $GLOBALS['module_stats'][$module]['status']) {
    print_vars($valid[$module]);
}

// EOF
