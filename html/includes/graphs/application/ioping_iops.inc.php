<?php

/**
 *
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$colours   = "mixed-q12";
$nototal   = (($width < 224) ? 1 : 0);
$unit_text = "IOPs";
$nototal   = "true";
//$multiplier   = "1000000";
$rrd_filename = get_rrd_path($device, "app-ioping-" . $app['app_instance'] . ".rrd");
$array        = [
  'reqps' => ['descr' => 'I/O Requests per second'],
];

$i = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $config['graph_colours'][$colours][$i];
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include("includes/graphs/generic_multi_line.inc.php");

// EOF
