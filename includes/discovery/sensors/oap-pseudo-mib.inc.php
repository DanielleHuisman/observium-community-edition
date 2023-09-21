<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */


/**
 *
 * This file maps the following MIBs into a single virtual OAP-PSEUDO-MIB:
 *
 * OAP-C1-EDFA
 * OAP-C2-EDFA
 * OAP-C3-EDFA
 * OAP-C4-EDFA
 * OAP-C5-EDFA
 * OAP-C6-EDFA
 * OAP-C7-EDFA
 * OAP-C8-EDFA
 * OAP-C9-EDFA
 * OAP-C10-EDFA
 * OAP-C11-EDFA
 * OAP-C12-EDFA
 * OAP-C13-EDFA
 * OAP-C14-EDFA
 * OAP-C15-EDFA
 * OAP-C16-EDFA
 * OAP-C1-OEO
 * OAP-C2-OEO
 * OAP-C3-OEO
 * OAP-C4-OEO
 * OAP-C5-OEO
 * OAP-C6-OEO
 * OAP-C7-OEO
 * OAP-C8-OEO
 * OAP-C9-OEO
 * OAP-C10-OEO
 * OAP-C11-OEO
 * OAP-C12-OEO
 * OAP-C13-OEO
 * OAP-C14-OEO
 * OAP-C15-OEO
 * OAP-C16-OEO
 */

// -EDFA

$edfa_sensor_map = [
  8  => [ // vWorkMode
          'oid_name'     => 'vWorkMode',
          'sensor_descr' => 'Work Mode',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-vWorkMode',
  ],
  9  => [ // vPUMPSwitch
          'oid_name'     => 'vPUMPSwitch',
          'sensor_descr' => 'Pump Switch',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-vPUMPSwitch',
  ],
  16 => [ // vInputPowerState
          'oid_name'     => 'vInputPowerState',
          'sensor_descr' => 'Input Power Alarm',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-alarm',
  ],
  17 => [ // vOutputPowerState
          'oid_name'     => 'vOutputPowerState',
          'sensor_descr' => 'Output Power Alarm',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-alarm',
  ],
  18 => [ // vModuleTemperatureState
          'oid_name'     => 'vModuleTemperatureState',
          'sensor_descr' => 'Module Temperature Alarm',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-alarm',
  ],
  19 => [ // vPUMPTemperatureState
          'oid_name'     => 'vPUMPTemperatureState',
          'sensor_descr' => 'Pump Temperature Alarm',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-alarm',
  ],
  20 => [ // vPUMPCurrentState
          'oid_name'     => 'vPUMPCurrentState',
          'sensor_descr' => 'Pump Current Alarm',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-alarm',
  ],
  21 => [ // vGainOrOutputPower
          'sensor_descr' => 'Target Gain',
          'sensor_class' => 'dbm',
          'scale'        => 0.01,
  ],
  22 => [ // vModuleTemperature
          'sensor_descr' => 'Module',
          'sensor_class' => 'temperature',
          'scale'        => 0.01,
  ],
  23 => [ // vModuleVoltage
          'sensor_descr' => 'Module',
          'sensor_class' => 'voltage',
          'scale'        => 0.01,
  ],
  24 => [ // vPUMPPower
          'sensor_descr' => 'Pump Power',
          'sensor_class' => 'dbm',
          'scale'        => 0.01,
  ],
  25 => [ // vPUMPTemperature
          'sensor_descr' => 'Pump',
          'sensor_class' => 'temperature',
          'scale'        => 0.01,
  ],
  26 => [ // vPUMPCurrent
          'sensor_descr' => 'Pump',
          'sensor_class' => 'current',
          'scale'        => 0.00001,
  ],
  27 => [ // vTECCurrent
          'sensor_descr' => 'Refrigeration',
          'sensor_class' => 'current',
          'scale'        => 0.00001,
  ],
  28 => [ // vInput
          'sensor_descr' => 'Input',
          'sensor_class' => 'dbm',
          'scale'        => 0.01,
  ],
  29 => [ // vOutput
          'sensor_descr' => 'Output',
          'sensor_class' => 'dbm',
          'scale'        => 0.01,
  ],
];

// -OEO
$oeo_port_map = [
  11 => 'A1',
  12 => 'A2',
  13 => 'B1',
  14 => 'B2',
  15 => 'C1',
  16 => 'C2',
  17 => 'D1',
  18 => 'D2',
];

$oeo_sensor_map = [
  1  => [ // State
          'oid_name'     => 'State',
          'sensor_descr' => '',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-oeo-State',
  ],
  2  => [ // WorkMode
          'oid_name'     => 'WorkMode',
          'sensor_descr' => 'Work Mode',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-oeo-WorkMode',
  ],
  3  => [ // TxPowerControl
          'oid_name'     => 'TxPowerControl',
          'sensor_descr' => 'Tx Power Control',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-oeo-TxPowerControl',
  ],
  4  => [ // TxPower
          'sensor_descr' => 'Tx Power',
          'sensor_class' => 'dbm',
          'scale'        => 0.01,
  ],
  5  => [ // RxPower
          'sensor_descr' => 'Rx Power',
          'sensor_class' => 'dbm',
          'scale'        => 0.01,
  ],
  9  => [ // ModeTemperature
          'sensor_descr' => '',
          'sensor_class' => 'temperature',
          'scale'        => 0.01,
  ],
  10 => [ // TxPowerAlarm
          'oid_name'     => 'TxPowerAlarm',
          'sensor_descr' => 'Tx Power Alarm',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-alarm',
  ],
  11 => [ // RxPowerAlarm
          'oid_name'     => 'RxPowerAlarm',
          'sensor_descr' => 'Rx Power Alarm',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-alarm',
  ],
  12 => [ // ModeTemperatureAlarm
          'oid_name'     => 'ModeTemperatureAlarm',
          'sensor_descr' => 'Temperature Alarm',
          'sensor_class' => 'state',
          'sensor_type'  => 'oap-edfa-alarm',
  ],
];


// For the worker module sensors map the indices because they are not unique
// because the manufacturer had defined a MIB for every combination of module
// type and slot number.
for ($slot_no = 1; $slot_no <= 16; $slot_no++) {
    $pfx = ".1.3.6.1.4.1.40989.10.16.{$slot_no}";
    if (snmp_get_oid($device, "{$pfx}.1.2.0") == 'EDFA') {
        foreach ($edfa_sensor_map as $index => $sensor) {
            //$pseudo_index = 1000 * $slot_no + $index;
            $full_index = "{$slot_no}.1.{$index}.0";
            $oid_num    = "{$pfx}.1.{$index}.0";
            $value      = snmp_get_oid($device, $oid_num);

            $descr = "Slot {$slot_no} EDFA";
            if ($sensor['sensor_descr'] != '') {
                $descr .= ' ' . $sensor['sensor_descr'];
            }

            $options = [];
            if ($sensor['sensor_class'] == 'state') {

                // Status
                discover_status($device, $oid_num, $sensor['oid_name'] . '.' . $full_index, $sensor['sensor_type'], $descr, $value, $options);

            } else {

                // Sensor
                $sensor_type = 'oap-pseudo';
                $scale       = $sensor['scale'];

                // Limits
                switch ($index) {
                    /*
                              // This is currently useless as the device has it hardcoded to -55~70.
                              case 22: // vModuleTemperature
                                $options = array(
                                  'limit_high' => $scale * snmp_get_oid($device, "{$pfx}.1.12.0"), // vModuleTemperatureUpperLimit
                                  'limit_low'  => $scale * snmp_get_oid($device, "{$pfx}.1.13.0"), // vModuleTemperatureLowerLimit
                                );
                                break;
                    */
                    case 25: // vPUMPTemperature
                        $options = [
                          'limit_high' => $scale * snmp_get_oid($device, "{$pfx}.1.14.0"), // vPUMPTemperatureUpperLimit
                          'limit_low'  => $scale * snmp_get_oid($device, "{$pfx}.1.15.0"), // vPUMPTemperatureLowerLimit
                        ];
                        break;
                    case 28: // vInput
                        $options = [
                          'limit_low' => $scale * snmp_get_oid($device, "{$pfx}.1.10.0"), // vInputPowerALM
                        ];
                        break;
                    case 29: // vOutput
                        $options = [
                          'limit_low' => $scale * snmp_get_oid($device, "{$pfx}.1.11.0"), // vOutputPowerALM
                        ];
                        break;
                }

                discover_sensor($sensor['sensor_class'], $device, $oid_num, $full_index, $sensor_type, $descr, $scale, $value, $options);

            }

        } // foreach $edfa_sensor_map
    } // if EDFA
    elseif (snmp_get_oid($device, "{$pfx}.2.2.0") == 'OEO') {
        foreach ($oeo_port_map as $port_index => $port_name) {
            foreach ($oeo_sensor_map as $obj_index => $sensor) {
                //$pseudo_index = 1000 * $slot_no + 100 * ($port_index - 10) + $obj_index;
                $full_index = "{$slot_no}.2.{$port_index}.{$obj_index}.0";
                $oid_num    = "{$pfx}.2.{$port_index}.{$obj_index}.0";
                $value      = snmp_get_oid($device, $oid_num);

                $descr = "Slot {$slot_no} OEO Port {$port_name}";
                if ($sensor['sensor_descr'] != '') {
                    $descr .= ' ' . $sensor['sensor_descr'];
                }

                $options = [];
                if ($sensor['sensor_class'] == 'state') {

                    // Status
                    discover_status($device, $oid_num, $sensor['oid_name'] . '.' . $full_index, $sensor['sensor_type'], $descr, $value, $options);

                } else {

                    // Sensor
                    $sensor_type = 'oap-pseudo';
                    $scale       = $sensor['scale'];

                    discover_sensor($sensor['sensor_class'], $device, $oid_num, $full_index, $sensor_type, $descr, $scale, $value);

                }

                // When the port state is off (0) all the other objects for that port are missing.
                if ($obj_index == 1 && $value == 0) {
                    break;
                }

            } // foreach $oeo_sensor_map
        } // foreach $oeo_port_map
    } // if OEO
} // for each slot number

// EOF
