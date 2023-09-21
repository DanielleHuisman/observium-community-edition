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

$file = get_rrd_path($device, "bluecoat-sg-proxy-mib_sgproxyhttpperf.rrd");

$rrd_list[0]['filename'] = $file;
$rrd_list[0]['descr']    = "ClientConn";
$rrd_list[0]['ds']       = "ClientConn";

$rrd_list[1]['filename'] = $file;
$rrd_list[1]['descr']    = "ClientConnAc";
$rrd_list[1]['ds']       = "ClientConnAc";

$rrd_list[2]['filename'] = $file;
$rrd_list[2]['descr']    = "ClientConnId";
$rrd_list[2]['ds']       = "ClientConnId";

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Connections/sec";
$scale_min = "0";

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");
