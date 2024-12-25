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

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$drbd_rrd = get_rrd_path($device, "app-drbd-" . $app['app_instance'] . ".rrd");

if (rrd_is_file($drbd_rrd)) {
    $rrd_filename = $drbd_rrd;
}

$ds_in  = "nr";
$ds_out = "ns";

$multiplier = "1024";

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

// EOF
