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

$rrd_list[0]['filename'] = $rrd_filename;
$rrd_list[0]['descr']    = "txpow";
$rrd_list[0]['ds']       = "txpow";

$unit_text = "dBm";

$units       = '';
$total_units = '';
$colours     = 'mixed';

$scale_min = "0";

$nototal = 1;

if ($rrd_list) {
    include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");
}

?>
