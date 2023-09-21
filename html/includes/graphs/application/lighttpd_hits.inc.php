<?php

$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/common.inc.php");

$colour_area = "B0C4DE";
$colour_line = "191970";
#$colour_area_max = "FFEE99";
$colour_area_max = "B0C4DE";

$rrd_filename = get_rrd_path($device, "app-lighttpd-" . $app['app_id']);

$ds = "totalaccesses";

$graph_max = 1;

$unit_text = "Hits/Sec";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
