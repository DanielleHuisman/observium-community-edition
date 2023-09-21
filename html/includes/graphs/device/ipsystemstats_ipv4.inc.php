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

$rrd_filename = get_rrd_path($device, "ipSystemStats-ipv4.rrd");

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_options .= " DEF:InForwDatagrams=$rrd_filename_escape:InForwDatagrams:AVERAGE";
$rrd_options .= " DEF:InDelivers=$rrd_filename_escape:InDelivers:AVERAGE";
$rrd_options .= " DEF:InReceives=$rrd_filename_escape:InReceives:AVERAGE";
$rrd_options .= " DEF:InDiscards=$rrd_filename_escape:InDiscards:AVERAGE";

$rrd_options .= " DEF:OutForwDatagrams=$rrd_filename_escape:OutForwDatagrams:AVERAGE";
$rrd_options .= " CDEF:OutForwDatagrams_n=OutForwDatagrams,-1,*";
$rrd_options .= " DEF:OutRequests=$rrd_filename_escape:OutRequests:AVERAGE";
$rrd_options .= " CDEF:OutRequests_n=OutRequests,-1,*";
$rrd_options .= " DEF:OutDiscards=$rrd_filename_escape:OutDiscards:AVERAGE";
$rrd_options .= " CDEF:OutDiscards_n=OutDiscards,-1,*";
$rrd_options .= " DEF:OutNoRoutes=$rrd_filename_escape:OutNoRoutes:AVERAGE";
$rrd_options .= " CDEF:OutNoRoutes_n=OutNoRoutes,-1,*";

$rrd_options .= " COMMENT:'Packets/sec       Current  Average  Maximum\\n'";

$rrd_options .= " LINE1.25:InReceives#7D9B5B:'InReceives   v4'";
$rrd_options .= " GPRINT:InReceives:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:InReceives:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:InReceives:MAX:%6.2lf%s\\n";

$rrd_options .= " AREA:InForwDatagrams#AF63AF:'InForward    v4'";
$rrd_options .= " GPRINT:InForwDatagrams:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:InForwDatagrams:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:InForwDatagrams:MAX:%6.2lf%s\\n";

$rrd_options .= " AREA:InDelivers#CDEB8B:'InDelivers   v4':STACK";
$rrd_options .= " GPRINT:InDelivers:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:InDelivers:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:InDelivers:MAX:%6.2lf%s\\n";

$rrd_options .= " AREA:OutRequests_n#C3D9FF:'OutRequests  v4'";
$rrd_options .= " GPRINT:OutRequests:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:OutRequests:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:OutRequests:MAX:%6.2lf%s\\n";

$rrd_options .= " AREA:OutForwDatagrams#AF63AF:'OutForward   v4'";
$rrd_options .= " GPRINT:OutForwDatagrams:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:OutForwDatagrams:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:OutForwDatagrams:MAX:%6.2lf%s\\n";

$rrd_options .= " LINE1.25:InReceives#9DaB6B:";
$rrd_options .= " LINE1.25:OutRequests_n#93a6eF:";

// EOF
