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

/*
in mw (config ddm power_unit mw)
Command: show ddm ports 1 status
 Port    Temperature   Voltage    Bias Current   TX Power     RX Power
        (in Celsius)     (V)         (mA)         (mW)         (mW)
------- ------------- ---------- -------------- ---------- ------------
  1        45.2305      3.2733       31.584       0.0578       0.0929

Command: show ddm ports 1 configuration
Port:            1
--------------------------------------------
DDM state : Enabled
Shutdown  : None
Threshold       Temperature    Voltage   Bias-Current   TX-Power  RX-Power
------------ --------------- --------- ------------- ----------- ----------
High Alarm        110.0000      3.6000      80.0000     0.3981      0.6310
Low Alarm         -45.0000      3.0000      2.0000      0.0251      0.0050
High Warning      95.0000       3.5000      70.0000     0.3162      0.5012
Low Warning       -42.0000      3.0500      3.0000      0.0316      0.0063

DDM-MGMT-MIB::swDdmTrapState.0 = INTEGER: disabled(2)
DDM-MGMT-MIB::swDdmLogState.0 = INTEGER: enabled(1)
DDM-MGMT-MIB::swDdmPowerUnit.0 = INTEGER: mw(1)
DDM-MGMT-MIB::swDdmPort.1 = INTEGER: 1
DDM-MGMT-MIB::swDdmTemperature.1 = STRING: 45.2305
DDM-MGMT-MIB::swDdmVoltage.1 = STRING: 3.2477
DDM-MGMT-MIB::swDdmBiasCurrent.1 = STRING: 31.584
DDM-MGMT-MIB::swDdmTxPower.1 = STRING: 0.0583
DDM-MGMT-MIB::swDdmRxPower.1 = STRING: 0.0932
DDM-MGMT-MIB::swDdmPortState.1 = INTEGER: enabled(1)
DDM-MGMT-MIB::swDdmPortShutdown.1 = INTEGER: none(3)
*/

/*
in dbm (config ddm power_unit dbm)
Command: show ddm ports 1 status
 Port    Temperature   Voltage    Bias Current   TX Power     RX Power
        (in Celsius)     (V)         (mA)         (dBm)        (dBm)
------- ------------- ---------- -------------- ---------- ------------
  1        45.7148      3.2477       31.584       -12.3807     -10.2919

Command: show ddm ports 1 configuration
Port:            1
--------------------------------------------
DDM state : Enabled
Shutdown  : None
Threshold       Temperature    Voltage   Bias-Current   TX-Power  RX-Power
------------ --------------- --------- ------------- ----------- ----------
High Alarm        110.0000      3.6000      80.0000     -4.0001     -1.9997
Low Alarm         -45.0000      3.0000      2.0000      -16.0033    -23.0103
High Warning      95.0000       3.5000      70.0000     -5.0004     -2.9999
Low Warning       -42.0000      3.0500      3.0000      -15.0031    -22.0066

DDM-MGMT-MIB::swDdmTrapState.0 = INTEGER: disabled(2)
DDM-MGMT-MIB::swDdmLogState.0 = INTEGER: enabled(1)
DDM-MGMT-MIB::swDdmPowerUnit.0 = INTEGER: dbm(2)
DDM-MGMT-MIB::swDdmPort.1 = INTEGER: 1
DDM-MGMT-MIB::swDdmTemperature.1 = STRING: 45.2305
DDM-MGMT-MIB::swDdmVoltage.1 = STRING: 3.2354
DDM-MGMT-MIB::swDdmBiasCurrent.1 = STRING: 31.584
DDM-MGMT-MIB::swDdmTxPower.1 = STRING: -12.3807
DDM-MGMT-MIB::swDdmRxPower.1 = STRING: -10.2826
DDM-MGMT-MIB::swDdmPortState.1 = INTEGER: enabled(1)
DDM-MGMT-MIB::swDdmPortShutdown.1 = INTEGER: none(3)
*/

$oids = snmpwalk_cache_oid($device, 'swDdmStatusTable', [], $mib);
print_debug_vars($oids);

if (!snmp_status()) {
    return;
}

// oid exists on D-Link
//// -- DGS-3627G 1A1G with firmware 3.03.B04
//// -- DES-3200-28/C1 with firmware 4.46.B008
//// (command "config ddm power_unit dbm" supported)
// oid doesn't exists on D-Link
//// -- DGS-3120-24SC B1 with firmware 3.12.R510
//// -- DES-3526 A4 with firmware 6.20.B20/B21/B30
//// -- DES-3550 A4G with firmware 6.20.B20
//// (command "config ddm power_unit dbm" unsupported) - tx/rx power always in mw

//DDM-MGMT-MIB::swDdmPowerUnit.0 = INTEGER: dbm(2)
$power_unit = snmp_get_oid($device, 'swDdmPowerUnit.0', $mib);

if (!snmp_status()) {
    $power_unit = 'mw';
}

$oids_limit = snmpwalk_cache_twopart_oid($device, 'swDdmThresholdMgmtEntry', [], $mib);
print_debug_vars($oids_limit);

foreach ($oids as $index => $entry) {
    $entry['index'] = $index;
    $match          = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%index%']];
    $options        = entity_measured_match_definition($device, $match, $entry);

    $name = $options['port_label'];

    // Temperature
    $descr    = $name . ' Temperature';
    $class    = 'temperature';
    $oid_name = 'swDdmTemperature';
    $oid_num  = '.1.3.6.1.4.1.171.12.72.2.1.1.1.2.' . $index;
    $scale    = 1;
    $value    = $entry[$oid_name];

    $limits      = [];
    $limit_type  = 'temperature';
    $limits_oids = ['limit_high' => 'swDdmHighAlarm', 'limit_high_warn' => 'swDdmHighWarning',
                    'limit_low'  => 'swDdmLowAlarm', 'limit_low_warn' => 'swDdmLowWarning'];
    foreach ($limits_oids as $limit => $limit_oid) {
        // Prevent php8 fatal errors
        if (!is_numeric($oids_limit[$index][$limit_type][$limit_oid])) {
            continue;
        }

        $limits[$limit] = $oids_limit[$index][$limit_type][$limit_oid] * $scale;
    }

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // Voltage
    $descr    = $name . ' Voltage';
    $class    = 'voltage';
    $oid_name = 'swDdmVoltage';
    $oid_num  = '.1.3.6.1.4.1.171.12.72.2.1.1.1.3.' . $index;
    $scale    = 1;
    $value    = $entry[$oid_name];

    $limits      = [];
    $limit_type  = 'voltage';
    $limits_oids = ['limit_high' => 'swDdmHighAlarm', 'limit_high_warn' => 'swDdmHighWarning',
                    'limit_low'  => 'swDdmLowAlarm', 'limit_low_warn' => 'swDdmLowWarning'];
    foreach ($limits_oids as $limit => $limit_oid) {
        // Prevent php8 fatal errors
        if (!is_numeric($oids_limit[$index][$limit_type][$limit_oid])) {
            continue;
        }

        $limits[$limit] = $oids_limit[$index][$limit_type][$limit_oid] * $scale;
    }

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // Tx Bias
    $descr    = $name . ' Tx Bias';
    $class    = 'current';
    $oid_name = 'swDdmBiasCurrent';
    $oid_num  = '.1.3.6.1.4.1.171.12.72.2.1.1.1.4.' . $index;
    $scale    = 0.001;
    $value    = $entry[$oid_name];

    $limits      = [];
    $limit_type  = 'bias';
    $limits_oids = ['limit_high' => 'swDdmHighAlarm', 'limit_high_warn' => 'swDdmHighWarning',
                    'limit_low'  => 'swDdmLowAlarm', 'limit_low_warn' => 'swDdmLowWarning'];
    foreach ($limits_oids as $limit => $limit_oid) {
        // Prevent php8 fatal errors
        if (!is_numeric($oids_limit[$index][$limit_type][$limit_oid])) {
            continue;
        }

        $limits[$limit] = $oids_limit[$index][$limit_type][$limit_oid] * $scale;
    }

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // Tx Power
    $descr    = $name . ' Tx Power';
    $class    = $power_unit === 'mw' ? 'power' : 'dbm';
    $oid_name = 'swDdmTxPower';
    $oid_num  = '.1.3.6.1.4.1.171.12.72.2.1.1.1.5.' . $index;
    $scale    = $power_unit === 'mw' ? 0.001 : 1;
    $value    = $entry[$oid_name];

    $limits      = [];
    $limit_type  = 'txPower';
    $limits_oids = ['limit_high' => 'swDdmHighAlarm', 'limit_high_warn' => 'swDdmHighWarning',
                    'limit_low'  => 'swDdmLowAlarm', 'limit_low_warn' => 'swDdmLowWarning'];
    foreach ($limits_oids as $limit => $limit_oid) {
        // Prevent php8 fatal errors
        if (!is_numeric($oids_limit[$index][$limit_type][$limit_oid])) {
            continue;
        }

        $limits[$limit] = $oids_limit[$index][$limit_type][$limit_oid] * $scale;
    }

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

    // Rx Power
    $descr    = $name . ' Rx Power';
    $class    = $power_unit === 'mw' ? 'power' : 'dbm';
    $oid_name = 'swDdmRxPower';
    $oid_num  = '.1.3.6.1.4.1.171.12.72.2.1.1.1.6.' . $index;
    $scale    = $power_unit === 'mw' ? 0.001 : 1;
    $value    = $entry[$oid_name];

    $limits      = [];
    $limit_type  = 'rxPower';
    $limits_oids = ['limit_high' => 'swDdmHighAlarm', 'limit_high_warn' => 'swDdmHighWarning',
                    'limit_low'  => 'swDdmLowAlarm', 'limit_low_warn' => 'swDdmLowWarning'];
    foreach ($limits_oids as $limit => $limit_oid) {
        // Prevent php8 fatal errors
        if (!is_numeric($oids_limit[$index][$limit_type][$limit_oid])) {
            continue;
        }

        $limits[$limit] = $oids_limit[$index][$limit_type][$limit_oid] * $scale;
    }

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
}

// EOF
