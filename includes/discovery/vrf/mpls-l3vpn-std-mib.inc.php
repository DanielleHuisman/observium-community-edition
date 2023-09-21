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

if (snmp_get_oid($device, 'mplsL3VpnConfiguredVrfs.0', 'MPLS-L3VPN-STD-MIB') <= 0) {
    print_debug("Not found VRFs by MPLS-L3VPN-STD-MIB.");
    return;
}

// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfDescription."SA_MPLS" = STRING:
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfDescription."INTERNET" = STRING:
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfDescription."ELEC_MPLS" = STRING:
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfDescription."SAPAT_MPLS" = STRING:
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfRD."SA_MPLS" = STRING: "503:503"
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfRD."INTERNET" = STRING: "1000:1000"
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfRD."ELEC_MPLS" = STRING: "519:519"
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfRD."SAPAT_MPLS" = STRING: "510:510"
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfCreationTime."SA_MPLS" = Timeticks: (3263183100) 377 days, 16:23:51.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfCreationTime."INTERNET" = Timeticks: (3263183300) 377 days, 16:23:53.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfCreationTime."ELEC_MPLS" = Timeticks: (2175281700) 251 days, 18:26:57.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfCreationTime."SAPAT_MPLS" = Timeticks: (3263183100) 377 days, 16:23:51.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfOperStatus."SA_MPLS" = INTEGER: up(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfOperStatus."INTERNET" = INTEGER: up(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfOperStatus."ELEC_MPLS" = INTEGER: down(2)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfOperStatus."SAPAT_MPLS" = INTEGER: down(2)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfActiveInterfaces."SA_MPLS" = Gauge32: 3
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfActiveInterfaces."INTERNET" = Gauge32: 2
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfActiveInterfaces."ELEC_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfActiveInterfaces."SAPAT_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfAssociatedInterfaces."SA_MPLS" = Gauge32: 3
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfAssociatedInterfaces."INTERNET" = Gauge32: 2
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfAssociatedInterfaces."ELEC_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfAssociatedInterfaces."SAPAT_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfHighRteThresh."SA_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfHighRteThresh."INTERNET" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfHighRteThresh."ELEC_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfHighRteThresh."SAPAT_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfMaxRoutes."SA_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfMaxRoutes."INTERNET" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfMaxRoutes."ELEC_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfMaxRoutes."SAPAT_MPLS" = Gauge32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfLastChanged."SA_MPLS" = Timeticks: (19900) 0:03:19.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfLastChanged."INTERNET" = Timeticks: (19900) 0:03:19.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfLastChanged."ELEC_MPLS" = Timeticks: (1088148600) 125 days, 22:38:06.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfLastChanged."SAPAT_MPLS" = Timeticks: (19900) 0:03:19.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfRowStatus."SA_MPLS" = INTEGER: active(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfRowStatus."INTERNET" = INTEGER: active(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfRowStatus."ELEC_MPLS" = INTEGER: active(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfRowStatus."SAPAT_MPLS" = INTEGER: active(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfAdminStatus."SA_MPLS" = INTEGER: up(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfAdminStatus."INTERNET" = INTEGER: up(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfAdminStatus."ELEC_MPLS" = INTEGER: up(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfConfAdminStatus."SAPAT_MPLS" = INTEGER: up(1)

#$mpls_vpn_target = snmpwalk_cache_oid($device, 'mplsL3VpnVrfRTTable', [], 'MPLS-L3VPN-STD-MIB');
#print_debug_vars($mpls_vpn_target);

$mpls_vpn_vrf = snmpwalk_cache_oid($device, 'mplsL3VpnVrfEntry', [], 'MPLS-L3VPN-STD-MIB');
//$mpls_vpn_vrf = snmpwalk_cache_oid($device, 'mplsL3VpnVrfRD',                     [], 'MPLS-L3VPN-STD-MIB');
//$mpls_vpn_vrf = snmpwalk_cache_oid($device, 'mplsL3VpnVrfDescription',    $mpls_vpn_vrf, 'MPLS-L3VPN-STD-MIB');
//$mpls_vpn_vrf = snmpwalk_cache_oid($device, 'mplsL3VpnVrfConfAdminStatus', $mpls_vpn_vrf, 'MPLS-L3VPN-STD-MIB');
//$mpls_vpn_vrf = snmpwalk_cache_oid($device, 'mplsL3VpnVrfOperStatus',      $mpls_vpn_vrf, 'MPLS-L3VPN-STD-MIB');
$vrf_discovered = snmp_endtime();

// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesAdded."SA_MPLS" = Counter32: 1696
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesAdded."INTERNET" = Counter32: 7054
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesAdded."ELEC_MPLS" = Counter32: 7
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesAdded."SAPAT_MPLS" = Counter32: 1
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesDeleted."SA_MPLS" = Counter32: 1436
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesDeleted."INTERNET" = Counter32: 6735
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesDeleted."ELEC_MPLS" = Counter32: 6
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesDeleted."SAPAT_MPLS" = Counter32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfCurrNumRoutes."SA_MPLS" = Gauge32: 260
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfCurrNumRoutes."INTERNET" = Gauge32: 319
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfCurrNumRoutes."ELEC_MPLS" = Gauge32: 1
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfCurrNumRoutes."SAPAT_MPLS" = Gauge32: 1
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesDropped."SA_MPLS" = Counter32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesDropped."INTERNET" = Counter32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesDropped."ELEC_MPLS" = Counter32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfRoutesDropped."SAPAT_MPLS" = Counter32: 0
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfDiscTime."SA_MPLS" = Timeticks: (0) 0:00:00.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfDiscTime."INTERNET" = Timeticks: (0) 0:00:00.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfDiscTime."ELEC_MPLS" = Timeticks: (0) 0:00:00.00
// MPLS-L3VPN-STD-MIB::mplsL3VpnVrfPerfDiscTime."SAPAT_MPLS" = Timeticks: (0) 0:00:00.00
$mpls_vpn_vrf = snmpwalk_cache_oid($device, 'mplsL3VpnVrfPerfEntry', $mpls_vpn_vrf, 'MPLS-L3VPN-STD-MIB');
print_debug_vars($mpls_vpn_vrf);

foreach ($mpls_vpn_vrf as $vrf_name => $entry) {
    if ($entry['mplsL3VpnVrfConfRowStatus'] !== 'active') {
        continue;
    } // Skip inactive

    /* Juniper specific case:
    mplsL3VpnVrfRD."BeamMGMT" = "10.51.0.236:666"
    mplsL3VpnVrfRD."CUST_ALX" = "134743L:12007"
    mplsL3VpnVrfRD."Mgmt-intf" = "10.51.0.236:667"
    */
    $discovery_vrf[$vrf_name] = [
      'vrf_mib'   => $mib,
      'vrf_name'  => $vrf_name,
      'vrf_descr' => $entry['mplsL3VpnVrfDescription'],
      'vrf_rd'    => str_replace('L', '', $entry['mplsL3VpnVrfRD']),

      'vrf_admin_status'   => $entry['mplsL3VpnVrfConfAdminStatus'],
      'vrf_oper_status'    => $entry['mplsL3VpnVrfOperStatus'],
      'vrf_active_ports'   => $entry['mplsL3VpnVrfActiveInterfaces'],
      'vrf_total_ports'    => $entry['mplsL3VpnVrfAssociatedInterfaces'],
      'vrf_added_routes'   => $entry['mplsL3VpnVrfPerfRoutesAdded'],
      'vrf_deleted_routes' => $entry['mplsL3VpnVrfPerfRoutesDeleted'],
      'vrf_total_routes'   => $entry['mplsL3VpnVrfPerfCurrNumRoutes'],
      //'vrf_added'          => $vrf_discovered - timeticks_to_sec($entry['mplsL3VpnVrfCreationTime']),
      'vrf_added'          => $device['last_rebooted'] + timeticks_to_sec($entry['mplsL3VpnVrfCreationTime']),
      'vrf_last_change'    => $device['last_rebooted'] + timeticks_to_sec($entry['mplsL3VpnVrfConfLastChanged']),
    ];
}

// MPLS-L3VPN-STD-MIB::mplsL3VpnIfConfRowStatus."SA_MPLS".32 = INTEGER: active(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnIfConfRowStatus."SA_MPLS".46 = INTEGER: active(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnIfConfRowStatus."SA_MPLS".47 = INTEGER: active(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnIfConfRowStatus."INTERNET".18 = INTEGER: active(1)
// MPLS-L3VPN-STD-MIB::mplsL3VpnIfConfRowStatus."INTERNET".19 = INTEGER: active(1)

//$mpls_vpn_if = snmpwalk_cache_twopart_oid($device, 'mplsL3VpnIfConfEntry', [], 'MPLS-L3VPN-STD-MIB');
$mpls_vpn_if = snmpwalk_cache_twopart_oid($device, 'mplsL3VpnIfConfRowStatus', [], 'MPLS-L3VPN-STD-MIB');
print_debug_vars($mpls_vpn_if);
foreach ($mpls_vpn_if as $vrf_name => $entry) {
    if (!isset($discovery_vrf[$vrf_name])) {
        print_debug("Unknown VRF name '$vrf_name'.");
        continue;
    }

    foreach ($entry as $vrf_ifIndex => $entry2) {
        if ($entry2['mplsL3VpnIfConfRowStatus'] !== 'active') {
            continue;
        } // Skip inactive interfaces
        $discovery_vrf[$vrf_name]['ifIndex'][] = $vrf_ifIndex;
    }
}

// EOF
