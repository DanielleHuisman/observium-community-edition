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

// Do not poll member id on every poll, use discovery since this id required in discovery stage!
if (!is_numeric($attribs['eqlgrpmemid'])) {
    // eqlMemberName.1.443914937 = hostname-1
    // eqlMemberName.1.1664046123 = hostname-2
    $eqlgrpmembers = snmpwalk_cache_oid($device, 'eqlMemberName', [], 'EQLMEMBER-MIB');

    foreach ($eqlgrpmembers as $index => $entry) {
        // Find member id and name in results
        if (!empty($entry['eqlMemberName']) && strtolower($entry['eqlMemberName']) == strtolower($poll_device['sysName'])) {
            [, $eqlgrpmemid] = explode('.', $index);
            break;
        }
    }

    if (!isset($eqlgrpmemid)) {
        // Fall-back to old method.
        $eqlgrpmemid = snmp_get_oid($device, 'eqliscsiLocalMemberId.0', 'EQLVOLUME-MIB');
    }

    if (is_numeric($eqlgrpmemid) && $eqlgrpmemid != $attribs['eqlgrpmemid']) {
        // Store member id when detected
        set_dev_attrib($device, 'eqlgrpmemid', $eqlgrpmemid);
        $attribs['eqlgrpmemid'] = $eqlgrpmemid;
        print_debug("\neqlgrpmemid: $eqlgrpmemid");
    }
} else {
    $eqlgrpmemid = $attribs['eqlgrpmemid'];
}

// EQLMEMBER-MIB::eqlMemberProductFamily.1.$eqlgrpmemid = STRING: PS6500
// EQLMEMBER-MIB::eqlMemberControllerMajorVersion.1.$eqlgrpmemid = Gauge32: 6
// EQLMEMBER-MIB::eqlMemberControllerMinorVersion.1.$eqlgrpmemid = Gauge32: 0
// EQLMEMBER-MIB::eqlMemberControllerMaintenanceVersion.1.$eqlgrpmemid = Gauge32: 2
// EQLMEMBER-MIB::eqlMemberSerialNumber.1.$eqlgrpmemid = STRING: XXXNNNNNNNXNNNN
// EQLMEMBER-MIB::eqlMemberServiceTag.1.$eqlgrpmemid = STRING: XXXXXXX

$hardware = 'EqualLogic ' . snmp_get_oid($device, 'eqlMemberProductFamily.1.' . $eqlgrpmemid, 'EQLMEMBER-MIB');

$serial = snmp_get_oid($device, 'eqlMemberSerialNumber.1.' . $eqlgrpmemid, 'EQLMEMBER-MIB');
$serial .= ' [' . snmp_get_oid($device, 'eqlMemberServiceTag.1.' . $eqlgrpmemid, 'EQLMEMBER-MIB') . ']';

$eqlmajor = snmp_get_oid($device, 'eqlMemberControllerMajorVersion.1.' . $eqlgrpmemid, 'EQLMEMBER-MIB');
$eqlminor = snmp_get_oid($device, 'eqlMemberControllerMinorVersion.1.' . $eqlgrpmemid, 'EQLMEMBER-MIB');
$eqlmaint = snmp_get_oid($device, 'eqlMemberControllerMaintenanceVersion.1.' . $eqlgrpmemid, 'EQLMEMBER-MIB');
$version  = sprintf('%d.%d.%d', $eqlmajor, $eqlminor, $eqlmaint);

unset($eqlgrpmemid, $eqlgrpmembers, $eqlgrpmem, $eqlmajor, $eqlminor, $eqlmaint, $index);

// EOF
