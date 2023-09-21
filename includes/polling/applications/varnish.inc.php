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

if (!empty($agent_data['app']['varnish'])) {
    $app_id = discover_app($device, 'varnish');

    // Varnish specific output from agent
    $data = explode(";", $agent_data['app']['varnish']);

    $stats = [
      'backend_req'       => $data[0],
      'backend_unhealthy' => $data[1],
      'backend_busy'      => $data[2],
      'backend_fail'      => $data[3],
      'backend_reuse'     => $data[4],
      'backend_toolate'   => $data[5],
      'backend_recycle'   => $data[6],
      'backend_retry'     => $data[7],
      'cache_hitpass'     => $data[8],
      'cache_hit'         => $data[9],
      'cache_miss'        => $data[10],
      'lru_nuked'         => $data[11],
      'lru_moved'         => $data[12],
    ];

    rrdtool_update_ng($device, 'varnish', $stats, $app_id);
    update_application($app_id, $stats);

    unset($data);
}

// EOF
