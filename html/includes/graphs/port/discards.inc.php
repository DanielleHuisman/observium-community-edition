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

$ds_in  = "INDISCARDS";
$ds_out = "OUTDISCARDS";

$colour_area_in = $config['colours']['graphs']['pkts']['in_area'];
$colour_line_in = darken_color($colour_area_in);
//$colour_line_in = $config['colours']['graphs']['pkts']['in_line'];
$colour_area_out = $config['colours']['graphs']['pkts']['out_area'];
$colour_line_out = $config['colours']['graphs']['pkts']['out_line'];

$colour_area_in_max  = $config['colours']['graphs']['pkts']['in_max'];
$colour_area_out_max = $config['colours']['graphs']['pkts']['out_max'];

$graph_max = 1;
$unit_text = "Packets/s";

$args['nototal'] = 1;
$print_total     = 0;
$nototal         = 1;

include($config['html_dir'] . "/includes/graphs/generic_duplex.inc.php");

// EOF
