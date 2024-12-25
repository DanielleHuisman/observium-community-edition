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

$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/common.inc.php");

$ds = "PrePolicyByte";

$colour_area = "CDEB8B";
$colour_line = "006600";

$colour_area_max = "FFEE99";

$graph_max  = 1;
$multiplier = 8;

$unit_text = "Bps";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
