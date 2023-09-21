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

$rrd_filename = get_rrd_path($device, 'app-mongodb-' . $app["app_id"] . '.rrd');

$array = ['vsize' => 'Virtual memory',
          'res'   => 'Resident memory',
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
$unit_text = "Bytes";
$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
