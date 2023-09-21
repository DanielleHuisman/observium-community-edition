<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$rrd_filename = get_rrd_path($device, "aruba-controller.rrd");

$ds              = "NUMAPS";
$colour_line     = "8C0000";
$colour_area     = "EBCD8B";
$colour_area_max = "cc9999";
$unit_text       = "APs";
$line_text       = 'Active APs';
$scale_min       = 0;

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
