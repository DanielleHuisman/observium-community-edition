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

if (!empty($agent_data['app']['postgresql'])) {
    $app_id = discover_app($device, 'postgresql');

    foreach (explode("\n", $agent_data['app']['postgresql']) as $line) {
        [$item, $value] = explode(":", $line, 2);
        $pgsql_data[trim($item)] = trim($value);
    }

    // there are differences between stats in postgresql 8.x and 9.x
    // if $pgsql_data['version']
    rrdtool_update_ng($device, 'postgresql', $pgsql_data, $app_id);
    update_application($app_id, $pgsql_data);

    unset($app_id, $pgsql_data, $item, $value, $line);
}

// EOF
