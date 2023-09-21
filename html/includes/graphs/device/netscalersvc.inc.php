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

// Generate a list of svcs and build an rrd_list array using arguments passed from parent

foreach (dbFetchRows("SELECT * FROM `netscaler_services` WHERE `device_id` = ?", [$device['device_id']]) as $svc) {
    $rrd_filename = get_rrd_path($device, "nscaler-svc-" . $svc['svc_name'] . ".rrd");

    if (rrd_is_file($rrd_filename)) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $svc['svc_name'];

        if (isset($ds_in) && isset($ds_out)) {
            $rrd_list[$i]['descr_in']  = $svc['svc_name'];
            $rrd_list[$i]['descr_out'] = $svc['svc_ip'] . ":" . $svc['svc_port'];
            $rrd_list[$i]['ds_in']     = $ds_in;
            $rrd_list[$i]['ds_out']    = $ds_out;
        } else {
            $rrd_list[$i]['ds'] = $ds;
        }
        $i++;
    }

    unset($ignore);
}

// EOF
