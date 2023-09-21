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

if (!isset($cache_storage)) {
    $cache_storage = [];
} // This cache used also in storage module

$table_rows = [];

$sql = 'SELECT * FROM `mempools`';
//$sql .= ' LEFT JOIN `mempools-state` USING (`mempool_id`)';
$sql .= ' WHERE `device_id` = ?';

foreach (dbFetchRows($sql, [$device['device_id']]) as $mempool) {
    $mib_lower = strtolower($mempool['mempool_mib']);
    $file      = $config['install_dir'] . '/includes/polling/mempools/' . $mib_lower . '.inc.php';

    if (!$mempool['mempool_multiplier']) {
        $mempool['mempool_multiplier'] = 1;
    }

    if (is_file($file)) {
        $cache_mempool = NULL;
        $index         = $mempool['mempool_index'];

        include($file);

        // Merge calculated used/total/free/perc array keys into $mempool variable
        $mempool = array_merge($mempool, calculate_mempool_properties($mempool['mempool_multiplier'], $mempool['used'], $mempool['total'], $mempool['free'], $mempool['perc']));

    } elseif (!safe_empty($mempool['mempool_table'])) {
        // Check if we can poll the device ourselves with generic code using definitions.
        // Table is always set when definitions add mempools.
        $table_def = $config['mibs'][$mempool['mempool_mib']]['mempool'][$mempool['mempool_table']];

        if ($table_def['type'] === 'static') {

            if (isset($table_def['oid_perc_num'])) {
                $mempool['perc'] = snmp_get_oid($device, $table_def['oid_perc_num']);
            } elseif (isset($table_def['oid_perc'])) {
                $mempool['perc'] = snmp_get_oid($device, $table_def['oid_perc'], $mempool['mempool_mib']);
            }

            if (isset($table_def['oid_free_num'])) {
                $mempool['free'] = snmp_get_oid($device, $table_def['oid_free_num']);
            } elseif (isset($table_def['oid_free'])) {
                $mempool['free'] = snmp_get_oid($device, $table_def['oid_free'], $mempool['mempool_mib']);
            }

            if (isset($table_def['oid_used_num'])) {
                $mempool['used'] = snmp_get_oid($device, $table_def['oid_used_num']);
            } elseif (isset($table_def['oid_used'])) {
                $mempool['used'] = snmp_get_oid($device, $table_def['oid_used'], $mempool['mempool_mib']);
            }

            if (isset($table_def['total'])) {
                $mempool['total'] = $table_def['total'];
            } elseif (isset($table_def['oid_total_num'])) {
                $mempool['total'] = snmp_get_oid($device, $table_def['oid_total_num']);
            } elseif (isset($table_def['oid_total'])) {
                $mempool['total'] = snmp_get_oid($device, $table_def['oid_total'], $mempool['mempool_mib']);
            }

        } else {
            // FIXME. Need pre-cache same as for sensors
            if (isset($table_def['oid_perc_num'])) {
                $mempool['perc'] = snmp_get_oid($device, $table_def['oid_perc_num'] . '.' . $mempool['mempool_index']);
            } elseif (isset($table_def['oid_perc'])) {
                $mempool['perc'] = snmp_get_oid($device, $table_def['oid_perc'] . '.' . $mempool['mempool_index'], $mempool['mempool_mib']);
            }

            if (isset($table_def['oid_free_num'])) {
                $mempool['free'] = snmp_get_oid($device, $table_def['oid_free_num'] . '.' . $mempool['mempool_index']);
            } elseif (isset($table_def['oid_free'])) {
                $mempool['free'] = snmp_get_oid($device, $table_def['oid_free'] . '.' . $mempool['mempool_index'], $mempool['mempool_mib']);
            }

            if (isset($table_def['oid_used_num'])) {
                $mempool['used'] = snmp_get_oid($device, $table_def['oid_used_num'] . '.' . $mempool['mempool_index']);
            } elseif (isset($table_def['oid_used'])) {
                $mempool['used'] = snmp_get_oid($device, $table_def['oid_used'] . '.' . $mempool['mempool_index'], $mempool['mempool_mib']);
            }

            if (isset($table_def['oid_total'])) {
                // Static Total Oid, NMS-CHASSIS
                $oid              = str_contains($table_def['oid_total'], '.') ? $table_def['oid_total'] : $table_def['oid_total'] . '.' . $mempool['mempool_index'];
                $mempool['total'] = snmp_get_oid($device, $oid, $mempool['mempool_mib']);
            } elseif (isset($table_def['oid_total_num'])) {
                $mempool['total'] = snmp_get_oid($device, $table_def['oid_total_num'] . '.' . $mempool['mempool_index']);
            }
            if (safe_empty($mempool['total']) && isset($table_def['total'])) {
                $mempool['total'] = $table_def['total'];
            }

        }
        // Clean not numeric symbols from snmp output
        foreach (['perc', 'free', 'used', 'total'] as $param) {
            // Convert strings '3.40 TB' to value
            // See QNAP NAS-MIB or HIK-DEVICE-MIB
            $unit = ($param !== 'perc' && isset($table_def['unit'])) ? $table_def['unit'] : NULL;

            if (isset($mempool[$param])) {
                $mempool[$param] = snmp_fix_numeric($mempool[$param], $unit);
            }
        }

        // Merge calculated used/total/free/perc array keys into $mempool variable (with additional options)
        $mempool = array_merge($mempool, calculate_mempool_properties($mempool['mempool_multiplier'], $mempool['used'], $mempool['total'], $mempool['free'], $mempool['perc'], $table_def));
    } else {
        // Unknown, so force rediscovery as there's a broken mempool
        force_discovery($device, 'mempools');
    }

    $hc = ($mempool['mempool_hc'] ? ' (HC)' : '');

    // Update StatsD/Carbon
    if ($config['statsd']['enable'] == TRUE) {
        StatsD ::gauge(str_replace('.', '_', $device['hostname']) . '.' . 'mempool' . '.' . $mempool['mempool_mib'] . '.' . $mempool['mempool_index'] . '.used', $mempool['used']);
        StatsD ::gauge(str_replace('.', '_', $device['hostname']) . '.' . 'mempool' . '.' . $mempool['mempool_mib'] . '.' . $mempool['mempool_index'] . '.free', $mempool['free']);
    }

    // Need to handle multiple mempools from the same MIB
    if (isset($mempool['mempool_table'])) {
        $filename = $mib_lower . '-' . $mempool['mempool_table'] . '-' . $mempool['mempool_index'];
        rename_rrd($device, 'mempool-' . $mib_lower . '-' . $mempool['mempool_index'], 'mempool-' . $mib_lower . '-' . $mempool['mempool_table'] . '-' . $mempool['mempool_index']);
    } else {
        $filename = $mib_lower . '-' . $mempool['mempool_index'];
    }

    rrdtool_update_ng($device, 'mempool', ['used' => $mempool['used'], 'free' => $mempool['free']], $filename);

    $mempool['state'] = ['mempool_polled' => time(),
                         'mempool_used'   => $mempool['used'],
                         'mempool_perc'   => $mempool['perc'],
                         'mempool_free'   => $mempool['free'],
                         'mempool_total'  => $mempool['total']];

    dbUpdate($mempool['state'], 'mempools', '`mempool_id` = ?', [$mempool['mempool_id']]);
    $graphs['mempool'] = TRUE;

    check_entity('mempool', $mempool, ['mempool_perc' => $mempool['perc'], 'mempool_free' => $mempool['free'], 'mempool_used' => $mempool['used']]);

    $table_row    = [];
    $table_row[]  = $mempool['mempool_descr'];
    $table_row[]  = $mempool['mempool_mib'];
    $table_row[]  = $mempool['mempool_index'];
    $table_row[]  = format_bytes($mempool['total']);
    $table_row[]  = format_bytes($mempool['used']);
    $table_row[]  = format_bytes($mempool['free']);
    $table_row[]  = $mempool['perc'] . '%';
    $table_rows[] = $table_row;
    unset($table_row);

}

$headers = ['%WLabel%n', '%WType%n', '%WIndex%n', '%WTotal%n', '%WUsed%n', '%WFree%n', '%WPerc%n'];
print_cli_table($table_rows, $headers);

unset($cache_mempool, $mempool, $index, $table_row, $table_rows, $table_headers, $unit);

// EOF
