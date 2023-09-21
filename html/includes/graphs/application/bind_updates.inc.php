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

$colours      = "mixed";
$nototal      = (($width < 224) ? 1 : 0);
$unit_text    = "Count";
$rrd_filename = get_rrd_path($device, "app-bind-" . $app['app_id'] . "-ns-stats.rrd");

$array = [
  'UpdateDone'      => ['descr' => "Completed", 'colour' => '228b22'],
  'UpdateFail'      => ['descr' => "Failed", 'colour' => 'ff0000'],
  'UpdateRej'       => ['descr' => "Rejected", 'colour' => 'cd853f'],
  'UpdateBadPrereq' => ['descr' => "Rejected due to prereq fail", 'colour' => 'ff8c00'],
  'UpdateReqFwd'    => ['descr' => "Fwd request", 'colour' => '6495ed'],
  'UpdateRespFwd'   => ['descr' => "Fwd response", 'colour' => '40e0d0'],
  'UpdateFwdFail'   => ['descr' => "Fwd failed", 'colour' => 'ffd700'],
];
$i     = 0;

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

include($config['html_dir'] . "/includes/graphs/generic_multi.inc.php");

// EOF
