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

$rrd_filename = get_rrd_path($device, "cipsec_flow.rrd");

$ds_in  = "InOctets";
$ds_out = "OutOctets";

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

?>
