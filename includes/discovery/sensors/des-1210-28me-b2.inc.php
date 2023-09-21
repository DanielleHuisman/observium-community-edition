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

// DES-1210-28ME-B2::sfpConnectorType.28 = STRING: SFP - SC
// DES-1210-28ME-B2::sfpTranceiverCode.28 = STRING: Single Mode
// DES-1210-28ME-B2::sfpBaudRate.28 = STRING: d
// DES-1210-28ME-B2::sfpVendorName.28 = STRING:
// DES-1210-28ME-B2::sfpVendorOui.28 = STRING:  0: 0: 0
// DES-1210-28ME-B2::sfpVendorPn.28 = STRING: AP-B53121-3CS3
// DES-1210-28ME-B2::sfpVendorRev.28 = STRING: 1.00
// DES-1210-28ME-B2::sfpWavelength.28 = STRING: 60e
// DES-1210-28ME-B2::sfpVendorSn.28 = STRING: SG53E2080XXXX
// DES-1210-28ME-B2::sfpDateCode.28 = STRING: 120224

// DES-1210-28ME-B2::ddmActionPort.28 = INTEGER: 28
// DES-1210-28ME-B2::ddmActionState.28 = INTEGER: disable(0)
// DES-1210-28ME-B2::ddmActionShutdown.28 = INTEGER: none(0)

// DES-1210-28ME-B2::ddmHighAlarm.28.temperature = STRING: +95.000
// DES-1210-28ME-B2::ddmHighAlarm.28.voltage = STRING: 3.80
// DES-1210-28ME-B2::ddmHighAlarm.28.bias = STRING: 20.0
// DES-1210-28ME-B2::ddmHighAlarm.28.txPower = STRING: 1.5900
// DES-1210-28ME-B2::ddmHighAlarm.28.rxPower = STRING: 1.2599

// DES-1210-28ME-B2::ddmLowAlarm.28.temperature = STRING: -25.000
// DES-1210-28ME-B2::ddmLowAlarm.28.voltage = STRING: 2.80
// DES-1210-28ME-B2::ddmLowAlarm.28.bias = STRING: 0.5
// DES-1210-28ME-B2::ddmLowAlarm.28.txPower = STRING: 0.1599
// DES-1210-28ME-B2::ddmLowAlarm.28.rxPower = STRING: 0.0099

// DES-1210-28ME-B2::ddmHighWarning.28.temperature = STRING: +90.000
// DES-1210-28ME-B2::ddmHighWarning.28.voltage = STRING: 3.70
// DES-1210-28ME-B2::ddmHighWarning.28.bias = STRING: 18.0
// DES-1210-28ME-B2::ddmHighWarning.28.txPower = STRING: 1.2599
// DES-1210-28ME-B2::ddmHighWarning.28.rxPower = STRING: 1.0000

// DES-1210-28ME-B2::ddmLowWarning.28.temperature = STRING: -20.000
// DES-1210-28ME-B2::ddmLowWarning.28.voltage = STRING: 2.90
// DES-1210-28ME-B2::ddmLowWarning.28.bias = STRING: 1.0
// DES-1210-28ME-B2::ddmLowWarning.28.txPower = STRING: 0.2000
// DES-1210-28ME-B2::ddmLowWarning.28.rxPower = STRING: 0.0126

// DES-1210-28ME-B2::ddmStatusPort.28 = INTEGER: 28
// DES-1210-28ME-B2::ddmTemperature.28 = STRING:
// DES-1210-28ME-B2::ddmVoltage.28 = STRING:
// DES-1210-28ME-B2::ddmBiasCurrent.28 = STRING:
// DES-1210-28ME-B2::ddmTxPower.28 = STRING:
// DES-1210-28ME-B2::ddmRxPower.28 = STRING:

$oids = snmpwalk_cache_oid($device, 'ddmStatusTable', [], $mib);
print_debug_vars($oids);

if (!snmp_status()) {
    return;
}

// DES-1210-28ME-B2::ddmPowerUnit.0 = INTEGER: mw(0)
$power_unit = snmp_get_oid($device, 'ddmPowerUnit.0', $mib);

$oids = snmpwalk_cache_oid($device, 'sfpVendorPn', $oids, $mib);

$oids_limit = snmpwalk_cache_twopart_oid($device, 'ddmThresholdMgmtEntry', [], $mib);

foreach ($oids as $index => $entry) {
    $entry['index'] = $index;
    $match          = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%index%']];
    $options        = entity_measured_match_definition($device, $match, $entry);

    $name = $options['port_label'];

    // Temperature
    $descr    = $name . ' Temperature';
    $class    = 'temperature';
    $oid_name = 'ddmTemperature';
    $oid_num  = '.1.3.6.1.4.1.171.10.75.15.2.105.2.1.1.1.2.' . $index;
    $scale    = 1;
    $value    = $entry[$oid_name];
    $limits   = [
      'limit_high'      => $oids_limit[$index]['temperature']['ddmHighAlarm'],
      'limit_low'       => $oids_limit[$index]['temperature']['ddmLowAlarm'],
      'limit_high_warn' => $oids_limit[$index]['temperature']['ddmHighWarning'],
      'limit_low_warn'  => $oids_limit[$index]['temperature']['ddmLowWarning']
    ];
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // Voltage
    $descr    = $name . ' Voltage';
    $class    = 'voltage';
    $oid_name = 'ddmVoltage';
    $oid_num  = '.1.3.6.1.4.1.171.10.75.15.2.105.2.1.1.1.3.' . $index;
    $scale    = 1;
    $value    = $entry[$oid_name];
    $limits   = [
      'limit_high'      => $oids_limit[$index]['voltage']['ddmHighAlarm'],
      'limit_low'       => $oids_limit[$index]['voltage']['ddmLowAlarm'],
      'limit_high_warn' => $oids_limit[$index]['voltage']['ddmHighWarning'],
      'limit_low_warn'  => $oids_limit[$index]['voltage']['ddmLowWarning']
    ];
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // Tx Bias
    $descr    = $name . ' Tx Bias';
    $class    = 'current';
    $oid_name = 'ddmBiasCurrent';
    $oid_num  = '.1.3.6.1.4.1.171.10.75.15.2.105.2.1.1.1.4.' . $index;
    $scale    = 0.001;
    $value    = $entry[$oid_name];
    $limits   = [
      'limit_high'      => $oids_limit[$index]['bias']['ddmHighAlarm'] * $scale,
      'limit_low'       => $oids_limit[$index]['bias']['ddmLowAlarm'] * $scale,
      'limit_high_warn' => $oids_limit[$index]['bias']['ddmHighWarning'] * $scale,
      'limit_low_warn'  => $oids_limit[$index]['bias']['ddmLowWarning'] * $scale
    ];
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // Tx Power
    $descr    = $name . ' Tx Power';
    $class    = $power_unit === 'mw' ? 'power' : 'dbm';
    $oid_name = 'ddmTxPower';
    $oid_num  = '.1.3.6.1.4.1.171.10.75.15.2.105.2.1.1.1.5.' . $index;
    $scale    = $power_unit === 'mw' ? 0.001 : 1;
    $value    = $entry[$oid_name];
    $limits   = [
      'limit_high'      => $oids_limit[$index]['txPower']['ddmHighAlarm'] * $scale,
      'limit_low'       => $oids_limit[$index]['txPower']['ddmLowAlarm'] * $scale,
      'limit_high_warn' => $oids_limit[$index]['txPower']['ddmHighWarning'] * $scale,
      'limit_low_warn'  => $oids_limit[$index]['txPower']['ddmLowWarning'] * $scale
    ];
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // Rx Power
    $descr    = $name . ' Rx Power';
    $class    = $power_unit === 'mw' ? 'power' : 'dbm';
    $oid_name = 'ddmRxPower';
    $oid_num  = '.1.3.6.1.4.1.171.10.75.15.2.105.2.1.1.1.6.' . $index;
    $scale    = $power_unit === 'mw' ? 0.001 : 1;
    $value    = $entry[$oid_name];
    $limits   = [
      'limit_high'      => $oids_limit[$index]['rxPower']['ddmHighAlarm'] * $scale,
      'limit_low'       => $oids_limit[$index]['rxPower']['ddmLowAlarm'] * $scale,
      'limit_high_warn' => $oids_limit[$index]['rxPower']['ddmHighWarning'] * $scale,
      'limit_low_warn'  => $oids_limit[$index]['rxPower']['ddmLowWarning'] * $scale
    ];
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
}

// EOF
