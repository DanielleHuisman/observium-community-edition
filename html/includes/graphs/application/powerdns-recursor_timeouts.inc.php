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
$unit_text    = "Queries/sec";
$rrd_filename = get_rrd_path($device, "app-powerdns-recursor-" . $app['app_id'] . ".rrd");
$array        = [
  'outQ_all'        => ['descr' => 'Outqueries', 'colour' => 'FF0000FF'],
  'timeoutOutgoing' => ['descr' => 'Outgoing timeouts', 'colour' => '00FF00FF'],
  'outQ_throttled'  => ['descr' => 'Throttled outqueries', 'colour' => '0000FFFF'],
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

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
