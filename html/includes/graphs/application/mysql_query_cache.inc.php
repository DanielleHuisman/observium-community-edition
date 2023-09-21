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

$mysql_rrd = get_rrd_path($device, "app-mysql-" . $app['app_id'] . ".rrd");

if (rrd_is_file($mysql_rrd)) {
    $rrd_filename = $mysql_rrd;
}

$array = ['QCQICe' => ['descr' => 'Queries in cache', 'colour' => '22FF22'],
          'QCHs'   => ['descr' => 'Cache hits', 'colour' => '0022FF'],
          'QCIs'   => ['descr' => 'Inserts', 'colour' => 'FF0000'],
          'QCNCd'  => ['descr' => 'Not cached', 'colour' => '00AAAA'],
          'QCLMPs' => ['descr' => 'Low-memory prunes', 'colour' => 'FF00FF'],
];

$i = 0;
if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $data['descr'];
        $rrd_list[$i]['ds']       = $ds;
#    $rrd_list[$i]['colour'] = $data['colour'];
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Commands";

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
