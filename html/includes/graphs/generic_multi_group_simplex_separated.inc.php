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

if ($width > "1000") {
    $descr_len = 36;
} elseif ($width > "500") {
    $descr_len = 24;
} else {
    $descr_len = 12;
    $descr_len += round(($width - 250) / 8);
}

if ($nototal) {
    $descrlen += "2";
    $unitlen  += "2";
}

if ($width > "500") {
    if (!$noheader) {
        $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . "  Now     Min      Max     Avg'";
        if (!$nototal) {
            $rrd_options .= " COMMENT:'Total      '";
        }
        $rrd_options .= " COMMENT:'\l'";
    }
} else {
    if (!$noheader) {
        $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . "  Now     Min      Max     Avg\l'";
    }
    $nototal = 1;
}

$colour_iter = 0;
$rrd_multi   = [];
$i           = 1;

if (isset($colour_scheme)) {
    $scheme_colours = generate_palette(safe_count($groups), $colour_scheme);
}

foreach ($groups as $id => $group) {

    foreach ($group['list'] as $rrd_id => $rrd) {

        $i++;

        $ds      = $rrd['ds'] . "_" . $i;
        $orig_ds = $ds;

        $rrd_options .= " DEF:" . $ds . "=" . rrdtool_escape($rrd['filename']) . ":" . $rrd['ds'] . ":AVERAGE ";

        # if we've been passed a multiplier we must make a CDEF based on it!
        if (is_numeric($multiplier) || is_numeric($divider) || is_numeric($divisor)) {

            if (is_numeric($divider)) {
                $multiplier = float_div(1, $divisor);
            }
            if (is_numeric($divisor)) {
                $multiplier = float_div(1, $divider);
            }

            $rrd_options .= " CDEF:c_" . $ds . "=" . $ds . "," . $multiplier . ",*";

            //$rrd_options .= " CDEF:c_" . $ds . "min=" . $ds . "min," . $multiplier . ",*";
            //$rrd_options .= " CDEF:c_" . $ds . "max=" . $ds . "max," . $multiplier . ",*";

            $ds = "c_" . $ds;
        }

        // Create Aggreage
        $group['aggregate'][] = $ds;
        $group['ds_list'][]   = $ds;
    }

    $group_ds = $id;


    if ($rrd['colour']) {
        $colour = $rrd['colour'];
    } elseif (isset($scheme_colours[$id])) {
        if (is_array($scheme_colours[$id]) && isset($scheme_colours[$id][$rrd_id])) {
            $colour = ltrim($scheme_colours[$id][$rrd_id], '#');
        } else {
            $colour = ltrim($scheme_colours[$id], '#');
        }
    } else {
        if (!$config['graph_colours'][$colours][$colour_iter]) {
            $colour_iter = 0;
        }
        $colour = $config['graph_colours'][$colours][$colour_iter];
        $colour_iter++;
    }

    if (!is_array($group['ds_list'])) {
        continue;
    }

    $rrd_options .= " CDEF:" . $group_ds . "=" . rrd_aggregate_dses($group['ds_list']);


    if ($rrd['invert']) {
        $rrd_options  .= " CDEF:" . $group_ds . "i=" . $group_ds . ",-1,*";
        $rrd_optionsc .= " AREA:" . $group_ds . "i#" . $colour . ":'" . rrdtool_escape($group['descr'], $descr_len) . "'" . $cstack;
        $rrd_optionsc .= " GPRINT:" . $group_ds . ":LAST:%5.1lf%s GPRINT:" . $group_ds . ":MIN:%5.1lf%s";
        $rrd_optionsc .= " GPRINT:" . $group_ds . ":MAX:%5.1lf%s GPRINT:" . $group_ds . ":AVERAGE:%5.1lf%s";
        $cstack       = ":STACK";
        if (!$nototal) {
            $rrd_optionsc .= " GPRINT:tot" . $group_ds . ":%5.2lf%s" . rrdtool_escape($total_units) . "";
        }
        $rrd_optionsc .= "'\\n' COMMENT:'\\n'";
    } else {
        $rrd_optionsb .= " AREA:" . $group_ds . "#" . $colour . ":'" . rrdtool_escape($group['descr'], $descr_len) . "'" . $bstack;
        $rrd_optionsb .= " GPRINT:" . $group_ds . ":LAST:%5.1lf%s GPRINT:" . $group_ds . ":MIN:%5.1lf%s";
        $rrd_optionsb .= " GPRINT:" . $group_ds . ":MAX:%5.1lf%s GPRINT:" . $group_ds . ":AVERAGE:%5.1lf%s";
        $bstack       = ":STACK";
        if (!$nototal) {
            $rrd_optionsb .= " GPRINT:tot" . $group_ds . ":%5.2lf%s" . rrdtool_escape($total_units) . "";
        }
        $rrd_optionsb .= "'\\n' COMMENT:'\\n'";
    }
}

/*

if ($vars['previous'] == "yes")
{
  $thingX  = implode(',', $rrd_multi['thingX']);
  $plusesX = str_repeat(',ADDNAN', safe_count($rrd_multi['thingX']) - 1);
  if (is_numeric($multiplier))
  {
    $rrd_options .= " CDEF:X=" . $thingX . $plusesX.",".$multiplier. ",*";
  }
  else if (is_numeric($divider))
  {
    $rrd_options .= " CDEF:X=" . $thingX . $plusesX.",".$divider. ",/";
  } else {
    $rrd_options .= " CDEF:X=" . $thingX . $plusesX;
  }

  $rrd_options .= " AREA:X#99999999:";
  $rrd_options .= " LINE1.25:X#666666:";

}

*/

$rrd_options .= $rrd_optionsb;

/*
if($show_aggregate == TRUE)
{
  $rrd_options .= " CDEF:aggregate=" . implode(',', $rrd_multi['aggregate']) . str_repeat(',+', safe_count($rrd_multi['aggregate']) - 1);
  $rrd_options .= " LINE1.5:aggregate#000000:'".rrdtool_escape("Aggregate", $descr_len)."'";
  $rrd_options .= " GPRINT:aggregate:LAST:%5.1lf%s GPRINT:aggregate:MIN:%5.1lf%s";
  $rrd_options .= " GPRINT:aggregate:MAX:%5.1lf%s GPRINT:aggregate:AVERAGE:%5.1lf%s";
  $rrd_options .= "'\\n' COMMENT:'\\n'";
}
*/

$rrd_options .= " HRULE:0#555555";
$rrd_options .= $rrd_optionsc;

// Clean
unset($rrd_multi, $thingX, $plusesX, $cstack, $bstack);

// EOF
