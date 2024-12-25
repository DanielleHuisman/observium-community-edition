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
$rrd_filename = get_rrd_path($device, "app-dovecot.rrd");
$array        = [
  'auth_successes'      => ['descr' => 'Successful auths / s'],
  'auth_master_success' => ['descr' => 'Master auths / s'],
  'auth_failures'       => ['descr' => 'Failed auths / s'],
  'auth_db_tempfails'   => ['descr' => 'Temporary failures / s']
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

include($config['html_dir'] . "/includes/graphs/generic_multi.inc.php");

// EOF
