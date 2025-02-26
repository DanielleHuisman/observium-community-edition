<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$scale_min    = 0;
$colours      = "mixed";
$nototal      = (($width < 550) ? 1 : 0);
$unit_text    = "Messages/minute";
$rrd_filename = get_rrd_path($device, "app-exim.rrd");
$array        = [
  'rejected'   => ['descr' => 'Rejected'],
  'bounced'    => ['descr' => 'Bounced'],
  'greylisted' => ['descr' => 'Greylisted'],
  'delayed'    => ['descr' => 'Delayed'],
];

$i = 0;
$x = 0;

if (rrd_is_file($rrd_filename)) {
    $max_colours = safe_count($config['graph_colours'][$colours]);
    foreach ($array as $ds => $data) {
        $x                        = (($x <= $max_colours) ? $x : 0);
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $config['graph_colours'][$colours][$x];
        $i++;
        $x++;
    }
}

include($config['html_dir'] . "/includes/graphs/minute_multi_line.inc.php");

// EOF
