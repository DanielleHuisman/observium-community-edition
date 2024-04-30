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

$graph_max = 1;

$ds = "TotConns";

$colour_area = "B0C4DE";
$colour_line = "191970";

$colour_area_max = "FFEE99";

$nototal   = 1;
$unit_text = "Conn/sec";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
