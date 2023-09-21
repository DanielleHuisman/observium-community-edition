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

$rrd_filename = get_rrd_path($device, "netscaler-stats-tcp.rrd");

$ds_in  = "TotRxBytes";
$ds_out = "TotTxBytes";

$colour_area_in  = $config['colours']['graphs']['pkts']['in_area'];
$colour_line_in  = $config['colours']['graphs']['pkts']['in_line'];
$colour_area_out = $config['colours']['graphs']['pkts']['out_area'];
$colour_line_out = $config['colours']['graphs']['pkts']['out_line'];

$colour_area_in_max  = $config['colours']['graphs']['pkts']['in_max'];
$colour_area_out_max = $config['colours']['graphs']['pkts']['out_max'];

$graph_max = 1;
$unit_text = "Packets";

include($config['html_dir'] . "/includes/graphs/generic_duplex.inc.php");

// EOF
