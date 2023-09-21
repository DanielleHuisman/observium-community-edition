<?php

$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/common.inc.php");

include("lvs_stats_common.inc.php");

$ds = "bytes";

$colour_area = "F0E68C";
$colour_line = "FF4500";

$colour_area_max = "FFEE99";

$graph_max = 1;

$unit_text = "bytes/sec";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
