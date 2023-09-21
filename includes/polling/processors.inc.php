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

// Pre-cache all processors oid
$query = 'SELECT `processor_oid` FROM `processors` WHERE `device_id` = ? AND `processor_oid` REGEXP ? AND `processor_type` != ?';
// wmi excluded, select only valid numeric OIDs like .1.2.3.3 (excluded ucd-old, hr-average)
if ($oid_to_cache = dbFetchColumn($query, [$device['device_id'], '^\.?[0-9]+(\.[0-9]+)+$', 'wmi'])) {
    usort($oid_to_cache, 'compare_numeric_oids'); // correctly sort numeric oids
    print_debug_vars($oid_to_cache);
    $oid_cache = snmp_get_multi_oid($device, $oid_to_cache, $oid_cache, NULL, NULL, OBS_SNMP_ALL_NUMERIC);
    print_debug_vars($oid_cache);
}

$sql = "SELECT * FROM `processors` WHERE `device_id` = ?";

foreach (dbFetchRows($sql, [$device['device_id']]) as $processor) {
    // echo("Processor " . $processor['processor_descr'] . " ");

    $processor['processor_oid'] = '.' . ltrim($processor['processor_oid'], '.'); // Fix first dot in oid

    $file = $config['install_dir'] . "/includes/polling/processors/" . $processor['processor_type'] . ".inc.php";
    if (is_file($file)) {
        include($file);
    } elseif (isset($oid_cache[$processor['processor_oid']])) {
        // Use cached OIDs
        $proc = $oid_cache[$processor['processor_oid']];
    } else {
        // Not should be happen, but keep it as last anyway
        $proc = snmp_get_oid($device, $processor['processor_oid']);
    }

    $unit = NULL;
    // FIXME. This is mostly derp way for connect MIB name in poller.
    // Currently I need to add support for definition mib polling
    // New discover_processor_ng() will added soon, than this compat code will removed!
    if (str_contains($processor['processor_type'], 'CPULoad')) {
        foreach (get_device_mibs_permitted($device) as $mib) {
            if (isset($config['mibs'][$mib]['processor'][$processor['processor_type']])) {
                $def = $config['mibs'][$mib]['processor'][$processor['processor_type']];
                // Units, see: LANCOM-GS2310PPLUS-MIB
                $unit = isset($def['unit']) ? $def['unit'] : NULL;
                break;
            }
        }
    }

    $proc = snmp_fix_numeric($proc, $unit);

    if (is_numeric($proc)) {
        if (!$processor['processor_precision']) {
            $processor['processor_precision'] = 1;
        }
        $proc = round($proc / $processor['processor_precision'], 2);
        if ($processor['processor_returns_idle'] == 1) {
            $proc = 100 - $proc;
        } // The OID returns idle value, so we subtract it from 100.
    } else {
        $proc = 0;
    }
    $processor['processor_usage'] = $proc;

    $graphs['processor'] = TRUE;

    // Update StatsD/Carbon
    if ($config['statsd']['enable'] == TRUE) {
        StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'processor' . '.' . $processor['processor_type'] . "-" . $processor['processor_index'], $proc);
    }

    // Update RRD
    rrdtool_update_ng($device, 'processor', ['usage' => $proc], $processor['processor_type'] . "-" . $processor['processor_index']);

    // Update SQL State
    dbUpdate(['processor_usage' => $proc, 'processor_polled' => time()], 'processors', '`processor_id` = ?', [$processor['processor_id']]);

    // Check alerts
    check_entity('processor', $processor, ['processor_usage' => $proc]);

    $table_row    = [];
    $table_row[]  = $processor['processor_descr'];
    $table_row[]  = $processor['processor_type'];
    $table_row[]  = $processor['processor_index'];
    $table_row[]  = $processor['processor_usage'] . '%';
    $table_rows[] = $table_row;
    unset($table_row);

}

$headers = ['%WLabel%n', '%WType%n', '%WIndex%n', '%WUsage%n'];
print_cli_table($table_rows, $headers);

// EOF
