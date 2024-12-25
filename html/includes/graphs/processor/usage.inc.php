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

$scale_min = "0";
$scale_max = "100";

$ds = "usage";

$descr = rrdtool_escape(rewrite_entity_name($proc['processor_descr'], 'processor'), 28);

$colour_line   = "cc0000";
$colour_area   = "FFBBBB";
$colour_minmax = "c5c5c5";

$graph_max = 1;
$unit_text = "Usage";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
