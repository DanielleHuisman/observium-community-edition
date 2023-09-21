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

$units       = '';
$unit_text   = ''; // Multiple OIDs can have different units.
$total_units = '';

$i = 1;

$scale_min      = "0";
$colours        = 'mixed-q12';
$nototal        = 1;
$show_aggregate = TRUE;

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");