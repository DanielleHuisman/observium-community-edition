<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

$ds = "sensor";

$line_text = rrdtool_escape($sensor['sensor_descr'], 22);

$colour_line = "CC0000";
$colour_minmax = "FF5555";

$scale_min = 0;
$graph_max = 60;
$unit_text = "C";
$print_min = 1;

include($config['html_dir']."/includes/graphs/generic_simplex.inc.php");

if (is_numeric($sensor['sensor_limit']))     { $rrd_options .= " HRULE:".$sensor['sensor_limit']."#999999::dashes"; }
if (is_numeric($sensor['sensor_limit_low'])) { $rrd_options .= " HRULE:".$sensor['sensor_limit_low']."#999999::dashes"; }

$graph_return['descr'] = 'Temperature sensor measured in celsius.';

// EOF
