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

$colour_iter = 0;
$rrd_multi   = [];
$i           = 0;
$g_i         = 0;
$c_i         = 0;

if ($vars['perc'] || $vars['perc_agg']) {
    $scale_min = 0;
}


include_once($config['html_dir'] . '/includes/graphs/common.inc.php');
include_once($config['html_dir'] . '/includes/graphs/legend.inc.php');

foreach ($data as $id => $group) {

    if (!$config['graph_colours'][$colours_in][$iter] || !$config['graph_colours'][$colours_out][$iter]) {
        $iter = 0;
    }
    if (!isset($group['colours_in'])) {
        $group['colours_in'] = $config['group_colours'][$g_i]['in'];
    }
    if (!isset($group['colours_out'])) {
        $group['colours_out'] = $config['group_colours'][$g_i]['out'];
    }

    foreach ($group['rrds'] as $rrd) {

        if (!$config['graph_colours'][$group['colours_out']][$c_i] || !$config['graph_colours'][$group['colours_in']][$c_i]) {
            $c_i = 0;
        }
        $colour_in  = $config['graph_colours'][$group['colours_in']][$c_i];
        $colour_out = $config['graph_colours'][$group['colours_out']][$c_i];

        $ds_in  = $rrd['ds_in'] . "_" . $i;
        $ods_in = $ds_in;

        $ds_out  = $rrd['ds_out'] . "_" . $i;
        $ods_out = $ds_out;

        $rrd_filename_escape = rrdtool_escape($rrd['file']);

        $rrd_options .= " DEF:" . $ds_in . "=" . $rrd_filename_escape . ":" . $rrd['ds_in'] . ":AVERAGE ";         // Load data
        $rrd_options .= " DEF:" . $ds_out . "=" . $rrd_filename_escape . ":" . $rrd['ds_out'] . ":AVERAGE ";       // Load data

        $ds_io = "io_" . $i;

        $rrd_options .= " CDEF:" . $ds_io . "=" . $ds_in . "," . $ds_out . ",ADDNAN";

        // if we've been passed a multiplier we must make a CDEF based on it!
        if (is_numeric($multiplier) || is_numeric($divider) || is_numeric($divisor)) {

            if (is_numeric($divider)) {
                $multiplier = 1 / $divisor;
            }
            if (is_numeric($divisor)) {
                $multiplier = 1 / $divider;
            }

            $rrd_options .= " CDEF:c_" . $ds_in . "=" . $ds_in . "," . $multiplier . ",*";
            $rrd_options .= " CDEF:c_" . $ds_out . "=" . $ds_out . "," . $multiplier . ",*";
            $rrd_options .= " CDEF:c_" . $ds_io . "=" . $ds_io . "," . $multiplier . ",*";

            $ds_in  = "c_" . $ds_in;
            $ds_out = "c_" . $ds_out;
            $ds_tot = "c_" . $ds_io;

        }

        // Create Aggregate
        $group['ds_list_in'][]  = $ds_in;
        $group['ds_list_out'][] = $ds_out;
        $group['ds_list_io'][]  = $ds_io;

        /* per-entity

        $rrd_options_b .= " AREA:" . $ds_in . "#".$colour_in.":'" . $rrd['descr'] . "'$stack";
        $rrd_options_c .= " AREA:" . $ds_out . "#".$colour_out.":'" . $rrd['descr'] . "'$stack";

        $stack = ":STACK";

        */

        $i++;
        $c_i++;

    }

    $group_ds = $id;

    $rrd_options .= " CDEF:" . $group_ds . "_in=" . rrd_aggregate_dses($group['ds_list_in']);
    $rrd_options .= " CDEF:" . $group_ds . "_out=" . rrd_aggregate_dses($group['ds_list_out']);
    $rrd_options .= " CDEF:" . $group_ds . "_io=" . rrd_aggregate_dses($group['ds_list_io']);

    $rrd_options .= " VDEF:" . $group_ds . "_tot_in=" . $group_ds . "_in,TOTAL";
    $rrd_options .= " VDEF:" . $group_ds . "_tot_out=" . $group_ds . "_out,TOTAL";
    $rrd_options .= " VDEF:" . $group_ds . "_tot_io=" . $group_ds . "_io,TOTAL";

    $rrd_options_b .= " CDEF:" . $group_ds . "_perc_in=" . $group_ds . "_in,agg_in,/,100,*";
    $rrd_options_b .= " CDEF:" . $group_ds . "_perc_out=" . $group_ds . "_out,-1,*,agg_out,/,100,*";
    $rrd_options_b .= " CDEF:" . $group_ds . "_perc_io=" . $group_ds . "_io,agg_io,/,100,*";


    // Create Aggregate
    $group_ds_in[]  = $group_ds . "_in";
    $group_ds_out[] = $group_ds . "_out";
    $group_ds_io[]  = $group_ds . "_io";


    $rrd_options_d .= " HRULE:0#" . $colour_in . ":'" . rrdtool_escape($group['descr'], $descr_len) . "'";
    $rrd_options_d .= " GPRINT:" . $group_ds . "_in:LAST:%6.2lf%s$units";
    $rrd_options_d .= " GPRINT:" . $group_ds . "_in:AVERAGE:%6.2lf%s$units";
    $rrd_options_d .= " GPRINT:" . $group_ds . "_in:MAX:%6.2lf%s$units";
    $rrd_options_d .= " GPRINT:" . $group_ds . "_tot_in:%6.2lf%sB\l";

    $rrd_options_d .= " HRULE:0#" . $colour_out . ":'" . rrdtool_escape($group['descr'], $descr_len) . "'";
    $rrd_options_d .= " GPRINT:" . $group_ds . "_out:LAST:%6.2lf%s$units";
    $rrd_options_d .= " GPRINT:" . $group_ds . "_out:AVERAGE:%6.2lf%s$units";
    $rrd_options_d .= " GPRINT:" . $group_ds . "_out:MAX:%6.2lf%s$units";
    $rrd_options_d .= " GPRINT:" . $group_ds . "_tot_out:%6.2lf%sB\l";

    if ($vars['perc_agg']) {
        $rrd_options_b .= " AREA:" . $group_ds . "_perc_io#" . $group['colour_in'] . ":'" . "'$g_stack";
    } elseif ($vars['perc']) {
        $rrd_options_b .= " AREA:" . $group_ds . "_perc_in#" . $group['colour_in'] . ":'" . "'$g_stack";
        $rrd_options_c .= " AREA:" . $group_ds . "_perc_out#" . $group['colour_out'] . ":'" . "'$g_stack";
    } else {
        $rrd_options_b .= " AREA:" . $group_ds . "_in#" . $group['colour_in'] . ":'" . "'$g_stack";
        $rrd_options_c .= " AREA:" . $group_ds . "_out#" . $group['colour_out'] . ":'" . "'$g_stack";
    }

    $g_stack = ":STACK";
    $g_i++;

}

$rrd_options .= " CDEF:agg_in=" . rrd_aggregate_dses($group_ds_in);
$rrd_options .= " CDEF:agg_out=" . rrd_aggregate_dses($group_ds_out);
$rrd_options .= " CDEF:agg_io=" . rrd_aggregate_dses($group_ds_io);

$rrd_options .= " VDEF:agg_tot_in=agg_in,TOTAL";
$rrd_options .= " VDEF:agg_tot_out=agg_out,TOTAL";
$rrd_options .= " VDEF:agg_tot_io=agg_io,TOTAL";

$rrd_options .= $rrd_options_b;
$rrd_options .= $rrd_options_c;
$rrd_options .= $rrd_options_d;

//r($data);
