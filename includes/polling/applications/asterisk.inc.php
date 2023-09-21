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

if (!empty($agent_data['app']['asterisk'])) {
    $app_id = discover_app($device, 'asterisk');

    foreach (explode("\n", $agent_data['app']['asterisk']) as $line) {
        [$key, $val] = explode(":", $line);
        $asterisk_data[trim($key)] = intval(trim($val));
    }

    update_application($app_id, $asterisk_data);

    rrdtool_update_ng($device, 'asterisk', $asterisk_data, $app_id);

    unset($key, $line, $val, $asterisk_data, $app_id);
}

// EOF
