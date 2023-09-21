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

if ($agent_data['lmsensors'] != '|') {
    $array         = preg_split("/\n/", $agent_data['lmsensors'], -1, PREG_SPLIT_NO_EMPTY);
    $sensors_array = [];

    // i5k_amb-isa-0000
    // power_meter-acpi-0
    $pattern_module = '/^[a-z][a-z\-\_\d]+$/';
    // Adapter: ISA adapter
    // Adapter: Virtual device
    $pattern_adapter = '/^Adapter:\ +(?<adapter>.+)$/';
    // Ch. 0 DIMM 0:  +98.5°C  (low  = +127.5°C, high = +127.5°C)
    // Core 0:       +49.0°C  (high = +80.0°C, crit = +100.0°C)
    // fan5:           0 RPM  (min =  712 RPM)  ALARM
    // temp4:       -128.0°C  (high = +85.0°C, hyst = +80.0°C)  sensor = Intel PECI
    // Core 0:       +50.0°C  (high = +80.0°C, crit = +100.0°C)
    $pattern_sensor = '/^(?<descr>[^:]+):\s+(?<value>[+\-]?\d[\d\.]*)\s*(?<unit>\S+)(?<additional>.*)$/';
    $pattern_limits = '/\((?<limits>.+?)\)/';
    // intrusion0:  OK
    // beep_enable: disabled
    $pattern_status = '/^(?<descr>[^:]+):\s+(?<status>[a-zA-Z]+)$/';

    $module  = '';
    $adapter = '';
    foreach ($array as $line) {
        // Set module name
        if (preg_match($pattern_module, $line)) {
            $module = $line;
        }
        // Set adapter name
        if (preg_match($pattern_adapter, $line, $matches)) {
            $adapter = $matches['adapter'];
        }
        // Reset module and adapter after empty line
        if (trim($line) == '') {
            $module  = '';
            $adapter = '';
        }

        $sensor = ['scale' => 1];
        if (preg_match($pattern_sensor, $line, $matches)) {
            // Numeric sensors
            $unit            = preg_replace('/[^a-zA-Z]/', '', $matches['unit']);
            $sensor['descr'] = trim($matches['descr']); // Used as Index
            if (strlen($module) && strlen($adapter)) {
                // Append rename rrd, since description changed
                $sensor['rename_rrd'] = 'lmsensors-' . $sensor['descr'];
                $sensor['descr']      .= ' (' . $module . ')';
            }

            $sensor['current'] = preg_replace('/[^0-9\.\-]/', '', $matches['value']);
            switch ($unit) {
                case "F":
                    $sensor['class'] = "temperature";
                    $sensor['unit']  = 'F';
                    break;
                case "C":
                    $sensor['class'] = "temperature";
                    break;
                case "RPM":
                    $sensor['class'] = "fanspeed";
                    break;
                case "V":
                    $sensor['class'] = "voltage";
                    break;
                case "W":
                    $sensor['class'] = "power";
                    break;
            }
            // Limits
            if (isset($matches['additional']) && preg_match($pattern_limits, $matches['additional'], $limits)) {
                // low  = +127.5°C, high = +127.5°C
                foreach (explode(',', $limits['limits']) as $limit) {
                    // low  = +127.5°C
                    [$param, $value] = explode('=', $limit);
                    $param = trim($param);
                    switch ($param) {
                        case "low":
                        case "high":
                        case "crit":
                        case "warn":
                        case "hyst":
                            $sensor[$param] = preg_replace('/[^0-9\.\-]/', '', $value);
                            break;
                    }
                }
            }
        } elseif (preg_match($pattern_status, $line, $matches)) {
            // Named status
            $sensor['descr']  = trim($matches['descr']);
            $sensor['status'] = trim($matches['status']);
        } else {
            // Skip other lines
            continue;
        }
        $sensors_array[$sensor['descr']] = $sensor;
    }

    foreach ($sensors_array as $key => $sensor) {
        $options = [];

        if (isset($sensor['status'])) {
            // Statuses
            switch (strtolower($sensor['status'])) {
                case 'ok':
                    $istatus = 1;
                    $type    = 'unix-agent-state';
                    break;
                case 'disabled':
                    $istatus = 1;
                    $type    = 'unix-agent-enable';
                    break;
                case 'enabled':
                    $istatus = 0;
                    $type    = 'unix-agent-enable';
                    break;
                case 'non-critical':
                    // Warn
                    $istatus = 2;
                    $type    = 'unix-agent-state';
                    break;
                default:
                    // Fail
                    $istatus = 0;
                    $type    = 'unix-agent-state';
            }
            discover_status($device, '', $key, $type, $sensor['descr'], $istatus, ['entPhysicalClass' => 'other'], 'agent');
            $agent_sensors['state'][$type][$key] = ['description' => $sensor['descr'], 'current' => $istatus, 'index' => $key];
            // Statuses End
        } else {
            // Sensors

            if (isset($sensor['crit'])) {
                // high = +80.0°C, crit = +100.0°C
                $options['limit_high'] = $sensor['crit'];
                if (isset($sensor['high'])) {
                    $options['limit_high_warn'] = $sensor['high'];
                }
            } elseif (isset($sensor['high'])) {
                // high = +85.0°C, hyst = +80.0°C
                $options['limit_high'] = $sensor['high'];
                if (isset($sensor['hyst'])) {
                    $options['limit_high_warn'] = $sensor['hyst'];
                }
            } elseif (isset($sensor['max'])) {
                // min =  +2.70 V, max =  +3.30 V
                $options['limit_high'] = $sensor['max'];
            }

            if (isset($sensor['low']) && $sensor['low'] != $sensor['high']) {
                // low  = +127.5°C, high = +127.5°C
                $options['limit_low'] = $sensor['low'];
            } elseif (isset($sensor['min'])) {
                // min =  +2.70 V, max =  +3.30 V
                $options['limit_low'] = $sensor['min'];
            }

            $agent_sensors[$sensor['class']]['lmsensors'][$key] = ['description' => $sensor['descr'], 'current' => $sensor['current'], 'index' => $key];
            if (isset($sensor['unit'])) {
                $options['sensor_unit']                                     = $sensor['unit'];
                $agent_sensors[$sensor['class']]['lmsensors'][$key]['unit'] = $sensor['unit'];
            }
            if (isset($sensor['rename_rrd'])) {
                $options['rename_rrd']                                            = $sensor['rename_rrd'];
                $agent_sensors[$sensor['class']]['lmsensors'][$key]['rename_rrd'] = $sensor['rename_rrd'];
            }
            discover_sensor($sensor['class'], $device, '', $key, 'lmsensors', $sensor['descr'], $sensor['scale'], $sensor['current'], $options, 'agent');
        }
    }

    #print_r($sensors_array);
    unset($sensor, $sensors_array);
}

// EOF
