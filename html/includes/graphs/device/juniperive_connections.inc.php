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

$rrd_filename = get_rrd_path($device, "juniperive_connections.rrd");

$rrd_list[0]['filename'] = $rrd_filename;
$rrd_list[0]['descr']    = "Web";
$rrd_list[0]['ds']       = "webusers";

$rrd_list[1]['filename'] = $rrd_filename;
$rrd_list[1]['descr']    = "Mail";
$rrd_list[1]['ds']       = "mailusers";

$colours   = "juniperive";
$nototal   = 1;
$unit_text = "Connections";
$scale_min = "0";

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

?>
