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

// FIXME. This file unused

if (!is_array($vars['id'])) {
    $vars['id'] = [$vars['id']];
}
if (!is_array($vars['idb'])) {
    $vars['idb'] = [$vars['idb']];
}
if (!is_array($vars['idc'])) {
    $vars['idc'] = [$vars['idc']];
}

if (!isset($vars['colour_a'])) {
    $vars['colour_a'] = "0a5f7f";
}
if (!isset($vars['colour_b'])) {
    $vars['colour_b'] = "4d9221";
}
if (!isset($vars['colour_c'])) {
    $vars['colour_c'] = "d9534f";
}

if (!isset($vars['descr_a'])) {
    $vars['descr_a'] = "Peering";
}
if (!isset($vars['descr_b'])) {
    $vars['descr_b'] = "Cache";
}
if (!isset($vars['descr_c'])) {
    $vars['descr_c'] = "Transit";
}


if ($vars['legend']) {
    $legend = $vars['legend'];
}

$descr_lens = [strlen($vars['descr_a']), strlen($vars['descr_b']), strlen($vars['descr_c'])];
$max_descr  = max($descr_lens);
if ($max_descr < 10) {
    $max_descr = 10;
}


include($config['html_dir'] . "/includes/graphs/common.inc.php");

if ($height < "99") {
    $rrd_options .= " --only-graph";
}
$i         = 1;
$rrd_multi = [];
foreach ($vars['id'] as $ifid) {
    $int     = dbFetchRow("SELECT `ifIndex`, `hostname`, D.`device_id` FROM `ports` AS I, devices AS D WHERE I.port_id = ? AND I.device_id = D.device_id", [$ifid]);
    $rrdfile = get_port_rrdfilename($int, NULL, TRUE);
    if (rrd_is_file($rrdfile)) {
        if (strstr($inverse, "a")) {
            $in  = "OUT";
            $out = "IN";
        } else {
            $in  = "IN";
            $out = "OUT";
        }
        $rrd_options .= " DEF:inoctets" . $i . "=" . $rrdfile . ":" . $in . "OCTETS:AVERAGE";
        $rrd_options .= " DEF:outoctets" . $i . "=" . $rrdfile . ":" . $out . "OCTETS:AVERAGE";

        $rrd_multi['in_thing'][]  = "inoctets" . $i . ",UN,0," . "inoctets" . $i . ",IF";
        $rrd_multi['out_thing'][] = "outoctets" . $i . ",UN,0," . "outoctets" . $i . ",IF";

        $i++;
    }
}

foreach ($vars['idb'] as $ifid) {
    $int     = dbFetchRow("SELECT `ifIndex`, `hostname`, D.`device_id` FROM `ports` AS I, devices AS D WHERE I.port_id = ? AND I.device_id = D.device_id", [$ifid]);
    $rrdfile = get_port_rrdfilename($int, NULL, TRUE);
    if (rrd_is_file($rrdfile)) {
        if (strstr($inverse, "b")) {
            $in  = "OUT";
            $out = "IN";
        } else {
            $in  = "IN";
            $out = "OUT";
        }
        $rrd_options .= " DEF:inoctetsb" . $i . "=" . $rrdfile . ":" . $in . "OCTETS:AVERAGE";
        $rrd_options .= " DEF:outoctetsb" . $i . "=" . $rrdfile . ":" . $out . "OCTETS:AVERAGE";

        $rrd_multi['in_thingb'][]  = "inoctetsb" . $i . ",UN,0," . "inoctetsb" . $i . ",IF";
        $rrd_multi['out_thingb'][] = "outoctetsb" . $i . ",UN,0," . "outoctetsb" . $i . ",IF";

        $i++;
    }
}

foreach ($vars['idc'] as $ifid) {
    $int     = dbFetchRow("SELECT `ifIndex`, `hostname`, D.`device_id` FROM `ports` AS I, devices as D WHERE I.port_id = ? AND I.device_id = D.device_id", [$ifid]);
    $rrdfile = get_port_rrdfilename($int, NULL, TRUE);
    if (rrd_is_file($rrdfile)) {
        if (strstr($inverse, "c")) {
            $in  = "OUT";
            $out = "IN";
        } else {
            $in  = "IN";
            $out = "OUT";
        }
        $rrd_options .= " DEF:inoctetsc" . $i . "=" . $rrdfile . ":" . $in . "OCTETS:AVERAGE";
        $rrd_options .= " DEF:outoctetsc" . $i . "=" . $rrdfile . ":" . $out . "OCTETS:AVERAGE";

        $rrd_multi['in_thingc'][]  = "inoctetsc" . $i . ",UN,0," . "inoctetsc" . $i . ",IF";
        $rrd_multi['out_thingc'][] = "outoctetsc" . $i . ",UN,0," . "outoctetsc" . $i . ",IF";

        $i++;
    }
}

foreach (['', 'b', 'c'] as $trio) {
    $in_name      = 'in_thing' . $trio;
    $out_name     = 'out_thing' . $trio;
    $pluses_name  = 'pluses' . $trio;
    $$in_name     = implode(',', $rrd_multi[$in_name]);
    $$out_name    = implode(',', $rrd_multi[$out_name]);
    $$pluses_name = str_repeat(',ADDNAN', safe_count($rrd_multi[$in_name]) - 1);
    unset($in_name, $out_name, $pluses_name);
}

$rrd_options .= " CDEF:inoctets=" . $in_thing . $pluses;
$rrd_options .= " CDEF:outoctets=" . $out_thing . $pluses;
$rrd_options .= " CDEF:inoctetsb=" . $in_thingb . $plusesb;
$rrd_options .= " CDEF:outoctetsb=" . $out_thingb . $plusesb;
$rrd_options .= " CDEF:inoctetsc=" . $in_thingc . $plusesc;
$rrd_options .= " CDEF:outoctetsc=" . $out_thingc . $plusesc;

$rrd_options .= " CDEF:outoctetst=outoctets,outoctetsb,outoctetsc,+,+";
$rrd_options .= " CDEF:inoctetst=inoctets,inoctetsb,inoctetsc,+,+";

$rrd_options .= " CDEF:octets_total=inoctetst,outoctetst,+";

$rrd_options .= " CDEF:in_perc_a=inoctets,inoctetst,/,100,*";
$rrd_options .= " CDEF:in_perc_b=inoctetsb,inoctetst,/,100,*";
$rrd_options .= " CDEF:in_perc_c=inoctetsc,inoctetst,/,100,*";

$rrd_options .= " CDEF:out_perc_a=outoctets,outoctetst,/,100,*";
$rrd_options .= " CDEF:out_perc_b=outoctetsb,outoctetst,/,100,*";
$rrd_options .= " CDEF:out_perc_c=outoctetsc,outoctetst,/,100,*";

$rrd_options .= " CDEF:perc_a=inoctets,outoctets,+,octets_total,/,100,*";
$rrd_options .= " CDEF:perc_b=inoctetsb,outoctetsb,+,octets_total,/,100,*";
$rrd_options .= " CDEF:perc_c=inoctetsc,outoctetsc,+,octets_total,/,100,*";


$rrd_options .= " CDEF:doutoctets=outoctets,-1,*";
$rrd_options .= " CDEF:inbits=inoctets,8,*";
$rrd_options .= " CDEF:outbits=outoctets,8,*";
$rrd_options .= " CDEF:doutbits=doutoctets,8,*";
$rrd_options .= " CDEF:doutoctetsb=outoctetsb,-1,*";
$rrd_options .= " CDEF:inbitsb=inoctetsb,8,*";
$rrd_options .= " CDEF:outbitsb=outoctetsb,8,*";
$rrd_options .= " CDEF:doutbitsb=doutoctetsb,8,*";
$rrd_options .= " CDEF:doutoctetsc=outoctetsc,-1,*";
$rrd_options .= " CDEF:inbitsc=inoctetsc,8,*";
$rrd_options .= " CDEF:outbitsc=outoctetsc,8,*";
$rrd_options .= " CDEF:doutbitsc=doutoctetsc,8,*";
$rrd_options .= " CDEF:inbits_tot=inbits,inbitsb,inbitsc,+,+";
$rrd_options .= " CDEF:outbits_tot=outbits,outbitsb,outbitsc,+,+";
$rrd_options .= " CDEF:inbits_stot=inbitsc,inbitsb,+";
$rrd_options .= " CDEF:outbits_stot=outbitsc,outbitsb,+";
$rrd_options .= " CDEF:doutbits_stot=outbits_stot,-1,*";
$rrd_options .= " CDEF:doutbits_tot=outbits_tot,-1,*";
$rrd_options .= " CDEF:nothing=outbits_tot,outbits_tot,-";

$rrd_options .= " COMMENT:'" . str_pad('%age', $max_descr) . "        Last     Avg      Min      Max'\\n";

$rrd_options .= " AREA:perc_a#" . $vars['colour_a'] . ":'" . str_pad($vars['descr_a'], $max_descr) . "'";
$rrd_options .= " GPRINT:perc_a:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:perc_a:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:perc_a:MIN:%6.2lf%s";
$rrd_options .= " GPRINT:perc_a:MAX:%6.2lf%s\\l";

$rrd_options .= " AREA:perc_b#" . $vars['colour_b'] . ":'" . str_pad($vars['descr_b'], $max_descr) . "':STACK";
$rrd_options .= " GPRINT:perc_b:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:perc_b:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:perc_b:MIN:%6.2lf%s";
$rrd_options .= " GPRINT:perc_b:MAX:%6.2lf%s\\l";

$rrd_options .= " AREA:perc_c#" . $vars['colour_c'] . ":'" . str_pad($vars['descr_c'], $max_descr) . "':STACK";
$rrd_options .= " GPRINT:perc_c:LAST:%6.2lf%s";
$rrd_options .= " GPRINT:perc_c:AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:perc_c:MIN:%6.2lf%s";
$rrd_options .= " GPRINT:perc_c:MAX:%6.2lf%s\\l";


/*
if ($legend == "no" && FALSE)
{
  $rrd_options .= " AREA:inbits_tot#cdeb8b:";
  $rrd_options .= " AREA:doutbits_tot#cdeb8b:";
  $rrd_options .= " LINE1.25:inbits_tot#aacc77:";
  $rrd_options .= " LINE1.25:doutbits_tot#aacc88:";
  $rrd_options .= " AREA:inbits_stot#c3d9ff:";
  $rrd_options .= " AREA:doutbits_stot#c3d9ff:";
  $rrd_options .= " LINE1:inbits_stot#b3a9cf:";
  $rrd_options .= " LINE1:doutbits_stot#b3a9cf:";
  $rrd_options .= " AREA:inbitsc#ffcc99:";
  $rrd_options .= " AREA:doutbitsc#ffcc99:";
  $rrd_options .= " LINE1.25:inbitsc#ddaa88";
  $rrd_options .= " LINE1.25:doutbitsc#ddaa88";
  $rrd_options .= " LINE1:inbits#006600:";
  $rrd_options .= " LINE1:doutbits#006600:";
  $rrd_options .= " LINE1:inbitsb#000099:";
  $rrd_options .= " LINE1:doutbitsb#000099:";
  $rrd_options .= " LINE0.5:nothing#555555:";
} else {
  $rrd_options .= " COMMENT:'bps            Current   Average      Min      Max\\n'";
  $rrd_options .= " AREA:inbits_tot#cdeb8b:'ATM  In '";
  $rrd_options .= " GPRINT:inbits:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits:MAX:%6.2lf%s\\l";
  $rrd_options .= " AREA:doutbits_tot#cdeb8b:";
  $rrd_options .= " COMMENT:'       Out'";
  $rrd_options .= " GPRINT:outbits:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits:MAX:%6.2lf%s\\l";
  $rrd_options .= " LINE1.25:inbits_tot#aacc77:";
  $rrd_options .= " LINE1.25:doutbits_tot#aacc88:";
  $rrd_options .= " AREA:inbits_stot#c3d9ff:'NGN  In '";
  $rrd_options .= " GPRINT:inbitsb:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:inbitsb:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:inbitsb:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:inbitsb:MAX:%6.2lf%s\\l";
  $rrd_options .= " AREA:doutbits_stot#c3d9ff:";
  $rrd_options .= " COMMENT:'       Out'";
  $rrd_options .= " GPRINT:outbitsb:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:outbitsb:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:outbitsb:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:outbitsb:MAX:%6.2lf%s\\l";
  $rrd_options .= " LINE1:inbits_stot#b3a9cf:";
  $rrd_options .= " LINE1:doutbits_stot#b3a9cf:";
  $rrd_options .= " AREA:inbitsc#ffcc99:'Wave In '";
  $rrd_options .= " GPRINT:inbitsc:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:inbitsc:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:inbitsc:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:inbitsc:MAX:%6.2lf%s\\l";
  $rrd_options .= " AREA:doutbitsc#ffcc99:";
  $rrd_options .= " COMMENT:'       Out'";
  $rrd_options .= " GPRINT:outbitsc:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:outbitsc:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:outbitsc:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:outbitsc:MAX:%6.2lf%s\\l";
  $rrd_options .= " LINE1.25:inbitsc#ddaa88";
  $rrd_options .= " LINE1.25:doutbitsc#ddaa88";
  $rrd_options .= " LINE1:inbits#006600:";
  $rrd_options .= " LINE1:doutbits#006600:";
  $rrd_options .= " LINE1:inbitsb#000099:";
  $rrd_options .= " LINE1:doutbitsb#000099:";
  $rrd_options .= " LINE0.5:nothing#555555:";

  $rrd_options .= " COMMENT:'Total  In '";
  $rrd_options .= " GPRINT:inbits_tot:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits_tot:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits_tot:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits_tot:MAX:%6.2lf%s\\l";
  $rrd_options .= " COMMENT:'       Out'";
  $rrd_options .= " GPRINT:outbits_tot:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits_tot:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits_tot:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits_tot:MAX:%6.2lf%s\\l";
}

*/

// Clean
unset($rrd_multi, $in_thing, $out_thing, $pluses, $in_thingb, $out_thingb, $plusesb, $in_thingc, $out_thingc, $plusesc);

// EOF
