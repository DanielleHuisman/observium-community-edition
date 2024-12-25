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

$rrd_filename = get_rrd_path($device, "aethra-mib_xdsl.rrd");

$ds_in  = "DsTotBytes";
$ds_out = "UsTotBytes";
$format = "octets";

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

// EOF
