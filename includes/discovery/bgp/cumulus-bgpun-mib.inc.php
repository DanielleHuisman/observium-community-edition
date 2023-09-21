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

/**
 * @var array  $device
 * @var string $mib
 * @var array  $entry
 * @var string $bgpLocalAs
 * @var array  $p_list
 * @var array  $peerlist
 * @var bool   $check_vrfs
 * @var string $vrf_name
 */

// NOTE. This mib deprecated since Cumulus 5.1

// CUMULUS-BGPUN-MIB::bgpPeerState.192.168.0.1 = INTEGER: established(6)
// CUMULUS-BGPUN-MIB::bgpPeerState.192.168.0.5 = INTEGER: established(6)
// CUMULUS-BGPUN-MIB::bgpPeerAdminStatus.192.168.0.1 = INTEGER: start(2)
// CUMULUS-BGPUN-MIB::bgpPeerAdminStatus.192.168.0.5 = INTEGER: start(2)
// CUMULUS-BGPUN-MIB::bgpPeerLocalAddr.192.168.0.1 = STRING: "fe80::e04:1fff:fe86:1"
// CUMULUS-BGPUN-MIB::bgpPeerLocalAddr.192.168.0.5 = STRING: "192.168.125.2"
// CUMULUS-BGPUN-MIB::bgpPeerRemoteAs.192.168.0.1 = INTEGER: 65000
// CUMULUS-BGPUN-MIB::bgpPeerRemoteAs.192.168.0.5 = INTEGER: 65005
// CUMULUS-BGPUN-MIB::bgpPeerInUpdates.192.168.0.1 = Counter32: 6
// CUMULUS-BGPUN-MIB::bgpPeerInUpdates.192.168.0.5 = Counter32: 6
// CUMULUS-BGPUN-MIB::bgpPeerOutUpdates.192.168.0.1 = Counter32: 6
// CUMULUS-BGPUN-MIB::bgpPeerOutUpdates.192.168.0.5 = Counter32: 6
// CUMULUS-BGPUN-MIB::bgpPeerInTotalMessages.192.168.0.1 = Counter32: 32629
// CUMULUS-BGPUN-MIB::bgpPeerInTotalMessages.192.168.0.5 = Counter32: 32629
// CUMULUS-BGPUN-MIB::bgpPeerOutTotalMessages.192.168.0.1 = Counter32: 32629
// CUMULUS-BGPUN-MIB::bgpPeerOutTotalMessages.192.168.0.5 = Counter32: 32629
// CUMULUS-BGPUN-MIB::bgpPeerLastError.192.168.0.1 = Hex-STRING: 00 00
// CUMULUS-BGPUN-MIB::bgpPeerLastError.192.168.0.5 = Hex-STRING: 00 00
// CUMULUS-BGPUN-MIB::bgpPeerFsmEstablishedTime.192.168.0.1 = Gauge32: 97875
// CUMULUS-BGPUN-MIB::bgpPeerFsmEstablishedTime.192.168.0.5 = Gauge32: 97875
// CUMULUS-BGPUN-MIB::bgpPeerInUpdateElapsedTime.192.168.0.1 = Gauge32: 11473
// CUMULUS-BGPUN-MIB::bgpPeerInUpdateElapsedTime.192.168.0.5 = Gauge32: 11473
// CUMULUS-BGPUN-MIB::bgpPeerIface.192.168.0.1 = STRING: "swp1"
// CUMULUS-BGPUN-MIB::bgpPeerIface.192.168.0.5 = STRING: "192.168.125.254"

$peers_data = snmpwalk_cache_oid($device, 'bgpPeerRemoteAs', [], 'CUMULUS-BGPUN-MIB');
//$peers_data = snmpwalk_cache_oid($device, 'bgpPeerRemoteAddr',  $peers_data, 'CUMULUS-BGPUN-MIB');
$peers_data = snmpwalk_cache_oid($device, 'bgpPeerLocalAddr', $peers_data, 'CUMULUS-BGPUN-MIB');
$peers_data = snmpwalk_cache_oid($device, 'bgpPeerIface', $peers_data, 'CUMULUS-BGPUN-MIB');
$peers_data = snmpwalk_cache_oid($device, 'bgpPeerAdminStatus', $peers_data, 'CUMULUS-BGPUN-MIB');

foreach ($peers_data as $index => $bgp4_entry) {
    $peer_ip = $index;
    $peer_as = snmp_dewrap32bit($bgp4_entry['bgpPeerRemoteAs']); // Dewrap for 32bit ASN
    if ($peer_as > $bgp4_entry['bgpPeerRemoteAs']) {
        $peers_data[$index]['bgpPeerRemoteAs'] = $peer_as;
    }
    $local_ip = $bgp4_entry['bgpPeerLocalAddr'];

    // Add bgpPeerIdentifier
    //$bgp4_entry['bgpPeerIdentifier'] = get_ip_version($bgp4_entry['bgpPeerIface']) ? $bgp4_entry['bgpPeerIface'] : $index;
    $bgp4_entry['bgpPeerIdentifier']         = $index;
    $peers_data[$index]['bgpPeerIdentifier'] = $bgp4_entry['bgpPeerIdentifier'];

    $peer = [
      'mib'          => $mib,
      'index'        => $index,
      'identifier'   => $bgp4_entry['bgpPeerIdentifier'],
      'local_ip'     => $local_ip,
      'ip'           => $peer_ip === '0.0.0.0' ? '' : $peer_ip,
      'local_as'     => $bgpLocalAs,
      'as'           => $peer_as,
      'admin_status' => $bgp4_entry['bgpPeerAdminStatus']
    ];
    if ($check_vrfs) {
        $peer['virtual_name'] = $vrf_name;
    }
    if (!isset($p_list[$peer_ip][$peer_as]) && is_bgp_peer_valid($peer, $device)) {
        print_debug("Found peer IP: $peer_ip (AS$peer_as, LocalIP: $local_ip)");
        $peerlist[]                 = $peer;
        $p_list[$peer_ip][$peer_as] = 1;
    }
}

// EOF
