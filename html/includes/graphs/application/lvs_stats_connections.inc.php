<?php

$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/common.inc.php");

include("lvs_stats_common.inc.php");

$colour_area = "B0C4DE";
$colour_line = "191970";
#$colour_area_max = "FFEE99";
$colour_area_max = "B0C4DE";

$ds = "connections";

$graph_max = 1;

$unit_text = "Con/sec";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
