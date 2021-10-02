<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

$scale_min = "0";
$scale_max = "60";

include_once($config['html_dir']."/includes/graphs/common.inc.php");

$rrd_options .= " COMMENT:'                          Min     Last   Max\\n'";

$rrd_options .= " DEF:sensor=$rrd_filename_escape:sensor:AVERAGE";
$rrd_options .= " DEF:sensor_max=$rrd_filename_escape:sensor:MAX";
$rrd_options .= " DEF:sensor_min=$rrd_filename_escape:sensor:MIN";
$rrd_options .= " CDEF:sensor_diff=sensor_max,sensor_min,-";
$rrd_options .= " AREA:sensor_min";
$rrd_options .= " AREA:sensor_diff#c5c5c5::STACK";

$rrd_options .= " LINE1.5:sensor#cc0000:'" . rrdtool_escape($sensor['sensor_descr'],20)."'";
$rrd_options .= " GPRINT:sensor_min:MIN:%4.1lfC";
$rrd_options .= " GPRINT:sensor:LAST:%4.1lfC";
$rrd_options .= " GPRINT:sensor_max:MAX:%4.1lfC\\l";

if (is_numeric($sensor['sensor_limit'])) $rrd_options .= " HRULE:".$sensor['sensor_limit']."#999999::dashes";
if (is_numeric($sensor['sensor_limit_low'])) $rrd_options .= " HRULE:".$sensor['sensor_limit_low']."#999999::dashes";

#wtfbroken code.
if (get_var_true($vars['previous']))
{
  $rrd_options .= " DEF:sensorX=$rrd_filename_escape:sensor:AVERAGE:start=".$prev_from.":end=".$from;
  $rrd_options .= " LINE1.5:sensorX#0000cc:'Prev " . rrdtool_escape($sensor['sensor_descr'],18)."'";
  $rrd_options .= " SHIFT:sensorX:$period";
  $rrd_options .= " GPRINT:sensorX$current_id:MIN:%5.2lfC";
  $rrd_options .= " GPRINT:sensorX:LAST:%5.2lfC";
  $rrd_options .= " GPRINT:sensorX:MAX:%5.2lfC\\l";
}

$graph_return['descr'] = 'Dew point measured in celsius.';

// EOF
