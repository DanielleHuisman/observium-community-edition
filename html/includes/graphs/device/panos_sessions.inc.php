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

$rrd_filename = get_rrd_path($device, "panos-sessions.rrd");

$ds = "sessions";

$colour_area = "9999cc";
$colour_line = "0000cc";

$colour_area_max = "9999cc";

$graph_max = 1;

$unit_text = "Sessions";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
