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

$rrd_filename = get_rrd_path($device, "juniperive_storage.rrd");

$rrd_list[0]['filename'] = $rrd_filename;
$rrd_list[0]['descr']    = "Disk";
$rrd_list[0]['ds']       = "diskpercent";

$rrd_list[1]['filename'] = $rrd_filename;
$rrd_list[1]['descr']    = "Log";
$rrd_list[1]['ds']       = "logpercent";

$colours = "juniperive";

$unit_text   = "Storage %";
$units       = '%';
$total_units = '%';

$scale_min = "0";
$scale_max = "100";
$nototal   = 1;

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

?>
