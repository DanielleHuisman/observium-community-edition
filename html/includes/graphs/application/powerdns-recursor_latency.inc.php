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

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$scale_min    = 0;
$colours      = "mixed";
$nototal      = (($width < 224) ? 1 : 0);
$unit_text    = "Questions/sec";
$rrd_filename = get_rrd_path($device, "app-powerdns-recursor-" . $app['app_id'] . ".rrd");
$array        = [
  'packetCacheHits' => ['descr' => '<<1ms (PacketCache)', 'colour' => '00FF00FF'],
  'answers_1ms'     => ['descr' => '<1ms', 'colour' => '00FFF0FF'],
  'answers_10ms'    => ['descr' => '<10ms', 'colour' => '0F0FFFFF'],
  'answers_100ms'   => ['descr' => '<100ms', 'colour' => 'FF9900FF'],
  'answers_1000ms'  => ['descr' => '<1s', 'colour' => 'FFFF00FF'],
  'answers_1s'      => ['descr' => '>1s', 'colour' => 'FF0000FF'],
];

$i = 0;

if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
        $rrd_list[$i]['colour']   = $data['colour'];
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
