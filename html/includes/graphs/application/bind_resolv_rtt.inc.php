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
  'QryRTT10'       => ['descr' => "< 10ms", 'colour' => '00d200'],
  'QryRTT100'      => ['descr' => "10-100ms", 'colour' => '26ac00'],
  'QryRTT500'      => ['descr' => "100-500ms", 'colour' => '498900'],
  'QryRTT800'      => ['descr' => "500-800ms", 'colour' => '894900'],
  'QryRTT1600'     => ['descr' => "800-1600ms", 'colour' => 'ac2600'],
  'QryRTT1600plus' => ['descr' => "> 1600ms", 'colour' => 'd20000'],
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
