<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2014 Adam Armstrong
 *
 */

$rrd_filename = get_rrd_path($device, "panos-gptunnels.rrd");

$ds = "gptunnels";

$colour_area = "9999cc";
$colour_line = "0000cc";

$colour_area_max = "9999cc";

$graph_max = 1;

$unit_text = "GlobalProtect Tunnels";

include("includes/graphs/generic_simplex.inc.php");

?>
