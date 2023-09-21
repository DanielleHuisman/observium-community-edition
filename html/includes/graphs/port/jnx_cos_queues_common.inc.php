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

$scale_min = 0;

$nototal = 1;

$queue_list = json_decode(get_entity_attrib('port', $port['port_id'], 'jnx_cos_queues'));

$queues = [];

foreach (json_decode(get_entity_attrib('device', $device['device_id'], 'jnx_cos_queues'), TRUE) as $data) {
    if (isset($data['queue'])) {
        $queues[$data['queue']] = $data;
    }
}


//print_r($queues);

foreach ($queue_list as $queue) {
    $rrd_filename                 = $config['rrd_dir'] . '/' . $device['hostname'] . '/' . 'port-' . get_port_rrdindex($port) . '-' . $queue . '-jnx_cos_qstat.rrd';
    $rrd_filenames[]              = $rrd_filename;
    $rrd_list[$queue]['filename'] = $rrd_filename;

    $text = 'Queue ' . $queue;
    if (isset($queues[$queue]['name'])) {
        $text = $queues[$queue]['name'] . ' (' . $queue . ')';
    }
    if (isset($queues[$queue]['prio'])) {
        $text .= ' (' . $queues[$queue]['prio'] . ')';
    }

    $rrd_list[$queue]['descr'] = $text;
    $rrd_list[$queue]['ds']    = $ds;
}

$colours = 'mixed';

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

