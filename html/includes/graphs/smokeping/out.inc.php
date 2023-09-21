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

$dest = device_by_id_cache($vars['dest']);

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
    $descr_len = 18;
} else {
    $descr_len = 12 + round(($width - 275) / 8);
}

if ($width > "500") {
    $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . " RTT      Loss    SDev   RTT\:SDev                              \l'";
} else {
    $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . " RTT      Loss    SDev   RTT\:SDev                              \l'";
}

$target = $dest['hostname'];
if ($config['smokeping']['suffix']) {
    $target = str_replace($config['smokeping']['suffix'], "", $target);
}
if ($config['smokeping']['split_char']) {
    $target = str_replace(".", $config['smokeping']['split_char'], $target);
}

if (isset($config['smokeping']['master_hostname'])) {
    $master_hostname = $config['smokeping']['master_hostname'];
} else {
    $master_hostname = $config['own_hostname'] ?: get_localhost();
}

foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($config['smokeping']['dir'])) as $filename_dir) {
    if (($device['hostname'] == $master_hostname) && strstr($filename_dir, $target . ".rrd")) {
        $filename = $filename_dir;
    } elseif (strstr($filename_dir, $target . "~" . $device['hostname'] . ".rrd")) {
        $filename = $filename_dir;
    }
}

if (!isset($config['graph_colours'][$colourset][$iter])) {
    $iter = 0;
}
$colour = $config['graph_colours'][$colourset][$iter];
$iter++;

$descr           = rrdtool_escape($source, $descr_len);
$filename_escape = rrdtool_escape($filename);

$rrd_options .= " DEF:median$i=" . $filename_escape . ":median:AVERAGE ";
$rrd_options .= " DEF:loss$i=" . $filename_escape . ":loss:AVERAGE";
$rrd_options .= " CDEF:ploss$i=loss$i,$pings,/,100,*";
$rrd_options .= " CDEF:dm$i=median$i";
#  $rrd_options .= " CDEF:dm$i=median$i,0,".$max->{$start}.",LIMIT";

// start emulate Smokeping::calc_stddev
foreach (range(1, $pings) as $p) {
    $rrd_options .= " DEF:pin" . $i . "p" . $p . "=" . $filename_escape . ":ping" . $p . ":AVERAGE";
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

// EOF
