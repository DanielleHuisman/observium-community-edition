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

$rrd_list[0]['filename'] = $rrd_filename;
$rrd_list[0]['descr']    = "MonBSSIDs";
$rrd_list[0]['ds']       = "nummonbssid";

$unit_text = "BSSIDs";

$units       = '';
$total_units = '';
$colours     = 'mixed';

$scale_min = "0";

$nototal = 1;

if ($rrd_list) {
    include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");
}

?>
