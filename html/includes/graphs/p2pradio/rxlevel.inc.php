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

$colours   = "oranges";
$nototal   = 0;
$unit_text = "dBm";
$units     = "dBm";

$array = [
  'rx_level' => ['descr' => 'Rx Level'],
];

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $config['graph_colours'][$colours][$i];
        $i++;
    }
} else {
    echo("file missing: $file");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");
