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

$ds_in  = "TotalClients";
$ds_out = "TotalServers";

$unit_text = "Connections";

include("netscalersvc.inc.php");

$units       = 'pps';
$total_units = 'Pkts';
$multiplier  = 1;
$colours_in  = 'purples';
$colours_out = 'oranges';

#$nototal = 1;

$graph_title .= "::connections";

include($config['html_dir'] . "/includes/graphs/generic_multi_separated.inc.php");

// EOF
