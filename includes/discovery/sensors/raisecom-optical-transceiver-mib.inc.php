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

$mib   = 'RAISECOM-OPTICAL-TRANSCEIVER-MIB';
$cache = [];
$oids  = [
    // The OIDs below are indexed in a traditional way:
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverPortDDMEnable.1 = INTEGER: enable(1)
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverPortDDMEnable.2 = INTEGER: enable(1)
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverPortDDMEnable.3 = INTEGER: enable(1)
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverPortDDMEnable.4 = INTEGER: enable(1)
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverPortDDMEnable.5 = INTEGER: enable(1)
    'raisecomOpticalTransceiverPortDDMEnable',
    'raisecomOpticalTransceiverDDM',
    'raisecomOpticalTransceiverHwInfoAbsStatus',
    // The OIDs below are not:
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamHighAlarmThresh.1.transceiverTemperature = INTEGER: 75000
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamHighAlarmThresh.1.txbiasCurrent = INTEGER: 100000
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamHighAlarmThresh.1.txPower = INTEGER: -2495
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamHighAlarmThresh.1.rxPower = INTEGER: -1992
    // RAISECOM-OPTICAL-TRANSCEIVER-MIB::raisecomOpticalTransceiverParamHighAlarmThresh.1.laserTemperature = INTEGER: 3600
    'raisecomOpticalTransceiverParameterValue',
    'raisecomOpticalTransceiverParamHighAlarmThresh',
    'raisecomOpticalTransceiverParamHighWarningThresh',
    'raisecomOpticalTransceiverParamLowAlarmThresh',
    'raisecomOpticalTransceiverParamLowWarningThresh',
    'raisecomOpticalTransceiverParamAlarmStatus',
];
foreach ($oids as $oid) {
    $cache = snmpwalk_cache_oid($device, $oid, $cache, $mib);
}

// Sensors are grouped by type so repeating "temperature" within a group of temperature
// sensors is excess. However, the alarm state labels do need to tell the type the alarm
// monitors, hence the labels are different.
$sensors = [
  'transceiverTemperature' => [
    'sensor_descr' => '',
    'state_descr'  => 'Temperature',
    'class'        => 'temperature',
    'scale'        => 0.001,
  ],
  'txbiasCurrent'          => [
    'sensor_descr' => 'Tx Bias',
    'state_descr'  => 'Tx Bias',
    'class'        => 'current',
    'scale'        => 0.000001,
  ],
  'txPower'                => [
    'sensor_descr' => 'Tx',
    'state_descr'  => 'Tx Power',
    'class'        => 'dbm',
    'scale'        => 0.001,
  ],
  'rxPower'                => [
    'sensor_descr' => 'Rx',
    'state_descr'  => 'Rx Power',
    'class'        => 'dbm',
    'scale'        => 0.001,
  ],
  'laserTemperature'       => [
    'sensor_descr' => 'Laser',
    'state_descr'  => 'Laser Temperature',
    'class'        => 'temperature',
    'scale'        => 0.01,
  ],
];

// Disabling DDM for an interface (port) will make all sensors and alarms for that port read 0.
// Disabling DDM for the device will do exactly the same for every interface (port). However,
// in any case it will be useful to know the list of transceivers and DDM configuration.
$oid        = 'raisecomOpticalTransceiverDDMEnable.0';
$device_ddm = snmp_get_oid($device, $oid, $mib);
$oid_num    = snmp_translate($oid, $mib);
discover_status($device, $oid_num, $oid, 'raisecom-enablevar-state', 'Device DDM', $device_ddm, ['entPhysicalClass' => 'device']);

foreach ($cache as $index => $data) {
    if (!is_numeric($index)) // Consider the unusually indexed OIDs a derived and optional information of the normal OIDs.
    {
        continue;
    }

    $port         = sprintf('Port %2u', $index); // Enforce natural sort order.
    $skip_sensors = FALSE;

    $prop = 'raisecomOpticalTransceiverPortDDMEnable';
    if (array_key_exists($prop, $data)) {
        $oid     = "{$prop}.{$index}";
        $oid_num = snmp_translate($oid, $mib);
        discover_status($device, $oid_num, $oid, 'raisecom-enablevar-state', "{$port} DDM", $data[$prop], ['entPhysicalClass' => 'port']);
        if ($data[$prop] != 'enable') {
            $skip_sensors = TRUE;
        }
    }

    $prop = 'raisecomOpticalTransceiverHwInfoAbsStatus';
    if (array_key_exists($prop, $data)) {
        $oid     = "{$prop}.{$index}";
        $oid_num = snmp_translate($oid, $mib);
        discover_status($device, $oid_num, $oid, 'raisecom-present-state', "{$port} Transceiver Hardware", $data[$prop], ['entPhysicalClass' => 'port']);
        if ($data[$prop] != 'present') {
            continue;
        }
    }

    $prop = 'raisecomOpticalTransceiverDDM';
    if (array_key_exists($prop, $data)) {
        $oid     = "{$prop}.{$index}";
        $oid_num = snmp_translate($oid, $mib);
        discover_status($device, $oid_num, $oid, 'raisecom-support-state', "{$port} Transceiver DDM", $data[$prop], ['entPhysicalClass' => 'port']);
        if ($data[$prop] != 'support') {
            $skip_sensors = TRUE;
        }
    }

    if ($device_ddm != 'enable' || $skip_sensors) {
        continue;
    }

    foreach ($sensors as $prop => $sensor) {
        $descr = "{$port} Transceiver";
        if ($sensor['sensor_descr'] != '') {
            $descr .= " {$sensor['sensor_descr']}";
        }
        $val_key  = 'raisecomOpticalTransceiverParameterValue';
        $readings = $cache["{$index}.{$prop}"];
        $oid_num  = snmp_translate("{$val_key}.{$index}.{$prop}", $mib);
        $options  = [
          'limit_low'        => $sensor['scale'] * $readings['raisecomOpticalTransceiverParamLowAlarmThresh'],
          'limit_high'       => $sensor['scale'] * $readings['raisecomOpticalTransceiverParamHighAlarmThresh'],
          'limit_low_warn'   => $sensor['scale'] * $readings['raisecomOpticalTransceiverParamLowWarningThresh'],
          'limit_high_warn'  => $sensor['scale'] * $readings['raisecomOpticalTransceiverParamHighWarningThresh'],
          'entPhysicalClass' => 'port',
        ];
        $value    = $readings[$val_key];
        discover_sensor($sensor['class'], $device, $oid_num, "{$index}.{$prop}", $mib, $descr, $sensor['scale'], $value, $options);

        $descr = "{$port} Transceiver";
        if ($sensor['state_descr'] != '') {
            $descr .= " {$sensor['state_descr']}";
        }
        $val_key = 'raisecomOpticalTransceiverParamAlarmStatus';
        $oid_num = snmp_translate("{$val_key}.{$index}.{$prop}", $mib);
        $value   = $readings[$val_key];
        discover_status($device, $oid_num, "{$index}.{$prop}", 'raisecom-alarm-state', $descr, $value, ['entPhysicalClass' => 'port']);
    } // foreach ($sensors)
} // foreach ($cache)

unset ($mib, $cache, $oids, $sensors, $oid, $value, $oid_num, $device_ddm, $index, $data, $port, $prop, $sensor, $descr, $options, $val_key);

// EOF
