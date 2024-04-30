<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$graph_array['to']   = get_time();
$graph_array['from'] = get_time('day');
//$graph_array_zoom           = $graph_array;
//$graph_array_zoom['height'] = "150";
//$graph_array_zoom['width']  = "400";
//$graph_array['legend']      = "no";

$where_clause = generate_where_clause(['`app_type` = ?', generate_query_permitted_ng(['devices'])]);


// Merge device and app arrays for ease of sorting. This may not scale well to huge numbers of apps.
$app_devices = [];
foreach (dbFetchRows("SELECT * FROM `applications` " . $where_clause, [$vars['app']]) as $app) {
    $devices[$app['app_id']] = array_merge($app, device_by_id_cache($app['device_id']));
}

$devices = array_sort_by($devices, 'hostname', SORT_ASC, SORT_STRING);

foreach ($devices as $device) {

    // Faux $app array for easier code reading
    $app = &$device;

    echo generate_box_open();

    echo '<table class="table table-hover table-condensed table-striped ">';

    print_device_row($device, NULL, ['tab' => 'apps', 'app' => $app['app_type'], 'instance' => $app['app_id']]);

    echo '<tr><td colspan="6">';

    $graph_array['id']     = $device['app_id'];
    $graph_array['types']  = [];
    $graph_array['legend'] = "no";

    foreach ($config['app'][$vars['app']]['top'] as $graph_type) {
        $graph_array['types'][] = "application_" . $vars['app'] . "_" . $graph_type;
    }
    print_graph_summary_row($graph_array);

    echo '</td>';
    echo '</tr>';

    echo '</table>';

    echo generate_box_close();

}

//echo '</table>';

//echo generate_box_close();

// EOF
