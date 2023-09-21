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
$rrd_list[0]['descr']    = "ClientRequests";
$rrd_list[0]['ds']       = "ClientRequests";

$rrd_list[1]['filename'] = $file;
$rrd_list[1]['descr']    = "ClientHits";
$rrd_list[1]['ds']       = "ClientHits";

$rrd_list[2]['filename'] = $file;
$rrd_list[2]['descr']    = "ClientPartialHits";
$rrd_list[2]['ds']       = "ClientPartialHits";

$rrd_list[3]['filename'] = $file;
$rrd_list[3]['descr']    = "ClientMisses";
$rrd_list[3]['ds']       = "ClientMisses";

$rrd_list[4]['filename'] = $file;
$rrd_list[4]['descr']    = "ClientErrors";
$rrd_list[4]['ds']       = "ClientErrors";

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Requests/sec";
$scale_min = "0";

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

// EOF
