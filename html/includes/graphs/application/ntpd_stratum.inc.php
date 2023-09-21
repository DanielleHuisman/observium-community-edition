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

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$scale_min       = 0;
$ds              = "stratum";
$colour_area     = "FFCECE";
$colour_line     = "880000";
$colour_area_max = "FFCCCC";
$graph_max       = 0;
$unit_text       = "Stratum";
$ntpdserver_rrd  = get_rrd_path($device, "app-ntpd-server-" . $app['app_id'] . ".rrd");

if (rrd_is_file($ntpdserver_rrd)) {
    $rrd_filename = $ntpdserver_rrd;
}

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
