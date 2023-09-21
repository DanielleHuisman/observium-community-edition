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


// RIP falz 2017-05

$cienaWsXcvrObjects_oids = [
  'cwsXcvrTemperatureActual',
  'cwsXcvrTemperatureThresholdHighAlarmThreshold',
  'cwsXcvrTemperatureThresholdLowAlarmThreshold',
  'cwsXcvrTemperatureThresholdHighWarningThreshold',
  'cwsXcvrTemperatureThresholdLowWarningThreshold',

  'cwsXcvrChannelRxPowerActual',
  'cwsXcvrRxPowerThresholdHighAlarmThreshold',
  'cwsXcvrRxPowerThresholdLowAlarmThreshold',
  'cwsXcvrRxPowerThresholdHighWarningThreshold',
  'cwsXcvrRxPowerThresholdLowWarningThreshold',

  'cwsXcvrChannelTxPowerActual',
  'cwsXcvrTxPowerThresholdHighAlarmThreshold',
  'cwsXcvrTxPowerThresholdLowAlarmThreshold',
  'cwsXcvrTxPowerThresholdHighWarningThreshold',
  'cwsXcvrTxPowerThresholdLowWarningThreshold'
];

$oids = [];
foreach ($cienaWsXcvrObjects_oids as $oid) {
    $oids = snmpwalk_cache_oid($device, $oid, $oids, 'CIENA-WS-XCVR-MIB');
}

foreach ($oids as $index => $entry) {

    // array index looks like [4.0] if theres only one lane, or [24.1.0] if there are >1 lanes such as LR4. it will then be 24.1.0 ... 24.4.0.
    // the first number is is ifIndex, grab it.
    [$ifIndex, $lane,] = explode(".", $index, 3);

    // if 2nd element is greater than 0, it's the lane number (1-4 for LR4)
    if ($lane > 0) {
        $laneDescr = " Lane " . $lane;
    }

    $options = ['entPhysicalIndex' => $index];

    $port = get_port_by_index_cache($device['device_id'], $ifIndex);
    if (is_array($port)) {
        $entry['ifDescr']           = $port['ifDescr'];
        $options['measured_class']  = 'port';
        $options['measured_entity'] = $port['port_id'];
    } else {
        $entry['ifDescr'] = snmp_get($device, "ifDescr.$ifIndex", '-Oqv', 'IF-MIB');
    }

    // ciena $ifDescr is stupidly long: "Ciena Waveserver 100GigEthernet 1". trim off the first part to save some bits
    $entry['ifDescr'] = preg_replace('/^Ciena Waveserver /', '', $entry['ifDescr']);
    #echo $entry['ifDescr'];


    // CIENA-WS-XCVR-MIB::cwsXcvrTemperatureActual.4.0 = INTEGER: 32
    // CIENA-WS-XCVR-MIB::cwsXcvrTemperatureThresholdHighAlarmThreshold.4.0 = INTEGER: 78
    // CIENA-WS-XCVR-MIB::cwsXcvrTemperatureThresholdLowAlarmThreshold.4.0 = INTEGER: 0
    // CIENA-WS-XCVR-MIB::cwsXcvrTemperatureThresholdHighWarningThreshold.4.0 = INTEGER: 69
    // CIENA-WS-XCVR-MIB::cwsXcvrTemperatureThresholdLowWarningThreshold.4.0 = INTEGER: 0

    $descr    = $entry['ifDescr'] . ' Temp';
    $oid_name = 'cwsXcvrTemperatureActual';
    $oid_num  = '.1.3.6.1.4.1.1271.3.4.15.13.1.2.' . $index;
    $type     = 'cwsXcvrTemperatureActual'; // $mib . '-' . $oid_name;
    if (version_compare($device['version'], '1.6', '>=')) {
        // See: http://jira.observium.org/browse/OBS-2753
        $scale = 0.001;
    } else {
        $scale = 1;
    }
    $value = $entry[$oid_name];

    $limits = ['limit_high'      => $entry['cwsXcvrTemperatureThresholdHighAlarmThreshold'],
               'limit_low'       => $entry['cwsXcvrTemperatureThresholdLowAlarmThreshold'],
               'limit_high_warn' => $entry['cwsXcvrTemperatureThresholdHighWarningThreshold'],
               'limit_low_warn'  => $entry['cwsXcvrTemperatureThresholdLowWarningThreshold']];

    if ($value != 0) {
        $limits['rename_rrd'] = 'cwsXcvrTemperatureActual-' . $index;
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
    }

    if ($entry['cwsXcvrChannelTxPowerActual'] == 0 && $entry['cwsXcvrChannelRxPowerActual'] == 0) {
        // Skip other empty dom sensors
        continue;
    }


    #CIENA-WS-XCVR-MIB::cwsXcvrChannelRxPowerActual.4.1.0 = INTEGER: -15.8
    #CIENA-WS-XCVR-MIB::cwsXcvrRxPowerThresholdHighAlarmThreshold.4.1.0 = INTEGER: 5.5
    #CIENA-WS-XCVR-MIB::cwsXcvrRxPowerThresholdLowAlarmThreshold.4.1.0 = INTEGER: -22.5
    #CIENA-WS-XCVR-MIB::cwsXcvrRxPowerThresholdHighWarningThreshold.4.1.0 = INTEGER: .0
    #CIENA-WS-XCVR-MIB::cwsXcvrRxPowerThresholdLowWarningThreshold.4.1.0 = INTEGER: .0

    $descr    = $entry['ifDescr'] . $laneDescr . " RX Power";
    $oid_name = 'cwsXcvrChannelRxPowerActual';
    $oid_num  = '.1.3.6.1.4.1.1271.3.4.15.17.1.2.' . $index;
    $type     = 'cwsXcvrChannelRxPowerActual'; // $mib . '-' . $oid_name;
    $scale    = .1;
    $value    = $entry[$oid_name];

    $limits = ['limit_high'      => $entry['cwsXcvrRxPowerThresholdHighAlarmThreshold'],
               'limit_low'       => $entry['cwsXcvrRxPowerThresholdLowAlarmThreshold'],
               'limit_high_warn' => $entry['cwsXcvrRxPowerThresholdHighWarningThreshold'],
               'limit_low_warn'  => $entry['cwsXcvrRxPowerThresholdLowWarningThreshold']];

    # some interfaces always return .0 for warnings, unset them if this is the case.
    if ($entry['cwsXcvrRxPowerThresholdHighWarningThreshold'] == .0 || $entry['cwsXcvrRxPowerThresholdLowWarningThreshold'] == .0) {
        unset($limits['limit_low_warn']);
        unset($limits['limit_high_warn']);
    }

    $limits['rename_rrd'] = 'cwsXcvrChannelRxPowerActual-' . $index;
    discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));


    // CIENA-WS-XCVR-MIB::cwsXcvrChannelTxPowerActual.4.1.0 = INTEGER: 1.5
    // CIENA-WS-XCVR-MIB::cwsXcvrTxPowerThresholdHighAlarmThreshold.4.1.0 = INTEGER: 4.5
    // CIENA-WS-XCVR-MIB::cwsXcvrTxPowerThresholdLowAlarmThreshold.4.1.0 = INTEGER: -8.5
    // CIENA-WS-XCVR-MIB::cwsXcvrTxPowerThresholdHighWarningThreshold.4.1.0 = INTEGER: .0
    // CIENA-WS-XCVR-MIB::cwsXcvrTxPowerThresholdLowWarningThreshold.4.1.0 = INTEGER: .0

    $descr    = $entry['ifDescr'] . $laneDescr . " TX Power";
    $oid_name = 'cwsXcvrChannelTxPowerActual';
    $oid_num  = '.1.3.6.1.4.1.1271.3.4.15.20.1.2.' . $index;
    $type     = 'cwsXcvrChannelTxPowerActual'; // $mib . '-' . $oid_name;
    $scale    = .1;
    $value    = $entry[$oid_name];

    $limits = ['limit_high'      => $entry['cwsXcvrTxPowerThresholdHighAlarmThreshold'],
               'limit_low'       => $entry['cwsXcvrTxPowerThresholdLowAlarmThreshold'],
               'limit_high_warn' => $entry['cwsXcvrTxPowerThresholdHighWarningThreshold'],
               'limit_low_warn'  => $entry['cwsXcvrTxPowerThresholdLowWarningThreshold']];


    # some interfaces always return .0 for warnings, unset them if this is the case.
    if ($entry['cwsXcvrTxPowerThresholdHighWarningThreshold'] == .0 || $entry['cwsXcvrTxPowerThresholdLowWarningThreshold'] == .0) {
        unset($limits['limit_low_warn']);
        unset($limits['limit_high_warn']);
    }

    $limits['rename_rrd'] = 'cwsXcvrChannelTxPowerActual-' . $index;
    discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, $type, $descr, $scale, $value, array_merge($options, $limits));
}

// EOF
