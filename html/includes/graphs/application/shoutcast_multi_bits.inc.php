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

$units       = "b";
$total_units = "B";
$colours_in  = "greens";
$multiplier  = "8";
$colours_out = "blues";

$nototal = 1;

$ds_in  = "traf_in";
$ds_out = "traf_out";

$graph_title = "Traffic Statistic";

$colour_line_in  = "006600";
$colour_line_out = "000099";
$colour_area_in  = "CDEB8B";
$colour_area_out = "C3D9FF";

// FIXME Not compatible this way with get_rrd_path; as long as no advanced storage is used this will work
// Call get_rrd_path below instead of using $rrddir.
$rrddir = $config['rrd_dir'] . "/" . $device['hostname'];
$files  = [];
$i      = 0;

if ($handle = opendir($rrddir)) {
    while (FALSE !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." &&
            str_starts($file, "app-shoutcast-" . $app['app_id'])) {
            array_push($files, $file);
        }
    }
}

foreach ($files as $id => $file) {
    $hostname = str_replace(['app-shoutcast-' . $app['app_id'] . '-', '.rrd'], '', $file);
    [$host, $port] = explode('_', $hostname, 2);
    $rrd_filenames[]          = $rrddir . "/" . $file;
    $rrd_list[$i]['filename'] = $rrddir . "/" . $file;
    $rrd_list[$i]['descr']    = $host . ":" . $port;
    $rrd_list[$i]['ds_in']    = $ds_in;
    $rrd_list[$i]['ds_out']   = $ds_out;
    $i++;
}

include($config['html_dir'] . "/includes/graphs/generic_multi_bits_separated.inc.php");

// EOF
