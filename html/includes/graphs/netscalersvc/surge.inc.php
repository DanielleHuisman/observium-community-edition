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

$ds = "SurgeCount";

$colour_area = $config['colours']['graphs']['pkts']['in_area'];
$colour_line = $config['colours']['graphs']['pkts']['in_line'];

$colour_area_in = $config['colours']['graphs']['pkts']['in_max'];

$text = "Client";

$graph_max = 1;
$unit_text = "Surge Count";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");
