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

$rrd_filename = get_rrd_path($device, 'app-mysql-' . $app["app_id"] . '.rrd');

$array = ['MaCs' => ['descr' => 'Max Connections', 'colour' => '22FF22'],
          'MUCs' => ['descr' => 'Max Used Connections', 'colour' => '0022FF'],
          'ACs'  => ['descr' => 'Aborted Clients', 'colour' => 'FF0000'],
          'AdCs' => ['descr' => 'Aborted Connects', 'colour' => '0080C0'],
          'TCd'  => ['descr' => 'Threads Connected', 'colour' => 'FF0000'],
          'Cs'   => ['descr' => 'New Connections', 'colour' => '0080C0'],
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
$unit_text = "Connections";

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
