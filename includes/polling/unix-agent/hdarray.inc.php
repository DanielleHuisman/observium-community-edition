<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

global $agent_sensors;

if ($agent_data['array'] !== '|') {
    $items = explode("\n", $agent_data['hdarray']);
    if (OBS_DEBUG) {
        echo "hdarray: " . print_r($items);
    }

    if (safe_count($items)) {
        foreach ($items as $item) {
            [ $param, $status ] = explode('=', $item, 2);
            $itemcount++; // Note this is not best index
            switch ($status) {
                case 'Ok':
                    $istatus = 1;
                    break;
                case 'Non-Critical':
                    // Warn
                    $istatus = 2;
                    break;
                default:
                    // Fail
                    $istatus = 0;
            }
            echo "Status: $status istatus: $istatus";
            if ($param == 'Controller Status') {
                discover_status($device, '', $itemcount, 'unix-agent-state', $param, $istatus, ['entPhysicalClass' => 'controller'], 'agent');
                $agent_sensors['state']['unix-agent-state'][$itemcount] = ['description' => $param, 'current' => $istatus, 'index' => $itemcount];
            } elseif (preg_match('/^Drive \S/', $param)) {
                discover_status($device, '', $itemcount, 'unix-agent-state', $param, $istatus, ['entPhysicalClass' => 'storage'], 'agent');
                $agent_sensors['state']['unix-agent-state'][$itemcount] = ['description' => $param, 'current' => $istatus, 'index' => $itemcount];
            }
        }
        echo PHP_EOL;
    }
}

// EOF
