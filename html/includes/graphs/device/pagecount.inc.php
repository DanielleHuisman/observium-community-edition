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

$rrd_list = [];
foreach (dbFetchRows("SELECT * FROM `sensors` WHERE `sensor_class` = ? AND `sensor_descr` LIKE ? AND `device_id` = ? ORDER BY `sensor_index`", ['counter', '%print%', $device['device_id']]) as $sensor) {
    $rrd_filename = get_rrd_path($device, get_sensor_rrd($device, $sensor));

    if (($auth == TRUE || is_entity_permitted($sensor['sensor_id'], 'sensor')) && rrd_is_file($rrd_filename)) {
        //if (!str_contains_array($sensor['sensor_descr'], array('Total', 'Printed'))) { continue; } // FIXME, currently show only Total here
        //if (!str_icontains_array($sensor['sensor_type'], 'print')) { continue; }

        $descr      = rewrite_entity_name($sensor['sensor_descr'], 'sensor');
        $rrd_list[] = ['filename' => $rrd_filename,
                       'descr'    => $descr,
                       'ds'       => "sensor"];
    }
}

$unit_text = "Pages";

$units       = '%';
$total_units = '%';
$colours     = 'mixed-10c';
$nototal     = 1;
$scale_rigid = FALSE;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
