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

$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/common.inc.php");

$ds = "DropPkt";

$colour_area = "CDEB8B";
$colour_line = "006600";

$colour_area_max = "FFEE99";

$graph_max = 1;
#$multiplier = 8;

$unit_text = "Pkts";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
