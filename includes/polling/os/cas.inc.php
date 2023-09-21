<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// SNMPv2-MIB::sysDescr.0 Blue Coat S400-A3, Version: 1.2.4.4, Release Id: 157593
// BLUECOAT-CAS-MIB::casInstalledFirmwareVersion.0 = STRING: 1.2.4.4(157593)
// BLUECOAT-CAS-MIB::casAvStatusIndex.0 = Counter32: 0
// BLUECOAT-CAS-MIB::casAvVendorName.0 = STRING: Kaspersky Labs
// BLUECOAT-CAS-MIB::casAvEngineVersion.0 = STRING: 8.2.5.17
// BLUECOAT-CAS-MIB::casAvPatternVersion.0 = STRING: 160119 210400.6788010

$av = snmp_get($device, 'casAvVendorName.0', '-Osqv', 'BLUECOAT-CAS-MIB');
if (!empty($av)) {
    $eng      = snmp_get($device, 'casAvEngineVersion.0', '-Osqv', 'BLUECOAT-CAS-MIB');
    $pat      = snmp_get($device, 'casAvPatternVersion.0', '-Osqv', 'BLUECOAT-CAS-MIB');
    $features = "$av-$eng ($pat)";
}

if (preg_match('/Blue Coat (?<hw>\S+), Version: (?<version>[\d\.\-]+)/', $poll_device['sysDescr'], $matches)) {
    $hardware = $matches['hw'];
    $version  = $matches['version'];
} else {
    [$hardware] = explode(',', $poll_device['sysDescr']);
    $hardware = trim($hardware);
    $version  = snmp_get($device, 'casInstalledFirmwareVersion.0', '-Osqv', 'BLUECOAT-CAS-MIB');
}

// EOF
