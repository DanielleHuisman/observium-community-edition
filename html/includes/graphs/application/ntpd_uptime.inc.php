<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$scale_min       = 0;
$ds              = "uptime";
$colour_area     = "EEEEEE";
$colour_line     = "36393D";
$colour_area_max = "FFEE99";
$graph_max       = 0;
$unit_text       = "Seconds";
$ntpdserver_rrd  = get_rrd_path($device, "app-ntpd-server-".$app['app_id'].".rrd");

if (rrd_is_file($ntpdserver_rrd))
{
  $rrd_filename = $ntpdserver_rrd;
}

include($config['html_dir']."/includes/graphs/common.inc.php");

$rrd_options   .= " DEF:uptime=".$rrd_filename_escape.":uptime:AVERAGE";
$rrd_options   .= " CDEF:cuptime=uptime,86400,/";

if ($width < 224)
{
  $rrd_options .= " 'COMMENT:Days         Cur      Min     Max     Avg\\n'";
} else {
  $rrd_options .= " 'COMMENT:Days      Current  Minimum  Maximum  Average\\n'";
}

$rrd_options   .= " AREA:cuptime#".$colour_area.":";
$rrd_options   .= " LINE1.25:cuptime#".$colour_line.":Uptime";
$rrd_options   .= " GPRINT:cuptime:LAST:%6.2lf";
$rrd_options   .= " GPRINT:cuptime:AVERAGE:%6.2lf";
$rrd_options   .= " GPRINT:cuptime:MAX:%6.2lf";
$rrd_options   .= " GPRINT:cuptime:AVERAGE:%6.2lf\\n";

// EOF
