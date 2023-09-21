<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// FIXME - INSTANCES

if (!empty($agent_data['app']['shoutcast'])) {
    $app_id = discover_app($device, 'shoutcast');

    // Polls shoutcast statistics from agent script
    $servers = explode("\n", $agent_data['app']['shoutcast']);
    $data    = [];

    foreach ($servers as $item => $server) {
        $server = trim($server);

        if (!empty($server)) {
            $data = explode(";", $server);
            [$host, $port] = explode(":", $data[0], 2);

            $stats[$data[0]] = [
              'bitrate'  => $data[1],
              'traf_in'  => $data[2],
              'traf_out' => $data[3],
              'current'  => $data[4],
              'status'   => $data[5],
              'peak'     => $data[6],
              'max'      => $data[7],
              'unique'   => $data[8],
            ];

            rrdtool_update_ng($device, 'shoutcast', $stats[$data[0]], "$app_id-" . $host . "_" . $port);

        }
    }

    update_application($app_id, $stats);

    unset($app_id, $host, $port, $data, $servers, $server, $item);
}

// EOF
