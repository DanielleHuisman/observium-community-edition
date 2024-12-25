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

$i = 1;

foreach (dbFetchRows("SELECT * FROM `ucd_diskio` AS U, `devices` AS D WHERE D.device_id = ? AND U.device_id = D.device_id", [$device['device_id']]) as $disk) {
    $rrd_filename = get_rrd_path($device, "ucd_diskio-" . $disk['diskio_descr'] . ".rrd");
    if (rrd_is_file($rrd_filename)) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $disk['diskio_descr'];
        $rrd_list[$i]['ds_in']    = $ds_in;
        $rrd_list[$i]['ds_out']   = $ds_out;
        $i++;
    }
}

?>
