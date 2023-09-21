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

$rrd_options .= " -u 100 -l 0 -E -b 1024 ";

$iter = "1";

$colour      = "CC0000";
$colour_area = "ffaaaa";

if ($width > "500") {
    $descr_len = 13;
} else {
    $descr_len = 8;
    $descr_len += round(($width - 250) / 8);
}

if ($width > "500") {
    $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . "Total      Used      Free(    Min       Max      Avg)'";
    $rrd_options .= " COMMENT:'\l'";
} else {
    $rrd_options .= " COMMENT:'" . substr(str_pad($unit_text, $descr_len + 5), 0, $descr_len + 5) . "Total      Used      Free\l'";
}

$descr = rrdtool_escape(rewrite_entity_name($mempool['mempool_descr'], 'mempool'), $descr_len);

$perc       = round($mempool['mempool_perc'], 0);
$background = get_percentage_colours($perc);

$rrd_options .= " DEF:" . $mempool['mempool_id'] . "used=$rrd_filename_escape:used:AVERAGE";
$rrd_options .= " DEF:" . $mempool['mempool_id'] . "free=$rrd_filename_escape:free:AVERAGE";
$rrd_options .= " CDEF:" . $mempool['mempool_id'] . "size=" . $mempool['mempool_id'] . "used," . $mempool['mempool_id'] . "free,+";
$rrd_options .= " CDEF:" . $mempool['mempool_id'] . "perc=" . $mempool['mempool_id'] . "used," . $mempool['mempool_id'] . "size,/,100,*";
$rrd_options .= " CDEF:" . $mempool['mempool_id'] . "percx=100," . $mempool['mempool_id'] . "perc,-";
$rrd_options .= " AREA:" . $mempool['mempool_id'] . "perc#" . $background['right'] . ":";

if ($width > "500") {
    $rrd_options .= " LINE1.25:" . $mempool['mempool_id'] . "perc#" . $background['left'] . ":'$descr'";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "size:LAST:%6.2lf%sB";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "used:LAST:%6.2lf%sB";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "free:LAST:%6.2lf%sB";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "free:MIN:%5.2lf%sB";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "free:MAX:%5.2lf%sB";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "free:AVERAGE:%5.2lf%sB\\n";
    $rrd_options .= " COMMENT:'" . substr(str_pad('', $descr_len + 12), 0, $descr_len + 12) . " '";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "perc:LAST:'%5.2lf%%  '";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "percx:LAST:'%5.2lf%% '";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "percx:MIN:'%5.2lf%% '";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "percx:MAX:'%5.2lf%% '";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "percx:AVERAGE:%5.2lf%%\\n";
} else {
    $rrd_options .= " LINE1.25:" . $mempool['mempool_id'] . "perc#" . $background['left'] . ":'$descr'";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "size:LAST:%6.2lf%sB";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "used:LAST:%6.2lf%sB";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "free:LAST:%6.2lf%sB";
    $rrd_options .= " COMMENT:'\l'";
    $rrd_options .= " COMMENT:'" . substr(str_pad('', $descr_len + 12), 0, $descr_len + 12) . " '";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "perc:LAST:'%5.2lf%%  '";
    $rrd_options .= " GPRINT:" . $mempool['mempool_id'] . "percx:LAST:'%5.2lf%% '";
    $rrd_options .= " COMMENT:'\l'";
}

// EOF
