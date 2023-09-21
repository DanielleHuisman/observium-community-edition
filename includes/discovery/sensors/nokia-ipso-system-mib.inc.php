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

// NOKIA-IPSO-SYSTEM-MIB::ipsoChassisTemperature.0 = INTEGER: normal(1)
$value = snmp_get($device, 'ipsoChassisTemperature.0', '-Oqv', 'NOKIA-IPSO-SYSTEM-MIB');
if ($value !== '') {
    $oid   = '.1.3.6.1.4.1.94.1.21.1.1.5.0';
    $descr = 'Chassis Temperature';

    discover_status($device, $oid, 'ipsoChassisTemperature.0', 'ipso-temperature-state', $descr, $value, ['entPhysicalClass' => 'temperature']);
}

// NOKIA-IPSO-SYSTEM-MIB::ipsoFanOperStatus.1 = INTEGER: running(1)
$data       = snmpwalk_cache_oid($device, 'ipsoFanTable', [], 'NOKIA-IPSO-SYSTEM-MIB');
$data_multi = safe_count($data) > 1; // Set TRUE if more than one index
foreach ($data as $index => $entry) {
    $oid   = '.1.3.6.1.4.1.94.1.21.1.2.1.1.2.' . $index;
    $descr = 'Chassis Fan';
    if ($data_multi) {
        $descr .= " $index";
    }
    $value = $entry['ipsoFanOperStatus'];

    discover_status($device, $oid, "ipsoFanOperStatus.$index", 'ipso-sensor-state', $descr, $value, ['entPhysicalClass' => 'fan']);
}

// NOKIA-IPSO-SYSTEM-MIB::ipsoPowerSupplyOverTemperature.1 = INTEGER: normal(1)
// NOKIA-IPSO-SYSTEM-MIB::ipsoPowerSupplyOperStatus.1 = INTEGER: running(1)
$data       = snmpwalk_cache_oid($device, 'ipsoPowerSupplyTable', [], 'NOKIA-IPSO-SYSTEM-MIB');
$data_multi = safe_count($data) > 1; // Set TRUE if more than one index
foreach ($data as $index => $entry) {
    $oid   = '.1.3.6.1.4.1.94.1.21.1.3.1.1.2.' . $index;
    $descr = 'Power Supply Temperature';
    if ($data_multi) {
        $descr .= " $index";
    }
    $value = $entry['ipsoPowerSupplyOverTemperature'];

    discover_status($device, $oid, "ipsoPowerSupplyOverTemperature.$index", 'ipso-temperature-state', $descr, $value, ['entPhysicalClass' => 'temperature']);

    $oid   = '.1.3.6.1.4.1.94.1.21.1.3.1.1.3.' . $index;
    $descr = 'Power Supply';
    if ($data_multi) {
        $descr .= " $index";
    }
    $value = $entry['ipsoPowerSupplyOperStatus'];

    discover_status($device, $oid, "ipsoPowerSupplyOperStatus.$index", 'ipso-sensor-state', $descr, $value, ['entPhysicalClass' => 'other']);
}

// EOF
