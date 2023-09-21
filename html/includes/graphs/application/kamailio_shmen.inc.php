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

/*
  DS:shmemfragments:GAUGE:600:0:125000000000 \
  DS:shmemfreesize:GAUGE:600:0:125000000000 \
  DS:shmemmaxusedsize:GAUGE:600:0:125000000000 \
  DS:shmemrealusedsize:GAUGE:600:0:125000000000 \
  DS:shmemtotalsize:GAUGE:600:0:125000000000 \
  DS:shmemusedsize:GAUGE:600:0:125000000000 \
*/

$rrd_filename = get_rrd_path($device, "app-kamailio-" . $app['app_id'] . ".rrd");

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

if ($width > 500) {
    $descr_len = 22;
} else {
    $descr_len = 12;
}
$descr_len += round(($width - 150) / 8);

$iter    = 0;
$colours = 'mixed';

$rrd_options .= " COMMENT:'" . str_pad('Size   %used', $descr_len + 20, ' ', STR_PAD_LEFT) . "\\\l'";

if (rrd_is_file($rrd_filename)) {
    $colour = $config['graph_colours'][$colours][$iter];

    $descr = 'Max Used';

    $rrd_options .= " DEF:" . $iter . "used=$rrd_filename_escape:shmemmaxusedsize:AVERAGE";
    $rrd_options .= " DEF:" . $iter . "size=$rrd_filename_escape:shmemtotalsize:AVERAGE";
    $rrd_options .= " CDEF:" . $iter . "free=" . $iter . "size," . $iter . "used,-";
    $rrd_options .= " CDEF:" . $iter . "perc=" . $iter . "used," . $iter . "size,/,100,*";
    $rrd_options .= " AREA:" . $iter . "used#" . $colour . "10";
    $rrd_options .= " LINE1.25:" . $iter . "used#" . $colour . ":'$descr'";
    $rrd_options .= " GPRINT:" . $iter . "used:LAST:%6.2lf%sB";
    $rrd_options .= " GPRINT:" . $iter . "perc:LAST:%5.2lf%%\\\l";
    $iter++;

    $colour = $config['graph_colours'][$colours][$iter];

    $descr = 'Used';

    $rrd_options .= " DEF:" . $iter . "used=$rrd_filename_escape:shmemusedsize:AVERAGE";
    $rrd_options .= " DEF:" . $iter . "free=$rrd_filename_escape:shmemfreesize:AVERAGE";
    $rrd_options .= " DEF:" . $iter . "size=$rrd_filename_escape:shmemtotalsize:AVERAGE";
    $rrd_options .= " CDEF:" . $iter . "perc=" . $iter . "used," . $iter . "size,/,100,*";
    $rrd_options .= " AREA:" . $iter . "used#" . $colour . "10";
    $rrd_options .= " LINE1.25:" . $iter . "used#" . $colour . ":'$descr'";
    $rrd_options .= " GPRINT:" . $iter . "used:LAST:%6.2lf%sB";
    $rrd_options .= " GPRINT:" . $iter . "perc:LAST:%5.2lf%%\\\l";
    $iter++;

    $colour = $config['graph_colours'][$colours][$iter];

    $descr = 'Real Used';

    $rrd_options .= " DEF:" . $iter . "used=$rrd_filename_escape:shmemrealusedsize:AVERAGE";
    $rrd_options .= " DEF:" . $iter . "size=$rrd_filename_escape:shmemtotalsize:AVERAGE";
    $rrd_options .= " CDEF:" . $iter . "free=" . $iter . "size," . $iter . "used,-";
    $rrd_options .= " CDEF:" . $iter . "perc=" . $iter . "used," . $iter . "size,/,100,*";
    $rrd_options .= " AREA:" . $iter . "used#" . $colour . "10";
    $rrd_options .= " LINE1.25:" . $iter . "used#" . $colour . ":'$descr'";
    $rrd_options .= " GPRINT:" . $iter . "used:LAST:%6.2lf%sB";
    $rrd_options .= " GPRINT:" . $iter . "perc:LAST:%5.2lf%%\\\l";
    $iter++;

    $colour = $config['graph_colours'][$colours][$iter];

    $descr = 'Fragments';

    $rrd_options .= " DEF:" . $iter . "used=$rrd_filename_escape:shmemfragments:AVERAGE";
    $rrd_options .= " DEF:" . $iter . "size=$rrd_filename_escape:shmemtotalsize:AVERAGE";
    $rrd_options .= " CDEF:" . $iter . "free=" . $iter . "size," . $iter . "used,-";
    $rrd_options .= " CDEF:" . $iter . "perc=" . $iter . "used," . $iter . "size,/,100,*";
    $rrd_options .= " AREA:" . $iter . "used#" . $colour . "10";
    $rrd_options .= " LINE1.25:" . $iter . "used#" . $colour . ":'$descr'";
    $rrd_options .= " GPRINT:" . $iter . "used:LAST:%6.2lf%sB";
    $rrd_options .= " GPRINT:" . $iter . "perc:LAST:%5.2lf%%\\\l";
    $iter++;

    $colour = $config['graph_colours'][$colours][$iter];

    $descr = 'Total';

    $rrd_options .= " DEF:" . $iter . "size=$rrd_filename_escape:shmemtotalsize:AVERAGE";
    $rrd_options .= " LINE1.25:" . $iter . "size#" . $colour . ":'$descr'";
    $rrd_options .= " GPRINT:" . $iter . "size:LAST:%6.2lf%sB";
    $rrd_options .= "\\\l";
} else {
    echo("file missing: $rrd_filename");
}

// EOF
