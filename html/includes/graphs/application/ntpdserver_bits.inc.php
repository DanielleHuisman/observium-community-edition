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

$nototal = 1;

$ds_in  = "packets_recv";
$ds_out = "packets_sent";

$graph_title .= "::packets";
$unit_text   = "Packets";

$colour_line_in      = $config['colours']['graphs']['pkts']['in_line'];
$colour_line_out     = $config['colours']['graphs']['pkts']['out_line'];
$colour_area_in      = $config['colours']['graphs']['pkts']['in_area'];
$colour_area_out     = $config['colours']['graphs']['pkts']['out_area'];
$colour_area_in_max  = $config['colours']['graphs']['pkts']['in_max'];
$colour_area_out_max = $config['colours']['graphs']['pkts']['out_max'];

$ntpdserver_rrd = get_rrd_path($device, "app-ntpdserver-" . $app['app_id'] . ".rrd");

if (rrd_is_file($ntpdserver_rrd)) {
    $rrd_filename = $ntpdserver_rrd;
}

include($config['html_dir'] . "/includes/graphs/generic_duplex.inc.php");

// EOF
