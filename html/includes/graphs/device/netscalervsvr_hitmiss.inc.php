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

$ds_in  = "TotHits";
$ds_out = "TotMiss";

$unit_text = "Hit/Miss";

include("netscalervsvr.inc.php");

$units       = 'pps';
$total_units = 'Pkts';
$multiplier  = 1;
$colours_in  = 'greens';
$colours_out = 'oranges';

#$nototal = 1;

$graph_title .= "::packets";

include($config['html_dir'] . "/includes/graphs/generic_multi_separated.inc.php");

// EOF
