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

$rrd_filename = get_rrd_path($device, "netapp_stats.rrd");

$format = "bytes";
$ds_in  = "tape_rd";
$ds_out = "tape_wr";

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

?>
