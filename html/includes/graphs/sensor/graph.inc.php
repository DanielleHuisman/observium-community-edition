<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Some Class specific options
switch ($sensor['sensor_class']) {
    case 'humidity':
    case 'capacity':
    case 'load':
    case 'progress':
        $scale_min = "0";
        $scale_max = "100";
        break;

    case 'snr':
    case 'attenuation':
    case 'sound':
        $scale_min = "0";
        $scale_max = "60";
        break;

    case 'waterflow':
        $alt_y = FALSE; // Disable alternative Y grid for waterflow
    default:
        $scale_rigid = FALSE;
}

$ds = "sensor";

$sensor_type = $config['sensor_types'][$sensor['sensor_type']];

$line_text = $sensor['sensor_descr'];

$colour_line   = "cc0000";
$colour_area   = "FFBBBB";
$colour_minmax = "c5c5c5";

$graph_max = 1;
if (isset($sensor_type['graph_unit'])) {
    $unit_text = $sensor_type['graph_unit'];
} else {
    $unit_text = $sensor_type['symbol'];
}
$print_min = 1;

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

if (is_numeric($sensor['sensor_limit'])) {
    $rrd_options .= " HRULE:" . $sensor['sensor_limit'] . "#999999::dashes";
}
if (is_numeric($sensor['sensor_limit_low'])) {
    $rrd_options .= " HRULE:" . $sensor['sensor_limit_low'] . "#999999::dashes";
}

$graph_return['descr'] = $sensor_type['descr'];


// EOF
