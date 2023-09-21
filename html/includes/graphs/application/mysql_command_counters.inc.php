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

$array = ['CDe'  => ['descr' => 'Delete', 'colour' => '22FF22'],
          'CIt'  => ['descr' => 'Insert', 'colour' => '0022FF'],
          'CISt' => ['descr' => 'Insert Select', 'colour' => 'FF0000'],
          'CLd'  => ['descr' => 'Load Data', 'colour' => '00AAAA'],
          'CRe'  => ['descr' => 'Replace', 'colour' => 'FF00FF'],
          'CRSt' => ['descr' => 'Replace Select', 'colour' => 'FFA500'],
          'CSt'  => ['descr' => 'Select', 'colour' => 'CC0000'],
          'CUe'  => ['descr' => 'Update', 'colour' => '0000CC'],
          'CUMi' => ['descr' => 'Update Multiple', 'colour' => '0080C0'],
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
