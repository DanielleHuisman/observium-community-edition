<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Keep this for some OSes, who not include UPS-MIB by default
if (!is_device_mib($device, 'UPS-MIB')) {
    return;
}

if (empty($hardware)) {
    //echo "DEVEL UPS\n";
    $hardware = snmp_get_oid($device, 'upsIdentModel.0', 'UPS-MIB');
}
if (empty($version)) {
    //echo "DEVEL UPS\n";
    $version = snmp_get_oid($device, 'upsIdentUPSSoftwareVersion.0', 'UPS-MIB');
    if (empty($version)) {
        $version = snmp_get_oid($device, 'upsIdentAgentSoftwareVersion.0', 'UPS-MIB');
    }
}

// Better to use vendor from OS definition
if ($device['os'] === 'generic-ups' && empty($vendor)) {
    $vendor = snmp_get_oid($device, 'upsIdentManufacturer.0', 'UPS-MIB');
}

// EOF
