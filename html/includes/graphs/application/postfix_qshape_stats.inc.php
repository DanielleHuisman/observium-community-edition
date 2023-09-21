<?php

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$scale_min = 0;
$colours   = "mixed";
$nototal   = (($width < 550) ? 1 : 0);
#$unit_text    = "Messages in queue";
$units_descr  = "Messages in queue";
$rrd_filename = get_rrd_path($device, "app-postfix-qshape");
$array        = [
  'incoming' => ['descr' => 'Incoming'],
  'active'   => ['descr' => 'Active'],
  'deferred' => ['descr' => 'Deferred'],
  'hold'     => ['descr' => 'Hold'],
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

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
