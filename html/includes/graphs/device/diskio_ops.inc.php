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
$unit_text   = 'Operations/sec';
$total_units = 'B';
$colours_in  = 'reds';
$multiplier  = "1";
$colours_out = 'oranges';

$ds_in  = "reads";
$ds_out = "writes";

$nototal = 1;

include($config['html_dir'] . "/includes/graphs/device/diskio_common.inc.php");

include($config['html_dir'] . "/includes/graphs/generic_multi_separated.inc.php");

// EOF
