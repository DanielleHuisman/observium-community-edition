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

$scale_min = 0;

$rrd_filename = get_rrd_path($device, "netstats-tcp.rrd");

$ds = "tcpCurrEstab";

$colour_area = "ef3b2c";
$colour_line = "80000d";

$colour_area_max = "dddddd";

$graph_max = 1;

$unit_text = "Established";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
