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

// Dear Tobias. You write in Perl, this makes me hate you forever.
// This is my translation of Smokeping's graphing.
// Thanks to Bill Fenner for Perl->Human translation:>

$scale_min   = 0;
$scale_rigid = TRUE;

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");
include("smokeping_common.inc.php");

$i         = 0;
$pings     = 20;
$iter      = 0;
$colourset = "mixed";

if ($width > "500") {
    $descr_len = 25;
} else {
    $descr_len = 12 + round(($width - 275) / 8);
}

if ($width > "500") {
    $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . " RTT      Loss    SDev   RTT\:SDev\l'";
} else {
    $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . " RTT      Loss    SDev   RTT\:SDev\l'";
}

foreach ($smokeping_files[$direction][$device['hostname']] as $source => $filename) {

    if (!isset($config['graph_colours'][$colourset][$iter])) {
        $iter = 0;
    }
    $colour = $config['graph_colours'][$colourset][$iter];
    $iter++;

    $descr = rrdtool_escape($source, $descr_len);

    $rrd_options .= " DEF:median$i=" . $filename . ":median:AVERAGE ";
    $rrd_options .= " DEF:loss$i=" . $filename . ":loss:AVERAGE";
    $rrd_options .= " CDEF:ploss$i=loss$i,$pings,/,100,*";
    $rrd_options .= " CDEF:dm$i=median$i";
#  $rrd_options .= " CDEF:dm$i=median$i,0,".$max->{$start}.",LIMIT";

    // start emulate Smokeping::calc_stddev
    foreach (range(1, $pings) as $p) {
        $rrd_options .= " DEF:pin" . $i . "p" . $p . "=" . $filename . ":ping" . $p . ":AVERAGE";
        $rrd_options .= " CDEF:p" . $i . "p" . $p . "=pin" . $i . "p" . $p . ",UN,0,pin" . $i . "p" . $p . ",IF";
    }

    unset($pings_options, $m_options, $sdev_options);

    foreach (range(2, $pings) as $p) {
        $pings_options .= ",p" . $i . "p" . $p . ",UN,+";
        $m_options     .= ",p" . $i . "p" . $p . ",+";
        $sdev_options  .= ",p" . $i . "p" . $p . ",m" . $i . ",-,DUP,*,+";
    }

    $rrd_options .= " CDEF:pings" . $i . "=" . $pings . ",p" . $i . "p1,UN" . $pings_options . ",-";
    $rrd_options .= " CDEF:m" . $i . "=p" . $i . "p1" . $m_options . ",pings" . $i . ",/";
    $rrd_options .= " CDEF:sdev" . $i . "=p" . $i . "p1,m" . $i . ",-,DUP,*" . $sdev_options . ",pings" . $i . ",/,SQRT";
    // end emulate Smokeping::calc_stddev

    $rrd_options .= " CDEF:dmlow$i=dm$i,sdev$i,2,/,-";
    $rrd_options .= " CDEF:s2d$i=sdev$i";
    $rrd_options .= " AREA:dmlow$i";
    $rrd_options .= " AREA:s2d$i#" . $colour . "30::STACK";
    $rrd_options .= " LINE1:dm$i#" . $colour . ":'$descr'";

#  $rrd_options .= " LINE1:sdev$i#000000:$descr";

    $rrd_options .= " VDEF:avmed$i=median$i,AVERAGE";
    $rrd_options .= " VDEF:avsd$i=sdev$i,AVERAGE";
    $rrd_options .= " CDEF:msr$i=median$i,POP,avmed$i,avsd$i,/";
    $rrd_options .= " VDEF:avmsr$i=msr$i,AVERAGE";

    $rrd_options .= " GPRINT:avmed$i:'%5.1lf%ss'";
    $rrd_options .= " GPRINT:ploss$i:AVERAGE:'%5.1lf%%'";

    $rrd_options .= " GPRINT:avsd$i:'%5.1lf%Ss'";
    $rrd_options .= " GPRINT:avmsr$i:'%5.1lf%s\\l'";

    $i++;
}

?>
