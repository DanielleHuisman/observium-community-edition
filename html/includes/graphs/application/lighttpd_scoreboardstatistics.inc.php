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

$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "app-lighttpd-" . $app['app_id']);

$array = ['connectionsp' => ['descr' => 'Connect', 'colour' => '750F7DFF'],
          'connectionsC' => ['descr' => 'Close', 'colour' => '00FF00FF'],
          'connectionsE' => ['descr' => 'Hard Error', 'colour' => '4444FFFF'],
          'connectionsk' => ['descr' => 'Keep-alive', 'colour' => '157419FF'],
          'connectionsr' => ['descr' => 'Read', 'colour' => 'FF0000FF'],
          'connectionsR' => ['descr' => 'Read-POST', 'colour' => '6DC8FEFF'],
          'connectionsW' => ['descr' => 'Write', 'colour' => 'FFAB00FF'],
          'connectionsh' => ['descr' => 'Handle-request', 'colour' => 'FFFF00FF'],
          'connectionsq' => ['descr' => 'Request-start', 'colour' => 'FF5576FF'],
          'connectionsQ' => ['descr' => 'Request-end', 'colour' => 'FF3005FF'],
          'connectionss' => ['descr' => 'Response-start', 'colour' => '800080'],
          'connectionsS' => ['descr' => 'Response-end', 'colour' => '959868'],
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

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Workers";

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
