<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

$query = 'SELECT `port_id`, `ifIndex`, `ifDescr` FROM `ports` WHERE `device_id` = ? AND `deleted` = ?';
$ports_ifDescr = [];
foreach (dbFetchRows($query, [$device['device_id'], 0]) as $entry) {
    $ports_ifDescr[$entry['ifDescr']] = $entry;
}
//print_vars($ports_ifDescr);

// NEWTEC-IP-MIB::ntcIpMgmtInterfaceIpAddress.mgmt1 = STRING: 192.168.202.2/24
// NEWTEC-IP-MIB::ntcIpMgmtInterfaceIpAddress.mgmt2 = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcIpMgmtInterfaceIpAddress.mgmt = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcIpMgmtInterfaceState.mgmt1 = INTEGER: on(1)
// NEWTEC-IP-MIB::ntcIpMgmtInterfaceState.mgmt2 = INTEGER: off(0)
// NEWTEC-IP-MIB::ntcIpMgmtInterfaceState.mgmt = INTEGER: off(0)
// NEWTEC-IP-MIB::ntcIpMgmtInterfaceVirtualIpAddr.mgmt1 = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcIpMgmtInterfaceVirtualIpAddr.mgmt2 = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcIpMgmtInterfaceVirtualIpAddr.mgmt = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcMgmtGateway.0 = IpAddress: 192.168.202.1

foreach (snmpwalk_cache_oid($device, 'ntcIpMgmtInterfaceTable', [], $mib) as $ifDescr => $entry) {
    // if ($entry['ntcIpMgmtInterfaceState'] === 'off') {
    //     continue;
    // }

    $ifIndex = $ports_ifDescr[$ifDescr]['ifIndex'] ?? 0;

    [ $ip, $prefix ] = explode('/', $entry['ntcIpMgmtInterfaceIpAddress']);
    $data = [
        'ifIndex' => $ifIndex,
        'ip'      => $ip,
        //'mask'    => $ip_mask,
        'prefix'  => $prefix
    ];
    discover_add_ip_address($device, $mib, $data);

    [ $ip, $prefix ] = explode('/', $entry['ntcIpMgmtInterfaceVirtualIpAddr']);
    $data = [
        'ifIndex' => $ifIndex,
        'ip'      => $ip,
        //'mask'    => $ip_mask,
        'prefix'  => $prefix
    ];
    discover_add_ip_address($device, $mib, $data);
}

// NEWTEC-IP-MIB::ntcDataInterfaceIpAddress.data1 = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcDataInterfaceIpAddress.data2 = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcDataInterfaceIpAddress.data = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcDataInterfaceState.data1 = INTEGER: off(0)
// NEWTEC-IP-MIB::ntcDataInterfaceState.data2 = INTEGER: off(0)
// NEWTEC-IP-MIB::ntcDataInterfaceState.data = INTEGER: on(1)
// NEWTEC-IP-MIB::ntcDataInterfaceFysIpAddress.data1 = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcDataInterfaceFysIpAddress.data2 = STRING: 0.0.0.0/24
// NEWTEC-IP-MIB::ntcDataInterfaceFysIpAddress.data = STRING: 10.252.10.5/29
// NEWTEC-IP-MIB::ntcDataGateway.0 = IpAddress: 10.252.10.6

foreach (snmpwalk_cache_oid($device, 'ntcDataInterfaceTable', [], $mib) as $ifDescr => $entry) {
    // if ($entry['ntcDataInterfaceState'] === 'off') {
    //     continue;
    // }

    $ifIndex = $ports_ifDescr[$ifDescr]['ifIndex'] ?? 0;

    [ $ip, $prefix ] = explode('/', $entry['ntcDataInterfaceIpAddress']);
    $data = [
        'ifIndex' => $ifIndex,
        'ip'      => $ip,
        //'mask'    => $ip_mask,
        'prefix'  => $prefix
    ];
    discover_add_ip_address($device, $mib, $data);

    [ $ip, $prefix ] = explode('/', $entry['ntcDataInterfaceFysIpAddress']);
    $data = [
        'ifIndex' => $ifIndex,
        'ip'      => $ip,
        //'mask'    => $ip_mask,
        'prefix'  => $prefix
    ];
    discover_add_ip_address($device, $mib, $data);
}

// EOF
