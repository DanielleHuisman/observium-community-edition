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

// Correct output of the agent script should look like this:
//<<<exim-mailqueue>>>
//frozen:173
//bounces:1052
//total:2496
//active:2323

if (!empty($agent_data['app']['exim_mailqueue'])) {
    $app_id = discover_app($device, 'exim_mailqueue');

    foreach (explode("\n", $agent_data['app']['exim_mailqueue']) as $line) {
        [$item, $value] = explode(":", $line, 2);
        $exim_data[trim($item)] = trim($value);
    }

    update_application($app_id, $exim_data);
    rrdtool_update_ng($device, 'exim-mailqueue', $exim_data, $app_id);

    unset($line, $item, $value, $exim_data, $app_id);
}

// EOF
