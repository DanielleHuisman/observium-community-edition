<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Currently not possible convert to definitions because type detection is hard, based on descriptions
// SAME code in FORTINET-FORTIGATE-MIB

// ATEN-IPMI-MIB::sensorNumber.3 = INTEGER: 10
// ATEN-IPMI-MIB::sensorReading.3 = STRING: "37.000"
// ATEN-IPMI-MIB::sensorPositiveHysteresis.3 = INTEGER: 0
// ATEN-IPMI-MIB::sensorNegativeHysteresis.3 = INTEGER: 0
// ATEN-IPMI-MIB::lncThreshold.3 = STRING: "10.000"
// ATEN-IPMI-MIB::lcThreshold.3 = STRING: "5.000"
// ATEN-IPMI-MIB::lnrThreshold.3 = STRING: "5.000"
// ATEN-IPMI-MIB::uncThreshold.3 = STRING: "85.000"
// ATEN-IPMI-MIB::ucThreshold.3 = STRING: "90.000"
// ATEN-IPMI-MIB::unrThreshold.3 = STRING: "105.000"
// ATEN-IPMI-MIB::eventAssertionEnable.3 = INTEGER: 2560
// ATEN-IPMI-MIB::eventDeassertionEnable.3 = INTEGER: 2560
// ATEN-IPMI-MIB::sensorIDString.3 = STRING: "PCH Temp"

// ATEN-IPMI-MIB::sensorNumber.75 = INTEGER: 0
// ATEN-IPMI-MIB::sensorReading.75 = STRING: "0.000"
// ATEN-IPMI-MIB::sensorPositiveHysteresis.75 = INTEGER: 0
// ATEN-IPMI-MIB::sensorNegativeHysteresis.75 = INTEGER: 0
// ATEN-IPMI-MIB::lncThreshold.75 = STRING: "0.000"
// ATEN-IPMI-MIB::lcThreshold.75 = STRING: "0.000"
// ATEN-IPMI-MIB::lnrThreshold.75 = STRING: "0.000"
// ATEN-IPMI-MIB::uncThreshold.75 = STRING: "0.000"
// ATEN-IPMI-MIB::ucThreshold.75 = STRING: "0.000"
// ATEN-IPMI-MIB::unrThreshold.75 = STRING: "0.000"
// ATEN-IPMI-MIB::eventAssertionEnable.75 = INTEGER: 0
// ATEN-IPMI-MIB::eventDeassertionEnable.75 = INTEGER: 0
// ATEN-IPMI-MIB::sensorIDString.75 = ""

// ATEN-IPMI-MIB::sensorIDString.1 = STRING: "CPU Temp"
// ATEN-IPMI-MIB::sensorIDString.2 = STRING: "PCH Temp"
// ATEN-IPMI-MIB::sensorIDString.3 = STRING: "System Temp"
// ATEN-IPMI-MIB::sensorIDString.4 = STRING: "Peripheral Temp"
// ATEN-IPMI-MIB::sensorIDString.5 = STRING: "MB_10G Temp"
// ATEN-IPMI-MIB::sensorIDString.6 = STRING: "VRMCpu Temp"
// ATEN-IPMI-MIB::sensorIDString.7 = STRING: "VRMABC Temp"
// ATEN-IPMI-MIB::sensorIDString.8 = STRING: "VRMDEF Temp"
// ATEN-IPMI-MIB::sensorIDString.9 = STRING: "M2NVMeSSD Temp"
// ATEN-IPMI-MIB::sensorIDString.10 = STRING: "FAN1"
// ATEN-IPMI-MIB::sensorIDString.11 = STRING: "FAN2"
// ATEN-IPMI-MIB::sensorIDString.12 = STRING: "FAN3"
// ATEN-IPMI-MIB::sensorIDString.13 = STRING: "FAN4"
// ATEN-IPMI-MIB::sensorIDString.14 = STRING: "FAN5"
// ATEN-IPMI-MIB::sensorIDString.15 = STRING: "FAN6"
// ATEN-IPMI-MIB::sensorIDString.16 = STRING: "FAN7"
// ATEN-IPMI-MIB::sensorIDString.17 = STRING: "DIMMA1 Temp"
// ATEN-IPMI-MIB::sensorIDString.18 = STRING: "DIMMB1 Temp"
// ATEN-IPMI-MIB::sensorIDString.19 = STRING: "DIMMC1 Temp"
// ATEN-IPMI-MIB::sensorIDString.20 = STRING: "DIMMD1 Temp"
// ATEN-IPMI-MIB::sensorIDString.21 = STRING: "DIMME1 Temp"
// ATEN-IPMI-MIB::sensorIDString.22 = STRING: "DIMMF1 Temp"
// ATEN-IPMI-MIB::sensorIDString.23 = STRING: "12V"
// ATEN-IPMI-MIB::sensorIDString.24 = STRING: "5VCC"
// ATEN-IPMI-MIB::sensorIDString.25 = STRING: "3.3VCC"
// ATEN-IPMI-MIB::sensorIDString.26 = STRING: "VBAT"
// ATEN-IPMI-MIB::sensorIDString.27 = STRING: "Vcpu"
// ATEN-IPMI-MIB::sensorIDString.28 = STRING: "VDimmABC"
// ATEN-IPMI-MIB::sensorIDString.29 = STRING: "VDimmDEF"
// ATEN-IPMI-MIB::sensorIDString.30 = STRING: "5VSB"
// ATEN-IPMI-MIB::sensorIDString.31 = STRING: "3.3VSB"
// ATEN-IPMI-MIB::sensorIDString.32 = STRING: "P1V8_PCH"
// ATEN-IPMI-MIB::sensorIDString.33 = STRING: "PVNN_PCH"
// ATEN-IPMI-MIB::sensorIDString.34 = STRING: "P1V05_PCH"
// ATEN-IPMI-MIB::sensorIDString.35 = STRING: "Chassis Intru"
// ATEN-IPMI-MIB::sensorIDString.36 = STRING: "PS1 Status"
// ATEN-IPMI-MIB::sensorIDString.37 = STRING: "PS2 Status"
// ATEN-IPMI-MIB::sensorIDString.38 = STRING: "AOC_SAS Temp"
// ATEN-IPMI-MIB::sensorIDString.39 = STRING: "HDD Temp"
// ATEN-IPMI-MIB::sensorIDString.40 = STRING: "HDD Status"
$oids = snmpwalk_cache_oid($device, "sensorTable", [], "ATEN-IPMI-MIB", NULL, OBS_SNMP_ALL_ASCII);
print_debug_vars($oids);

$limits = ['limit_low'  => 'lcThreshold', 'limit_low_warn' => 'lncThreshold',
           'limit_high' => 'ucThreshold', 'limit_high_warn' => 'uncThreshold'];

foreach ($oids as $index => $entry) {
    // skip empty sensors
    if ($entry['sensorNumber'] == 0 || $entry['sensorIDString'] === '') {
        continue;
    }

    $descr = trim($entry['sensorIDString'], ' .');

    $oid_name = 'sensorReading';
    $oid_num  = '.1.3.6.1.4.1.21317.1.3.1.2.' . $index;
    $scale    = 1;
    $value    = $entry[$oid_name];

    // Limits
    //              [lncThreshold]             => string(6) "10.000"
    //              [lcThreshold]              => string(5) "5.000"
    //              [lnrThreshold]             => string(5) "5.000"
    //              [uncThreshold]             => string(7) "100.000"
    //              [ucThreshold]              => string(7) "105.000"
    //              [unrThreshold]             => string(7) "110.000"

    //              [lncThreshold]             => string(7) "700.000"
    //              [lcThreshold]              => string(7) "500.000"
    //              [lnrThreshold]             => string(7) "300.000"
    //              [uncThreshold]             => string(9) "25300.000"
    //              [ucThreshold]              => string(9) "25400.000"
    //              [unrThreshold]             => string(9) "25500.000"
    $options = [];
    foreach ($limits as $limit => $limit_oid) {
        if (isset($entry[$limit_oid]) && $entry[$limit_oid] != 0) {
            $options[$limit] = $entry[$limit_oid];
        }
    }
    $limits_count = count($options);

    // Detect class based on descr anv value (this is derp, table not have other data for detect class
    if (str_iends($descr, [' Temp', ' Temperature'])) {
        if ($value == 0 && !$limits_count) {
            continue;
        }
        $descr = str_replace([' Temp', ' Temperature'], '', $descr);
        $class = 'temperature';
    } elseif (str_icontains_array($descr, 'Fan')) {
        if ($value == 0 && !$limits_count) {
            continue;
        }
        if ($value > 100 || $value == 0) {
            $class = 'fanspeed';
        } elseif ($value > 0) {
            $class   = 'load';
            $options = [];
        }
    } elseif (str_iends($descr, [' Curr', ' Current', ' IIN' ])) {
        if ($value == 0) {
            continue;
        }
        $descr = str_replace([' Curr', ' Current' ], '', $descr);
        $class = 'current';
    } elseif (str_iends($descr, [' Pwr', ' Power', ' POUT' ])) {
        if ($value == 0) {
            continue;
        }
        $descr = str_replace([' Pwr', ' Power' ], '', $descr);
        $class = 'power';
    } elseif (preg_match('/\d+V(SB|DD)?\d*$/', $descr) || preg_match('/P\d+V\d+/', $descr) || preg_match('/^\d+(\.\d+)?V/', $descr) ||
              str_icontains_array($descr, [ 'VCC', 'VTT', 'VDD', 'VDQ', 'VBAT', 'VSA', 'Vcore', 'VIN', 'VOUT', 'Vbus', 'Vsht', 'VDimm', 'Vcpu', 'PVNN', 'SOC', 'VMEM' ])) {
        if ($value == 0) {
            continue;
        }
        $class = 'voltage';
    } elseif (str_icontains_array($descr, ['Status', 'Intru'])) {
        $physical_class = str_istarts($descr, 'PS') ? 'powersupply' : 'other';
        $options        = ['entPhysicalClass' => $physical_class];

        // if (str_starts($value, '0x')) {
        //   $options['status_unit'] = 'hex';
        // }
        $type = str_icontains_array($descr, 'Intru') ? 'aten-state-invert' : 'aten-state';
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, $options);
        continue;
    } else {
        // FIXME, not always?
        if ($value == 0 && !$limits_count) {
            continue;
        }
        $class = 'temperature';
    }

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
}

// EOF
