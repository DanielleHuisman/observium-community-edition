<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */

$rrd_filename = get_rrd_path($device, "aruba-controller.rrd");

$ds          = "NUMCLIENTS";
$colour_line = "008C00";
$colour_area = "CDEB8B";
//$colour_area_max = "cc9999";
$unit_text = "Clients";
$line_text = 'Clients';
$scale_min = 0;

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

// EOF
