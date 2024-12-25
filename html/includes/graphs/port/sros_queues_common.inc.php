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

$units       = "b";
$total_units = "B";
$scale_min   = 0;
$nototal     = 1;


$queues = json_decode(get_entity_attrib('port', $port['port_id'], 'sros_' . $dir . '_queues'));

//print_r($queues);

foreach ($queues as $queue) {


    if (isset($config['sros_queues'][$dir]['labels'][$queue])) {
        $label = $config['sros_queues'][$dir]['labels'][$queue] . ' (' . $queue . ')';
    } else {
        $label = 'Queue ' . $queue;
    }

    $rrd_filename                 = $config['rrd_dir'] . '/' . $device['hostname'] . '/' . 'port-' . get_port_rrdindex($port) . '-' . $queue . '-sros_' . $dir . '_qstat.rrd';
    $rrd_filenames[]              = $rrd_filename;
    $rrd_list[$queue]['filename'] = $rrd_filename;
    $rrd_list[$queue]['descr']    = $label;
    $rrd_list[$queue]['ds']       = $ds;
}

//print_r($rrd_list);

$colours = 'mixed';

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

