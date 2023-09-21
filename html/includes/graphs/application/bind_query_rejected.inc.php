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
  'AuthQryRej'      => ['descr' => "Auth queries rejected", 'colour' => '6495ed'],
  'RecQryRej'       => ['descr' => "Recursive queries rejected", 'colour' => '40e0d0'],
  'XfrRej'          => ['descr' => "Transfer requests rejected", 'colour' => 'ffd700'],
  'UpdateRej'       => ['descr' => "Update requests rejected", 'colour' => 'cd853f'],
  'UpdateBadPrereq' => ['descr' => "Updates rejected due to prereq fail", 'colour' => 'ff8c00'],
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
