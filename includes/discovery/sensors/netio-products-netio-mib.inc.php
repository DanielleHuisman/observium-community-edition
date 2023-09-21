<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     definitions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */


// FIXME - this is here because no ability to handle this from definitions yet.

// Input Voltage
$oid   = ".1.3.6.1.4.1.47952.1.2.1";
$descr = "Input";

$value = snmp_get_oid($device, $oid, $mib);
if (is_numeric($value)) {
    discover_sensor('voltage', $device, $oid, "netioVoltage", $mib, $descr, 0.001, $value);
}

// Input Frequency
$oid   = ".1.3.6.1.4.1.47952.1.2.2";
$descr = "Input";

$value = snmp_get_oid($device, $oid, $mib);
if (is_numeric($value)) {
    discover_sensor('frequency', $device, $oid, "netioFrequency", $mib, $descr, 0.001, $value);
}

// Input Current
$oid   = ".1.3.6.1.4.1.47952.1.2.3";
$descr = "Input";

$value = snmp_get_oid($device, $oid, $mib);
if (is_numeric($value)) {
    discover_sensor('current', $device, $oid, "netioTotalCurrent", $mib, $descr, 0.001, $value);
}

// Input Current
$oid   = ".1.3.6.1.4.1.47952.1.2.4";
$descr = "Input";

$value = snmp_get_oid($device, $oid, $mib);
if (is_numeric($value)) {
    discover_sensor('powerfactor', $device, $oid, "netioOverallPowerFactor", $mib, $descr, 0.001, $value);
}

// Input Current
$oid   = ".1.3.6.1.4.1.47952.1.2.5";
$descr = "Input";

$value = snmp_get_oid($device, $oid, $mib);
if (is_numeric($value)) {
    discover_sensor('power', $device, $oid, "netioTotalLoad", $mib, $descr, 1, $value);
}


