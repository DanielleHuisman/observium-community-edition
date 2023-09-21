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

$scale_min = "0";

$rrd_filename = get_rrd_path($device, "cras_sessions.rrd");

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_options .= " DEF:email=$rrd_filename_escape:email:AVERAGE";
$rrd_options .= " DEF:ipsec=$rrd_filename_escape:ipsec:AVERAGE";
$rrd_options .= " DEF:l2l=$rrd_filename_escape:l2l:AVERAGE";
$rrd_options .= " DEF:lb=$rrd_filename_escape:lb:AVERAGE";
$rrd_options .= " DEF:svc=$rrd_filename_escape:svc:AVERAGE";
$rrd_options .= " DEF:webvpn=$rrd_filename_escape:webvpn:AVERAGE";
//$rrd_options .= " CDEF:webvpn_only=webvpn,svc,-";

$rrd_options .= " COMMENT:'Sessions         Current    Average   Maximum\\n'";

$rrd_options .= " AREA:svc#aa0000:'SSLVPN Tunnels':STACK";
$rrd_options .= " GPRINT:svc:LAST:'%6.2lf%s'";
$rrd_options .= " GPRINT:svc:AVERAGE:' %6.2lf%s'";
$rrd_options .= " GPRINT:svc:MAX:' %6.2lf%s\\n'";

$rrd_options .= " AREA:webvpn#999999:'Clientless VPN':STACK";
$rrd_options .= " GPRINT:webvpn:LAST:'%6.2lf%s'";
$rrd_options .= " GPRINT:webvpn:AVERAGE:' %6.2lf%s'";
$rrd_options .= " GPRINT:webvpn:MAX:' %6.2lf%s\\n'";

$rrd_options .= " AREA:ipsec#00aa00:'IPSEC         ':STACK";
$rrd_options .= " GPRINT:ipsec:LAST:'%6.2lf%s'";
$rrd_options .= " GPRINT:ipsec:AVERAGE:' %6.2lf%s'";
$rrd_options .= " GPRINT:ipsec:MAX:' %6.2lf%s\\n'";

$rrd_options .= " AREA:l2l#aaaa00:'Lan-to-Lan    ':STACK";
$rrd_options .= " GPRINT:l2l:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:l2l:AVERAGE:' %6.2lf%s'";
$rrd_options .= " GPRINT:l2l:MAX:' %6.2lf%s\\n'";

$rrd_options .= " AREA:email#0000aa:'Email         ':STACK";
$rrd_options .= " GPRINT:email:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:email:AVERAGE:' %6.2lf%s'";
$rrd_options .= " GPRINT:email:MAX:' %6.2lf%s\\n'";

$rrd_options .= " AREA:lb#aa00aa:'Load Balancer ':STACK";
$rrd_options .= " GPRINT:lb:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:lb:AVERAGE:' %6.2lf%s'";
$rrd_options .= " GPRINT:lb:MAX:' %6.2lf%s\\n'";

// EOF
