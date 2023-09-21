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

if (!empty($agent_data['app']['ntpd'])) {
    $app_id = discover_app($device, 'ntpd');

    foreach (explode("\n", $agent_data['app']['ntpd']) as $line) {
        [$item, $value] = explode(":", $line, 2);
        $ntpd_data[trim($item)] = trim($value);
    }

    $ntpd_type = (isset($ntpd_data['server']) ? "server" : "client");

    switch ($ntpd_type) {
        case 'server':
            rrdtool_update_ng($device, 'ntpd-server', $ntpd_data, $app_id);
            break;
        case 'client':
            rrdtool_update_ng($device, 'ntpd-client', $ntpd_data, $app_id);
            break;
    }

    update_application($app_id, $ntpd_data);

    unset($ntpd_type, $app_id, $ntpd_data);
}

// EOF
