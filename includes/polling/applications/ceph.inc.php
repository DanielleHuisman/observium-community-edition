<?php

if (!empty($agent_data['app']['ceph'])) {
    $app_id = discover_app($device, 'ceph');

    [$osdtotal, $osdup, $osdin, $ops, $wrbps, $rdbps] = explode("\n", $agent_data['app']['ceph']);

    $data = [
      'OSD_total' => $osdtotal,
      'OSD_in'    => $osdup,
      'OSD_out'   => $osdin,
      'ops'       => $ops,
      'wrbps'     => $wrbps,
      'rdbps'     => $rdbps,

    ];

    rrdtool_update_ng($device, 'ceph', $data, $app_id);

    update_application($app_id, $data);

}

// EOF
