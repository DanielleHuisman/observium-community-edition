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

$ds              = "numasoclients";
$colour_line     = "008C00";
$colour_area     = "CDEB8B";
$colour_area_max = "c0c0c0";
$graph_max       = 1;
$graph_min       = 0;
$unit_text       = "Clients";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
