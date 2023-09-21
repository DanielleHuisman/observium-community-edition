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

$mongo_rrd = get_rrd_path($device, "app-mongodb-" . $app['app_id'] . ".rrd");

if (rrd_is_file($mongo_rrd)) {
    $rrd_filename = $mongo_rrd;
}

$ds_in  = "net_in";
$ds_out = "net_out";

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

// EOF
