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
$rrd_filename = get_rrd_path($device, "app-bind-" . $app['app_id'] . "-resolver-default.rrd");

$array = [
  'ValAttempt' => ['descr' => "Attempted validation", 'colour' => '4242CC', 'invert' => TRUE],
  'ValOk'      => ['descr' => "Succeeded validation", 'colour' => '33A533'],
  'ValNegOk'   => ['descr' => "NX Succeeded validation", 'colour' => 'FFA500'],
  'ValFail'    => ['descr' => "Failed validation", 'colour' => 'ff0000'],
];
$i     = 0;

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

include($config['html_dir'] . "/includes/graphs/generic_multi.inc.php");

// EOF
