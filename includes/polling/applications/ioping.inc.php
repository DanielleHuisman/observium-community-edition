<?php
/*
* includes/polling/applications/ioping.inc.php
* Observium
*
*   This file is part of Observium.
*
* @package    observium
* @subpackage poller
* @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
*
*/

if (!empty($agent_data['app']['ioping'])) {

    foreach (explode("\n", $agent_data['app']['ioping']) as $data) {

        $data = preg_split('/\s+/', $data);

        $app_instance = $data[0];
        $app_id       = discover_app($device, 'ioping', $app_instance);

        $data['reqs']       = $data[1];
        $data['rtime']      = $data[2];
        $data['reqps']      = $data[3];
        $data['tfspeed']    = $data[4];
        $data['minreqtime'] = $data[5];
        $data['avgreqtime'] = $data[6];
        $data['maxreqtime'] = $data[7];
        $data['reqstdev']   = $data[8];

        update_application($app_id, $data);

        rrdtool_update_ng($device, 'ioping', $data, $app_instance);

    }
}