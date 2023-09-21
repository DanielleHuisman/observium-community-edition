<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$table_rows = [];

if (!isset($cache_storage)) {
    $cache_storage = [];
} // This cache used also in storage module

// Cache snmpwalk by definitions
poll_cache_storage($device, $cache_storage);

// CLEANME. Compatibility with old (incorrect) field
$object_field = get_db_version() > 468 ? 'storage_object' : 'storage_type';

$sql = "SELECT * FROM `storage` WHERE `device_id` = ?";
foreach (dbFetchRows($sql, [$device['device_id']]) as $storage) {
    $storage_size = $storage['storage_size']; // Memo old size
    $file         = $config['install_dir'] . "/includes/polling/storage/" . strtolower($storage['storage_mib']) . ".inc.php";
    if (is_file($file)) {
        // File based include
        include($file);
    } else {
        // Definition based storage poller
        // Table is always set when definitions add storage.
        if (safe_empty($storage[$object_field]) || !is_array($config['mibs'][$storage['storage_mib']]['storage'][$storage[$object_field]])) {
            // Unknown, so force rediscovery as there's a broken storage
            force_discovery($device, 'storage');

            // CLEANME. Compat, clean after 2022/09
            if ($storage['storage_mib'] === 'NETAPP-MIB') {
                $storage['storage_object'] = 'dfEntry';
            }
        }

        poll_storage_definition($device, $config['mibs'][$storage['storage_mib']]['storage'][$storage[$object_field]], $storage, $cache_storage);
    }

    print_debug_vars($storage);

    if (is_numeric($storage['perc'])) {
        $percent = $storage['perc'];
    } elseif (is_numeric($storage['used']) && $storage['size']) {
        $percent = round(float_div($storage['used'], $storage['size']) * 100, 2);
    } else {
        $percent = 0;
    }

    if (!isset($storage['units'])) {
        $storage['units'] = $storage['storage_units'];
    }

    $hc = ($storage['storage_hc'] ? ' (HC)' : '');

    // Update StatsD/Carbon
    if ($config['statsd']['enable'] == TRUE) {
        StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'storage' . '.' . $storage['storage_mib'] . "-" . safename($storage['storage_descr']) . ".used", $storage['used']);
        StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'storage' . '.' . $storage['storage_mib'] . "-" . safename($storage['storage_descr']) . ".free", $storage['free']);
    }

    // Update RRD

    rrdtool_update_ng($device, 'storage', ['used' => $storage['used'], 'free' => $storage['free']], strtolower($storage['storage_mib']) . "-" . $storage['storage_descr']);

    //if (!is_numeric($storage['storage_polled']))
    //{
    //  dbInsert(array('storage_id'     => $storage['storage_id'],
    //                 'storage_polled' => time(),
    //                 'storage_used'   => $storage['used'],
    //                 'storage_free'   => $storage['free'],
    //                 'storage_size'   => $storage['size'],
    //                 'storage_units'  => $storage['units'],
    //                 'storage_perc'   => $percent), 'storage-state');
    //} else {
    $update = dbUpdate(['storage_polled' => time(),
                        'storage_used'   => $storage['used'],
                        'storage_free'   => $storage['free'],
                        'storage_size'   => $storage['size'],
                        'storage_units'  => $storage['units'],
                        'storage_perc'   => $percent], 'storage', '`storage_id` = ?', [$storage['storage_id']]);
    if (format_bytes($storage_size) != format_bytes($storage['size'])) {
        //&& (abs($storage_size - $storage['size']) / max($storage_size, $storage['size'])) > 0.0001 ) // Log only if size diff more than 0.01%
        log_event('Storage size changed: ' . format_bytes($storage_size) . ' -> ' . format_bytes($storage['size']) . ' (' . $storage['storage_descr'] . ')', $device, 'storage', $storage['storage_id']);
    }
    //}
    $graphs['storage'] = TRUE;

    // Check alerts
    check_entity('storage', $storage, ['storage_perc' => $percent, 'storage_free' => $storage['free'], 'storage_used' => $storage['used']]);

    $table_row    = [];
    $table_row[]  = $storage['storage_descr'];
    $table_row[]  = $storage['storage_mib'];
    $table_row[]  = $storage['storage_index'];
    $table_row[]  = format_bytes($storage['size']);
    $table_row[]  = format_bytes($storage['used']);
    $table_row[]  = format_bytes($storage['free']);
    $table_row[]  = $percent . '%';
    $table_rows[] = $table_row;
    unset($table_row);

}

$headers = ['%WLabel%n', '%WMIB%n', '%WIndex%n', '%WTotal%n', '%WUsed%n', '%WFree%n', '%WPerc%n'];
print_cli_table($table_rows, $headers);

unset($storage, $table, $table_row, $table_rows, $unit);

// EOF
