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

if (!empty($agent_data['app']['icecast'])) {
    $app_id = discover_app($device, 'icecast');

    foreach (explode("\n", $agent_data['app']['icecast']) as $line) {
        [$item, $value] = explode(":", $line, 2);
        $icecast_data[trim($item)] = trim($value);
    }

    rrdtool_update_ng($device, 'icecast', $icecast_data, $app_id);
    update_application($app_id, $icecast_data);
    unset($app_id, $icecast_data);
}
// EOF
