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
$rrd_list[0]['descr']    = "tcpCurrEstab";
$rrd_list[0]['ds']       = "tcpCurrEstab";

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Established Sessions";
$scale_min = "0";

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

