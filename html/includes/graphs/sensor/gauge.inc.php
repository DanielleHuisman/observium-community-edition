<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$ds = "sensor";

$line_text = rrdtool_escape($sensor['sensor_descr'], 20);

$colour_line = "cc0000";
$colour_area = "FFBBBB";
$colour_minmax = "c5c5c5";

$graph_max = 1;
$unit_text = "Gauge";
$print_min = 1;

include($config['html_dir']."/includes/graphs/generic_simplex.inc.php");

$graph_return['descr'] = 'Gauges.';

// EOF
