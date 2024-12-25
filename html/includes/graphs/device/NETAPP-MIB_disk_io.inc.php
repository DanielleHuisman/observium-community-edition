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

$rrd_filename = get_rrd_path($device, "netapp-mib_misc.rrd");

$ds_in  = "DiskReadBytes";
$ds_out = "DiskWriteBytes";

$format = "octets";

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");