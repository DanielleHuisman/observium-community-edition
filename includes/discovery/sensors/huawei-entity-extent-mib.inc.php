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

//HUAWEI-ENTITY-EXTENT-MIB::hwEntityBomEnDesc.67158029 = STRING: S23&33&53&CX200D,CX7E1FANA,Fan Assembly
//HUAWEI-ENTITY-EXTENT-MIB::hwEntityBomEnDesc.67174413 = STRING: S5300C,CX7M1PWD,DC Power Module
//
//HUAWEI-ENTITY-EXTENT-MIB::hwEntityBomEnDesc.67371017 = STRING: Finished Board,S9700,EH1D2L08QX2E,8-Port 40GE QSFP+ Interface Card(X2E,QSFP+),20M TCAM
//HUAWEI-ENTITY-EXTENT-MIB::hwEntityBomEnDesc.68419593 = STRING: Finished Board,S12700,ET1D2X48SX2S,48-Port 10GE SFP+ Interface Card(X2S,SFP+)

$huawei['sensors_names'] = snmpwalk_cache_oid($device, 'hwEntityBomEnDesc', [], 'HUAWEI-ENTITY-EXTENT-MIB');
//$huawei['sensors_names'] = snmpwalk_cache_oid($device, 'entPhysicalName',   $huawei['sensors_names'], 'ENTITY-MIB');
//$huawei['sensors_names'] = snmpwalk_cache_oid($device, 'entPhysicalAlias',  $huawei['sensors_names'], 'ENTITY-MIB');

$huawei['temp']    = snmpwalk_cache_oid($device, 'hwEntityTemperature', [], 'HUAWEI-ENTITY-EXTENT-MIB');
$huawei['voltage'] = snmpwalk_cache_oid($device, 'hwEntityVoltage', [], 'HUAWEI-ENTITY-EXTENT-MIB');
$huawei['fan']     = snmpwalk_cache_oid($device, 'hwFanStatusEntry', [], 'HUAWEI-ENTITY-EXTENT-MIB');
print_debug_vars($huawei);

// Temperatures
foreach ($huawei['temp'] as $index => $entry) {
    $oid_name = 'hwEntityTemperature';
    $oid_num  = ".1.3.6.1.4.1.2011.5.25.31.1.1.1.1.11.$index";

    $value = $entry[$oid_name];
    if ($value > 0 && $value <= 1000) {
        $descr = snmp_get_oid($device, "entPhysicalAlias.$index", 'ENTITY-MIB');
        if (empty($descr)) {
            $descr = snmp_get_oid($device, "entPhysicalName.$index", 'ENTITY-MIB');
        }
        if (empty($descr)) {
            $descr_array = explode(',', $huawei['sensors_names'][$index]['hwEntityBomEnDesc']);
            $descr       = end($descr_array);
        }

        $options               = ['limit_high' => snmp_get_oid($device, "hwEntityTemperatureThreshold.$index", 'HUAWEI-ENTITY-EXTENT-MIB')];
        $options['rename_rrd'] = "huawei-$index";

        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
    }
}

// Voltages
foreach ($huawei['voltage'] as $index => $entry) {
    $oid_name = 'hwEntityVoltage';
    $oid_num  = ".1.3.6.1.4.1.2011.5.25.31.1.1.1.1.13.$index";

    $value = $entry[$oid_name];
    if ($value != 0 && $value <= 1000) {
        if (strlen($huawei['sensors_names'][$index]['hwEntityBomEnDesc'])) {
            $descr_array = explode(',', $huawei['sensors_names'][$index]['hwEntityBomEnDesc']);
            $descr       = end($descr_array);
        } else {
            $descr = snmp_get_oid($device, "entPhysicalAlias.$index", 'ENTITY-MIB');
            if (empty($descr)) {
                $descr = snmp_get_oid($device, "entPhysicalName.$index", 'ENTITY-MIB');
            }
        }

        $options = ['limit_high' => snmp_get_oid($device, "hwEntityVoltageHighThreshold.$index", 'HUAWEI-ENTITY-EXTENT-MIB'),
                    'limit_low'  => snmp_get_oid($device, "hwEntityVoltageLowThreshold.$index", 'HUAWEI-ENTITY-EXTENT-MIB')];

        $options['rename_rrd'] = "huawei-$index";
        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
    }
}

foreach ($huawei['fan'] as $index => $entry) {
    if ($entry['hwEntityFanPresent'] === 'absent') {
        continue;
    }

    $descr = 'Slot ' . $entry['hwEntityFanSlot'] . ' Fan ' . $entry['hwEntityFanSn'];

    $oid_name = 'hwEntityFanSpeed';
    $oid_num  = '.1.3.6.1.4.1.2011.5.25.31.1.1.10.1.5.' . $index;
    $value    = $entry[$oid_name];

    if ($entry['hwEntityFanSpeed'] > 0) {
        $options = ['rename_rrd' => "huawei-$index"];
        discover_sensor_ng($device, 'load', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
    }

    $oid_name = 'hwEntityFanState';
    $oid_num  = '.1.3.6.1.4.1.2011.5.25.31.1.1.10.1.7.' . $index;
    $value    = $entry[$oid_name];
    discover_status($device, $oid_num, $index, 'huawei-entity-ext-mib-fan-state', $descr, $value, ['entPhysicalClass' => 'fan']);
}

// Optical sensors
//$entity_array   = snmpwalk_cache_oid($device, 'HwOpticalModuleInfoEntry', array(), 'HUAWEI-ENTITY-EXTENT-MIB');
//$entity_array   = snmpwalk_cache_oid($device, 'hwEntityOpticalTemperature', [], 'HUAWEI-ENTITY-EXTENT-MIB');
$entity_array = [];
/**
 * Rx/Tx power scales should be in dBm, but seems some platforms return power in mW
 * FIXME. I not sure that this is correct hack, because latest firmwares always return in dBm?
 * See: https://jira.observium.org/browse/OBS-1362 (initially power hack was added)
 *      https://jira.observium.org/browse/OBS-2937 (issue with different scales sensor and limits)
 */
$power_class       = 'power';
$power_scale       = 0.000001;

foreach (snmpwalk_cache_oid($device, 'hwEntityOpticalTemperature', [], 'HUAWEI-ENTITY-EXTENT-MIB') as $index => $entry) {
    // Ignore optical sensors with temperature of zero or negative
    if ($entry['hwEntityOpticalTemperature'] > 1) {
        $optical_oids  = [
          'hwEntityOpticalVoltage.' . $index,
          'hwEntityOpticalBiasCurrent.' . $index,
          'hwEntityOpticalRxPower.' . $index,
          'hwEntityOpticalRxHighThreshold.' . $index,
          'hwEntityOpticalRxHighWarnThreshold.' . $index,
          'hwEntityOpticalRxLowThreshold.' . $index,
          'hwEntityOpticalRxLowWarnThreshold.' . $index,
          'hwEntityOpticalTxPower.' . $index,
          'hwEntityOpticalTxHighThreshold.' . $index,
          'hwEntityOpticalTxHighWarnThreshold.' . $index,
          'hwEntityOpticalTxLowThreshold.' . $index,
          'hwEntityOpticalTxLowWarnThreshold.' . $index,
          // Multi Lane transceivers
          'hwEntityOpticalLaneBiasCurrent.' . $index,
          'hwEntityOpticalLaneRxPower.' . $index,
          'hwEntityOpticalLaneTxPower.' . $index,
          // Transceiver descriptions
          'hwEntityOpticalVenderPn.' . $index,
          'hwEntityOpticalVenderName.' . $index,
        ];
        $optical_entry = snmp_get_multi_oid($device, $optical_oids, [], 'HUAWEI-ENTITY-EXTENT-MIB');
        if (isset($optical_entry[$index])) {
            $entity_array[$index] = array_merge($entry, $optical_entry[$index]);
        }

        // Detect correct scale for power sensors
        if (($entity_array[$index]['hwEntityOpticalRxPower'] < 0 && $entity_array[$index]['hwEntityOpticalRxPower'] != -1) ||
            ($entity_array[$index]['hwEntityOpticalTxPower'] < 0 && $entity_array[$index]['hwEntityOpticalTxPower'] != -1)) {
            // Disable power hack, sensors in dBm
            $power_class = 'dbm';
            $power_scale = 0.01;
        }
        unset($optical_entry, $entry);
    }
}
print_debug_vars($entity_array);

$rx_limit_oids = [
  'limit_high'      => 'hwEntityOpticalRxHighThreshold',
  'limit_high_warn' => 'hwEntityOpticalRxHighWarnThreshold',
  'limit_low'       => 'hwEntityOpticalRxLowThreshold',
  'limit_low_warn'  => 'hwEntityOpticalRxLowWarnThreshold'
];
$tx_limit_oids = [
  'limit_high'      => 'hwEntityOpticalTxHighThreshold',
  'limit_high_warn' => 'hwEntityOpticalTxHighWarnThreshold',
  'limit_low'       => 'hwEntityOpticalTxLowThreshold',
  'limit_low_warn'  => 'hwEntityOpticalTxLowWarnThreshold'
];

foreach ($entity_array as $index => $entry) {
    $port    = get_port_by_ent_index($device, $index);
    $options = ['entPhysicalIndex' => $index];
    if (is_array($port)) {
        $entry['ifDescr']                     = $port['port_label'];
        $options['measured_class']            = 'port';
        $options['measured_entity']           = $port['port_id'];
        $options['entPhysicalIndex_measured'] = $port['ifIndex'];
    } else {
        // Skip?
        continue;
    }

    $temperatureoid = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.5.' . $index;
    $voltageoid     = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.6.' . $index;
    $biascurrentoid = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.7.' . $index;

    $options['rename_rrd'] = "huawei-$index";
    if (!safe_empty($entry['hwEntityOpticalVenderPn'])) {
        $trans = ' (' . str_ireplace('-EQUIV', '', $entry['hwEntityOpticalVenderName']) . ' ' . $entry['hwEntityOpticalVenderPn'] . ')';
    } else {
        $trans = '';
    }
    discover_sensor_ng($device, 'temperature', $mib, 'hwEntityOpticalTemperature', $temperatureoid, $index, NULL, $entry['ifDescr'] . ' Temperature' . $trans, 1, $entry['hwEntityOpticalTemperature'], $options);
    discover_sensor_ng($device, 'voltage', $mib, 'hwEntityOpticalVoltage', $voltageoid, $index, NULL, $entry['ifDescr'] . ' Voltage' . $trans, 0.001, $entry['hwEntityOpticalVoltage'], $options);
    if ($entry['hwEntityOpticalBiasCurrent'] != -1) {
        discover_sensor_ng($device, 'current', $mib, 'hwEntityOpticalBiasCurrent', $biascurrentoid, $index, NULL, $entry['ifDescr'] . ' Bias Current' . $trans, 0.000001, $entry['hwEntityOpticalBiasCurrent'], $options);
    }

    // MultiLane transceivers
    /*
    hwEntityOpticalVoltage.16981633 = 3310
    hwEntityOpticalBiasCurrent.16981633 = 5900
    hwEntityOpticalRxPower.16981633 = 133
    hwEntityOpticalRxHighThreshold.16981633 = 340
    hwEntityOpticalRxHighWarnThreshold.16981633 = No Such Instance currently exists at this OID
    hwEntityOpticalRxLowThreshold.16981633 = -1429
    hwEntityOpticalRxLowWarnThreshold.16981633 = No Such Instance currently exists at this OID
    hwEntityOpticalTxPower.16981633 = -132
    hwEntityOpticalTxHighThreshold.16981633 = 340
    hwEntityOpticalTxHighWarnThreshold.16981633 = No Such Instance currently exists at this OID
    hwEntityOpticalTxLowThreshold.16981633 = -1160
    hwEntityOpticalTxLowWarnThreshold.16981633 = No Such Instance currently exists at this OID
    hwEntityOpticalLaneBiasCurrent.16981633 = 5.90,5.90,5.90,5.90
    hwEntityOpticalLaneRxPower.16981633 = 133.83,98.54,179.26,80.16
    hwEntityOpticalLaneTxPower.16981633 = -132.65,-59.63,-24.66,-56.65
    hwEntityOpticalVenderPn.16981633 = 02311GBW-OSI
    hwEntityOpticalVenderName.16981633 = Huawei-EQUIV
     */

    $power_scale_multi = 0.01;

    // Rx power
    $rxoptions = $options;
    if (isset($entry['hwEntityOpticalLaneRxPower']) && !safe_empty($entry['hwEntityOpticalLaneRxPower'])) {
        // Prefer Lane Oid, as correct dBm
        $lane_oid  = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.32.' . $index;
        $multilane = str_contains($entry['hwEntityOpticalLaneRxPower'], ',');

        foreach ($rx_limit_oids as $limit => $limit_oid) {
            if (isset($entry[$limit_oid]) && $entry[$limit_oid] != -1) {
                $rxoptions[$limit] = $entry[$limit_oid] * 0.01;
            }
        }

        if ($multilane) {
            // Fix incorrect Power scale.. again
            // See: https://jira.observium.org/browse/OBS-4148
            /*
            hwEntityOpticalRxPower.16850463 = -121
            hwEntityOpticalRxHighThreshold.16850463 = 400
            hwEntityOpticalRxHighWarnThreshold.16850463 = No Such Object available on this agent at this OID
            hwEntityOpticalRxLowThreshold.16850463 = -1801
            hwEntityOpticalRxLowWarnThreshold.16850463 = No Such Object available on this agent at this OID
            hwEntityOpticalLaneRxPower.16850463 = -1.21,-1.51,-1.83,-2.18
             */
            [$lane1_rxpower] = explode(',', $entry['hwEntityOpticalLaneRxPower']);
            if (($entry['hwEntityOpticalRxPower'] != -1) && float_cmp($entry['hwEntityOpticalRxPower'] * 0.01, $lane1_rxpower, 0.01) === 0) {
                $power_scale_multi = 1;
            }

            $rxoptions['sensor_unit'] = 'split1';
            $lane_descr               = $entry['ifDescr'] . ' Lane 1 Rx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneRxPower', $lane_oid, $index, NULL, $lane_descr, $power_scale_multi, $entry['hwEntityOpticalLaneRxPower'], $rxoptions);

            $rxoptions['sensor_unit'] = 'split2';
            $lane_descr               = $entry['ifDescr'] . ' Lane 2 Rx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneRxPower', $lane_oid, $index, NULL, $lane_descr, $power_scale_multi, $entry['hwEntityOpticalLaneRxPower'], $rxoptions);

            $rxoptions['sensor_unit'] = 'split3';
            $lane_descr               = $entry['ifDescr'] . ' Lane 3 Rx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneRxPower', $lane_oid, $index, NULL, $lane_descr, $power_scale_multi, $entry['hwEntityOpticalLaneRxPower'], $rxoptions);

            $rxoptions['sensor_unit'] = 'split4';
            $lane_descr               = $entry['ifDescr'] . ' Lane 4 Rx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneRxPower', $lane_oid, $index, NULL, $lane_descr, $power_scale_multi, $entry['hwEntityOpticalLaneRxPower'], $rxoptions);
        } else {
            $rxoptions['rename_rrd'] = "HUAWEI-ENTITY-EXTENT-MIB-hwEntityOpticalRxPower-$index";
            $lane_descr              = $entry['ifDescr'] . ' Rx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneRxPower', $lane_oid, $index, NULL, $lane_descr, $power_scale_multi, $entry['hwEntityOpticalLaneRxPower'], $rxoptions);
        }
    } elseif ($entry['hwEntityOpticalRxPower'] != -1) {
        // Huawei does not follow their own MIB for some devices and instead reports Rx/Tx Power as dBm converted to mW then multiplied by 1000
        $rxpoweroid = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.8.' . $index;

        // Limits always as dBm
        foreach ($rx_limit_oids as $limit => $limit_oid) {
            if (isset($entry[$limit_oid]) && $entry[$limit_oid] != -1) {
                $rxoptions[$limit] = $entry[$limit_oid] * 0.01;
                if ($power_class === 'power') {
                    $rxoptions[$limit] = value_to_si($rxoptions[$limit], 'dBm', 'power'); // Limit in dBm, convert to W
                }
            }
        }

        $rxoptions['rename_rrd'] = "huawei-hwEntityOpticalRxPower.$index";
        discover_sensor_ng($device, $power_class, $mib, 'hwEntityOpticalRxPower', $rxpoweroid, $index, NULL, $entry['ifDescr'] . ' Rx Power' . $trans, $power_scale, $entry['hwEntityOpticalRxPower'], $rxoptions);
    }

    // Tx power
    $txoptions = $options;
    if (isset($entry['hwEntityOpticalLaneTxPower']) && !safe_empty($entry['hwEntityOpticalLaneTxPower'])) {
        // Prefer Lane Oid, as correct dBm
        $lane_oid  = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.33.' . $index;
        $multilane = str_contains($entry['hwEntityOpticalLaneTxPower'], ',');

        foreach ($tx_limit_oids as $limit => $limit_oid) {
            if (isset($entry[$limit_oid]) && $entry[$limit_oid] != -1) {
                $txoptions[$limit] = $entry[$limit_oid] * 0.01;
            }
        }

        if ($multilane) {
            // Fix incorrect Power scale.. again
            // See: https://jira.observium.org/browse/OBS-4148
            /*
            hwEntityOpticalTxPower.16850463 = -25
            hwEntityOpticalTxHighThreshold.16850463 = 400
            hwEntityOpticalTxHighWarnThreshold.16850463 = No Such Object available on this agent at this OID
            hwEntityOpticalTxLowThreshold.16850463 = -1060
            hwEntityOpticalTxLowWarnThreshold.16850463 = No Such Object available on this agent at this OID
            hwEntityOpticalLaneTxPower.16850463 = -0.25,-0.26,-0.26,-0.23
             */
            [$lane1_txpower] = explode(',', $entry['hwEntityOpticalLaneTxPower']);
            if (($entry['hwEntityOpticalTxPower'] != -1) && float_cmp($entry['hwEntityOpticalTxPower'] * 0.01, $lane1_txpower, 0.01) === 0) {
                $power_scale_multi = 1;
            }

            $txoptions['sensor_unit'] = 'split1';
            $lane_descr               = $entry['ifDescr'] . ' Lane 1 Tx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneTxPower', $lane_oid, $index, NULL, $lane_descr, $power_scale_multi, $entry['hwEntityOpticalLaneTxPower'], $txoptions);

            $txoptions['sensor_unit'] = 'split2';
            $lane_descr               = $entry['ifDescr'] . ' Lane 2 Tx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneTxPower', $lane_oid, $index, NULL, $lane_descr, $power_scale_multi, $entry['hwEntityOpticalLaneTxPower'], $txoptions);

            $txoptions['sensor_unit'] = 'split3';
            $lane_descr               = $entry['ifDescr'] . ' Lane 3 Tx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneTxPower', $lane_oid, $index, NULL, $lane_descr, $power_scale_multi, $entry['hwEntityOpticalLaneTxPower'], $txoptions);

            $txoptions['sensor_unit'] = 'split4';
            $lane_descr               = $entry['ifDescr'] . ' Lane 4 Tx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneTxPower', $lane_oid, $index, NULL, $lane_descr, $power_scale_multi, $entry['hwEntityOpticalLaneTxPower'], $txoptions);
        } else {
            $txoptions['rename_rrd'] = "HUAWEI-ENTITY-EXTENT-MIB-hwEntityOpticalTxPower-$index";
            $lane_descr              = $entry['ifDescr'] . ' Tx Power' . $trans;
            discover_sensor_ng($device, 'dbm', $mib, 'hwEntityOpticalLaneTxPower', $lane_oid, $index, NULL, $lane_descr, 0.01, $entry['hwEntityOpticalLaneTxPower'], $txoptions);
        }
    } elseif ($entry['hwEntityOpticalTxPower'] != -1) {
        // Huawei does not follow their own MIB for some devices and instead reports Rx/Tx Power as dBm converted to mW then multiplied by 1000
        $txpoweroid = '.1.3.6.1.4.1.2011.5.25.31.1.1.3.1.9.' . $index;

        foreach ($tx_limit_oids as $limit => $limit_oid) {
            if (isset($entry[$limit_oid]) && $entry[$limit_oid] != -1) {
                $txoptions[$limit] = $entry[$limit_oid] * 0.01;
                if ($power_class === 'power') {
                    $txoptions[$limit] = value_to_si($txoptions[$limit], 'dBm', 'power'); // Limit in dBm, convert to W
                }
            }
        }

        $txoptions['rename_rrd'] = "huawei-hwEntityOpticalTxPower.$index";
        discover_sensor_ng($device, $power_class, $mib, 'hwEntityOpticalTxPower', $txpoweroid, $index, NULL, $entry['ifDescr'] . ' Tx Power' . $trans, $power_scale, $entry['hwEntityOpticalTxPower'], $txoptions);
    }

}

unset($entity_array, $huawei);

// EOF
