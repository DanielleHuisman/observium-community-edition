<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($_SESSION['widescreen']) {
    if ($config['graphs']['size'] === 'big') {
        $width_div  = 586;
        $width      = 508;
        $height     = 149;
        $height_div = 220;
    } else {
        $width_div  = 350;
        $width      = 276;
        $height     = 109;
        $height_div = 180;
    }
} else {
    if ($config['graphs']['size'] === 'big') {
        $width_div  = 614;
        $width      = 533;
        $height     = 159;
        $height_div = 218;
    } else {
        $width_div  = 302;
        $width      = 227;
        $height     = 100;
        $height_div = 158;
    }
}

$graph_array['height'] = 100;
$graph_array['width']  = 212;

$where_clause = generate_where_clause(generate_query_permitted_ng(['devices']));

$query = "SELECT * FROM `applications` " . $where_clause . " ORDER BY `app_type`;";

foreach (dbFetchRows($query) as $app) {
    $app_types[$app['app_type']]['instances'][$app['app_id']] = array_merge($app, device_by_id_cache($app['device_id']));
}

echo '<div class="row">';

foreach ($app_types as $app_type => $app) {

    echo '<div class="col-md-12"><div class="box box-solid"><h2>' . generate_link(nicecase($app_type), ['page' => 'apps', 'app' => $app_type]) . '</h2></div><div class="row">';

    $app['instances'] = array_sort_by($app['instances'], 'hostname', SORT_ASC, SORT_STRING);

    foreach ($app['instances'] as $app_id => $device) {

        $app_id = $device['app_id'];

        $graph_type = $config['app'][$app_type]['top'][0];

        $graph_array['type'] = "application_" . $app_type . "_" . $graph_type;
        $graph_array['id']   = $app_id;

        $graph_array['device'] = $device['device_id'];
        $graph_array['legend'] = "no";

        $link_array         = $graph_array;
        $link_array['page'] = "graphs";
        unset($link_array['height'], $link_array['width'], $link_array['legend']);
        $link            = generate_url($link_array);
        $overlib_content = generate_overlib_content($graph_array, $device['hostname']);

        $graph_array['width']  = $width;
        $graph_array['height'] = $height;
        $graph                 = generate_graph_tag($graph_array);


        echo generate_box_open(['title'         => $device['hostname'],
                                'url'           => generate_device_url($device),
                                'header-border' => TRUE,
                                'box-style'     => 'float: left; margin-left: 10px; margin-bottom: 10px;  width:' . $width_div . 'px; min-width: ' . $width_div . 'px; max-width:' . $width_div . 'px; min-height:' . $height_div . 'px; max-height:' . $height_div . ';']);

        echo overlib_link($link, $graph, $overlib_content);

        echo generate_box_close();
    }

    echo '</div></div>';

}

echo '</div>';


// EOF
