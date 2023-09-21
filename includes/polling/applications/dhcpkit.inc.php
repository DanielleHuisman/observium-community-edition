<?php

/**
 * Observium Network Management and Monitoring System
 *
 * @package        observium
 * @subpackage     poller
 * @author         Sander Steffann <sander@steffann.nl>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

foreach ($agent_data['app']['dhcpkit'] as $collection => $collection_data) {
    $lines = explode("\n", $agent_data['app']['dhcpkit'][$collection]);
    $data  = [];

    foreach ($lines as $line) {
        // Line format is "key:value"
        [$key, $value] = explode(':', $line, 2);

        // Adjust naming
        $key = preg_replace('/\\.information_request$/', '.inf_req', $key);
        $key = preg_replace('/^messages_(in|out)\\./', 'msg_$1_', $key);

        $data[$key] = intval($value);
    }

    $app_id = discover_app($device, 'dhcpkit', $collection);
    update_application($app_id, $data);
    rrdtool_update_ng($device, 'dhcpkit-stats', $data, $app_id);

    unset($lines);
    unset($data);
}

/* End of file dhcpkit.inc.php */
