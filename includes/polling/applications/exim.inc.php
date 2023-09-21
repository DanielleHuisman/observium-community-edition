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

if (!empty($agent_data['app']['exim'])) {
    $app_id = discover_app($device, 'exim');

    foreach (explode("\n", $agent_data['app']['exim']) as $line) {
        [$item, $value] = explode(":", $line, 2);
        $exim_data[trim($item)] = trim($value);
    }

    rrdtool_update_ng($device, 'exim', $exim_data, $app_id);
    update_application($app_id, $exim_data);

    unset($exim_data, $item, $value, $app_id);
}

// EOF
