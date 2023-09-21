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

// Draw generic bits graph
// args: ds_in, ds_out, rrd_filename, bg, legend, from, to, width, height, inverse, previous

include($config['html_dir'] . "/includes/graphs/common.inc.php");

$graph_return['valid_options'][] = "previous";
$graph_return['valid_options'][] = "95th";
$graph_return['valid_options'][] = "trend";
$graph_return['valid_options'][] = "inverse";

$do_95th = !(isset($vars['95th']) && get_var_false($vars['95th']));

switch ($format) {
    case 'octets':
    case 'bytes':
        //$units = "Bps";
        $units     = "Bytes/sec";
        $format    = "octets";
        $unit_text = "Bytes/s";
        break;
    case 'blocks':
        // only change unit text
        $units     = "Blocks/sec";
        $format    = "octets";
        $unit_text = "Blocks/s";
        break;
    case 'bits':
    default:
        $units     = "Bits/sec";
        $format    = "bits";
        $unit_text = "Bits/s";
}
/*
if ($format == "octets" || $format == "bytes")
{
  $units = "Bps";
  $format = "bytes";
  $unit_text = "Bytes/s";
} else {
  $units = "bps";
  $format = "bits";
  $unit_text = "Bits/s";
}
*/

$i         = 0;
$unit_text = rrdtool_escape($unit_text, 9);

if (!$noheader) {
    $rrd_options .= " COMMENT:'$unit_text  Last     Avg      Max" . ($do_95th ? "     95th" : "") . " \\n'";
}

// Alternative style
if ($graph_style == 'mrtg') {
    $out_scale = 1;
} else {
    $out_scale = -1;
}

if ($rrd_filename) {
    $rrd_filename_out = $rrd_filename_escape;
    $rrd_filename_in  = $rrd_filename_escape;
} else {
    // When IPv6 host is used, need escaping for filename path
    if (isset($rrd_filename_in)) {
        $rrd_filename_in = rrdtool_escape($rrd_filename_in);
    }
    if (isset($rrd_filename_out)) {
        $rrd_filename_out = rrdtool_escape($rrd_filename_out);
    }
}

if ($inverse) {
    $in  = 'out';
    $out = 'in';
} else {
    $in  = 'in';
    $out = 'out';
}

if ($multiplier) {
    $rrd_options .= " DEF:p" . $out . "octets=" . $rrd_filename_out . ":" . $ds_out . ":AVERAGE";
    $rrd_options .= " DEF:p" . $in . "octets=" . $rrd_filename_in . ":" . $ds_in . ":AVERAGE";
    $rrd_options .= " DEF:p" . $out . "octets_max=" . $rrd_filename_out . ":" . $ds_out . ":MAX";
    $rrd_options .= " DEF:p" . $in . "octets_max=" . $rrd_filename_in . ":" . $ds_in . ":MAX";
    $rrd_options .= " CDEF:inoctets=pinoctets,$multiplier,*";
    $rrd_options .= " CDEF:outoctets=poutoctets,$multiplier,*";
    $rrd_options .= " CDEF:inoctets_max=pinoctets_max,$multiplier,*";
    $rrd_options .= " CDEF:outoctets_max=poutoctets_max,$multiplier,*";
} else {
    $rrd_options .= " DEF:" . $out . "octets=" . $rrd_filename_out . ":" . $ds_out . ":AVERAGE";
    $rrd_options .= " DEF:" . $in . "octets=" . $rrd_filename_in . ":" . $ds_in . ":AVERAGE";
    $rrd_options .= " DEF:" . $out . "octets_max=" . $rrd_filename_out . ":" . $ds_out . ":MAX";
    $rrd_options .= " DEF:" . $in . "octets_max=" . $rrd_filename_in . ":" . $ds_in . ":MAX";
}

// Unknown data
$rrd_options .= " CDEF:alloctets=" . $out . "octets," . $in . "octets,+";
$rrd_options .= " CDEF:wrongin=alloctets,UN,INF,UNKN,IF";
$rrd_options .= " CDEF:wrongout=wrongin," . $out_scale . ",*";

if ($vars['previous'] == "yes") {
    if ($multiplier) {
        $rrd_options .= " DEF:p" . $out . "octetsX=" . $rrd_filename_out . ":" . $ds_out . ":AVERAGE:start=" . $prev_from . ":end=" . $from;
        $rrd_options .= " DEF:p" . $in . "octetsX=" . $rrd_filename_in . ":" . $ds_in . ":AVERAGE:start=" . $prev_from . ":end=" . $from;
        $rrd_options .= " SHIFT:p" . $out . "octetsX:$period";
        $rrd_options .= " SHIFT:p" . $in . "octetsX:$period";
        $rrd_options .= " CDEF:inoctetsX=pinoctetsX,$multiplier,*";
        $rrd_options .= " CDEF:outoctetsX=poutoctetsX,$multiplier,*";
    } else {
        $rrd_options .= " DEF:" . $out . "octetsX=" . $rrd_filename_out . ":" . $ds_out . ":AVERAGE:start=" . $prev_from . ":end=" . $from;
        $rrd_options .= " DEF:" . $in . "octetsX=" . $rrd_filename_in . ":" . $ds_in . ":AVERAGE:start=" . $prev_from . ":end=" . $from;
        $rrd_options .= " SHIFT:" . $out . "octetsX:$period";
        $rrd_options .= " SHIFT:" . $in . "octetsX:$period";
    }

    $rrd_options .= " CDEF:octetsX=inoctetsX,outoctetsX,+";
    $rrd_options .= " CDEF:doutoctetsX=outoctetsX," . $out_scale . ",*";
    $rrd_options .= " CDEF:outbitsX=outoctetsX,8,*";
    #$rrd_options .= " CDEF:outbits_maxX=outoctets_maxX,8,*";
    #$rrd_options .= " CDEF:doutoctets_maxX=outoctets_maxX,".$out_scale.",*";
    $rrd_options .= " CDEF:doutbitsX=doutoctetsX,8,*";
    #$rrd_options .= " CDEF:doutbits_maxX=doutoctets_maxX,8,*";

    $rrd_options .= " CDEF:inbitsX=inoctetsX,8,*";
    #$rrd_options .= " CDEF:inbits_maxX=inoctets_maxX,8,*";
    $rrd_options .= " VDEF:totinX=inoctetsX,TOTAL";
    $rrd_options .= " VDEF:totoutX=outoctetsX,TOTAL";
    $rrd_options .= " VDEF:totX=octetsX,TOTAL";

}

$rrd_options .= " CDEF:octets=inoctets,outoctets,+";
$rrd_options .= " CDEF:doutoctets=outoctets," . $out_scale . ",*";
$rrd_options .= " CDEF:outbits=outoctets,8,*";
$rrd_options .= " CDEF:outbits_max=outoctets_max,8,*";
$rrd_options .= " CDEF:doutoctets_max=outoctets_max," . $out_scale . ",*";
$rrd_options .= " CDEF:doutbits=doutoctets,8,*";
$rrd_options .= " CDEF:doutbits_max=doutoctets_max,8,*";

$rrd_options .= " CDEF:inbits=inoctets,8,*";
$rrd_options .= " CDEF:inbits_max=inoctets_max,8,*";

if ($config['rrdgraph_real_95th']) {
    $rrd_options .= " CDEF:highbits=inoctets,outoctets,MAX,8,*";
    $rrd_options .= " VDEF:95thhigh=highbits,95,PERCENT";
}

$rrd_options .= " VDEF:totin=inoctets,TOTAL";
$rrd_options .= " VDEF:totout=outoctets,TOTAL";
$rrd_options .= " VDEF:tot=octets,TOTAL";

$rrd_options .= " VDEF:95thin=inbits,95,PERCENT";
$rrd_options .= " VDEF:95thout=outbits,95,PERCENT";
$rrd_options .= " CDEF:pout_tmp=doutbits," . $out_scale . ",* VDEF:dpout_tmp=pout_tmp,95,PERCENT CDEF:dpout_tmp2=doutbits,doutbits,-,dpout_tmp," . $out_scale . ",*,+ VDEF:d95thout=dpout_tmp2,FIRST";

/*
if ($format == "octets" || $format == "bytes")
{
  $units = "Bytes/sec";
  $format = "octets";
} else {
  $units = "Bits/sec";
  $format = "bits";
}
*/

if ($graph_max) {
    $rrd_options .= " AREA:in" . $format . "_max#" . $config['colours']['graphs']['data']['in_max'] . ":";
}
$rrd_options .= " AREA:in" . $format . "#" . $config['colours']['graphs']['data']['in_area'];
if ($graph_style != 'mrtg') {
    $rrd_options .= " LINE1.25:in" . $format . "#" . $config['colours']['graphs']['data']['in_line'];
}
$rrd_options .= ":'In '";
$rrd_options .= " GPRINT:in" . $format . ":LAST:%6.2lf%s";
$rrd_options .= " GPRINT:in" . $format . ":AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:in" . $format . "_max:MAX:%6.2lf%s";
if ($do_95th) {
    $rrd_options .= " GPRINT:95thin:%6.2lf%s";
}
$rrd_options .= " COMMENT:\\n";

if ($graph_max) {
    if ($graph_style == 'mrtg') {
        $rrd_options .= " LINE1:dout";
    } else {
        $rrd_options .= " AREA:dout";
    }
    $rrd_options .= $format . "_max#" . $config['colours']['graphs']['data']['out_max'] . ":";
}

if ($graph_style != 'mrtg') {
    $rrd_options .= " AREA:dout" . $format . "#" . $config['colours']['graphs']['data']['out_area'];
}

$rrd_options .= " LINE1.25:dout" . $format . "#" . $config['colours']['graphs']['data']['out_line'] . ":'Out'";
$rrd_options .= " GPRINT:out" . $format . ":LAST:%6.2lf%s";
$rrd_options .= " GPRINT:out" . $format . ":AVERAGE:%6.2lf%s";
$rrd_options .= " GPRINT:out" . $format . "_max:MAX:%6.2lf%s";
if ($do_95th) {
    $rrd_options .= " GPRINT:95thout:%6.2lf%s";
}
$rrd_options .= " COMMENT:\\n";

if ($do_95th) {
    if ($config['rrdgraph_real_95th']) {
        $rrd_options .= " HRULE:95thhigh#FF0000:'Highest'";
        $rrd_options .= " GPRINT:95thhigh:%30.2lf%s\\n";
    } else {
        $rrd_options .= " HRULE:95thin#aa0000";
        $rrd_options .= " HRULE:d95thout#aa0000";
    }
}

$rrd_options .= " GPRINT:tot:'Total %6.2lf%s'";
$rrd_options .= " GPRINT:totin:'(In %6.2lf%s'";
$rrd_options .= " GPRINT:totout:'Out %6.2lf%s)\\l'";

$graph_return['legend_lines'] = 4;

if ($vars['previous'] == "yes") {
    $rrd_options .= " LINE1.25:in" . $format . "X#009900:'Prev In \\n'";
    $rrd_options .= " LINE1.25:dout" . $format . "X#000099:'Prev Out'";

    $graph_return['legend_lines'] += 2;

} else {
    $rrd_options .= " AREA:wrongin" . $nan_colour;
    $rrd_options .= " AREA:wrongout" . $nan_colour;
}

if ($vars['trend']) {
    $rrd_options .= " VDEF:slope_in=in" . $format . ",LSLSLOPE ";
    $rrd_options .= " VDEF:cons_in=in" . $format . ",LSLINT ";
    $rrd_options .= " CDEF:lsl2_in=in" . $format . ",POP,slope_in,COUNT,*,cons_in,+ ";
    $rrd_options .= ' LINE1.25:lsl2_in#ff0000::dashes=2';

    $rrd_options .= " VDEF:slope_out=out" . $format . ",LSLSLOPE ";
    $rrd_options .= " VDEF:cons_out=out" . $format . ",LSLINT ";
    $rrd_options .= " CDEF:lsl2_out=out" . $format . ",POP,slope_out,COUNT,*,cons_out,+ ";
    $rrd_options .= " CDEF:lsl2_out_rev=lsl2_out,-1,* ";
    $rrd_options .= ' LINE1.25:lsl2_out_rev#ff0000::dashes=2';
}

//if ($graph_style == 'mrtg')
//{
//  $midnight = strtotime('today midnight');
//  for ($i = 1; $i <= 2; $i++)
//  {
//    $rrd_options .= " VRULE:{$midnight}#FF0000";
//    $midnight -= 86400;
//  }
//}

// EOF
