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

$bcsiIfMediaInfoEntry = snmpwalk_cache_oid($device, 'bcsiIfMediaInfoEntry', [], 'BROCADE-OPTICAL-MONITORING-MIB');
print_debug_vars($bcsiIfMediaInfoEntry);

// Single-line
$bcsiOptMonInfoEntry = snmpwalk_cache_oid($device, 'bcsiOptMonInfoEntry', [], 'BROCADE-OPTICAL-MONITORING-MIB');
print_debug_vars($bcsiOptMonInfoEntry);

// Multi-line
$bcsiOptMonLaneEntry = snmpwalk_multipart_oid($device, 'bcsiOptMonLaneEntry', [], 'BROCADE-OPTICAL-MONITORING-MIB');
print_debug_vars($bcsiOptMonLaneEntry);

foreach ($bcsiOptMonLaneEntry as $ifIndex => $transeiver) {
    $multilane = count($transeiver) > 1; // Check if transceiver multi-lane (40G/100G)

    foreach ($transeiver as $lane => $entry) {
        $index = $ifIndex . '.' . $lane;

        $entry['ifIndex'] = $ifIndex;
        $entry['index']   = $index;
        $match            = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%ifIndex%']];
        $options          = entity_measured_match_definition($device, $match, $entry);
        //print_debug_vars($options);

        $name = $options['port_label'];
        if ($multilane) {
            // For multilane append lane number
            $name .= ' Lane ' . $lane;
        }
        if (isset($bcsiIfMediaInfoEntry[$ifIndex])) {
            $name .= ' (' . $bcsiIfMediaInfoEntry[$ifIndex]['bcsiIfMediaVendorName'] . ' ' . $bcsiIfMediaInfoEntry[$ifIndex]['bcsiIfMediaPartNumber'] . ')';
        }

        // Temperature
        $descr    = $name . ' Temperature';
        $class    = 'temperature';
        $oid_name = 'bcsiOptMonLaneTemperature';
        $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.1.1.2.' . $index;
        $scale    = 1;
        $value    = $entry[$oid_name];
        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        // Tx Bias
        $descr    = $name . ' Tx Bias';
        $class    = 'current';
        $oid_name = 'bcsiOptMonLaneTxBiasCurrent';
        $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.1.1.9.' . $index;
        $scale    = 0.001;
        $value    = $entry[$oid_name];
        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        // Tx Power
        $descr    = $name . ' Tx Power';
        $class    = 'power';
        $oid_name = 'bcsiOptMonLaneTxPowerVal';
        $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.1.1.5.' . $index;
        $scale    = 0.000001;
        $value    = $entry[$oid_name];
        if ($entry['bcsiOptMonLaneTxPowerStatus'] !== 'notSupported') {
            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }

        // Tx Power Status
        $descr    = $name . ' Tx Power Status';
        $class    = 'port';
        $oid_name = 'bcsiOptMonLaneTxPowerStatus';
        $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.1.1.3.' . $index;
        $value    = $entry[$oid_name];
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'bcsiOptMonPowerStatus', $descr, $value, $options);

        // Rx Power
        $descr    = $name . ' Rx Power';
        $class    = 'power';
        $oid_name = 'bcsiOptMonLaneRxPowerVal';
        $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.1.1.8.' . $index;
        $scale    = 0.000001;
        $value    = $entry[$oid_name];
        if ($entry['bcsiOptMonLaneRxPowerStatus'] !== 'notSupported') {
            discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }

        // Rx Power Status
        $descr    = $name . ' Rx Power Status';
        $class    = 'port';
        $oid_name = 'bcsiOptMonLaneRxPowerStatus';
        $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.1.1.6.' . $index;
        $value    = $entry[$oid_name];
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'bcsiOptMonPowerStatus', $descr, $value, $options);
    }
}

foreach ($bcsiOptMonInfoEntry as $ifIndex => $entry) {
    // Skip Multi-lane entries
    if (isset($bcsiOptMonLaneEntry[$ifIndex])) {
        continue;
    }

    $index            = $ifIndex;
    $entry['ifIndex'] = $ifIndex;
    $entry['index']   = $index;
    $match            = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%ifIndex%']];
    $options          = entity_measured_match_definition($device, $match, $entry);
    //print_debug_vars($options);

    $name = $options['port_label'];

    if (isset($bcsiIfMediaInfoEntry[$ifIndex])) {
        $name .= ' (' . $bcsiIfMediaInfoEntry[$ifIndex]['bcsiIfMediaVendorName'] . ' ' . $bcsiIfMediaInfoEntry[$ifIndex]['bcsiIfMediaPartNumber'] . ')';
    }

    // Temperature
    $descr    = $name . ' Temperature';
    $class    = 'temperature';
    $oid_name = 'bcsiOptMonTemperature';
    $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.2.1.1.' . $index;
    $scale    = 1;
    $value    = $entry[$oid_name];
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    // Tx Bias
    $descr    = $name . ' Tx Bias';
    $class    = 'current';
    $oid_name = 'bcsiOptMonTxBiasCurrent';
    $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.2.1.8.' . $index;
    $scale    = 0.001;
    $value    = $entry[$oid_name];
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    // Tx Power
    $descr    = $name . ' Tx Power';
    $class    = 'power';
    $oid_name = 'bcsiOptMonTxPowerVal';
    $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.2.1.4.' . $index;
    $scale    = 0.000001;
    $value    = $entry[$oid_name];
    if ($entry['bcsiOptMonTxPowerStatus'] !== 'notSupported') {
        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
    }

    // Tx Power Status
    $descr    = $name . ' Tx Power Status';
    $class    = 'port';
    $oid_name = 'bcsiOptMonTxPowerStatus';
    $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.2.1.2.' . $index;
    $value    = $entry[$oid_name];
    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'bcsiOptMonPowerStatus', $descr, $value, $options);

    // Rx Power
    $descr    = $name . ' Rx Power';
    $class    = 'power';
    $oid_name = 'bcsiOptMonRxPowerVal';
    $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.2.1.7.' . $index;
    $scale    = 0.000001;
    $value    = $entry[$oid_name];
    if ($entry['bcsiOptMonRxPowerStatus'] !== 'notSupported') {
        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
    }

    // Rx Power Status
    $descr    = $name . ' Rx Power Status';
    $class    = 'port';
    $oid_name = 'bcsiOptMonRxPowerStatus';
    $oid_num  = '.1.3.6.1.4.1.1588.3.1.8.1.2.1.5.' . $index;
    $value    = $entry[$oid_name];
    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'bcsiOptMonPowerStatus', $descr, $value, $options);
}

// EOF
