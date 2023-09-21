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

$ds = "AvgSvrTTFB";

$colour_area = $config['colours']['graphs']['pkts']['in_area'];
$colour_line = $config['colours']['graphs']['pkts']['in_line'];

$colour_area_in = $config['colours']['graphs']['pkts']['in_max'];

$text = "Client";

$graph_max = 1;
$unit_text = "ms to 1st byte";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");
