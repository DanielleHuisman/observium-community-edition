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

if (snmp_get_oid($device, 'mplsVpnConfiguredVrfs.0', 'MPLS-VPN-MIB') <= 0) {
    print_debug("Not found VRFs by MPLS-VPN-MIB.");
    return;
}
if (safe_count($discovery_vrf) &&
    $discovery_vrf[array_key_first($discovery_vrf)]['vrf_mib'] === 'MPLS-L3VPN-STD-MIB') {
    print_debug("VRFs already discovered by MPLS-L3VPN-STD-MIB, skip MPLS-VPN-MIB.");
    return;
}

// MPLS-VPN-MIB::mplsVpnVrfDescription."hostcomm-private" = STRING:
// MPLS-VPN-MIB::mplsVpnVrfRouteDistinguisher."hostcomm-private" = STRING: "10:10"
// MPLS-VPN-MIB::mplsVpnVrfCreationTime."hostcomm-private" = Timeticks: (1228) 0:00:12.28
// MPLS-VPN-MIB::mplsVpnVrfOperStatus."hostcomm-private" = INTEGER: up(1)
// MPLS-VPN-MIB::mplsVpnVrfActiveInterfaces."hostcomm-private" = Gauge32: 32
// MPLS-VPN-MIB::mplsVpnVrfAssociatedInterfaces."hostcomm-private" = Gauge32: 32
// MPLS-VPN-MIB::mplsVpnVrfConfMidRouteThreshold."hostcomm-private" = Gauge32: 4294967295
// MPLS-VPN-MIB::mplsVpnVrfConfHighRouteThreshold."hostcomm-private" = Gauge32: 4294967295
// MPLS-VPN-MIB::mplsVpnVrfConfMaxRoutes."hostcomm-private" = Gauge32: 4294967295
// MPLS-VPN-MIB::mplsVpnVrfConfLastChanged."hostcomm-private" = Timeticks: (1228) 0:00:12.28
// MPLS-VPN-MIB::mplsVpnVrfConfRowStatus."hostcomm-private" = INTEGER: active(1)
// MPLS-VPN-MIB::mplsVpnVrfConfStorageType."hostcomm-private" = INTEGER: volatile(2)

$mpls_vpn_vrf   = snmpwalk_cache_oid($device, 'mplsVpnVrfEntry', [], 'MPLS-VPN-MIB');
$vrf_discovered = snmp_endtime();

#$mpls_vpn_target = snmpwalk_cache_oid($device, 'mplsVpnVrfRouteTargetEntry', [], 'MPLS-VPN-MIB');
#print_debug_vars($mpls_vpn_target);

// MPLS-VPN-MIB::mplsVpnVrfSecIllegalLabelViolations."hostcomm-private" = Counter32: 0
// MPLS-VPN-MIB::mplsVpnVrfSecIllegalLabelRcvThresh."hostcomm-private" = Gauge32: 0
// MPLS-VPN-MIB::mplsVpnVrfPerfRoutesAdded."hostcomm-private" = Counter32: 723
// MPLS-VPN-MIB::mplsVpnVrfPerfRoutesDeleted."hostcomm-private" = Counter32: 585
// MPLS-VPN-MIB::mplsVpnVrfPerfCurrNumRoutes."hostcomm-private" = Gauge32: 138
$mpls_vpn_vrf = snmpwalk_cache_oid($device, 'mplsVpnVrfPerfEntry', $mpls_vpn_vrf, 'MPLS-VPN-MIB');
print_debug_vars($mpls_vpn_vrf);

foreach ($mpls_vpn_vrf as $vrf_name => $entry) {
    if ($entry['mplsVpnVrfConfRowStatus'] !== 'active') {
        continue;
    } // Skip inactive

    $discovery_vrf[$vrf_name] = [
      'vrf_mib'            => $mib,
      'vrf_name'           => $vrf_name,
      'vrf_descr'          => $entry['mplsVpnVrfDescription'],
      'vrf_rd'             => str_replace('L', '', $entry['mplsVpnVrfRouteDistinguisher']),
      //'vrf_admin_status'   => 'up',
      'vrf_oper_status'    => $entry['mplsVpnVrfOperStatus'],
      'vrf_active_ports'   => $entry['mplsVpnVrfActiveInterfaces'],
      'vrf_total_ports'    => $entry['mplsVpnVrfAssociatedInterfaces'],
      'vrf_added_routes'   => $entry['mplsVpnVrfPerfRoutesAdded'],
      'vrf_deleted_routes' => $entry['mplsVpnVrfPerfRoutesDeleted'],
      'vrf_total_routes'   => $entry['mplsVpnVrfPerfCurrNumRoutes'],
      // device starttime + time
      'vrf_added'          => $device['last_rebooted'] + timeticks_to_sec($entry['mplsVpnVrfCreationTime']),
      'vrf_last_change'    => $device['last_rebooted'] + timeticks_to_sec($entry['mplsVpnVrfConfLastChanged']),
    ];
}

// MPLS-VPN-MIB::mplsVpnInterfaceConfRowStatus."hostcomm-private".143 = INTEGER: active(1)
// MPLS-VPN-MIB::mplsVpnInterfaceConfRowStatus."hostcomm-private".145 = INTEGER: active(1)

//$mpls_vpn_if = snmpwalk_cache_twopart_oid($device, 'mplsVpnInterfaceConfEntry', [], 'MPLS-VPN-MIB');
$mpls_vpn_if = snmpwalk_cache_twopart_oid($device, 'mplsVpnInterfaceConfRowStatus', [], 'MPLS-VPN-MIB');
print_debug_vars($mpls_vpn_if);

foreach ($mpls_vpn_if as $vrf_name => $entry) {
    if (!isset($discovery_vrf[$vrf_name])) {
        print_debug("Unknown VRF name '$vrf_name'.");
        continue;
    }

    foreach ($entry as $vrf_ifIndex => $entry2) {
        if ($entry2['mplsVpnInterfaceConfRowStatus'] !== 'active') {
            continue;
        } // Skip inactive interfaces
        $discovery_vrf[$vrf_name]['ifIndex'][] = $vrf_ifIndex;
    }
}

// EOF
