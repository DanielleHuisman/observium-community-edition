<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

// SYSAPPL-MIB::sysApplInstallPkgManufacturer.9 = STRING: Cisco Systems, Inc.
// SYSAPPL-MIB::sysApplInstallPkgProductName.9 = STRING: Core
// SYSAPPL-MIB::sysApplInstallPkgVersion.9 = STRING: 8.6.12
// SYSAPPL-MIB::sysApplInstallPkgSerialNumber.9 = STRING:
if (is_device_mib($device, 'SYSAPPL-MIB')) {
    foreach (snmpwalk_cache_oid($device, 'sysApplInstallPkgProductName', [], 'SYSAPPL-MIB') as $index => $entry) {
        if ($entry['sysApplInstallPkgProductName'] === 'Core') {
            $data = snmp_get_multi_oid($device, "sysApplInstallPkgVersion.$index sysApplInstallPkgSerialNumber.$index", [], 'SYSAPPL-MIB');
            if (is_valid_param($entry[$index]['sysApplInstallPkgVersion'], 'version')) {
                $version = $entry[$index]['sysApplInstallPkgVersion'];
            }
            if (is_valid_param($entry[$index]['sysApplInstallPkgSerialNumber'], 'serial')) {
                $serial = $entry[$index]['sysApplInstallPkgSerialNumber'];
            }
            break;
        }
    }
}
// EOF
