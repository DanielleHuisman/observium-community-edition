<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @subpackage     raspberrypi
 * @author         Dennis de Houx <info@all-in-one.be>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 * @version        1.1.0
 *
 */

global $agent_sensors;

if ($agent_data['raspberrypi'] != ':') {
    echo "raspberrypi: ";

    $rpi_sensors = explode("\n", $agent_data['raspberrypi']);

    foreach ($rpi_sensors as $item => $rpi_sensor) {
        $rpi_sensor = trim($rpi_sensor);

        if (!empty($rpi_sensor)) {
            $data = explode(":", $rpi_sensor);
            [$type, $info] = explode("-", $data[0], 2);

            switch ($type) {
                case 'clock':
                    $sensorindex = ++$clockcount;
                    $sensortype  = 'frequency';
                    break;
                case 'volts':
                    $sensorindex = ++$voltcount;
                    $sensortype  = 'voltage';
                    break;
                case 'temp':
                    $sensorindex = ++$tempcount;
                    $sensortype  = 'temperature';
                    $info        = 'Raspberry Pi';
                    break;
                case 'model':
                    $hardware = $data[1];
                    break;
                default:
                    unset($sensortype);
                    break;
            }

            if (isset($sensortype)) {
                $value = trim($data[1]);
                discover_sensor($sensortype, $device, '', $sensorindex, 'raspberrypi', $info, 1, $value, [], 'agent');
                $agent_sensors[$sensortype]['raspberrypi'][$sensorindex] = ['description' => $info, 'current' => $value, 'index' => $sensorindex];
            }
        }
    }

    echo(PHP_EOL);
    unset($voltcount);
    unset($tempcount);
    unset($clockcount);
    unset($sensorindex);
    unset($value);
    unset($rpi_sensors);
    unset($rpi_sensor);
}

// EOF
