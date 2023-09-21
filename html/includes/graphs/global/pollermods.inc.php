<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

//r($vars);

$i = 0;

$modules           = [];
$module_total_time = 0;

$where = [];
$args  = [];

if (!safe_empty($vars['poller_id'])) {
    $where[] = "`poller_id` = ?";
    $args[]  = $vars['poller_id'];
}

$query = "SELECT * FROM `devices`";
if (safe_count($where)) {
    $query .= " WHERE ";
    $query .= implode(" AND ", $where);
}

foreach (dbFetchRows($query, $args) as $device) {

    $device['state'] = safe_unserialize($device['device_state']);

    $devices[$device['device_id']] = $device;

    foreach ($device['state']['poller_mod_perf'] as $mod => $time) {
        $modules[$mod]['time'] += $time;
        $modules[$mod]['count']++;
        $module_total_time += $time;
    }
}

$modules = array_sort_by($modules, 'time', SORT_DESC, SORT_NUMERIC);

foreach ($modules as $mod => $mod_data) {

    $groups[$mod]['descr'] = $mod;

    foreach ($devices as $device_id => $device) {

        $rrd_filename = get_rrd_path($device, 'perf-pollermodule-' . $mod . '.rrd');

        if (rrd_is_file($rrd_filename, TRUE)) {
            $groups[$mod]['list'][] = [
              'filename' => $rrd_filename,
              'descr'    => str_pad($device['hostname'], 25) . " (" . $device['os'] . ")",
              'ds'       => "val"
            ];
        }
    }
}

$units       = 'Seconds';
$total_units = 'Sec';
$colours     = 'mixed';

$nototal = 1;

include($config['html_dir'] . "/includes/graphs/generic_multi_group_simplex_separated.inc.php");

// EOF
