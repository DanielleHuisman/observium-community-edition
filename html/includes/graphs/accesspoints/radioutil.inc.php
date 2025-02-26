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

$ds              = "radioutil";
$colour_line     = "555555";
$colour_area     = "999999";
$colour_area_max = "e0e0e0";
$scale_max       = 100;
$scale_min       = 0;
$unit_text       = "Utilisation";
$units           = "%";

include($config['html_dir'] . "/includes/graphs/generic_simplex.inc.php");

?>
