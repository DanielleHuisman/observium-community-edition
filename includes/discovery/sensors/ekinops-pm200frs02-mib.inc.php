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

// EKINOPS-Pm200frs02-MIB::pm200frs02Rinvsfp.0 = STRING: Q1-Client1(PORT_Number 1   )
// NOT EQUIPPED

// EKINOPS-Pm200frs02-MIB::pm200frs02Rinvsfp.1 = STRING: Q2-Client2(PXF-QTS-100G-10G)
// Component          : QSFP28
// Vendor             : FINISAR CORP
// Part Number        : FCBN425QM1C01
// Revision Level     : A0
// Serial Number      : W3DA586
// Date Code (yymmdd) : 200326
// Connector Type     : No Separable Connector
// Wavelength (in nm) : 0,0nm

// EKINOPS-Pm200frs02-MIB::pm200frs02Mesrtrscv1Temp.0 = Gauge32: 0
// EKINOPS-Pm200frs02-MIB::pm200frs02Mesrtrscv2Temp.0 = Gauge32: 10680
// EKINOPS-Pm200frs02-MIB::pm200frs02Mesrtrscv1PowerSupply.0 = Gauge32: 0
// EKINOPS-Pm200frs02-MIB::pm200frs02Mesrtrscv2PowerSupply.0 = Gauge32: 32613

// Q1
$oid_descr = snmp_get_oid($device, 'pm200frs02Rinvsfp.0', 'EKINOPS-Pm200frs02-MIB');
if ($oid_descr && !str_contains($oid_descr, 'NOT EQUIPPED')) {
    $index = '0';

    [$name,] = explode("\n", $oid_descr);
    [, $ifAlias] = explode("(", $name);
    $ifAlias = trim($ifAlias, " )");

    $match   = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifAlias', 'match' => $ifAlias]];
    $options = entity_measured_match_definition($device, $match);
    print_debug_vars($options);

    $descr    = "Client Trscv Temperature ($ifAlias)";
    $class    = 'temperature';
    $oid_name = 'pm200frs02Mesrtrscv1Temp';
    $oid_num  = '.1.3.6.1.4.1.20044.90.3.1.252.' . $index;
    $scale    = 0.00390625; // value/256
    $value    = snmp_get_oid($device, $oid_name . '.' . $index, 'EKINOPS-Pm200frs02-MIB');
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    $descr    = "Client Trscv Voltage ($ifAlias)";
    $class    = 'voltage';
    $oid_name = 'pm200frs02Mesrtrscv1PowerSupply';
    $oid_num  = '.1.3.6.1.4.1.20044.90.3.1.254.' . $index;
    $scale    = 0.0001; // value/10000
    $value    = snmp_get_oid($device, $oid_name . '.' . $index, 'EKINOPS-Pm200frs02-MIB');
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
}

// Q2
$oid_descr = snmp_get_oid($device, 'pm200frs02Rinvsfp.1', 'EKINOPS-Pm200frs02-MIB');
if ($oid_descr && !str_contains($oid_descr, 'NOT EQUIPPED')) {
    $index = '0';

    [$name,] = explode("\n", $oid_descr);
    [, $ifAlias] = explode("(", $name);
    $ifAlias = trim($ifAlias, " )");

    $match   = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifAlias', 'match' => $ifAlias]];
    $options = entity_measured_match_definition($device, $match);
    print_debug_vars($options);

    $descr    = "Client Trscv Temperature ($ifAlias)";
    $class    = 'temperature';
    $oid_name = 'pm200frs02Mesrtrscv2Temp';
    $oid_num  = '.1.3.6.1.4.1.20044.90.3.1.253.' . $index;
    $scale    = 0.00390625; // value/256
    $value    = snmp_get_oid($device, $oid_name . '.' . $index, 'EKINOPS-Pm200frs02-MIB');
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

    $descr    = "Client Trscv Voltage ($ifAlias)";
    $class    = 'voltage';
    $oid_name = 'pm200frs02Mesrtrscv2PowerSupply';
    $oid_num  = '.1.3.6.1.4.1.20044.90.3.1.255.' . $index;
    $scale    = 0.0001; // value/10000
    $value    = snmp_get_oid($device, $oid_name . '.' . $index, 'EKINOPS-Pm200frs02-MIB');
    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
}

// EOF