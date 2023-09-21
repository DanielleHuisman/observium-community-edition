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

// ARISTA-VRF-MIB::aristaVrfRoutingStatus."mgmt" = BITS: 80 ipv4(0)
// ARISTA-VRF-MIB::aristaVrfRoutingStatus."mai_internal" = BITS: 80 ipv4(0)
// ARISTA-VRF-MIB::aristaVrfRouteDistinguisher."mgmt" = STRING: INVALID
// ARISTA-VRF-MIB::aristaVrfRouteDistinguisher."mai_internal" = STRING: 24971:3
// ARISTA-VRF-MIB::aristaVrfState."mgmt" = INTEGER: inactive(2)
// ARISTA-VRF-MIB::aristaVrfState."mai_internal" = INTEGER: active(1)

$mpls_vpn_vrf   = snmpwalk_cache_oid($device, 'aristaVrfEntry', [], 'ARISTA-VRF-MIB');
$vrf_discovered = snmp_endtime();
print_debug_vars($mpls_vpn_vrf);

foreach ($mpls_vpn_vrf as $vrf_name => $entry) {
    //if ($entry['aristaVrfState'] !== 'active') { continue; } // Skip inactive

    $discovery_vrf[$vrf_name] = [
      'vrf_mib'   => $mib,
      'vrf_name'  => $vrf_name,
      'vrf_descr' => '',
      'vrf_rd'    => $entry['aristaVrfState'] === 'active' ? $entry['aristaVrfRouteDistinguisher'] : '',

      'vrf_admin_status' => 'up',
      'vrf_oper_status'  => $entry['aristaVrfState'] === 'active' ? 'up' : 'down',
      /*
      'vrf_active_ports'   => $entry['mplsL3VpnVrfActiveInterfaces'],
      'vrf_total_ports'    => $entry['mplsL3VpnVrfAssociatedInterfaces'],
      'vrf_added_routes'   => $entry['mplsL3VpnVrfPerfRoutesAdded'],
      'vrf_deleted_routes' => $entry['mplsL3VpnVrfPerfRoutesDeleted'],
      'vrf_total_routes'   => $entry['mplsL3VpnVrfPerfCurrNumRoutes'],
      //'vrf_added'          => $vrf_discovered - timeticks_to_sec($entry['mplsL3VpnVrfCreationTime']),
      'vrf_added'          => $device['last_rebooted'] + timeticks_to_sec($entry['mplsL3VpnVrfCreationTime']),
      'vrf_last_change'    => $device['last_rebooted'] + timeticks_to_sec($entry['mplsL3VpnVrfConfLastChanged']),
      */
    ];

    // Detect if VRF SNMP context exist
    if (snmp_virtual_exist($device, $vrf_name)) {
        $vrf_contexts[$vrf_name] = $vrf_name;
    }
}

// ARISTA-VRF-MIB::aristaVrfIfMembership.999001 = STRING: mgmt
// ARISTA-VRF-MIB::aristaVrfIfMembership.5000002 = STRING: mai_internal

$mpls_vpn_if = snmpwalk_cache_oid($device, 'aristaVrfIfMembership', [], 'ARISTA-VRF-MIB');
print_debug_vars($mpls_vpn_if);
foreach ($mpls_vpn_if as $vrf_ifIndex => $entry) {
    $vrf_name = $entry['aristaVrfIfMembership'];
    if (!isset($discovery_vrf[$vrf_name])) {
        print_debug("Unknown VRF name '$vrf_name'.");
        continue;
    }

    $discovery_vrf[$vrf_name]['ifIndex'][] = $vrf_ifIndex;
}

// EOF
