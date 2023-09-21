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

// This could probably do with a rewrite, I suspect there's 1 table that can be walked for all the info below instead of 4.

$oids = snmpwalk_cache_oid($device, "snIfOpticalMonitoringInfoTable", [], "FOUNDRY-SN-SWITCH-GROUP-MIB");

foreach ($oids as $index => $entry) {
    $options = ['entPhysicalIndex' => $index];
    $port    = get_port_by_index_cache($device['device_id'], $index);

    if (is_array($port)) {
        $descr                      = $port["port_label"];
        $options['measured_class']  = 'port';
        $options['measured_entity'] = $port['port_id'];
    } else {
        $descr = snmp_get_oid($device, "ifDescr.$index", "IF-MIB");
    }

    $value = $entry['snIfOpticalMonitoringTxBiasCurrent'];
    if (!str_contains($value, "N/A")) {

        $oid = ".1.3.6.1.4.1.1991.1.1.3.3.6.1.4.$index";

        $options['rename_rrd'] = "brocade-dom-$index";
        discover_sensor_ng($device, 'current', $mib, 'snIfOpticalMonitoringTxBiasCurrent', $oid, $index, NULL, $descr . " TX Bias Current", 0.001, $value, $options);
    }

    $value = $entry['snIfOpticalMonitoringTxPower'];
    if (!str_contains($value, "N/A")) {
        $oid = ".1.3.6.1.4.1.1991.1.1.3.3.6.1.2.$index";

        $options['rename_rrd'] = "brocade-dom-tx-$index";
        if (str_icontains_array($value, 'uWatts')) {
            discover_sensor_ng($device, 'power', $mib, 'snIfOpticalMonitoringTxPower', $oid, $index, NULL, $descr . " TX Power", 0.000001, $value, $options);
        } else {
            discover_sensor_ng($device, 'dbm', $mib, 'snIfOpticalMonitoringTxPower', $oid, $index, NULL, $descr . " TX Power", 1, $value, $options);
        }
    }

    $value = $entry['snIfOpticalMonitoringRxPower'];
    if (!str_contains($value, "N/A")) {
        $oid = ".1.3.6.1.4.1.1991.1.1.3.3.6.1.3.$index";

        $options['rename_rrd'] = "brocade-dom-rx-$index";
        if (str_icontains_array($value, 'uWatts')) {
            discover_sensor_ng($device, 'power', $mib, 'snIfOpticalMonitoringRxPower', $oid, $index, NULL, $descr . " RX Power", 0.000001, $value, $options);
        } else {
            discover_sensor_ng($device, 'dbm', $mib, 'snIfOpticalMonitoringRxPower', $oid, $index, NULL, $descr . " RX Power", 1, $value, $options);
        }
    }

    $value = $entry['snIfOpticalMonitoringTemperature'];
    if (!str_contains($value, "N/A")) {
        $oid = ".1.3.6.1.4.1.1991.1.1.3.3.6.1.1.$index";

        $options['rename_rrd'] = "brocade-dom-$index";
        discover_sensor_ng($device, 'temperature', $mib, 'snIfOpticalMonitoringTemperature', $oid, $index, NULL, $descr . " Temperature", 1, $value, $options);
    }
}

/*
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringTemperature.201334784.1 = STRING: "57 C Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringTxPower.201334784.1 = STRING: "566.4 uWatts Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringRxPower.201334784.1 = STRING: "451.1 uWatts Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringTxBiasCurrent.201334784.1 = STRING: "6.184 mAmps Normal"

FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringTemperature.44.1 = " 31.6679 C Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringTxPower.44.1 = "-007.8489 dBm Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringRxPower.44.1 = "-008.9962 dBm Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringTxBiasCurrent.44.1 = " 15.792 mA Normal"
FOUNDRY-SN-SWITCH-GROUP-MIB::snIfOpticalLaneMonitoringVoltage.44.1 = "3.2744 volts Normal"
*/

$oids = snmpwalk_cache_twopart_oid($device, "snIfOpticalLaneMonitoringTable", [], "FOUNDRY-SN-SWITCH-GROUP-MIB");

//print_r($oids);

foreach ($oids as $ifIndex => $lanes) {

    $options = [];
    $port    = get_port_by_index_cache($device['device_id'], $ifIndex);

    if (is_array($port)) {
        $port_descr                 = $port["port_label"];
        $options['measured_class']  = 'port';
        $options['measured_entity'] = $port['port_id'];
    } else {
        $port_descr = snmp_get_oid($device, "ifDescr.$index", "IF-MIB");
    }

    foreach ($lanes as $lane => $entry) {

        $lane_descr = "$port_descr Lane $lane";
        $index      = "$ifIndex.$lane";

        if ($entry['snIfOpticalLaneMonitoringTemperature'] !== "NA") {
            $value    = $entry['snIfOpticalLaneMonitoringTemperature'];
            $oid_name = 'snIfOpticalLaneMonitoringTemperature';
            $oid_num  = '.1.3.6.1.4.1.1991.1.1.3.3.10.1.2.' . $index;
            $descr    = "$lane_descr Temperature";

            discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }

        if ($entry['snIfOpticalLaneMonitoringTxPower'] !== "NA") {
            $value    = $entry['snIfOpticalLaneMonitoringTxPower'];
            $oid_name = 'snIfOpticalLaneMonitoringTxPower';
            $oid_num  = '.1.3.6.1.4.1.1991.1.1.3.3.10.1.3.' . $index;
            $descr    = "$lane_descr TX Power";

            if (str_icontains_array($value, 'dBm')) {
                discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
            } else {
                discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.000001, $value, $options);
            }
        }

        if ($entry['snIfOpticalLaneMonitoringRxPower'] !== "NA") {
            $value    = $entry['snIfOpticalLaneMonitoringRxPower'];
            $oid_name = 'snIfOpticalLaneMonitoringRxPower';
            $oid_num  = '.1.3.6.1.4.1.1991.1.1.3.3.10.1.4.' . $index;
            $descr    = "$lane_descr RX Power";

            if (str_icontains_array($value, 'dBm')) {
                discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
            } else {
                discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.000001, $value, $options);
            }
        }

        if ($entry['snIfOpticalLaneMonitoringTxBiasCurrent'] !== "NA") {
            $value    = $entry['snIfOpticalLaneMonitoringTxBiasCurrent'];
            $oid_name = 'snIfOpticalLaneMonitoringTxBiasCurrent';
            $oid_num  = '.1.3.6.1.4.1.1991.1.1.3.3.10.1.5.' . $index;
            $descr    = "$lane_descr TX Bias Current";

            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, 0.001, $value, $options);
        }

        if ($entry['snIfOpticalLaneMonitoringVoltage'] !== "NA") {
            $value    = $entry['snIfOpticalLaneMonitoringVoltage'];
            $oid_name = 'snIfOpticalLaneMonitoringVoltage';
            $oid_num  = '.1.3.6.1.4.1.1991.1.1.3.3.10.1.6.' . $index;
            $descr    = "$lane_descr Voltage";

            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);
        }
    }
}


// EOF
