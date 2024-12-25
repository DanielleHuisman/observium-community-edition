<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

$jnxDomCurrentTable_oids = [
    'jnxDomCurrentModuleTemperature',
    'jnxDomCurrentModuleTemperatureHighAlarmThreshold',
    'jnxDomCurrentModuleTemperatureLowAlarmThreshold',
    'jnxDomCurrentModuleTemperatureHighWarningThreshold',
    'jnxDomCurrentModuleTemperatureLowWarningThreshold',

    'jnxDomCurrentModuleVoltage',
    'jnxDomCurrentModuleVoltageHighAlarmThreshold',
    'jnxDomCurrentModuleVoltageLowAlarmThreshold',
    'jnxDomCurrentModuleVoltageHighWarningThreshold',
    'jnxDomCurrentModuleVoltageLowWarningThreshold',

    'jnxDomCurrentTxLaserBiasCurrent',
    'jnxDomCurrentTxLaserBiasCurrentHighAlarmThreshold',
    'jnxDomCurrentTxLaserBiasCurrentLowAlarmThreshold',
    'jnxDomCurrentTxLaserBiasCurrentHighWarningThreshold',
    'jnxDomCurrentTxLaserBiasCurrentLowWarningThreshold',

    'jnxDomCurrentRxLaserPower',
    'jnxDomCurrentRxLaserPowerHighAlarmThreshold',
    'jnxDomCurrentRxLaserPowerLowAlarmThreshold',
    'jnxDomCurrentRxLaserPowerHighWarningThreshold',
    'jnxDomCurrentRxLaserPowerLowWarningThreshold',

    'jnxDomCurrentTxLaserOutputPower',
    'jnxDomCurrentTxLaserOutputPowerHighAlarmThreshold',
    'jnxDomCurrentTxLaserOutputPowerLowAlarmThreshold',
    'jnxDomCurrentTxLaserOutputPowerHighWarningThreshold',
    'jnxDomCurrentTxLaserOutputPowerLowWarningThreshold',

    'jnxDomCurrentModuleLaneCount'
];

$jnxDomCurrentLaneTable_oids = [
    'jnxDomCurrentLaneLaserTemperature',
    'jnxDomCurrentLaneTxLaserBiasCurrent',
    'jnxDomCurrentLaneRxLaserPower',
    'jnxDomCurrentLaneTxLaserOutputPower',
];

$oids = [];
foreach ($jnxDomCurrentTable_oids as $oid) {
    $oids = snmpwalk_cache_oid($device, $oid, $oids, 'JUNIPER-DOM-MIB');
}

$lane_oids = [];
foreach ($jnxDomCurrentLaneTable_oids as $oid) {
    $lane_oids = snmpwalk_cache_twopart_oid($device, $oid, $lane_oids, 'JUNIPER-DOM-MIB');
}

foreach ($oids as $index => $entry) {

    $entry['index'] = $index;
    $match          = [ 'measured_match' => [ 'entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%index%' ] ];
    $options        = entity_measured_match_definition($device, $match, $entry);

    if (isset($lane_oids[$index]) && safe_count($lane_oids[$index]) > 1) {
        // Multi-lane sensors.
        // Note, jnxDomCurrentModuleLaneCount can be 0 and 4 incorrectly
        foreach ($lane_oids[$index] as $i => $lane_entry) {
            $lane       = $i + 1;
            $lane_index = "$index.$i";

            $descr    = $entry['ifDescr'] . " Lane $lane Temperature";
            $oid_name = 'jnxDomCurrentLaneLaserTemperature';
            $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.2.1.1.9.{$lane_index}";
            $scale    = 1;
            $value    = $lane_entry[$oid_name];

            $limits = [
                'limit_high'      => $entry['jnxDomCurrentModuleTemperatureHighAlarmThreshold'],
                'limit_low'       => $entry['jnxDomCurrentModuleTemperatureLowAlarmThreshold'],
                'limit_high_warn' => $entry['jnxDomCurrentModuleTemperatureHighWarningThreshold'],
                'limit_low_warn'  => $entry['jnxDomCurrentModuleTemperatureLowWarningThreshold']
            ];

            if ($value != 0) {
                $limits['rename_rrd'] = "juniper-dom-$lane_index";
                discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $lane_index, $descr, $scale, $value, array_merge($options, $limits));
            }

            // jnxDomCurrentTxLaserBiasCurrent
            $descr    = $entry['ifDescr'] . " Lane $lane TX Bias";
            $oid_name = 'jnxDomCurrentLaneTxLaserBiasCurrent';
            $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.2.1.1.7.{$lane_index}";
            $scale    = 0.000001;
            $value    = $lane_entry[$oid_name];

            $limits = [
                'limit_high'      => $entry['jnxDomCurrentTxLaserBiasCurrentHighAlarmThreshold'] * $scale,
                'limit_low'       => $entry['jnxDomCurrentTxLaserBiasCurrentLowAlarmThreshold'] * $scale,
                'limit_high_warn' => $entry['jnxDomCurrentTxLaserBiasCurrentHighWarningThreshold'] * $scale,
                'limit_low_warn'  => $entry['jnxDomCurrentTxLaserBiasCurrentLowWarningThreshold'] * $scale
            ];

            $limits['rename_rrd'] = "juniper-dom-$lane_index";
            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $lane_index, $descr, $scale, $value, array_merge($options, $limits));

            # jnxDomCurrentRxLaserPower[508] -507 0.01 dbm
            $descr    = $entry['ifDescr'] . " Lane $lane RX Power";
            $oid_name = 'jnxDomCurrentLaneRxLaserPower';
            $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.2.1.1.6.{$lane_index}";
            $scale    = 0.01;
            $value    = $lane_entry[$oid_name];

            $limits = [
                'limit_high'      => $entry['jnxDomCurrentRxLaserPowerHighAlarmThreshold'] * $scale,
                'limit_low'       => $entry['jnxDomCurrentRxLaserPowerLowAlarmThreshold'] * $scale,
                'limit_high_warn' => $entry['jnxDomCurrentRxLaserPowerHighWarningThreshold'] * $scale,
                'limit_low_warn'  => $entry['jnxDomCurrentRxLaserPowerLowWarningThreshold'] * $scale
            ];

            $limits['rename_rrd'] = "juniper-dom-rx-$lane_index";
            discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $lane_index, $descr, $scale, $value, array_merge($options, $limits));

            # jnxDomCurrentTxLaserOutputPower[508] -507 0.01 dbm
            $descr    = $entry['ifDescr'] . " Lane $lane TX Power";
            $oid_name = 'jnxDomCurrentLaneTxLaserOutputPower';
            $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.2.1.1.8.{$lane_index}";
            $scale    = 0.01;
            $value    = $lane_entry[$oid_name];

            $limits = [
                'limit_high'      => $entry['jnxDomCurrentTxLaserOutputPowerHighAlarmThreshold'] * $scale,
                'limit_low'       => $entry['jnxDomCurrentTxLaserOutputPowerLowAlarmThreshold'] * $scale,
                'limit_high_warn' => $entry['jnxDomCurrentTxLaserOutputPowerHighWarningThreshold'] * $scale,
                'limit_low_warn'  => $entry['jnxDomCurrentTxLaserOutputPowerLowWarningThreshold'] * $scale
            ];

            $limits['rename_rrd'] = "juniper-dom-tx-$lane_index";
            discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $lane_index, $descr, $scale, $value, array_merge($options, $limits));
        }
    } else {

        if ($entry['jnxDomCurrentTxLaserBiasCurrent'] == 0 &&
            $entry['jnxDomCurrentTxLaserOutputPower'] == 0 && $entry['jnxDomCurrentRxLaserPower'] == 0) {
            // Skip empty dom sensors
            continue;
        }

        # jnxDomCurrentModuleTemperature[508] 35
        # jnxDomCurrentModuleTemperatureHighAlarmThreshold[508] 100
        # jnxDomCurrentModuleTemperatureLowAlarmThreshold[508] -25
        # jnxDomCurrentModuleTemperatureHighWarningThreshold[508] 95
        # jnxDomCurrentModuleTemperatureLowWarningThreshold[508] -20
        $descr    = $entry['ifDescr'] . ' Temperature';
        $oid_name = 'jnxDomCurrentModuleTemperature';
        $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.1.1.1.8.{$index}";
        $scale    = 1;
        $value    = $entry[$oid_name];

        $limits = [
            'limit_high'      => $entry['jnxDomCurrentModuleTemperatureHighAlarmThreshold'],
            'limit_low'       => $entry['jnxDomCurrentModuleTemperatureLowAlarmThreshold'],
            'limit_high_warn' => $entry['jnxDomCurrentModuleTemperatureHighWarningThreshold'],
            'limit_low_warn'  => $entry['jnxDomCurrentModuleTemperatureLowWarningThreshold']
        ];


        $limits['rename_rrd'] = "juniper-dom-$index";
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, array_merge($options, $limits));

        // jnxDomCurrentModuleVoltage
        if (isset($entry['jnxDomCurrentModuleVoltage'])) {
            $descr    = $entry['ifDescr'] . " Voltage";
            $oid_name = 'jnxDomCurrentModuleVoltage';
            $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.1.1.1.25.{$index}";
            $scale    = 0.001;
            $value    = $entry[$oid_name];

            $limits = [
                'limit_high'      => $entry['jnxDomCurrentModuleVoltageHighAlarmThreshold'] * $scale,
                'limit_low'       => $entry['jnxDomCurrentModuleVoltageLowAlarmThreshold'] * $scale,
                'limit_high_warn' => $entry['jnxDomCurrentModuleVoltageHighWarningThreshold'] * $scale,
                'limit_low_warn'  => $entry['jnxDomCurrentModuleVoltageLowWarningThreshold'] * $scale
            ];

            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, array_merge($options, $limits));
        }

        // jnxDomCurrentTxLaserBiasCurrent
        $descr    = $entry['ifDescr'] . " TX Bias";
        $oid_name = 'jnxDomCurrentTxLaserBiasCurrent';
        $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.1.1.1.6.{$index}";
        $scale    = 0.000001;
        $value    = $entry[$oid_name];

        $limits = [
            'limit_high'      => $entry['jnxDomCurrentTxLaserBiasCurrentHighAlarmThreshold'] * $scale,
            'limit_low'       => $entry['jnxDomCurrentTxLaserBiasCurrentLowAlarmThreshold'] * $scale,
            'limit_high_warn' => $entry['jnxDomCurrentTxLaserBiasCurrentHighWarningThreshold'] * $scale,
            'limit_low_warn'  => $entry['jnxDomCurrentTxLaserBiasCurrentLowWarningThreshold'] * $scale
        ];

        $limits['rename_rrd'] = "juniper-dom-$index";
        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, array_merge($options, $limits));

        # jnxDomCurrentRxLaserPower[508] -507 0.01 dbm
        $descr    = $entry['ifDescr'] . " RX Power";
        $oid_name = 'jnxDomCurrentRxLaserPower';
        $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.1.1.1.5.{$index}";
        $scale    = 0.01;
        $value    = $entry[$oid_name];

        $limits = [
            'limit_high'      => $entry['jnxDomCurrentRxLaserPowerHighAlarmThreshold'] * $scale,
            'limit_low'       => $entry['jnxDomCurrentRxLaserPowerLowAlarmThreshold'] * $scale,
            'limit_high_warn' => $entry['jnxDomCurrentRxLaserPowerHighWarningThreshold'] * $scale,
            'limit_low_warn'  => $entry['jnxDomCurrentRxLaserPowerLowWarningThreshold'] * $scale
        ];

        $limits['rename_rrd'] = "juniper-dom-rx-$index";
        discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, array_merge($options, $limits));

        # jnxDomCurrentTxLaserOutputPower[508] -507 0.01 dbm
        $descr    = $entry['ifDescr'] . " TX Power";
        $oid_name = 'jnxDomCurrentTxLaserOutputPower';
        $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.1.1.1.7.{$index}";
        $type     = 'juniper-dom-tx'; // $mib . '-' . $oid_name;
        $scale    = 0.01;
        $value    = $entry[$oid_name];

        $limits = [
            'limit_high'      => $entry['jnxDomCurrentTxLaserOutputPowerHighAlarmThreshold'] * $scale,
            'limit_low'       => $entry['jnxDomCurrentTxLaserOutputPowerLowAlarmThreshold'] * $scale,
            'limit_high_warn' => $entry['jnxDomCurrentTxLaserOutputPowerHighWarningThreshold'] * $scale,
            'limit_low_warn'  => $entry['jnxDomCurrentTxLaserOutputPowerLowWarningThreshold'] * $scale
        ];

        $limits['rename_rrd'] = "juniper-dom-tx-$index";
        discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, array_merge($options, $limits));

    }

}

// EOF
