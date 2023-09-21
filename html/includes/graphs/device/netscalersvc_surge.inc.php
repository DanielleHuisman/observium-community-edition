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

$ds = "SurgeCount";

$unit_text = "Surge";

include("netscalersvc.inc.php");

$units       = '';
$total_units = '';
$multiplier  = 1;
$colours     = 'purples';

#$nototal = 1;

$graph_title .= ":: Surge Count";

include($config['html_dir'] . "/includes/graphs/generic_multi_simplex_separated.inc.php");

// EOF
