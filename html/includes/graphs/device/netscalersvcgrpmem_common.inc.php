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

foreach (dbFetchRows("SELECT * FROM `netscaler_servicegroupmembers` AS NS, `devices` AS D WHERE D.device_id = ? AND NS.device_id = D.device_id", [$device['device_id']]) as $svc) {
    $rrd_filename = get_rrd_path($device, "nscaler-svcgrpmem-" . $svc['svc_name'] . ".rrd");

    if (rrd_is_file($rrd_filename)) {
        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $svc['svc_label'];
        $rrd_list[$i]['ds']       = $ds;
        $i++;
    }
}

// EOF
