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


// hardware platform
if (!strlen($hardware)) {
    if ($poll_device['sysObjectID'] == '.1.3.6.1.4.1.1916.2.93' || str_contains_array($poll_device['sysDescr'], 'Stack')) {
        // Stacked devices
        //EXTREME-STACKING-MIB::extremeStackMemberType.1 = OID: EXTREME-BASE-MIB::summitX460G2-48t-10G4
        //EXTREME-STACKING-MIB::extremeStackMemberType.1 = OID: EXTREME-BASE-MIB::x690-48x-4q-2c
        $hardware = snmp_get_oid($device, 'extremeStackMemberType.1', 'EXTREME-STACKING-MIB');
    } else {
        $hardware = snmp_translate($poll_device['sysObjectID'], 'EXTREME-BASE-MIB');
    }
    $hardware = rewrite_extreme_hardware($hardware);
}

// version
if (!strlen($version)) {
    $data = snmp_get_multi_oid($device, 'extremeImageBooted.0 extremePrimarySoftwareRev.0 extremeSecondarySoftwareRev.0', [], 'EXTREME-SYSTEM-MIB');

    // determine running firmware version
    switch ($data[0]['extremeImageBooted']) {
        case 'primary':
            $version = $data[0]['extremePrimarySoftwareRev'];
            break;
        case 'secondary':
            $version = $data[0]['extremeSecondarySoftwareRev'];
            break;
        default:
            //$version = 'UNKNOWN';
    }
}

// features
$data = snmp_get_multi_oid($device, 'extremeImageSshCapability.cur extremeImageUAACapability.cur', [], 'EXTREME-SYSTEM-MIB');

$features = '';
if ($data['cur']['extremeImageSshCapability'] != 'unknown' && $data['cur']['extremeImageSshCapability'] != '') {
    $features .= $data['cur']['extremeImageSshCapability'];
}

if ($data['cur']['extremeImageUAACapability'] != 'unknown' && $data['cur']['extremeImageUAACapability'] != '') {
    $features .= ' ' . $data['cur']['extremeImageUAACapability'];
}

// EOF
