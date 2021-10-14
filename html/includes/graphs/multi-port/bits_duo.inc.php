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

// FIXME. This file unused
return;

if (!is_array($vars['id'])) { $vars['id'] = array($vars['id']); }

if ($vars['legend']) { $legend = $vars['legend']; }

if ($height < "99") { $rrd_options .= " --only-graph"; }
$i = 1;
$rrd_multi = array();
foreach ($vars['id'] as $ifid)
{
  $int = dbFetchRow("SELECT `ifIndex`, `hostname`, D.`device_id` FROM `ports` AS I, devices AS D WHERE I.port_id = ? AND I.device_id = D.device_id", array($ifid));
  $rrdfile = get_port_rrdfilename($int, NULL, TRUE);
  if (rrd_is_file($rrdfile))
  {
    $rrd_options .= " DEF:inoctets" . $i . "=" . $rrdfile . ":INOCTETS:AVERAGE";
    $rrd_options .= " DEF:outoctets" . $i . "=" . $rrdfile . ":OUTOCTETS:AVERAGE";

    $rrd_multi['in_thing'][]  = "inoctets" .  $i . ",UN,0," . "inoctets" .  $i . ",IF";
    $rrd_multi['out_thing'][] = "outoctets" . $i . ",UN,0," . "outoctets" . $i . ",IF";

    $i++;
  }
}

if (!is_array($vars['idb'])) { $vars['idb'] = array($vars['idb']); }
foreach ($vars['idb'] as $ifid)
{
  $int = dbFetchRow("SELECT `ifIndex`, `hostname`, D.`device_id` FROM `ports` AS I, devices as D WHERE I.port_id = ? AND I.device_id = D.device_id", array($ifid));
  $rrdfile = get_port_rrdfilename($int, NULL, TRUE);
  if (rrd_is_file($rrdfile))
  {
    $rrd_options .= " DEF:inoctetsb" . $i . "=" . $rrdfile . ":INOCTETS:AVERAGE";
    $rrd_options .= " DEF:outoctetsb" . $i . "=" . $rrdfile . ":OUTOCTETS:AVERAGE";

    $rrd_multi['in_thingb'][]  = "inoctetsb" .  $i . ",UN,0," . "inoctetsb" .  $i . ",IF";
    $rrd_multi['out_thingb'][] = "outoctetsb" . $i . ",UN,0," . "outoctetsb" . $i . ",IF";

    $i++;
  }
}

if ($inverse) { $in = 'out'; $out = 'in'; } else { $in = 'in'; $out = 'out'; }
$in_thing   = implode(',', $rrd_multi['in_thing']);
$out_thing  = implode(',', $rrd_multi['out_thing']);
$pluses     = str_repeat(',ADDNAN', safe_count($rrd_multi['in_thing']) - 1);
$in_thingb  = implode(',', $rrd_multi['in_thingb']);
$out_thingb = implode(',', $rrd_multi['out_thingb']);
$plusesb    = str_repeat(',ADDNAN', safe_count($rrd_multi['in_thingb']) - 1);
$rrd_options .= " CDEF:".$in."octets=" . $in_thing . $pluses;
$rrd_options .= " CDEF:".$out."octets=" . $out_thing . $pluses;
$rrd_options .= " CDEF:".$in."octetsb=" . $in_thingb . $plusesb;
$rrd_options .= " CDEF:".$out."octetsb=" . $out_thingb . $plusesb;
$rrd_options .= " CDEF:doutoctets=outoctets,-1,*";
$rrd_options .= " CDEF:inbits=inoctets,8,*";
$rrd_options .= " CDEF:outbits=outoctets,8,*";
$rrd_options .= " CDEF:doutbits=doutoctets,8,*";
$rrd_options .= " CDEF:doutoctetsb=outoctetsb,-1,*";
$rrd_options .= " CDEF:inbitsb=inoctetsb,8,*";
$rrd_options .= " CDEF:outbitsb=outoctetsb,8,*";
$rrd_options .= " CDEF:doutbitsb=doutoctetsb,8,*";
$rrd_options .= " CDEF:inbits_tot=inbits,inbitsb,+";
$rrd_options .= " CDEF:outbits_tot=outbits,outbitsb,+";
$rrd_options .= " CDEF:doutbits_tot=outbits_tot,-1,*";
$rrd_options .= " CDEF:nothing=outbits_tot,outbits_tot,-";

if ($legend == "no")
{
  $rrd_options .= " AREA:inbits_tot#cdeb8b:";
  $rrd_options .= " AREA:inbits#ffcc99:";
  $rrd_options .= " AREA:doutbits_tot#C3D9FF:";
  $rrd_options .= " AREA:doutbits#ffcc99:";
  $rrd_options .= " LINE1:inbits#aa9966:";
  $rrd_options .= " LINE1:doutbits#aa9966:";
#   $rrd_options .= " LINE1:inbitsb#006600:";
#   $rrd_options .= " LINE1:doutbitsb#000066:";
  $rrd_options .= " LINE1.25:inbits_tot#006600:";
  $rrd_options .= " LINE1.25:doutbits_tot#000099:";
  $rrd_options .= " LINE0.5:nothing#555555:";
} else {
  $rrd_options .= " COMMENT:'bps            Current   Average      Min      Max\\n'";
  $rrd_options .= " AREA:inbits_tot#cdeb8b:'Peering In '";
  $rrd_options .= " GPRINT:inbitsb:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:inbitsb:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:inbitsb:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:inbitsb:MAX:%6.2lf%s\\l";
  $rrd_options .= " AREA:doutbits_tot#C3D9FF:";
  $rrd_options .= " COMMENT:'          Out'";
  $rrd_options .= " GPRINT:outbitsb:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:outbitsb:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:outbitsb:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:outbitsb:MAX:%6.2lf%s\\l";

  $rrd_options .= " AREA:inbits#ffcc99:'Transit In '";
  $rrd_options .= " GPRINT:inbits:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits:MAX:%6.2lf%s\\l";
  $rrd_options .= " AREA:doutbits#ffcc99:";
  $rrd_options .= " COMMENT:'          Out'";
  $rrd_options .= " GPRINT:outbits:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits:MAX:%6.2lf%s\\l";

  $rrd_options .= " COMMENT:'Total     In '";
  $rrd_options .= " GPRINT:inbits_tot:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits_tot:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits_tot:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:inbits_tot:MAX:%6.2lf%s\\l";
  $rrd_options .= " COMMENT:'          Out'";
  $rrd_options .= " GPRINT:outbits_tot:LAST:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits_tot:AVERAGE:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits_tot:MIN:%6.2lf%s";
  $rrd_options .= " GPRINT:outbits_tot:MAX:%6.2lf%s\\l";

  $rrd_options .= " LINE1:inbits#aa9966:";
  $rrd_options .= " LINE1:doutbits#aa9966:";
#   $rrd_options .= " LINE1.25:inbitsb#006600:";
#   $rrd_options .= " LINE1.25:doutbitsb#006600:";
  $rrd_options .= " LINE1.25:inbits_tot#006600:";
  $rrd_options .= " LINE1.25:doutbits_tot#000099:";
  $rrd_options .= " LINE0.5:nothing#555555:";
}

if ($width <= "300") { $rrd_options .= " --font LEGEND:7:".$config['mono_font']." --font AXIS:6:".$config['mono_font']." --font-render-mode normal"; }

// Clean
unset($rrd_multi, $in_thing, $out_thing, $pluses, $in_thingb, $out_thingb, $plusesb);

// EOF
