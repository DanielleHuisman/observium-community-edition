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

$rrd_filename = get_rrd_path($device, "asyncos_workq.rrd");

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$ds = "DEPTH";

$colour_area = "9999cc";
$colour_line = "0000cc";

$colour_area_max = "9999cc";

$unit_text = "Messages";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
