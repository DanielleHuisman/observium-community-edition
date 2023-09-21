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

if (!empty($agent_data['app']['postfix_qshape'])) {
    $app_id = discover_app($device, 'postfix_qshape');

    foreach (explode("\n", $agent_data['app']['postfix_qshape']) as $line) {
        [$item, $value] = explode(":", $line, 2);
        $queue_data[trim($item)] = trim($value);
    }

    rrdtool_update_ng($device, 'postfix-qshape', $queue_data);
    update_application($app_id, $queue_data);

    unset($queue_data, $item, $value);
}

// EOF
