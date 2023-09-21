<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// CUMULUS-BGPVRF-MIB::bgpVrfTable.254 = INTEGER: 254
// CUMULUS-BGPVRF-MIB::bgpVrfTable.1001 = INTEGER: 1001
// CUMULUS-BGPVRF-MIB::bgpVrfId.254 = INTEGER: 0
// CUMULUS-BGPVRF-MIB::bgpVrfId.1001 = INTEGER: 37
// CUMULUS-BGPVRF-MIB::bgpVrfName.254 = STRING: default
// CUMULUS-BGPVRF-MIB::bgpVrfName.1001 = STRING: mgmt

//$peers_vrf = snmpwalk_cache_oid($device, 'bgpVrfName', [], 'CUMULUS-BGPVRF-MIB');

// CUMULUS-BGPVRF-MIB::bgpLocalAs.254 = INTEGER: 64715
// CUMULUS-BGPVRF-MIB::bgpIdentifier.254 = IpAddress: 10.72.0.107

$vrf_index = $entry['vrf_index']; // from $local_as_array
$local_as = $entry['LocalAs'];
//$peers_localas = snmp_get_oid($device, 'bgpLocalAs.' . $vrf_index, 'CUMULUS-BGPVRF-MIB');
//$peers_localas = snmpwalk_cache_oid($device, 'bgpIdentifier', $peers_localas, 'CUMULUS-BGPVRF-MIB');

// CUMULUS-BGPVRF-MIB::bgpPeerIdentifier.254.ipv6.65024.4096.0.1.0.6146 = IpAddress: 10.72.0.56
// CUMULUS-BGPVRF-MIB::bgpPeerState.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: established(6)
// CUMULUS-BGPVRF-MIB::bgpPeerAdminStatus.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: start(2)
// CUMULUS-BGPVRF-MIB::bgpPeerNegotiatedVersion.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 4
// CUMULUS-BGPVRF-MIB::bgpPeerLocalAddr.254.ipv6.65024.4096.0.1.0.6146 = STRING: "fe00::1000:0:1:0:1803"
// CUMULUS-BGPVRF-MIB::bgpPeerLocalPort.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 33594
// CUMULUS-BGPVRF-MIB::bgpPeerRemoteAddr.254.ipv6.65024.4096.0.1.0.6146 = STRING: "fe00::1000:0:1:0:1802"
// CUMULUS-BGPVRF-MIB::bgpPeerRemotePort.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 179
// CUMULUS-BGPVRF-MIB::bgpPeerRemoteAs.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 64712
// CUMULUS-BGPVRF-MIB::bgpPeerInUpdates.254.ipv6.65024.4096.0.1.0.6146 = Counter32: 587
// CUMULUS-BGPVRF-MIB::bgpPeerOutUpdates.254.ipv6.65024.4096.0.1.0.6146 = Counter32: 925
// CUMULUS-BGPVRF-MIB::bgpPeerInTotalMessages.254.ipv6.65024.4096.0.1.0.6146 = Counter32: 1562209
// CUMULUS-BGPVRF-MIB::bgpPeerOutTotalMessages.254.ipv6.65024.4096.0.1.0.6146 = Counter32: 1562519
// CUMULUS-BGPVRF-MIB::bgpPeerLastError.254.ipv6.65024.4096.0.1.0.6146 = Hex-STRING: 00 00
// CUMULUS-BGPVRF-MIB::bgpPeerFsmEstablishedTransitions.254.ipv6.65024.4096.0.1.0.6146 = Counter32: 0
// CUMULUS-BGPVRF-MIB::bgpPeerFsmEstablishedTime.254.ipv6.65024.4096.0.1.0.6146 = Gauge32: 4083158
// CUMULUS-BGPVRF-MIB::bgpPeerConnectRetryInterval.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 10
// CUMULUS-BGPVRF-MIB::bgpPeerHoldTime.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 9
// CUMULUS-BGPVRF-MIB::bgpPeerKeepAlive.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 3
// CUMULUS-BGPVRF-MIB::bgpPeerHoldTimeConfigured.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 9
// CUMULUS-BGPVRF-MIB::bgpPeerKeepAliveConfigured.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 3
// CUMULUS-BGPVRF-MIB::bgpPeerEntry.22.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 0
// CUMULUS-BGPVRF-MIB::bgpPeerMinRouteAdvertisementInterval.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 0
// CUMULUS-BGPVRF-MIB::bgpPeerInUpdateElapsedTime.254.ipv6.65024.4096.0.1.0.6146 = Gauge32: 74904
// CUMULUS-BGPVRF-MIB::bgpPeerIface.254.ipv6.65024.4096.0.1.0.6146 = STRING: iface
// CUMULUS-BGPVRF-MIB::bgpPeerDesc.254.ipv6.65024.4096.0.1.0.6146 = STRING: NA
// CUMULUS-BGPVRF-MIB::bgpPeerIfindex.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: 0
// CUMULUS-BGPVRF-MIB::bgpPeerIdType.254.ipv6.65024.4096.0.1.0.6146 = INTEGER: ipv6(2)

$def = $config['mibs'][$mib]['bgp'];

$peers_data = snmp_cache_table($device, $def['oids']['PeerRemoteAs']['oid'], [], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

$peers_data = snmp_cache_table($device, $def['oids']['PeerRemoteAddr']['oid'],  $peers_data, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
$peers_data = snmp_cache_table($device, $def['oids']['PeerLocalAddr']['oid'],   $peers_data, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
$peers_data = snmp_cache_table($device, $def['oids']['PeerIdentifier']['oid'],  $peers_data, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
$peers_data = snmp_cache_table($device, $def['oids']['PeerAdminStatus']['oid'], $peers_data, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

//$peers_data = snmp_cache_table($device, $def['oids']['PeerDescription']['oid'], $peers_data, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
//$peers_data = snmp_cache_table($device, 'bgpPeerIface', $peers_data, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
//$peers_data = snmp_cache_table($device, 'bgpPeerIfindex', $peers_data, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
//$peers_data = snmp_cache_table($device, $def['oids']['PeerRemoteAddrType']['oid'], $peers_data, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

foreach ($peers_data as $index => $bgp4_entry) {
    $peer_vrf_index = explode('.', $index)[0];
    if ($vrf_index != $peer_vrf_index) {
        continue; // Skip different vrf index
    }

    $peer_ip = ip_uncompress($bgp4_entry['bgpPeerRemoteAddr']);
    $peer_as = snmp_dewrap32bit($bgp4_entry['bgpPeerRemoteAs']); // Dewrap for 32bit ASN
    if ($peer_as > $bgp4_entry['bgpPeerRemoteAs']) {
        $peers_data[$index]['bgpPeerRemoteAs'] = $peer_as;
    }
    $local_ip = ip_uncompress($bgp4_entry['bgpPeerLocalAddr']);

    $peer = [
        'mib'          => $mib,
        'index'        => $index,
        'identifier'   => $bgp4_entry['bgpPeerIdentifier'],
        'local_ip'     => $local_ip,
        'ip'           => $peer_ip === '0.0.0.0' ? '' : $peer_ip,
        'local_as'     => $local_as,
        'as'           => $peer_as,
        'admin_status' => $bgp4_entry['bgpPeerAdminStatus']
    ];
    if ($vrf_name && $vrf_name !== 'default') {
        $peer['virtual_name'] = $vrf_name;
    }

    if (!isset($p_list[$peer_ip][$peer_as]) && is_bgp_peer_valid($peer, $device)) {
        print_debug("Found peer IP: $peer_ip (AS$peer_as, LocalIP: $local_ip)");
        $peerlist[]                 = $peer;
        $p_list[$peer_ip][$peer_as] = 1;
    }
}

// EOF
