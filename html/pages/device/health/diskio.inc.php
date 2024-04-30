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

if (device_permitted($device)) {

    // Only show aggregate graph if we have access to the entire device.

    $graph_title           = nicecase($vars['metric']);
    $graph_array['type']   = "device_diskio_bits";
    $graph_array['device'] = $device['device_id'];
    $graph_array['legend'] = 'no';

    $box_args = ['title'         => $graph_title,
                 'header-border' => TRUE,
    ];

    echo generate_box_open($box_args);

    print_graph_row($graph_array);

    $graph_array['type'] = "device_diskio_ops";
    print_graph_row($graph_array);

    echo generate_box_close();

}

echo generate_box_open();

echo('<table class="table table-striped table-condensed ">');

//echo("<thead><tr>
//        <th>Device</th>
//      </tr></thead>");


foreach (dbFetchRows("SELECT * FROM `ucd_diskio` WHERE device_id = ? ORDER BY diskio_descr", [$device['device_id']]) as $drive) {

    $fs_url = "device/device=" . $device['device_id'] . "/tab=health/metric=diskio/";

    $graph_array_zoom['id']     = $drive['diskio_id'];
    $graph_array_zoom['type']   = "diskio_ops";
    $graph_array_zoom['width']  = "400";
    $graph_array_zoom['height'] = "125";
    $graph_array_zoom['from']   = get_time('twoday');
    $graph_array_zoom['to']     = get_time();

    echo("<tr><td><h3>");
    echo(overlib_link($fs_url, $drive['diskio_descr'], generate_graph_tag($graph_array_zoom), NULL));
    echo("</h3>");

    $types = ["diskio_bits", "diskio_ops"];

    $rrd_filename = get_rrd_path($device, "diskstat-" . $drive['diskio_descr'] . ".rrd");
    if (is_file($rrd_filename)) {
        $types[] = "diskio_stat";
    }

    foreach ($types as $graph_type) {

        $graph_array         = [];
        $graph_array['id']   = $drive['diskio_id'];
        $graph_array['type'] = $graph_type;

        print_graph_row($graph_array);

    }

}

echo "</td></tr>";
echo "</table>";

echo generate_box_close();

// EOF
