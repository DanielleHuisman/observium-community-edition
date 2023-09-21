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

$rrd_options .= " DEF:ipInDelivers=$rrd_filename_escape:InDelivers:AVERAGE";
$rrd_options .= " DEF:ipReasmReqds=$rrd_filename_escape:ReasmReqds:AVERAGE";
$rrd_options .= " DEF:ipReasmOKs=$rrd_filename_escape:ReasmOKs:AVERAGE";
$rrd_options .= " DEF:ipReasmFails=$rrd_filename_escape:ReasmFails:AVERAGE";
$rrd_options .= " DEF:ipFragFails=$rrd_filename_escape:OutFragFails:AVERAGE";
$rrd_options .= " DEF:ipFragCreates=$rrd_filename_escape:OutFragCreates:AVERAGE";

$rrd_options .= " DEF:MipInDelivers=$rrd_filename_escape:InDelivers:MAX";
$rrd_options .= " DEF:MipReasmOKs=$rrd_filename_escape:ReasmOKs:MAX";
$rrd_options .= " DEF:MipReasmReqds=$rrd_filename_escape:ReasmReqds:MAX";
$rrd_options .= " DEF:MipReasmFails=$rrd_filename_escape:ReasmFails:MAX";
$rrd_options .= " DEF:MipFragFails=$rrd_filename_escape:OutFragFails:MAX";
$rrd_options .= " DEF:MipFragCreates=$rrd_filename_escape:OutFragCreates:MAX";

$rrd_options .= " CDEF:ReasmReqds=ipReasmReqds,ipInDelivers,/,100,*";
$rrd_options .= " CDEF:ReasmOKs=ipReasmOKs,ipInDelivers,/,100,*";
$rrd_options .= " CDEF:ReasmFails=ipReasmFails,ipInDelivers,/,100,*";
$rrd_options .= " CDEF:FragFails=ipFragFails,ipInDelivers,/,100,*";
$rrd_options .= " CDEF:FragCreates=ipFragCreates,ipInDelivers,/,100,*";

$rrd_options .= " CDEF:FragFails_n=FragFails,-1,*";
$rrd_options .= " CDEF:FragCreates_n=FragCreates,-1,*";

$rrd_options .= " CDEF:MReasmReqds=MipReasmReqds,MipInDelivers,/,100,*";
$rrd_options .= " CDEF:MReasmOKs=MipReasmOKs,MipInDelivers,/,100,*";
$rrd_options .= " CDEF:MReasmFails=MipReasmFails,MipInDelivers,/,100,*";
$rrd_options .= " CDEF:MFragFails=MipFragFails,MipInDelivers,/,100,*";
$rrd_options .= " CDEF:MFragCreates=MipFragCreates,MipInDelivers,/,100,*";

$rrd_options .= " COMMENT:'% ipInDelivers   Current  Average  Maximum\\n'";

$rrd_options .= " LINE1.25:FragFails_n#cc0000:'Frag Fail    '";
$rrd_options .= " GPRINT:FragFails:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:FragFails:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:MFragFails:MAX:%6.2lf%s\\n";

$rrd_options .= " LINE1.25:FragCreates#00cc:'Frag Create  '";
$rrd_options .= " GPRINT:FragCreates:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:FragCreates:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:MFragCreates:MAX:%6.2lf%s\\n";

$rrd_options .= " LINE1.25:ReasmOKs#006600:'Reasm OK     '";
$rrd_options .= " GPRINT:ReasmOKs:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:ReasmOKs:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:MReasmOKs:MAX:%6.2lf%s\\n";

$rrd_options .= " LINE1.25:ReasmFails#660000:'Reasm Fail   '";
$rrd_options .= " GPRINT:ReasmFails:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:ReasmFails:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:MReasmFails:MAX:%6.2lf%s\\n";

$rrd_options .= " LINE1.25:ReasmReqds#000066:'Reasm Reqd   '";
$rrd_options .= " GPRINT:ReasmReqds:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:ReasmReqds:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:MReasmReqds:MAX:%6.2lf%s\\n";

// EOF
