<?php

$scale_min = "0";

$rrd_filename = get_rrd_path($device, "diskstat-" . $disk['diskio_descr'] . ".rrd");

include($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_options .= " DEF:read=$rrd_filename_escape:readcount:AVERAGE";
$rrd_options .= " DEF:timeread=$rrd_filename_escape:time_reading:AVERAGE";
$rrd_options .= " DEF:write=$rrd_filename_escape:writecount:AVERAGE";
$rrd_options .= " DEF:timewrite=$rrd_filename_escape:time_writing:AVERAGE";
$rrd_options .= " CDEF:a=timeread,read,/,1000,/";
$rrd_options .= " CDEF:b=timewrite,write,/,1000,/";
$rrd_options .= " CDEF:cdefd=a,b,+";
$rrd_options .= " COMMENT:'I/O Latency  Current    Average    Maximum\\n'";
$rrd_options .= " AREA:a#ffeeaa:'Read '";
$rrd_options .= " LINE1:a#c5aa00:";
$rrd_options .= " GPRINT:a:LAST:'    %6.2lf'";
$rrd_options .= " GPRINT:a:AVERAGE:'  %6.2lf'";
$rrd_options .= " GPRINT:a:MAX:'  %6.2lf\\n'";
$rrd_options .= " LINE1.25:b#ea8f00:'Write'";
$rrd_options .= " GPRINT:b:LAST:'    %6.2lf'";
$rrd_options .= " GPRINT:b:AVERAGE:'  %6.2lf'";
$rrd_options .= " GPRINT:b:MAX:'  %6.2lf\\n'";

/**
 * $rrd_options .= " AREA:timeread#ffeeaa:'timeread'";
 * $rrd_options .= " LINE1:timeread#c5aa00:";
 * $rrd_options .= " GPRINT:timeread:LAST:'    %6.2lf'";
 * $rrd_options .= " GPRINT:timeread:AVERAGE:'  %6.2lf'";
 * $rrd_options .= " GPRINT:timeread:MAX:'  %6.2lf\\n'";
 * $rrd_options .= " AREA:read#ffeeaa:'read'";
 * $rrd_options .= " LINE1:read#c5aa00:";
 * $rrd_options .= " GPRINT:read:LAST:'    %6.2lf'";
 * $rrd_options .= " GPRINT:read:AVERAGE:'  %6.2lf'";
 * $rrd_options .= " GPRINT:read:MAX:'  %6.2lf\\n'";
 **/

?>
