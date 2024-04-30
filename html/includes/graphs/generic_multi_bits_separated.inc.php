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

include($config['html_dir'] . "/includes/graphs/common.inc.php");

#$graph_return['valid_options'][] = "previous";
#$graph_return['valid_options'][] = "total";
#$graph_return['valid_options'][] = "trend";
$graph_return['valid_options'] = ["inverse", "line_graph"];

$i = 0;
if ($width > "500") {
    $descr_len = 18;
} else {
    $descr_len = 4;
    $descr_len += round(($width - 215) / 10);
}

$unit_text = "bps";

if (!$noheader) {
    if ($width > "500") {
        $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len), 0, $descr_len + 5) . "      Current   Average    Maximum  '";
        if (!$nototal) {
            $rrd_options .= " COMMENT:'95th  '";
            $rrd_options .= " COMMENT:'Total'";
        }
        $rrd_options .= " COMMENT:'\l'";
    } else {
        $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len), 0, $descr_len + 5) . "     Now         Avg         Max\l'";
    }
}

if (!isset($multiplier)) {
    $multiplier = "8";
}

// Override graphtype colours
// FIXME - dumb hack, if it's useful extend it and do it properly later
if(isset($vars['colours_in']) && is_array($config['graph_colours'][$vars['colours_in']])) { $colours_in = $vars['colours_in']; }
if(isset($vars['colours_out']) && is_array($config['graph_colours'][$vars['colours_out']])) { $colours_out = $vars['colours_out']; }

$rrd_multi = [];
$stack     = '';
foreach ($rrd_list as $rrd) {
    if (!$config['graph_colours'][$colours_in][$iter] || !$config['graph_colours'][$colours_out][$iter]) {
        $iter = 0;
    }

    if (!safe_empty($rrd['colour_in'])) {
        $colour_in = $rrd['colour_in'];
    } else {
        $colour_in = $config['graph_colours'][$colours_in][$iter];
    }
    if (!safe_empty($rrd['colour_out'])) {
        $colour_out = $rrd['colour_out'];
    } else {
        $colour_out = $config['graph_colours'][$colours_out][$iter];
    }

    if (isset($rrd['descr_in'])) {
        $descr = rrdtool_escape($rrd['descr_in'], $descr_len) . " <";
    } else {
        $descr = rrdtool_escape($rrd['descr'], $descr_len) . " <";
    }
    $descr_out = rrdtool_escape($rrd['descr_out'], $descr_len) . " >";

    $descr     = str_replace("'", "", $descr); // FIXME does this mean ' should be filtered in rrdtool_escape? probably...
    $descr_out = str_replace("'", "", $descr_out);

    $rrd_filename_escape = rrdtool_escape($rrd['filename']);

    $rrd_options .= " DEF:" . $in . $i . "=" . $rrd_filename_escape . ":" . $ds_in . ":AVERAGE ";
    $rrd_options .= " DEF:" . $out . $i . "=" . $rrd_filename_escape . ":" . $ds_out . ":AVERAGE ";

    $rrd_options .= " DEF:" . $in . $i . "_max=" . $rrd_filename_escape . ":" . $ds_in . ":MAX ";
    $rrd_options .= " DEF:" . $out . $i . "_max=" . $rrd_filename_escape . ":" . $ds_out . ":MAX ";
    $rrd_options .= " DEF:" . $in . $i . "_min=" . $rrd_filename_escape . ":" . $ds_in . ":MIN ";
    $rrd_options .= " DEF:" . $out . $i . "_min=" . $rrd_filename_escape . ":" . $ds_out . ":MIN ";

    $RRAs = ['ave' => '', 'min' => '_min', 'max' => '_max'];

    foreach ($RRAs as $label => $rra) {
        $rrd_options .= " CDEF:inB" . $i . $rra . "=in" . $i . $rra . ",$multiplier,* ";
        $rrd_options .= " CDEF:outB" . $i . $rra . "=out" . $i . $rra . ",$multiplier,*";
        $rrd_options .= " CDEF:outB" . $i . $rra . "_neg=outB" . $i . $rra . ",-1,*";
        $rrd_options .= " CDEF:octets" . $i . $rra . "=inB" . $i . $rra . ",outB" . $i . $rra . ",+";
    }

    $rrd_multi['in_thing'][]  = $in . $i;
    $rrd_multi['out_thing'][] = $out . $i;

    if ($vars['previous']) {
        $rrd_options .= " DEF:" . $in . $i . "X=" . $rrd_filename_escape . ":" . $ds_in . ":AVERAGE:start=" . $prev_from . ":end=" . $from;
        $rrd_options .= " DEF:" . $out . $i . "X=" . $rrd_filename_escape . ":" . $ds_out . ":AVERAGE:start=" . $prev_from . ":end=" . $from;
        $rrd_options .= " SHIFT:" . $in . $i . "X:$period";
        $rrd_options .= " SHIFT:" . $out . $i . "X:$period";

        $rrd_multi['in_thingX'][]  = "in" . $i . "X";
        $rrd_multi['out_thingX'][] = "out" . $i . "X";
    }

    $rrd_options .= " VDEF:totin" . $i . "=in" . $i . ",TOTAL";
    $rrd_options .= " VDEF:totout" . $i . "=out" . $i . ",TOTAL";
    $rrd_options .= " VDEF:tot" . $i . "=octets" . $i . ",TOTAL";

    if ($i) {
        $stack = ":STACK";
    }

    if ($vars['line_graph']) {
        $rrd_options .= " LINE1.25:inB" . $i . "#" . $colour_in . ":'" . $descr . "'";
    } else {
        $rrd_options .= " AREA:inB" . $i . "#" . $colour_in . ":'" . $descr . "'$stack";
    }
    $rrd_options .= " GPRINT:inB" . $i . ":LAST:%6.2lf%s$units";
    $rrd_options .= " GPRINT:inB" . $i . ":AVERAGE:%6.2lf%s$units";
    $rrd_options .= " GPRINT:inB" . $i . "_max:MAX:%6.2lf%s$units";

    if (!$nototal && $width > "500") {
        $rrd_options .= " VDEF:95thin" . $i . "=inB" . $i . ",95,PERCENT";
        $rrd_options .= " GPRINT:95thin" . $i . ":%6.2lf%s";
        $rrd_options .= " GPRINT:totin" . $i . ":%6.2lf%s$total_units";
    }

    $rrd_options .= " COMMENT:'\\l'";

    if ($vars['line_graph']) {
        $rrd_options .= " 'LINE1.25:outB" . $i . "_neg#" . $colour_out . ":" . $descr_out . "'";
    } else {
        $rrd_options  .= " 'HRULE:0#" . $colour_out . ":" . $descr_out . "'";
        $rrd_optionsb .= " 'AREA:outB" . $i . "_neg#" . $colour_out . ":$stack'";
    }
    $rrd_options .= " GPRINT:outB" . $i . ":LAST:%6.2lf%s$units";
    $rrd_options .= " GPRINT:outB" . $i . ":AVERAGE:%6.2lf%s$units";
    $rrd_options .= " GPRINT:outB" . $i . "_max:MAX:%6.2lf%s$units";

    if (!$nototal && $width > "500") {
        $rrd_options .= " VDEF:95thout" . $i . "=outB" . $i . ",95,PERCENT";
        $rrd_options .= " GPRINT:95thout" . $i . ":%6.2lf%s";
        $rrd_options .= " GPRINT:totout" . $i . ":%6.2lf%s$total_units";
    }
    $rrd_options .= " 'COMMENT:\\n'";

    $i++;
    $iter++;
}

$rrd_options .= " CDEF:" . $in . "octets=" . rrd_aggregate_dses($rrd_multi['in_thing']);
$rrd_options .= " CDEF:" . $out . "octets=" . rrd_aggregate_dses($rrd_multi['out_thing']);

$rrd_options .= " CDEF:doutoctets=outoctets,-1,*";
$rrd_options .= " CDEF:inbits=inoctets,8,*";
$rrd_options .= " CDEF:outbits=outoctets,8,*";
$rrd_options .= " CDEF:doutbits=doutoctets,8,*";
$rrd_options .= " VDEF:95thin=inbits,95,PERCENT";
$rrd_options .= " VDEF:95thout=outbits,95,PERCENT";
$rrd_options .= " CDEF:pout_tmp=doutbits,-1,* VDEF:dpout_tmp=pout_tmp,95,PERCENT CDEF:dpout_tmp2=doutbits,doutbits,-,dpout_tmp,-1,*,+ VDEF:d95thout=dpout_tmp2,FIRST";

$rrd_options .= " VDEF:totin=inoctets,TOTAL";
$rrd_options .= " VDEF:totout=outoctets,TOTAL";

if ($vars['previous'] == "yes") {

    $rrd_options .= " CDEF:" . $in . "octetsX=" . rrd_aggregate_dses($rrd_multi['in_thingX']);
    $rrd_options .= " CDEF:" . $out . "octetsX=" . rrd_aggregate_dses($rrd_multi['in_thingX']);
    $rrd_options .= " CDEF:doutoctetsX=outoctetsX,-1,*";
    $rrd_options .= " CDEF:inbitsX=inoctetsX,8,*";
    $rrd_options .= " CDEF:outbitsX=outoctetsX,8,*";
    $rrd_options .= " CDEF:doutbitsX=doutoctetsX,8,*";
    $rrd_options .= " VDEF:95thinX=inbitsX,95,PERCENT";
    $rrd_options .= " VDEF:95thoutX=outbitsX,95,PERCENT";
    $rrd_options .= " CDEF:poutX_tmp=doutbitsX,-1,* VDEF:dpoutX_tmp=poutX_tmp,95,PERCENT CDEF:dpoutX_tmp2=doutbitsX,doutbitsX,-,dpoutX_tmp,-1,*,+ VDEF:d95thoutX=dpoutX_tmp2,FIRST";
}

$rrd_options .= " 'COMMENT: \\n'";
$rrd_options .= " 'COMMENT:Aggregate Totals\\n'";

$rrd_options .= " GPRINT:totin:'%6.2lf%s$total_units'";
$rrd_options .= " 'COMMENT:" . str_pad("", $descr_len - 8) . "<'";
$rrd_options .= " GPRINT:inbits:LAST:%6.2lf%s$units";
$rrd_options .= " GPRINT:inbits:AVERAGE:%6.2lf%s$units";
$rrd_options .= " GPRINT:inbits:MAX:%6.2lf%s$units";
if (!$nototal && $width > "500") {
    $rrd_options .= " GPRINT:95thin:%6.2lf%s$units";
}
$rrd_options .= " COMMENT:\\n";

$rrd_options .= " GPRINT:totout:'%6.2lf%s$total_units'";
$rrd_options .= " 'COMMENT:" . str_pad("", $descr_len - 8) . ">'";
$rrd_options .= " GPRINT:outbits:LAST:%6.2lf%s$units";
$rrd_options .= " GPRINT:outbits:AVERAGE:%6.2lf%s$units";
$rrd_options .= " GPRINT:outbits:MAX:%6.2lf%s$units";
if (!$nototal && $width > "500") {
    $rrd_options .= " GPRINT:95thout:%6.2lf%s$units";
}
$rrd_options .= " COMMENT:\\n";

if ($custom_graph) {
    $rrd_options .= $custom_graph;
}

$rrd_options .= $rrd_optionsb;
$rrd_options .= " HRULE:0#999999";

if ($vars['previous'] == "yes") {
    $rrd_options .= " AREA:inbitsX#99999966:";
    $rrd_options .= " AREA:doutbitsX#99999966:";
}

// Clean
unset($rrd_multi, $in_thing, $out_thing, $pluses, $stack);

// EOF
