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

$scale_min = 0;

$rrd_filename = get_rrd_path($device, "netscaler-stats-tcp.rrd");

$ds = "ErrRetransmit";

$colour_area = "ef3b2c";
$colour_line = "67000d";

$colour_area_max = "dddddd";

$graph_max = 1;

$unit_text = "Retransmits/s";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
