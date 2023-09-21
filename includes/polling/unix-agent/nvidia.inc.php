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

global $agent_sensors;

if (!safe_empty($agent_data['nvidia']['smi'])) {

    $invalid = ['[Not Supported]', 'N/A', '[N/A]'];
    $nvidia  = parse_csv($agent_data['nvidia']['smi']);
    if (safe_count($nvidia)) {

        print_cli_heading("nvidia-smi", 3);
        foreach ($nvidia as $card) {

            $descr_card = "Nvidia Card " . ($card['index'] + 1) . ": " . $card['name'];
            print_cli_heading($descr_card, 4);

            if (!in_array($card['temperature.gpu'], $invalid)) {
                $index = 'temperature.gpu.' . $card['index'];
                $descr = $descr_card;
                discover_sensor('temperature', $device, '', $index, 'nvidia-smi', $descr, 1, $card['temperature.gpu'], ['limit_high' => 100], 'agent');
                $agent_sensors['temperature']['nvidia-smi'][$index] = ['description' => $descr, 'current' => $card['temperature.gpu'], 'index' => $index];
                print_cli_data("temperature.gpu", $card['temperature.gpu'] . "C");

            }

            if (!in_array($card['power.draw [W]'], $invalid)) {
                $index = 'power.draw.' . $card['index'];
                $descr = $descr_card;
                discover_sensor('power', $device, '', $index, 'nvidia-smi', $descr, 1, $card['power.draw [W]'], [], 'agent');
                $agent_sensors['power']['nvidia-smi'][$index] = ['description' => $descr, 'current' => $card['power.draw [W]'], 'index' => $index];
                print_cli_data("power.draw", $card['power.draw [W]'] . "W");
            }

            if (!in_array($card['fan.speed [%]'], $invalid)) {
                $index = 'fan.speed.' . $card['index'];
                $descr = $descr_card . " Fan Load";
                discover_sensor('load', $device, '', $index, 'nvidia-smi', $descr, 1, $card['fan.speed [%]'], ['limit_high' => 100, 'limit_low' => 0], 'agent');
                $agent_sensors['load']['nvidia-smi'][$index] = ['description' => $descr, 'current' => $card['fan.speed [%]'], 'index' => $index];
                print_cli_data("fan.speed", $card['fan.speed [%]'] . "");
            }

            if (!in_array($card['utilization.gpu [%]'], $invalid)) {
                $index = 'utilization.gpu.' . $card['index'];
                $descr = $descr_card . " GPU Load";
                discover_sensor('load', $device, '', $index, 'nvidia-smi', $descr, 1, $card['utilization.gpu [%]'], ['limit_high' => 100, 'limit_low' => 0], 'agent');
                $agent_sensors['load']['nvidia-smi'][$index] = ['description' => $descr, 'current' => $card['utilization.gpu [%]'], 'index' => $index];
                print_cli_data("utilization.gpu", $card['utilization.gpu [%]'] . "");
            }

            if (!in_array($card['utilization.memory [%]'], $invalid)) {
                $index = 'utilization.memory.' . $card['index'];
                $descr = $descr_card . " Memory Load";
                discover_sensor('load', $device, '', $index, 'nvidia-smi', $descr, 1, $card['utilization.memory [%]'], ['limit_high' => 100, 'limit_low' => 0], 'agent');
                $agent_sensors['load']['nvidia-smi'][$index] = ['description' => $descr, 'current' => $card['utilization.memory [%]'], 'index' => $index];
                print_cli_data("utilization.memory", $card['utilization.memory [%]'] . "");
            }

        }
        echo "\n";
    }
}

// EOF
