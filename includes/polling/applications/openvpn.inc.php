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

if (is_array($agent_data['app']['openvpn'])) {
    foreach ($agent_data['app']['openvpn'] as $key => $entry) {
        if (substr($key, 0, 9) == 'loadstats') {
            [, $instance] = explode('-', $key, 2);

            $loadstats[$instance] = [];

            # SUCCESS: nclients=1,bytesin=484758,bytesout=180629
            foreach (explode(',', str_replace('SUCCESS: ', '', $entry)) as $keyvalue) {
                [$key, $value] = explode('=', $keyvalue, 2);
                $loadstats[$instance][$key] = $value;
            }
        }
    }
}

foreach ($loadstats as $instance => $data) {
    $app_id = discover_app($device, 'openvpn', $instance);
    update_application($app_id, $data);
    rrdtool_update_ng($device, 'openvpn', $data, $instance);
}

unset($loadstats);

// EOF
