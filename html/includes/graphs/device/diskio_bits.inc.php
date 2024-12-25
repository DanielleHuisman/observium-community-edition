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

$units       = '';
$unit_text   = 'Bits/sec';
$total_units = 'B';
$colours_in  = 'greens';
$multiplier  = "1";
$colours_out = 'blues';

$ds_in  = "read";
$ds_out = "written";

$nototal = 1;

include($config['html_dir'] . "/includes/graphs/device/diskio_common.inc.php");

include($config['html_dir'] . "/includes/graphs/generic_multi_bits_separated.inc.php");

// EOF
