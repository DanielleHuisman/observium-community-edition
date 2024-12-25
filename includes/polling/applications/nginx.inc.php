<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

if (!empty($agent_data['app']['nginx'])) {
    $nginx = $agent_data['app']['nginx'];

    $app_id = discover_app($device, 'nginx');

    echo(' nginx statistics' . PHP_EOL);

    [$active, $reading, $writing, $waiting, $req] = explode("\n", $nginx);

    $data = [
      'Requests' => $req,
      'Active'   => $active,
      'Reading'  => $reading,
      'Writing'  => $writing,
      'Waiting'  => $waiting,
    ];

    rrdtool_update_ng($device, 'nginx', $data, $app_id);

    update_application($app_id, $data);

    unset($nginx, $active, $reading, $writing, $req);
}

// EOF
