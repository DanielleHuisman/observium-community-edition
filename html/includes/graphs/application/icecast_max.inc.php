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

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$scale_min       = 0;
$ds              = "max";
$colour_area     = "FFCECE";
$colour_line     = "880000";
$colour_area_max = "FFCCCC";
$graph_max       = 0;
$unit_text       = "Max";
$icecast_rrd     = get_rrd_path($device, "app-icecast-" . $app['app_id'] . ".rrd");

if (rrd_is_file($icecast_rrd)) {
    $rrd_filename = $icecast_rrd;
}

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
