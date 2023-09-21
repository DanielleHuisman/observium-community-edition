<?php

$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_filename = get_rrd_path($device, "app-lighttpd-" . $app['app_id']);

$ds = "totalkbytes";

$colour_area = "CDEB8B";
$colour_line = "006600";

$colour_area_max = "FFEE99";

$graph_max  = 1;
$multiplier = 8;

$unit_text = "Kbps";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
