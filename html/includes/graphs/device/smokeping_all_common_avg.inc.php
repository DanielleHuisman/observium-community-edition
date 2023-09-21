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

$i           = 0;
$pings       = 20;
$interval    = 300;
$description = "100 Bytes";
if (isset($config['smokeping']['pings'])) {
    $pings = $config['smokeping']['pings'];
}                 # matt.ayre: allow user config to annotate correct
if (isset($config['smokeping']['interval'])) {
    $interval = $config['smokeping']['interval'];
}                 # number of pings, interval duration (s) and
if (isset($config['smokeping']['description'])) {
    $description = $config['smokeping']['description'];
}                 # description (ie size and DSCP marking)
$iter      = 0;
$colourset = "mixed";

if ($width > "500") {
    $descr_len = 25;
} else {
    $descr_len = 12 + round(($width - 275) / 8);
}

// FIXME str_pad really needs a "limit to length" so we can rid of all the substrs all over the code to limit the length as below...
if ($width > "500") {
    $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . " RTT      Loss    SDev   RTT\:SDev\l'";
} else {
    $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . " RTT      Loss    SDev   RTT\:SDev\l'";
}

$p      = $pings;                # matt.ayre set up new constants
$swidth = 0.0001;

$lc = [
  0              => ['0', '#26ff00'],
  1              => ["1/$p", '#00b8ff'],
  2              => ["2/$p", '#0059ff'],
  3              => ["3/$p", '#5e00ff'],
  4              => ["4/$p", '#7e00ff'],
  intval($p / 2) => [intval($p / 2) . "/$p", '#dd00ff'],
  $p - 1         => [($p - 1) . "/$p", '#ff0000'],
];

foreach ($smokeping_files[$direction][$device['hostname']] as $source => $filename) {

    if (!isset($config['graph_colours'][$colourset][$iter])) {
        $iter = 0;
    }
    $colour = $config['graph_colours'][$colourset][$iter];
    $iter++;

    $descr = rrdtool_escape($source, $descr_len);

    $rrd_options .= " DEF:median$i=" . $filename . ":median:AVERAGE ";
    $rrd_options .= " CDEF:dm$i=median$i";                            # matt.ayre: removed trailing ",UN,0,median$i,IF" to fix red drop lines at the end of smokeping graphs
    $rrd_options .= " DEF:loss$i=" . $filename . ":loss:AVERAGE";
    $rrd_options .= " CDEF:ploss$i=loss$i,$pings,/,100,*";
#  $rrd_options .= " CDEF:dm$i=median$i";
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

    $dm_list    .= ",dm$i,+";
    $sd_list    .= ",s2d$i,+";
    $ploss_list .= ",ploss$i,+";


    ksort($lc);                               # matt.ayre: begin section for new CDEF/AREA/STACK for line and background colour
    $last = -1;
    foreach ($lc as $loss => $lc_loss) {
        $lvar = intval($loss);

        $swidth   = 0.0001;
        $bg_trans = "60";
        if ($loss == 0) {
            $bg_trans = "00";
            $swidth   = 0.0001;
        }

        // line and legend (@median)
        $rrd_options    .= " CDEF:me$lvar=loss$i,$last,GT,loss$i,$loss,LE,*,1,UNKN,IF,median$i,*";
        $rrd_options    .= " CDEF:meL$lvar=me$lvar,$swidth,-";
        $rrd_options    .= " CDEF:meH$lvar=me$lvar,$i,*,$swidth,2,*,+";
        $rrd_options_lc .= " AREA:meL$lvar";
        $rrd_options_lc .= " STACK:meH$lvar$lc_loss[1]:$lc_loss[0]";

        // background (@lossargs)
        $rrd_options    .= " CDEF:lossbg$lvar=loss$i,$last,GT,loss$i,$loss,LE,*,INF,UNKN,IF";
        $rrd_options_bg .= " AREA:lossbg$lvar$lc_loss[1]$bg_trans";

        $last = $loss;
    }

    $i++;

}

$descr = rrdtool_escape("Average", $descr_len);

$rrd_options .= " CDEF:ploss_all=0" . $ploss_list . ",$i,/";
$rrd_options .= " CDEF:dm_all=0" . $dm_list . ",$i,/";
#  $rrd_options .= " CDEF:dm_all_clean=dm_all,UN,NaN,dm_all,IF";
$rrd_options .= " CDEF:sd_all=0" . $sd_list . ",$i,/";
$rrd_options .= " CDEF:dmlow_all=dm_all,sd_all,2,/,-";

$rrd_options .= " AREA:dmlow_all";
$rrd_options .= " AREA:sd_all#AAAAAA::STACK";
$rrd_options .= " LINE1:dm_all#CC0000:'$descr'";

$rrd_options .= " VDEF:avmed=dm_all,AVERAGE";
$rrd_options .= " VDEF:avsd=sd_all,AVERAGE";
$rrd_options .= " CDEF:msr=dm_all,POP,avmed,avsd,/";
$rrd_options .= " VDEF:avmsr=msr,AVERAGE";

$rrd_options .= " GPRINT:avmed:'%5.1lf%ss'";
$rrd_options .= " GPRINT:ploss_all:AVERAGE:'%5.3lf%%'";
$rrd_options .= " GPRINT:avsd:'%5.1lf%Ss'";
$rrd_options .= " GPRINT:avmsr:'%5.1lf%s\\l'";

$rrd_options .= " COMMENT:' \l'";                   # matt.ayre: legend loss thresholds
$rrd_options .= " COMMENT:'loss color\:'";

$rrd_options .= $rrd_options_lc;
$rrd_options .= $rrd_options_bg;

$rrd_options .= " COMMENT:'\l'";                    # matt.ayre: annotation showing smokeping parameters
$rrd_options .= " COMMENT:'probes\:\t$pings ICMP Echo Pings ($description) every {$interval}s\l'";

// EOF
