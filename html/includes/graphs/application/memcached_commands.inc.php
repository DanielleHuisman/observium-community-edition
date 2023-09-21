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

include("memcached.inc.php");
include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$nototal = 1;

$ds_in  = "cmd_set";
$ds_out = "cmd_get";

$in_text  = "Set";
$out_text = "Get";

$graph_title         .= ":: Commands";
$unit_text           = "Commands";
$colour_line_in      = "008800FF";
$colour_line_out     = "000088FF";
$colour_area_in      = "bEFFbEAA";
$colour_area_out     = "bEbEFFAA";
$colour_area_in_max  = $config['colours']['graphs']['pkts']['in_max'];
$colour_area_out_max = $config['colours']['graphs']['pkts']['out_max'];

include($config['html_dir'] . "/includes/graphs/generic_duplex.inc.php");

// EOF
