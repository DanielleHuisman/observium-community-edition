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

$ds_in  = "net_rx";
$ds_out = "net_tx";

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

?>
