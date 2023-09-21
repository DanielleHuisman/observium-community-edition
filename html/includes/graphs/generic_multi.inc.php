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

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

if ($width > "500") {
    $descr_len = 24;
} else {
    $descr_len = 12;
    $descr_len += round(($width - 250) / 8);
}

if ($nototal) {
    $descrlen += "2";
    $unitlen  += "2";
}

if (!$noheader) {
    if ($width > "500") {
        $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . "Now      Min      Max     Avg\l'";
        if (!$nototal) {
            $rrd_options .= " COMMENT:'Total      '";
        }
        $rrd_options .= " COMMENT:'\l'";
    } else {
        $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . "Now      Min      Max     Avg\l'";
    }
}

$i           = 0;
$colour_iter = 0;
$cstack      = '';
$bstack      = '';
foreach ($rrd_list as $rrd) {

    if ($rrd['colour']) {
        $colour = $rrd['colour'];
    } else {
        if (!$config['graph_colours'][$colours][$colour_iter]) {
            $colour_iter = 0;
        }
        $colour = $config['graph_colours'][$colours][$colour_iter];
        $colour_iter++;
    }

    $ds       = $rrd['ds'];
    $filename = rrdtool_escape($rrd['filename']);

    $descr = rrdtool_escape($rrd['descr'], $descr_len);

    $id = "ds" . $i;

    $rrd_options .= " DEF:" . $id . "=$filename:$ds:AVERAGE";

    if ($simple_rrd) {
        $rrd_options .= " CDEF:" . $id . "min=" . $id . " ";
        $rrd_options .= " CDEF:" . $id . "max=" . $id . " ";
    } else {
        $rrd_options .= " DEF:" . $id . "min=$filename:$ds:MIN";
        $rrd_options .= " DEF:" . $id . "max=$filename:$ds:MAX";
    }

    if (is_numeric($multiplier)) {
        $g_defname   = $rrd['ds'] . "_cdef";
        $rrd_options .= " CDEF:" . $id . "c=" . $id . "," . $multiplier . ",*";
        $rrd_options .= " CDEF:" . $id . "cmin=" . $id . "min," . $multiplier . ",*";
        $rrd_options .= " CDEF:" . $id . "cmax=" . $id . "max," . $multiplier . ",*";

        $id = $id . 'c';

        // If we've been passed a divider (divisor!) we make a CDEF for it.
    } elseif (is_numeric($divider)) {
        $g_defname   = $rrd['ds'] . "_cdef";
        $rrd_options .= " CDEF:" . $id . "c=" . $id . "," . $divider . ",/";
        $rrd_options .= " CDEF:" . $id . "cmin=" . $id . "min," . $divider . ",/";
        $rrd_options .= " CDEF:" . $id . "cmax=" . $id . "max," . $divider . ",/";

        $id = $id . 'c';

    }


    if ($rrd['invert']) {
        $rrd_options  .= " CDEF:" . $id . "i=" . $id . ",-1,*";
        $rrd_optionsc .= " AREA:" . $id . "i#" . $colour . ":'$descr'" . ($rrd['stack'] === FALSE ? '' : $cstack);
        $rrd_optionsc .= " GPRINT:" . $id . ":LAST:%5.1lf%s GPRINT:" . $id . "min:MIN:%5.1lf%s";
        $rrd_optionsc .= " GPRINT:" . $id . "max:MAX:%5.1lf%s GPRINT:" . $id . ":AVERAGE:'%5.1lf%s\\n'";
        $cstack       = ":STACK";
    } else {
        $rrd_optionsb .= " AREA:" . $id . "#" . $colour . ":'$descr'" . ($rrd['stack'] === FALSE ? '' : $bstack);
        $rrd_optionsb .= " GPRINT:" . $id . ":LAST:%5.1lf%s GPRINT:" . $id . "min:MIN:%5.1lf%s";
        $rrd_optionsb .= " GPRINT:" . $id . "max:MAX:%5.1lf%s GPRINT:" . $id . ":AVERAGE:'%5.1lf%s\\n'";
        $bstack       = ":STACK";
    }

    $i++;

}

$rrd_options .= $rrd_optionsb;
$rrd_options .= " HRULE:0#555555";
$rrd_options .= $rrd_optionsc;

unset($cstack, $bstack);

// EOF
