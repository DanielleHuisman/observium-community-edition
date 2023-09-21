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
$nototal      = (($width < 550) ? 1 : 0);
$unit_text    = "Access/sec";
$rrd_filename = get_rrd_path($device, "app-freeradius-" . $app['app_id'] . ".rrd");
$array        = [
  'AccessAccepts'     => ['descr' => 'Access Accepts'],
  'AccessChallenges'  => ['descr' => 'Access Challenges'],
  'AccessRejects'     => ['descr' => 'Access Rejects'],
  'AccessReqs'        => ['descr' => 'Access Requests'],
  'AuthDroppedReqs'   => ['descr' => 'Auth Dropped Requests'],
  'AuthDuplicateReqs' => ['descr' => 'Auth Duplicate Requests'],
  'AuthInvalidReqs'   => ['descr' => 'Auth Invalid Requests'],
  'AuthMalformedReqs' => ['descr' => 'Auth Malformed Requests'],
  'AuthResponses'     => ['descr' => 'Auth Responses'],
  'AuthUnknownTypes'  => ['descr' => 'Auth Unknown Types'],
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
