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

$scale_min = 0;

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

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
        $rrd_options .= " COMMENT:'\l'";
    }
} else {
    if (!$noheader) {
        $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . "  Now     Min      Max     Avg\l'";
    }
    $nototal = 1;
}


$i       = 0;
$colours = "mixed-10b";

$device_state = safe_unserialize($device['device_state']);

$cpu_oids = ['ssCpuRawUser'      => ['colour' => 'c02020'],
             'ssCpuRawNice'      => ['colour' => '008f00'],
             'ssCpuRawSystem'    => ['colour' => 'ea8f00'],
             'ssCpuRawWait'      => ['colour' => '1f78b4'],
             'ssCpuRawInterrupt' => [],
             'ssCpuRawSoftIRQ'   => [],
             'ssCpuRawKernel'    => [],
             'ssCpuRawIdle'      => ['colour' => 'f5f5e500'],
];
$bstack   = '';
foreach ($cpu_oids as $stat => $data) {

    if (isset($device_state['ucd_ss_cpu'][$stat])) {

        if ($data['colour']) {
            $colour = $data['colour'];
        } else {
            if (!$config['graph_colours'][$colours][$colour_iter]) {
                $colour_iter = 0;
            }
            $colour = $config['graph_colours'][$colours][$colour_iter];
            $colour_iter++;
        }

        $rrd_filename        = get_rrd_path($device, "ucd_" . $stat . ".rrd");
        $rrd_filename_escape = rrdtool_escape($rrd_filename);

        $rrd_options   .= " DEF:" . $stat . "=" . $rrd_filename_escape . ":value:AVERAGE";
        $totals[]      = $stat;
        $rrd_options_b .= " CDEF:" . $stat . "_perc=" . $stat . ",total,/,100,*";

        $rrd_optionsc .= " AREA:" . $stat . "_perc#" . $colour . ":'" . rrdtool_escape(str_replace("ssCpuRaw", "", $stat), $descr_len) . "'$bstack";
        $rrd_optionsc .= " GPRINT:" . $stat . "_perc:LAST:%5.1lf%% GPRINT:" . $stat . "_perc:MIN:%5.1lf%%";
        $rrd_optionsc .= " GPRINT:" . $stat . "_perc:MAX:%5.1lf%% GPRINT:" . $stat . "_perc:AVERAGE:%5.1lf%%\\n";
        $bstack       = ":STACK";
    }

}

$rrd_options .= " CDEF:total=" . rrd_aggregate_dses($totals);
$rrd_options .= $rrd_options_b;
$rrd_options .= " HRULE:0#555555";
$rrd_options .= $rrd_optionsc;

// Clean
unset($rrd_multi, $thingX, $plusesX, $bstack);

// EOF
