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

# PowerWalker/BlueWalker UPS (Tested with BlueWalked VFI 2000 LCD (EPPC-MIB) sysDescr.0 = STRING: Network Management Card for UPS
$vendor = 'PowerWalker';
if (str_contains($poll_device['sysDescr'], 'Network Management Card for UPS')) {
    // Eaton:
    // EPPC-MIB::upsEIdentityManufacturer.0 = STRING: EPPC
    // EPPC-MIB::upsEIdentityModel.0 = STRING: ON-LINE
    // EPPC-MIB::upsEIdentityUPSFirmwareVerison.0 = STRING: 06.00
    // EPPC-MIB::upsEIndentityUPSSerialNumber.0 = STRING:
    // EPPC-MIB::upsEIdentityDescription.0 = STRING:
    // EPPC-MIB::upsEIdentityAgentSoftwareVerison.0 = STRING: 3.0.0.2
    // EPPC-MIB::upsEIdentityAttachedDevices.0 = STRING:

    // PowerWalker:
    // EPPC-MIB::upsEIdentityManufacturer.0 = STRING: EPPC
    // EPPC-MIB::upsEIdentityModel.0 = STRING: ON-LINE
    // EPPC-MIB::upsEIdentityUPSFirmwareVerison.0 = STRING: 00.00
    // EPPC-MIB::upsEIndentityUPSSerialNumber.0 = STRING:
    // EPPC-MIB::upsEIdentityDescription.0 = STRING: ups1
    // EPPC-MIB::upsEIdentityAgentSoftwareVerison.0 = STRING: 3.1.0.0
    if ($data = snmp_get_multi_oid($device, 'upsESystemConfigOutputVA.0 upsEIdentityModel.0', [], 'EPPC-MIB')) {
        $hardware = $data[0]['upsEIdentityModel'];
        if ($data[0]['upsESystemConfigOutputVA'] > 0) {
            $hardware .= '(' . $data[0]['upsESystemConfigOutputVA'] . 'VA)';
        }
    }

    if ($data = snmp_get_multi_oid($device, 'upsEIdentityDescription.0 upsEIdentityUPSFirmwareVerison.0 upsEIdentityAgentSoftwareVerison.0', [], 'EPPC-MIB')) {
        $version = $data[0]['upsEIdentityDescription'] . ' UPS: ' .
                   $data[0]['upsEIdentityUPSFirmwareVerison'] . ' Firmware: ' . $data[0]['upsEIdentityAgentSoftwareVerison'];

        if (version_compare($data[0]['upsEIdentityAgentSoftwareVerison'], '3.1.0.0', '<')) {
            // See: https://jira.observium.org/browse/OBS-4369
            $vendor = 'Eaton';
        }
    }

    //if ($data = snmp_get_multi_oid($device, 'upsESystemStatus.0 upsEBatteryTestResult.0', [], 'EPPC-MIB')) {
    //  $features = 'Status: ' . strtoupper($data[0]['upsESystemStatus'] . ' ' . $data[0]['upsEBatteryTestResult']);
    //}
} else {
    $hardware = 'EPPC - Unknown NMC Card';
}

// EOF
