<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     webui
 * @copyright  (C) Adam Armstrong
 *
 */

global $config;

$graph_array['height'] = "100";
$graph_array['width']  = "215";
$graph_array['to']     = get_time();
$graph_array['id']     = $ap['accesspoint_id'];
$graph_array['type']   = $graph_type;

print_graph_row($graph_array);

// EOF
