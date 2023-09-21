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

if (safe_count($discovery_vrf)) {
    // VRF already discovered by better MIBs
    return;
}

// CISCO-VRF-MIB::cvVrfName.1 = STRING: ISP-VZ
// CISCO-VRF-MIB::cvVrfName.2 = STRING: VPN-CLIENT
// CISCO-VRF-MIB::cvVrfVnetTag.1 = Gauge32: 0
// CISCO-VRF-MIB::cvVrfVnetTag.2 = Gauge32: 0
// CISCO-VRF-MIB::cvVrfOperStatus.1 = INTEGER: up(1)
// CISCO-VRF-MIB::cvVrfOperStatus.2 = INTEGER: up(1)
// CISCO-VRF-MIB::cvVrfRouteDistProt.1 = BITS: 42 other(1) bgp(6)
// CISCO-VRF-MIB::cvVrfRouteDistProt.2 = BITS: 42 other(1) bgp(6)
// CISCO-VRF-MIB::cvVrfStorageType.1 = INTEGER: nonVolatile(3)
// CISCO-VRF-MIB::cvVrfStorageType.2 = INTEGER: nonVolatile(3)
// CISCO-VRF-MIB::cvVrfRowStatus.1 = INTEGER: active(1)
// CISCO-VRF-MIB::cvVrfRowStatus.2 = INTEGER: active(1)
$vrfs           = snmpwalk_cache_oid($device, 'cvVrfTable', [], 'CISCO-VRF-MIB');
$vrf_discovered = snmp_endtime();
print_debug_vars($vrf);

$vrf_ids = [];
foreach ($vrfs as $index => $entry) {
    if ($entry['cvVrfRowStatus'] !== 'active') {
        continue;
    } // Skip inactive

    $vrf_name = $entry['cvVrfName'];

    $discovery_vrf[$vrf_name] = [
      'vrf_mib'   => $mib,
      'vrf_name'  => $vrf_name,
      'vrf_descr' => '',
      'vrf_rd'    => '',

      'vrf_admin_status' => 'up',
      'vrf_oper_status'  => $entry['cvVrfOperStatus'],
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

    $vrf_ids[$index] = $vrf_name;
}

// CISCO-VRF-MIB::cvVrfInterfaceType.1.2 = INTEGER: vrfEdge(3)
// CISCO-VRF-MIB::cvVrfInterfaceType.2.3 = INTEGER: vrfEdge(3)
// CISCO-VRF-MIB::cvVrfInterfaceVnetTagOverride.1.2 = Gauge32: 0
// CISCO-VRF-MIB::cvVrfInterfaceVnetTagOverride.2.3 = Gauge32: 0
// CISCO-VRF-MIB::cvVrfInterfaceStorageType.1.2 = INTEGER: nonVolatile(3)
// CISCO-VRF-MIB::cvVrfInterfaceStorageType.2.3 = INTEGER: nonVolatile(3)
// CISCO-VRF-MIB::cvVrfInterfaceRowStatus.1.2 = INTEGER: active(1)
// CISCO-VRF-MIB::cvVrfInterfaceRowStatus.2.3 = INTEGER: active(1)
$vrf_interfaces = snmpwalk_cache_twopart_oid($device, 'cvVrfInterfaceTable', [], 'CISCO-VRF-MIB');
print_debug_vars($vrf_interfaces);

foreach ($vrf_interfaces as $vrf_id => $int) {
    if (!isset($vrf_ids[$vrf_id])) {
        continue;
    } // skip unknown VRFs

    $vrf_name = $vrf_ids[$vrf_id];

    foreach ($int as $vrf_ifIndex => $entry) {
        if ($entry['cvVrfInterfaceRowStatus'] !== 'active') {
            continue;
        } // Skip inactive

        $discovery_vrf[$vrf_name]['ifIndex'][] = $vrf_ifIndex;
    }
}

unset($vrfs, $vrf_interfaces, $vrf_ids, $int);

// EOF
