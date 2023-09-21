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

$file = get_rrd_path($device, "bluecoat-tcp-stats.rrd");

$rrd_list[0]['filename'] = $file;
$rrd_list[0]['descr']    = "tcpActiveOpens";
$rrd_list[0]['ds']       = "tcpActiveOpens";

$rrd_list[1]['filename'] = $file;
$rrd_list[1]['descr']    = "tcpPassiveOpens";
$rrd_list[1]['ds']       = "tcpPassiveOpens";

$rrd_list[2]['filename'] = $file;
$rrd_list[2]['descr']    = "tcpAttemptFails";
$rrd_list[2]['ds']       = "tcpAttemptFails";

$rrd_list[3]['filename'] = $file;
$rrd_list[3]['descr']    = "tcpEstabResets";
$rrd_list[3]['ds']       = "tcpEstabResets";

$colours   = "mixed";
$nototal   = 1;
$unit_text = "TCP Connections/sec";
$scale_min = "0";

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");
