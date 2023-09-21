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

$file = get_rrd_path($device, "screenos_sessions.rrd");

$rrd_list[0]['filename'] = $file;
$rrd_list[0]['descr']    = "Maximum";
$rrd_list[0]['ds']       = "max";

$rrd_list[1]['filename'] = $file;
$rrd_list[1]['descr']    = "Allocated";
$rrd_list[1]['ds']       = "allocate";

$rrd_list[2]['filename'] = $file;
$rrd_list[2]['descr']    = "Failed";
$rrd_list[2]['ds']       = "failed";

$colours   = "mixed";
$nototal   = 1;
$unit_text = "Sessions";
$scale_min = "0";

include($config['html_dir'] . "/includes/graphs/generic_multi_line.inc.php");

?>
