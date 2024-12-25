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

$ds_in  = "PacketCounts";
$ds_out = "ClientPacketCounts";

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
