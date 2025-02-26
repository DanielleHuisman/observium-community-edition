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
$nototal      = (($width < 224) ? 1 : 0);
$unit_text    = "Queries/sec";
$rrd_filename = get_rrd_path($device, "app-powerdns-" . $app['app_id'] . ".rrd");
$array        = [
  'rec_questions' => ['descr' => 'Questions', 'colour' => '6699CCFF', 'invert' => 0],
  'rec_answers'   => ['descr' => 'Answers', 'colour' => '336699FF', 'invert' => 1],
];

$i = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $data['colour'];
        $rrd_list[$i]['invert']   = $data['invert'];
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
