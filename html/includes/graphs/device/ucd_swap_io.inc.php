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

$rrd_filename_in  = get_rrd_path($device, "ucd_ssRawSwapIn.rrd");
$rrd_filename_out = get_rrd_path($device, "ucd_ssRawSwapOut.rrd");
$ds_in            = "value";
$ds_out           = "value";

$format = 'blocks';
//$multiplier = 512; // not correct block conversion, this multiplier can be different

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

// EOF
