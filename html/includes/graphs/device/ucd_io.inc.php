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

$rrd_filename_in  = get_rrd_path($device, "ucd_ssIORawReceived.rrd");
$rrd_filename_out = get_rrd_path($device, "ucd_ssIORawSent.rrd");
$ds_in            = "value";
$ds_out           = "value";

$format = 'blocks';
//$multiplier = 512; // not correct block conversion, this multiplier can be different

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

// EOF
