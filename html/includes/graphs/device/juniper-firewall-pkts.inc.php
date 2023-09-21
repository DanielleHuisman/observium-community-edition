<?php

$filter  = $vars['filter'];
$counter = $vars['counter'];
$c_type  = $vars['counter_type'];

$rrd_filename = get_rrd_path($device, 'juniper-firewall-' . $filter . '-' . $counter . '-' . $c_type);

$ds = "pkts";

$unit_text   = "Pkts/s";
$colour_line = "330033";
$colour_area = "AA66AA";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

