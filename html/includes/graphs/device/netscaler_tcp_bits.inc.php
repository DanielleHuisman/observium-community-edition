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

$rrd_filename = get_rrd_path($device, "netscaler-stats-tcp.rrd");

$ds_in  = "TotRxBytes";
$ds_out = "TotTxBytes";

$multiplier = 8;

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

// EOF
