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

// ASCO-QEM-72EE2::phase_shift_between_normal_and_emergency.0 = INTEGER: 18
// ASCO-QEM-72EE2::normal_frequency.0 = INTEGER: 60
// ASCO-QEM-72EE2::emergency_frequency.0 = INTEGER: 0
// ASCO-QEM-72EE2::main_on_normal.0 = INTEGER: 1
// ASCO-QEM-72EE2::main_on_emergency.0 = INTEGER: 0
// ASCO-QEM-72EE2::auxiliary_on_normal.0 = INTEGER: 0
// ASCO-QEM-72EE2::auxiliary_on_emergency.0 = INTEGER: 0
// ASCO-QEM-72EE2::normal_source_available.0 = INTEGER: 1
// ASCO-QEM-72EE2::emergency_source_available.0 = INTEGER: 0
// ASCO-QEM-72EE2::engine_exerciser_with_load_active.0 = INTEGER: 0
// ASCO-QEM-72EE2::external_f17_is_active.0 = INTEGER: 0
// ASCO-QEM-72EE2::normal_voltage_phase_AB.0 = INTEGER: 245
// ASCO-QEM-72EE2::normal_voltage_phase_BC.0 = INTEGER: 250
// ASCO-QEM-72EE2::normal_voltage_phase_CA.0 = INTEGER: 245
// ASCO-QEM-72EE2::normal_voltage_unbalance.0 = INTEGER: 0

// ASCO-QEM-72EE2::phase_shift_between_normal_and_emergency.0 = INTEGER: 1805
// ASCO-QEM-72EE2::normal_frequency.0 = INTEGER: 6000
// ASCO-QEM-72EE2::emergency_frequency.0 = INTEGER: 0
// ASCO-QEM-72EE2::main_on_normal.0 = INTEGER: 1
// ASCO-QEM-72EE2::main_on_emergency.0 = INTEGER: 0
// ASCO-QEM-72EE2::auxiliary_on_normal.0 = INTEGER: 0
// ASCO-QEM-72EE2::auxiliary_on_emergency.0 = INTEGER: 0
// ASCO-QEM-72EE2::normal_source_available.0 = INTEGER: 1
// ASCO-QEM-72EE2::emergency_source_available.0 = INTEGER: 0
// ASCO-QEM-72EE2::engine_exerciser_with_load_active.0 = INTEGER: 0
// ASCO-QEM-72EE2::external_f17_is_active.0 = INTEGER: 0
// ASCO-QEM-72EE2::normal_voltage_phase_AB.0 = INTEGER: 2074
// ASCO-QEM-72EE2::normal_voltage_phase_BC.0 = INTEGER: 2096
// ASCO-QEM-72EE2::normal_voltage_phase_CA.0 = INTEGER: 2093
// ASCO-QEM-72EE2::normal_voltage_unbalance.0 = INTEGER: 0

if ($oids = snmp_get_multi_oid($device, 'normal_frequency.0 normal_voltage_phase_AB.0 normal_voltage_phase_BC.0 normal_voltage_phase_CA.0', [], 'ASCO-QEM-72EE2')) {
    if (strlen($oids[0]['normal_frequency']) > 2) {
        $scale_frequency = 0.01;
        $scale_voltage   = 0.1;
    } else {
        $scale_frequency = 1;
        $scale_voltage   = 1;
    }

    ## Input frequency
    $oid   = '.1.3.6.1.4.1.7777.1.1.2.0';
    $value = $oids[0]['normal_frequency'];
    discover_sensor_ng($device, 'frequency', $mib, 'normal_frequency', $oid, '0', NULL, 'Input', $scale_frequency, $value);

    $oid   = ".1.3.6.1.4.1.7777.1.1.12.0";
    $value = $oids[0]['normal_voltage_phase_AB'];
    discover_sensor_ng($device, 'voltage', $mib, 'normal_voltage_phase_AB', $oid, '0', NULL, 'Phase AB', $scale_voltage, $value);

    $oid   = ".1.3.6.1.4.1.7777.1.1.13.0";
    $value = $oids[0]['normal_voltage_phase_BC'];
    discover_sensor_ng($device, 'voltage', $mib, 'normal_voltage_phase_BC', $oid, '0', NULL, 'Phase BC', $scale_voltage, $value);

    $oid   = ".1.3.6.1.4.1.7777.1.1.14.0";
    $value = $oids[0]['normal_voltage_phase_CA'];
    discover_sensor_ng($device, 'voltage', $mib, 'normal_voltage_phase_CA', $oid, '0', NULL, 'Phase CA', $scale_voltage, $value);
}

// ASCO-QEM-72EE2::main_on_normal.0 = INTEGER: 1
// ASCO-QEM-72EE2::main_on_emergency.0 = INTEGER: 0
// ASCO-QEM-72EE2::auxiliary_on_normal.0 = INTEGER: 0
// ASCO-QEM-72EE2::auxiliary_on_emergency.0 = INTEGER: 0
// ASCO-QEM-72EE2::normal_source_available.0 = INTEGER: 1
// ASCO-QEM-72EE2::emergency_source_available.0 = INTEGER: 0
if ($oids = snmp_get_multi_oid($device, 'main_on_normal.0 main_on_emergency.0 auxiliary_on_normal.0 auxiliary_on_emergency.0 normal_source_available.0 emergency_source_available.0', [], 'ASCO-QEM-72EE2')) {

    $oid_name = 'main_on_normal';
    $oid_num  = '.1.3.6.1.4.1.7777.1.1.4.0';
    $value    = $oids[0][$oid_name];
    discover_status_ng($device, $mib, $oid_name, $oid_num, '0', 'status-normal', 'Main On Normal', $value, ['entPhysicalClass' => 'power']);

    $oid_name = 'main_on_emergency';
    $oid_num  = '.1.3.6.1.4.1.7777.1.1.5.0';
    $value    = $oids[0][$oid_name];
    discover_status_ng($device, $mib, $oid_name, $oid_num, '0', 'status-emergency', 'Main On Emergency', $value, ['entPhysicalClass' => 'power']);

    $oid_name = 'auxiliary_on_normal';
    $oid_num  = '.1.3.6.1.4.1.7777.1.1.6.0';
    $value    = $oids[0][$oid_name];
    discover_status_ng($device, $mib, $oid_name, $oid_num, '0', 'status-normal', 'Auxiliary On Normal', $value, ['entPhysicalClass' => 'power']);

    $oid_name = 'auxiliary_on_emergency';
    $oid_num  = '.1.3.6.1.4.1.7777.1.1.7.0';
    $value    = $oids[0][$oid_name];
    discover_status_ng($device, $mib, $oid_name, $oid_num, '0', 'status-emergency', 'Auxiliary On Emergency', $value, ['entPhysicalClass' => 'power']);

    $oid_name = 'normal_source_available';
    $oid_num  = '.1.3.6.1.4.1.7777.1.1.8.0';
    $value    = $oids[0][$oid_name];
    discover_status_ng($device, $mib, $oid_name, $oid_num, '0', 'status-normal', 'Normal Source Available', $value, ['entPhysicalClass' => 'power']);

    $oid_name = 'emergency_source_available';
    $oid_num  = '.1.3.6.1.4.1.7777.1.1.9.0';
    $value    = $oids[0][$oid_name];
    discover_status_ng($device, $mib, $oid_name, $oid_num, '0', 'status-emergency', 'Emergency Source Available', $value, ['entPhysicalClass' => 'power']);
}

// EOF
