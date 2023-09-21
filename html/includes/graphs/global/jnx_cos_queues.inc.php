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

$units       = "b";
$total_units = "B";

$nototal = 1;

$queue = (is_numeric($vars['queue']) ? $vars['queue'] : 1);

$metrics = ['QedBytes',
            'QedPkts',
            'TailDropPkts',
            'TotalRedDropPkts',
            'TotalRedDropBytes',
];

if (in_array($vars['ds'], $metrics)) {
    $ds = $vars['ds'];
} else {
    $ds = array_pop($metrics);
}

switch ($ds) {
    case 'QedBytes':
    case 'TotalRedDropBytes':
        $multiplier = 8;
        break;
    default:
        break;
}


$rows = dbFetchRows("SELECT * FROM `entity_attribs` WHERE `attrib_type` = 'jnx_cos_queues'");

foreach ($rows as $row) {

    $queues = json_decode($row['attrib_value']);

    if (in_array($queue, $queues)) {

        $port   = get_port_by_id_cache($row['entity_id']);
        $device = device_by_id_cache($port['device_id']);

        $rrd_filename = $config['rrd_dir'] . '/' . $device['hostname'] . '/' . 'port-' . get_port_rrdindex($port) . '-' . $queue . '-jnx_cos_qstat.rrd';
        $rrd_list[]   = ['filename' => $rrd_filename,
                         'descr'    => $device['hostname'] . ' ' . $port['port_label_short'],
                         'ds'       => $ds];
    }
}

//print_r($rrd_list);

$colours = 'mixed';

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

