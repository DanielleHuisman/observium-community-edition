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

$device       = device_by_id_cache($id);
$rrd_filename = get_rrd_path($device, "mem.rrd");

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$rrd_options .= " -b 1024";

$rrd_options .= " DEF:atotalswap=$rrd_filename_escape:totalswap:AVERAGE";
$rrd_options .= " DEF:aavailswap=$rrd_filename_escape:availswap:AVERAGE";
$rrd_options .= " DEF:atotalreal=$rrd_filename_escape:totalreal:AVERAGE";
$rrd_options .= " DEF:aavailreal=$rrd_filename_escape:availreal:AVERAGE";
$rrd_options .= " DEF:atotalfree=$rrd_filename_escape:totalfree:AVERAGE";
$rrd_options .= " DEF:ashared=$rrd_filename_escape:shared:AVERAGE";
$rrd_options .= " DEF:abuffered=$rrd_filename_escape:buffered:AVERAGE";
$rrd_options .= " DEF:acached=$rrd_filename_escape:cached:AVERAGE";
$rrd_options .= " CDEF:totalswap=atotalswap,1024,*";
$rrd_options .= " CDEF:availswap=aavailswap,1024,*";
$rrd_options .= " CDEF:totalreal=atotalreal,1024,*";
$rrd_options .= " CDEF:availreal=aavailreal,1024,*";
$rrd_options .= " CDEF:totalfree=atotalfree,1024,*";
$rrd_options .= " CDEF:shared=ashared,1024,*";
$rrd_options .= " CDEF:buffered=abuffered,1024,*";
$rrd_options .= " CDEF:cached=acached,1024,*";
$rrd_options .= " CDEF:usedreal=totalreal,availreal,-";
$rrd_options .= " CDEF:usedswap=totalswap,availswap,-";
$rrd_options .= " CDEF:cusedswap=usedswap,-1,*";
$rrd_options .= " CDEF:cdeftot=availreal,shared,buffered,usedreal,cached,usedswap,+,+,+,+,+";
$rrd_options .= " COMMENT:'Bytes       Current    Average     Maximum\\n'";
$rrd_options .= " LINE1:usedreal#d0b080:";
$rrd_options .= " AREA:usedreal#f0e0a0:used";
$rrd_options .= " GPRINT:usedreal:LAST:\ \ \ %7.2lf%sB";
$rrd_options .= " GPRINT:usedreal:AVERAGE:%7.2lf%sB";
$rrd_options .= " GPRINT:usedreal:MAX:%7.2lf%sB\\n";
$rrd_options .= " STACK:availreal#e5e5e5:free";
$rrd_options .= " GPRINT:availreal:LAST:\ \ \ %7.2lf%sB";
$rrd_options .= " GPRINT:availreal:AVERAGE:%7.2lf%sB";
$rrd_options .= " GPRINT:availreal:MAX:%7.2lf%sB\\n";
$rrd_options .= " LINE1:usedreal#d0b080:";
$rrd_options .= " AREA:shared#afeced::";
$rrd_options .= " AREA:buffered#cc0000::STACK";
$rrd_options .= " AREA:cached#ffaa66::STACK";
$rrd_options .= " LINE1.25:shared#008fea:shared";
$rrd_options .= " GPRINT:shared:LAST:\ %7.2lf%sB";
$rrd_options .= " GPRINT:shared:AVERAGE:%7.2lf%sB";
$rrd_options .= " GPRINT:shared:MAX:%7.2lf%sB\\n";
$rrd_options .= " LINE1.25:buffered#ff1a00:buffers:STACK";
$rrd_options .= " GPRINT:buffered:LAST:%7.2lf%sB";
$rrd_options .= " GPRINT:buffered:AVERAGE:%7.2lf%sB";
$rrd_options .= " GPRINT:buffered:MAX:%7.2lf%sB\\n";
$rrd_options .= " LINE1.25:cached#ea8f00:cached:STACK";
$rrd_options .= " GPRINT:cached:LAST:\ %7.2lf%sB";
$rrd_options .= " GPRINT:cached:AVERAGE:%7.2lf%sB";
$rrd_options .= " GPRINT:cached:MAX:%7.2lf%sB\\n";
$rrd_options .= " LINE1:totalreal#050505:";
$rrd_options .= " AREA:cusedswap#C3D9FF:swap";
$rrd_options .= " LINE1.25:cusedswap#356AA0:";
$rrd_options .= " GPRINT:usedswap:LAST:\ \ \ %7.2lf%sB";
$rrd_options .= " GPRINT:usedswap:AVERAGE:%7.2lf%sB";
$rrd_options .= " GPRINT:usedswap:MAX:%7.2lf%sB\\n";
$rrd_options .= " LINE1:totalreal#050505:total";
$rrd_options .= " GPRINT:totalreal:AVERAGE:\ \ %7.2lf%sB";

// EOF
