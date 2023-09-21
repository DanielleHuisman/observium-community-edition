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


$dir   = ($vars['dir'] == 'egress' ? 'egress' : 'ingress');
$queue = (is_numeric($vars['queue']) ? $vars['queue'] : 1);

$metrics = ['FwdInProfOcts',
            'FwdOutProfOcts',
            'FwdInProfPkts',
            'FwdOutProfPkts',
            'DroInProfOcts',
            'DroOutProfOcts',
            'DroInProfPkts',
            'DroOutProfPkts'];

if (in_array($vars['ds'], $metrics)) {
    $ds = $vars['ds'];
} else {
    $ds = array_pop($metrics);
}

if (strstr("Octs", $ds)) {
    $multiplier = 8;
}

$rows = dbFetchRows("SELECT * FROM `entity_attribs` WHERE `attrib_type` = 'sros_" . $dir . "_queues'");

//print_r($rows);

//$queues = json_decode(get_entity_attrib('port', $port['port_id'], 'sros_'.$dir.'_queues'));

//print_r($queues);

foreach ($rows as $row) {

    $queues = json_decode($row['attrib_value']);

    if (in_array($queue, $queues)) {

        $port   = get_port_by_id_cache($row['entity_id']);
        $device = device_by_id_cache($port['device_id']);

        $rrd_filename = $config['rrd_dir'] . '/' . $device['hostname'] . '/' . 'port-' . get_port_rrdindex($port) . '-' . $queue . '-sros_' . $dir . '_qstat.rrd';
        $rrd_list[]   = ['filename' => $rrd_filename,
                         'descr'    => $device['hostname'] . ' ' . $port['port_label_short'],
                         'ds'       => $ds];
    }
}

//print_r($rrd_list);

$colours = 'mixed';

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

