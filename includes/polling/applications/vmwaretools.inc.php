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

//<<<app-vmwaretools>>>
//vmtotalmem:2051
//vmswap:117
//vmballoon:1302
//vmmemres:256
//vmmemlimit:U
//vmspeed:2660000000
//vmcpulimit:U
//vmcpures:0

if (!empty($agent_data['app']['vmwaretools'])) {
    $app_id = discover_app($device, 'vmwaretools');

    // Parse the data, first try key:value format
    foreach (explode("\n", $agent_data['app']['vmwaretools']) as $line) {
        // Parse key:value line
        [$key, $value] = explode(':', $line, 2);
        $values[$key] = $value;
    }

    rrdtool_update_ng($device, 'vmwaretools', $values, $app_id);

    update_application($app_id, $values);

    unset($values, $app_id);
}

// EOF
