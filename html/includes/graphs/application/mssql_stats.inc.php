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

$colours      = "mixed";
$nototal      = 1;
$unit_text    = "Connections";
$rrd_filename = get_rrd_path($device, "wmi-app-mssql_" . $app['app_instance'] . "-stats.rrd");

if (rrd_is_file($rrd_filename)) {
    $rrd_list[0]['filename'] = $rrd_filename;
    $rrd_list[0]['descr']    = "User Connections";
    $rrd_list[0]['ds']       = "userconnections";

} else {
    echo("file missing: $rrd_filename");
}

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
