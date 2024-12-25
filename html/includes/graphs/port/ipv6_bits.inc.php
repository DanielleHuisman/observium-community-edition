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

$rrd_filename = get_port_rrdfilename($port, "ipv6-octets", TRUE);

$ds_in  = "InOctets";
$ds_out = "OutOctets";

$graph_max = 1;

include($config['html_dir'] . "/includes/graphs/generic_data.inc.php");

// EOF