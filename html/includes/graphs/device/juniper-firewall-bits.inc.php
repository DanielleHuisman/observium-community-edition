<?php

$filter  = $vars['filter'];
$counter = $vars['counter'];
$c_type  = $vars['counter_type'];

$rrd_filename = get_rrd_path($device, 'juniper-firewall-' . $filter . '-' . $counter . '-' . $c_type);

$ds         = "bytes";
$multiplier = 8;

$colour_line = $config['colours']['graphs']['data']['in_line'];
$colour_area = $config['colours']['graphs']['data']['in_area'];

$unit_text = "Bits/s";


include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

