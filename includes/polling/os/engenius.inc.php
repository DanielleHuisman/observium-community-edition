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

///////////// Senao Access Points (tested with ECB-9500)

// Yes, that's the Kenel version.
$kenelversion = snmp_get($device, 'entKenelVersion.0', '-OQv', 'SENAO-ENTERPRISE-INDOOR-AP-CB-MIB');

if ($kenelversion) {
    // Only fetch app version when we found a Kenel Version.
    $appversion = snmp_get($device, 'entAppVersion.0', '-OQv', 'SENAO-ENTERPRISE-INDOOR-AP-CB-MIB');
    $version    = "Kernel $kenelversion / Apps $appversion";
}

$hwversion = trim(snmp_get($device, 'entHwVersion.0', '-OQv', 'SENAO-ENTERPRISE-INDOOR-AP-CB-MIB'), '" .');

// There doesn't seem to be a real hardware identification.. sysName will have to do?
// On Engenius APs this is changeable in the system properties!
$hardware = str_replace('EnGenius ', '', $poll_device['sysName']) . ($hwversion == '' ? '' : ' v' . $hwversion);
if ($hardware[0] != 'E') {
    $hardware = '';
} // If the user has changed sysName, don't use it as hardware. Silly check, will work in 99% of cases?

// Operational mode
$mode = snmp_get($device, 'entSysMode.0', '-OQv', 'SENAO-ENTERPRISE-INDOOR-AP-CB-MIB');
switch ($mode) {
    case 'ap-router':
        $features = 'Router mode';
        break;
    case 'repeater':
        $features = 'Universal repeater mode';
        break;
    case 'ap-bridge':
        $features = 'Access Point mode';
        break;
    case 'client-bridge':
        $features = 'Client Bridge mode';
        break;
    case 'client-router':
        $features = 'Client router mode';
        break;
    case 'wds-bridge':
        $features = 'WDS Bridge mode';
        break;
    default:
        $features = '';
        break;
}

///////////// Engenius Access Points (tested with ECB-350)

if ($version == '') {
    $version = snmp_get($device, 'modelName.0', '-OQv', 'ENGENIUS-PRIVATE-MIB');
}

// EOF
