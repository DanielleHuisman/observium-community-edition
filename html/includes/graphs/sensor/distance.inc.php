<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

$ds = "sensor";

$line_text = rrdtool_escape($sensor['sensor_descr'], 22);

$colour_line = "cc1100";
$colour_area = "FFFF99";
$colour_minmax = "c5c5c5";

$scale_min = 0;
$graph_max = 1;
$unit_text = "m";
$print_min = 1;

include($config['html_dir']."/includes/graphs/generic_simplex.inc.php");

if (is_numeric($sensor['sensor_limit']))     { $rrd_options .= " HRULE:".$sensor['sensor_limit']."#99999960::dashes"; }
if (is_numeric($sensor['sensor_limit_low'])) { $rrd_options .= " HRULE:".$sensor['sensor_limit_low']."#99999960::dashes"; }

$graph_return['descr'] = 'Distance sensor measured in m.';

// EOF
