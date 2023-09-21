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

$scale_min = 0;
$colours   = "mixed";
$nototal   = 1;
$unit_text = "Queries/sec";

$thread = 0;

$i = 0;

while (TRUE) {
    $rrd_filename = get_rrd_path($device, "app-unbound-" . $app['app_id'] . "-thread$thread.rrd");
    if (rrd_is_file($rrd_filename, TRUE)) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = "Queries handled by thread$thread";
        $rrd_list[$i]['ds']       = "numQueries";
        $rrd_list[$i]['colour']   = $config['graph_colours'][$colours][$i % safe_count($config['graph_colours'][$colours])];
        $i++;

        $thread++;
    } else {
        break;
    }
}

$rrd_filename = get_rrd_path($device, "app-unbound-" . $app['app_id'] . "-total.rrd");

$array = [
  'numQueries' => ['descr' => 'Total queries', 'colour' => '00FF00FF'], /// FIXME better colours
  'cacheHits'  => ['descr' => 'Cache hits', 'colour' => '0000FFFF'],
  'prefetch'   => ['descr' => 'Cache prefetch', 'colour' => 'FF0000FF'],
];

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

$rrd_filename = get_rrd_path($device, "app-unbound-" . $app['app_id'] . "-queries.rrd");

$array = [
  'numQueryTCP'      => ['descr' => 'TCP queries', 'colour' => '00FFFFFF'],
  'numQueryIPv6'     => ['descr' => 'IPv6 queries', 'colour' => '00FFFFFF'],
  'numQueryUnwanted' => ['descr' => 'Unwanted queries', 'colour' => '00FFFFFF'],
  'numReplyUnwanted' => ['descr' => 'Unwanted replies', 'colour' => '00FFFFFF'], /// FIXME better colours
];

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

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
