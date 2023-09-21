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

$jnxDomCurrentTable_oids = [
  'jnxDomCurrentModuleTemperature',
  'jnxDomCurrentModuleTemperatureHighAlarmThreshold',
  'jnxDomCurrentModuleTemperatureLowAlarmThreshold',
  'jnxDomCurrentModuleTemperatureHighWarningThreshold',
  'jnxDomCurrentModuleTemperatureLowWarningThreshold',

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
//$oids = snmpwalk_cache_oid($device, 'jnxDomCurrentEntry',                    array(), 'JUNIPER-DOM-MIB');

$lane_oids = [];
foreach ($jnxDomCurrentLaneTable_oids as $oid) {
    $lane_oids = snmpwalk_cache_oid($device, $oid, $lane_oids, 'JUNIPER-DOM-MIB');
}

foreach ($oids as $index => $entry) {

    $options = ['entPhysicalIndex' => $index];

    $port = get_port_by_index_cache($device['device_id'], $index);
    if (is_array($port)) {
        $entry['ifDescr']           = $port['port_label'];
        $options['measured_class']  = 'port';
        $options['measured_entity'] = $port['port_id'];
    } else {
        $entry['ifDescr'] = snmp_get_oid($device, "ifDescr.$index", 'IF-MIB');
    }


    if (isset($entry['jnxDomCurrentModuleLaneCount']) && $entry['jnxDomCurrentModuleLaneCount'] > 1) {
        for ($i = 0; $i < $entry['jnxDomCurrentModuleLaneCount']; $i++) {
            $lane       = $i + 1;
            $lane_index = "{$index}.{$i}";

            $descr    = $entry['ifDescr'] . " Lane $lane Temperature";
            $oid_name = 'jnxDomCurrentLaneLaserTemperature';
            $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.2.1.1.9.{$lane_index}";
            $scale    = 1;
            $value    = $lane_oids[$lane_index][$oid_name];

            $limits = ['limit_high'      => $entry['jnxDomCurrentModuleTemperatureHighAlarmThreshold'],
                       'limit_low'       => $entry['jnxDomCurrentModuleTemperatureLowAlarmThreshold'],
                       'limit_high_warn' => $entry['jnxDomCurrentModuleTemperatureHighWarningThreshold'],
                       'limit_low_warn'  => $entry['jnxDomCurrentModuleTemperatureLowWarningThreshold']];

            if ($value != 0) {
                $limits['rename_rrd'] = "juniper-dom-$lane_index";
                discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $lane_index, NULL, $descr, $scale, $value, array_merge($options, $limits));
            }

            // jnxDomCurrentTxLaserBiasCurrent
            $descr    = $entry['ifDescr'] . " Lane $lane TX Bias";
            $oid_name = 'jnxDomCurrentLaneTxLaserBiasCurrent';
            $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.2.1.1.7.{$lane_index}";
            $scale    = si_to_scale('micro'); // Yah, I forgot number :)
            $value    = $lane_oids[$lane_index][$oid_name];

            $limits = ['limit_high'      => $entry['jnxDomCurrentTxLaserBiasCurrentHighAlarmThreshold'] * $scale,
                       'limit_low'       => $entry['jnxDomCurrentTxLaserBiasCurrentLowAlarmThreshold'] * $scale,
                       'limit_high_warn' => $entry['jnxDomCurrentTxLaserBiasCurrentHighWarningThreshold'] * $scale,
                       'limit_low_warn'  => $entry['jnxDomCurrentTxLaserBiasCurrentLowWarningThreshold'] * $scale];

            $limits['rename_rrd'] = "juniper-dom-$lane_index";
            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $lane_index, NULL, $descr, $scale, $value, array_merge($options, $limits));

            # jnxDomCurrentRxLaserPower[508] -507 0.01 dbm
            $descr    = $entry['ifDescr'] . " Lane $lane RX Power";
            $oid_name = 'jnxDomCurrentLaneRxLaserPower';
            $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.2.1.1.6.{$lane_index}";
            $scale    = 0.01;
            $value    = $lane_oids[$lane_index][$oid_name];

            $limits = ['limit_high'      => $entry['jnxDomCurrentRxLaserPowerHighAlarmThreshold'] * $scale,
                       'limit_low'       => $entry['jnxDomCurrentRxLaserPowerLowAlarmThreshold'] * $scale,
                       'limit_high_warn' => $entry['jnxDomCurrentRxLaserPowerHighWarningThreshold'] * $scale,
                       'limit_low_warn'  => $entry['jnxDomCurrentRxLaserPowerLowWarningThreshold'] * $scale];

            $limits['rename_rrd'] = "juniper-dom-rx-$lane_index";
            discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $lane_index, NULL, $descr, $scale, $value, array_merge($options, $limits));

            # jnxDomCurrentTxLaserOutputPower[508] -507 0.01 dbm
            $descr    = $entry['ifDescr'] . " Lane $lane TX Power";
            $oid_name = 'jnxDomCurrentLaneTxLaserOutputPower';
            $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.2.1.1.8.{$lane_index}";
            $scale    = 0.01;
            $value    = $lane_oids[$lane_index][$oid_name];

            $limits = ['limit_high'      => $entry['jnxDomCurrentTxLaserOutputPowerHighAlarmThreshold'] * $scale,
                       'limit_low'       => $entry['jnxDomCurrentTxLaserOutputPowerLowAlarmThreshold'] * $scale,
                       'limit_high_warn' => $entry['jnxDomCurrentTxLaserOutputPowerHighWarningThreshold'] * $scale,
                       'limit_low_warn'  => $entry['jnxDomCurrentTxLaserOutputPowerLowWarningThreshold'] * $scale];

            $limits['rename_rrd'] = "juniper-dom-tx-$lane_index";
            discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $lane_index, NULL, $descr, $scale, $value, array_merge($options, $limits));
        }
    } else {

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

        $limits = ['limit_high'      => $entry['jnxDomCurrentModuleTemperatureHighAlarmThreshold'],
                   'limit_low'       => $entry['jnxDomCurrentModuleTemperatureLowAlarmThreshold'],
                   'limit_high_warn' => $entry['jnxDomCurrentModuleTemperatureHighWarningThreshold'],
                   'limit_low_warn'  => $entry['jnxDomCurrentModuleTemperatureLowWarningThreshold']];

        if ($value != 0) {
            $limits['rename_rrd'] = "juniper-dom-$index";
            discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
        }

        if ($entry['jnxDomCurrentTxLaserBiasCurrent'] == 0 &&
            $entry['jnxDomCurrentTxLaserOutputPower'] == 0 && $entry['jnxDomCurrentRxLaserPower'] == 0) {
            // Skip other empty dom sensors
            continue;
        }

        // jnxDomCurrentTxLaserBiasCurrent
        $descr    = $entry['ifDescr'] . " TX Bias";
        $oid_name = 'jnxDomCurrentTxLaserBiasCurrent';
        $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.1.1.1.6.{$index}";
        $scale    = si_to_scale('micro'); // Yah, I forgot number :)
        $value    = $entry[$oid_name];

        $limits = ['limit_high'      => $entry['jnxDomCurrentTxLaserBiasCurrentHighAlarmThreshold'] * $scale,
                   'limit_low'       => $entry['jnxDomCurrentTxLaserBiasCurrentLowAlarmThreshold'] * $scale,
                   'limit_high_warn' => $entry['jnxDomCurrentTxLaserBiasCurrentHighWarningThreshold'] * $scale,
                   'limit_low_warn'  => $entry['jnxDomCurrentTxLaserBiasCurrentLowWarningThreshold'] * $scale];

        $limits['rename_rrd'] = "juniper-dom-$index";
        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

        # jnxDomCurrentRxLaserPower[508] -507 0.01 dbm
        $descr    = $entry['ifDescr'] . " RX Power";
        $oid_name = 'jnxDomCurrentRxLaserPower';
        $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.1.1.1.5.{$index}";
        $scale    = 0.01;
        $value    = $entry[$oid_name];

        $limits = ['limit_high'      => $entry['jnxDomCurrentRxLaserPowerHighAlarmThreshold'] * $scale,
                   'limit_low'       => $entry['jnxDomCurrentRxLaserPowerLowAlarmThreshold'] * $scale,
                   'limit_high_warn' => $entry['jnxDomCurrentRxLaserPowerHighWarningThreshold'] * $scale,
                   'limit_low_warn'  => $entry['jnxDomCurrentRxLaserPowerLowWarningThreshold'] * $scale];

        $limits['rename_rrd'] = "juniper-dom-rx-$index";
        discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

        # jnxDomCurrentTxLaserOutputPower[508] -507 0.01 dbm
        $descr    = $entry['ifDescr'] . " TX Power";
        $oid_name = 'jnxDomCurrentTxLaserOutputPower';
        $oid_num  = ".1.3.6.1.4.1.2636.3.60.1.1.1.1.7.{$index}";
        $type     = 'juniper-dom-tx'; // $mib . '-' . $oid_name;
        $scale    = 0.01;
        $value    = $entry[$oid_name];

        $limits = ['limit_high'      => $entry['jnxDomCurrentTxLaserOutputPowerHighAlarmThreshold'] * $scale,
                   'limit_low'       => $entry['jnxDomCurrentTxLaserOutputPowerLowAlarmThreshold'] * $scale,
                   'limit_high_warn' => $entry['jnxDomCurrentTxLaserOutputPowerHighWarningThreshold'] * $scale,
                   'limit_low_warn'  => $entry['jnxDomCurrentTxLaserOutputPowerLowWarningThreshold'] * $scale];

        $limits['rename_rrd'] = "juniper-dom-tx-$index";
        discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    }


}

// EOF
