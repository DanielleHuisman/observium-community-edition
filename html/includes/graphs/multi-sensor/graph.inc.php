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


$units       = '';
$unit_text   = 'Value';
$total_units = '';

$i = 1;

$rrd_list = [];

foreach ($vars['id'] as $port_id) {

    $sensor = dbFetchRow("SELECT * FROM `sensors` WHERE `sensor_id` = ?", [$port_id]);

    $device = device_by_id_cache($sensor['device_id']);

    $rrd_filename = get_rrd_path($device, get_sensor_rrd($device, $sensor));

    $rrd_list[$i]['filename'] = $rrd_filename;
    $rrd_list[$i]['descr']    = rewrite_entity_name($sensor['sensor_descr'], 'sensor');
    $rrd_list[$i]['ds']       = 'sensor';

    $i++;
}

$colours = 'mixed';

$scale_min  = "0";
$nototal    = 1;
$simple_rrd = TRUE;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF

