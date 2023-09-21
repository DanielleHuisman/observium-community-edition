<?php

$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/common.inc.php");

include("lvs_stats_common.inc.php");

$ds = "packets";

$colour_area = "CDEB8B";
$colour_line = "006600";

#$colour_area_max = "FFEE99";
$colour_area_max = "CDEB8B";

$graph_max = 1;

$unit_text = "Packets/sec";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
