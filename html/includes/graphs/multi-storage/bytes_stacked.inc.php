<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     graphs
 * @copyright  (C) Adam Armstrong
 *
 */

// FIXME -- bit derpy, maybe replace this.

include_once($config['html_dir'] . "/includes/graphs/common.inc.php");

$scale_min = "0";
$scale_max = "100";

if ($width > 500) {
    $descr_len = 22;
} else {
    $descr_len = 12;
}
$descr_len += round(($width - 250) / 8);

if ($width > "1000") {
    $descr_len = 36;
} elseif ($width > "500") {
    $descr_len = 24;
} else {
    $descr_len = 12;
    $descr_len += round(($width - 200) / 8);
}

$iter    = 0;
$colours = 'mixed';

$rrd_options .= " COMMENT:'" . str_pad('Size      Used    %used', $descr_len + 31, ' ', STR_PAD_LEFT) . "\\l'";


foreach ($vars['id'] as $storage_id) {

    $storage      = dbFetchRow("SELECT * FROM `storage` WHERE `storage_id` = ?", [$storage_id]);
    $device       = device_by_id_cache($storage['device_id']);
    $rrd_filename = get_rrd_path($device, "storage-" . strtolower($storage['storage_mib']) . "-" . $storage['storage_descr'] . ".rrd");

    if (!$config['graph_colours'][$colours][$iter]) {
        $iter = 0;
    }
    $colour = $config['graph_colours'][$colours][$iter];

    $descr = rrdtool_escape(rewrite_entity_name($storage['storage_descr'], 'storage'), $descr_len);
    if (isset($storage['storage_type'])) {
        $storage['storage_mib'] = $storage['storage_type'];
    }

    if (rrd_is_file($rrd_filename)) {
        $rrd_filename_escape = rrdtool_escape($rrd_filename);

        $rrd_options .= " DEF:" . $storage['storage_id'] . "used=$rrd_filename_escape:used:AVERAGE";
        $rrd_options .= " DEF:" . $storage['storage_id'] . "free=$rrd_filename_escape:free:AVERAGE";
        $rrd_options .= " CDEF:" . $storage['storage_id'] . "size=" . $storage['storage_id'] . "used," . $storage['storage_id'] . "free,+";
        $rrd_options .= " CDEF:" . $storage['storage_id'] . "perc=" . $storage['storage_id'] . "used," . $storage['storage_id'] . "size,/,100,*";
        $rrd_options .= " AREA:" . $storage['storage_id'] . "used#" . $colour . ":'" . $descr . "':STACK";
        $rrd_options .= " GPRINT:" . $storage['storage_id'] . "size:LAST:%6.2lf%sB";
        $rrd_options .= " GPRINT:" . $storage['storage_id'] . "used:LAST:%6.2lf%sB";
        $rrd_options .= " GPRINT:" . $storage['storage_id'] . "perc:LAST:%5.2lf%%\\l";
        $iter++;


        // Build lists of DSes to aggregate later
        $ds_used[] = $storage['storage_id'] . "used";
        $ds_free[] = $storage['storage_id'] . "used";
        $ds_size[] = $storage['storage_id'] . "used";

    } else {
        //echo($rrd_filename);
    }
}

$rrd_options .= " CDEF:agg_used=" . rrd_aggregate_dses($ds_used);
$rrd_options .= " CDEF:agg_free=" . rrd_aggregate_dses($ds_free);
$rrd_options .= " CDEF:agg_size=" . rrd_aggregate_dses($ds_size);

$rrd_options .= " CDEF:agg_perc=agg_used,agg_size,/,100,*";
$rrd_options .= " COMMENT:' '\\n";
$rrd_options .= " COMMENT:'" . str_pad('Totals ', $descr_len + 2, ' ', STR_PAD_RIGHT) . "'";
$rrd_options .= " GPRINT:agg_size:LAST:%6.2lf%sB";
$rrd_options .= " GPRINT:agg_used:LAST:%6.2lf%sB";
$rrd_options .= " GPRINT:agg_perc:LAST:%5.2lf%%\\l";

// EOF
