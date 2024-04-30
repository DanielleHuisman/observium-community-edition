<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     applications
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

## FIXME -- THIS IS MESSY AND NEEDS TO BE FIXED.

global $config;

$total = TRUE;

$rrddir = $config['rrd_dir'] . "/" . $device['hostname'];
$files  = [];

if ($handle = opendir($rrddir)) {
    while (FALSE !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." &&
            str_starts($file, "app-shoutcast-" . $app['app_id'])) {
            array_push($files, $file);
        }
    }
}

if (isset($total) && $total == TRUE) {
    $graphs = [
      'shoutcast_multi_bits'  => 'Traffic Statistics - Total of all Shoutcast servers',
      'shoutcast_multi_stats' => 'Shoutcast Statistics - Total of all Shoutcast servers'
    ];

    foreach ($graphs as $key => $text) {
        $graph_type          = $key;
        $graph_array['to']   = get_time();
        $graph_array['id']   = $app['app_id'];
        $graph_array['type'] = "application_" . $key;
        echo('<h3>' . $text . '</h3>');
        echo("<tr bgcolor='$row_colour'><td colspan=5>");

        print_graph_row($graph_array);

        echo("</td></tr>");
    }
}

foreach ($files as $id => $file) {
    $hostname = str_replace(['app-shoutcast-' . $app['app_id'] . '-', '.rrd'], '', $file);
    [$host, $port] = explode('_', $hostname, 2);
    $graphs = [
      'shoutcast_bits'  => 'Traffic Statistics - ' . $host . ' (Port: ' . $port . ')',
      'shoutcast_stats' => 'Shoutcast Statistics - ' . $host . ' (Port: ' . $port . ')'
    ];

    foreach ($graphs as $key => $text) {
        $graph_type              = $key;
        $graph_array['height']   = "100";
        $graph_array['width']    = "215";
        $graph_array['to']       = get_time();
        $graph_array['id']       = $app['app_id'];
        $graph_array['type']     = "application_" . $key;
        $graph_array['hostname'] = $hostname;
        echo('<h3>' . $text . '</h3>');
        echo("<tr bgcolor='$row_colour'><td colspan=5>");

        print_graph_row($graph_array);

        echo("</td></tr>");
    }
}

// EOF
