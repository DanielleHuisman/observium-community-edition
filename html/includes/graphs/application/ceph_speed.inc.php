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

$mysql_rrd = get_rrd_path($device, "app-ceph-" . $app['app_id'] . ".rrd");

if (rrd_is_file($mysql_rrd)) {
    $rrd_filename = $mysql_rrd;
}

$format = "bytes";

$ds_in  = "wrbps";
$ds_out = "rdbps";

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

// EOF
