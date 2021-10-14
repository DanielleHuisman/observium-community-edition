<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$scale_min = 0;

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$openvpn_rrd = get_rrd_path($device, "app-openvpn-" . $app['app_instance'] . ".rrd");

if (rrd_is_file($openvpn_rrd))
{
  $rrd_filename = $openvpn_rrd;
}

$ds = "nclients";

$colour_area = "B0C4DE";
$colour_line = "191970";

$colour_area_max = "FFEE99";

$graph_max = 1;

$unit_text = "Clients";

include($config['html_dir']."/includes/graphs/generic_simplex.inc.php");

// EOF
