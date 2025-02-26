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

$rrd_filename = get_rrd_path($device, 'app-mysql-' . $app["app_id"] . '.rrd');

$array = ['IBRd' => 'Pages Read',
          'IBCd' => 'Pages Created',
          'IBWr' => 'Pages Written',
];

$i = 0;
if (rrd_is_file($rrd_filename)) {
    foreach ($array as $ds => $data) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        if (is_array($data)) {
            $rrd_list[$i]['descr'] = $data['descr'];
        } else {
            $rrd_list[$i]['descr'] = $data;
        }
        $rrd_list[$i]['ds'] = $ds;
        $i++;
    }
} else {
    echo("file missing: $rrd_filename");
}

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Activity";

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
