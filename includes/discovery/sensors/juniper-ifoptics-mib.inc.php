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

$jnx = snmpwalk_cache_oid($device, 'jnxOpticsPMCurrentTable', [], 'JUNIPER-IFOPTICS-MIB');
if (count($jnx) == 0) {
    return;
}

$jnx_config = snmpwalk_cache_oid($device, 'jnxOpticsConfigTable', [], 'JUNIPER-IFOPTICS-MIB');
$jnx_config = snmpwalk_cache_oid($device, 'jnxIfOtnOChCfgTable', $jnx_config, 'JUNIPER-IFOTN-MIB');

foreach ($jnx_config as $entry) {
    $ifIndex = $entry['jnxIfOtnIndex'];
    if (isset($jnx[$ifIndex])) {
        $jnx[$ifIndex] = array_merge($jnx[$ifIndex], $entry);
    }
}
if (OBS_DEBUG > 1) {
    print_vars($jnx);
    //print_vars($jnx_config);
}

foreach ($jnx as $index => $entry) {
    $options = ['entPhysicalIndex' => $index];
    $port    = get_port_by_index_cache($device['device_id'], $index);
    if (is_array($port)) {
        $entry['ifDescr']           = $port['ifDescr'];
        $options['measured_class']  = 'port';
        $options['measured_entity'] = $port['port_id'];
    } else {
        $entry['ifDescr'] = snmp_get($device, 'ifDescr.' . $index, '-Oqv', 'IF-MIB');
    }

    // jnxPMCurTemperature
    $descr    = $entry['ifDescr'] . ' DOM';
    $oid_name = 'jnxPMCurTemperature';
    $oid_num  = ".1.3.6.1.4.1.2636.3.71.1.2.1.1.39.{$index}";
    $scale    = 1;
    $value    = $entry[$oid_name];
    $limits   = ['limit_high' => $entry['jnxModuleTempHighThresh'] * $scale,
                 'limit_low'  => $entry['jnxModuleTempLowThresh'] * $scale];
    if ($value != 0 && $value != -32768) {
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
    }

    if (($entry['jnxPMCurTxOutputPower'] == 0 || $entry['jnxPMCurTxOutputPower'] == -32768) &&
        ($entry['jnxPMCurRxInputPower'] == 0 || $entry['jnxPMCurRxInputPower'] == -32768) &&
        ($entry['jnxPMCurTxLaserBiasCurrent'] == 0 || $entry['jnxPMCurTxLaserBiasCurrent'] == -32768) &&
        ($entry['jnxPMCurRxLaserBiasCurrent'] == 0 || $entry['jnxPMCurRxLaserBiasCurrent'] == -32768)) {
        // Skip other empty dom sensors if all values are zero or -32768
        continue;
    }

    // jnxPMCurTxOutputPower
    $descr    = $entry['ifDescr'] . ' TX Power';
    $oid_name = 'jnxPMCurTxOutputPower';
    $oid_num  = ".1.3.6.1.4.1.2636.3.71.1.2.1.1.7.{$index}";
    $scale    = 0.01;
    $value    = $entry[$oid_name];
    $limits   = ['limit_high' => $entry['jnxTxPowerHighThresh'] * $scale,
                 'limit_low'  => $entry['jnxTxPowerLowThresh'] * $scale];

    discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // jnxPMCurRxInputPower
    $descr    = $entry['ifDescr'] . ' RX Power';
    $oid_name = 'jnxPMCurRxInputPower';
    $oid_num  = ".1.3.6.1.4.1.2636.3.71.1.2.1.1.8.{$index}";
    $scale    = 0.01;
    $value    = $entry[$oid_name];
    $limits   = ['limit_high' => $entry['jnxRxPowerHighThresh'] * $scale,
                 'limit_low'  => $entry['jnxRxPowerLowThresh'] * $scale];
    discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // jnxPMCurTxLaserBiasCurrent
    $descr    = $entry['ifDescr'] . ' TX Bias';
    $oid_name = 'jnxPMCurTxLaserBiasCurrent';
    $oid_num  = ".1.3.6.1.4.1.2636.3.71.1.2.1.1.35.{$index}";
    $scale    = 0.0001;
    $value    = $entry[$oid_name];
    discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // jnxPMCurRxLaserBiasCurrent
    $descr    = $entry['ifDescr'] . ' RX Bias';
    $oid_name = 'jnxPMCurRxLaserBiasCurrent';
    $oid_num  = ".1.3.6.1.4.1.2636.3.71.1.2.1.1.47.{$index}";
    $scale    = 0.0001;
    $value    = $entry[$oid_name];
    discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // FIXME. Not added or unsupported now
    // jnxPMCurChromaticDispersion
    // jnxPMCurDiffGroupDelay
    // jnxPMCurQ
    // jnxPMCurSNR
    // jnxPMCurSuspectedFlag
    // jnxPMCurCarFreqOffset
}

// EOF
