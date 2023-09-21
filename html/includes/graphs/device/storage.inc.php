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

$scale_min = "0";
$scale_max = "100";

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

if ($width > 350) {
    $padding    = 45;
    $text_width = 7;
} else {
    $padding    = 39;
    $text_width = 6;
}
$legend_len = ($vars['width'] + $padding) / $text_width;
$descr_len  = $legend_len - 31;

$iter                         = 0;
$colours                      = 'mixed-10c';
$rrd_options                  .= " COMMENT:'" . str_pad('Size       Used  % Used', $legend_len, ' ', STR_PAD_LEFT) . "\\l'";
$graph_return['legend_lines'] = 1;


foreach (dbFetchRows("SELECT * FROM storage where device_id = ?", [$device['device_id']]) as $storage) {
    if (!$config['graph_colours'][$colours][$iter]) {
        $iter = 0;
    }
    $colour = $config['graph_colours'][$colours][$iter];

    $descr = rrdtool_escape(rewrite_entity_name($storage['storage_descr'], 'storage'), $descr_len);
    $rrd   = get_rrd_path($device, "storage-" . strtolower($storage['storage_mib']) . "-" . $storage['storage_descr'] . ".rrd");
    if (rrd_is_file($rrd)) {
        $rrd_filename_escape = rrdtool_escape($rrd);
        $rrd_options         .= " DEF:" . $storage['storage_id'] . "used=$rrd_filename_escape:used:AVERAGE";
        $rrd_options         .= " DEF:" . $storage['storage_id'] . "free=$rrd_filename_escape:free:AVERAGE";
        $rrd_options         .= " CDEF:" . $storage['storage_id'] . "size=" . $storage['storage_id'] . "used," . $storage['storage_id'] . "free,+";
        $rrd_options         .= " CDEF:" . $storage['storage_id'] . "perc=" . $storage['storage_id'] . "used," . $storage['storage_id'] . "size,/,100,*";
        $rrd_options         .= " LINE1.25:" . $storage['storage_id'] . "perc#" . $colour . ":'$descr'";
        $rrd_options         .= " GPRINT:" . $storage['storage_id'] . "size:LAST:%6.2lf%sB";
        $rrd_options         .= " GPRINT:" . $storage['storage_id'] . "used:LAST:%6.2lf%sB";
        $rrd_options         .= " GPRINT:" . $storage['storage_id'] . "perc:LAST:%5.2lf%%\\l";
        $iter++;
        $graph_return['legend_lines']++;
        $graph_return['rrds'][] = $rrd;
    } else {
        $graph_return['missing_rrds'][] = $rrd;
    }
}




// EOF
