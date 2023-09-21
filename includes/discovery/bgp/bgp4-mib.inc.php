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

/**
 * @var array   $device
 * @var string  $mib
 * @var array   $entry
 * @var string  $bgpLocalAs
 * @var integer $cisco_version Come from cisco-bgp4-mib.inc.php
 * @var array   $p_list
 * @var array   $peerlist
 * @var bool    $check_vrfs
 * @var string  $vrf_name
 */

// All MIBs except CISCO-BGP4-MIB (table version 2)
if ($check_vrfs) {
    if (isset($cisco_version_vrf[$vrf_name]) && $cisco_version_vrf[$vrf_name] > 1) {
        print_debug("BGP4-MIB skipped because use CISCO-BGP4-MIB table version 2 (VRF: $vrf_name).");

        return;
    }
} elseif (isset($cisco_version) && $cisco_version > 1) {
    print_debug("BGP4-MIB skipped because use CISCO-BGP4-MIB table version 2.");
    return;
}
if (is_device_mib($device, [ 'CUMULUS-BGPUN-MIB', 'CUMULUS-BGPVRF-MIB' ])) {
    // CUMULUS-BGPUN-MIB is copy-paste of BGP4-MIB
    return;
}

$peers_data = snmpwalk_cache_oid($device, 'bgpPeerRemoteAs', [], 'BGP4-MIB');
if ($device['os'] === 'fortigate') {
    // Fetch remote addr on fortigate and aruba devices, because incorrect indexes
    $peers_data = snmpwalk_cache_oid($device, 'bgpPeerRemoteAddr', $peers_data, 'BGP4-MIB');
}
$peers_data = snmpwalk_cache_oid($device, 'bgpPeerLocalAddr', $peers_data, 'BGP4-MIB');
$peers_data = snmpwalk_cache_oid($device, 'bgpPeerIdentifier', $peers_data, 'BGP4-MIB');
$peers_data = snmpwalk_cache_oid($device, 'bgpPeerAdminStatus', $peers_data, 'BGP4-MIB');

foreach ($peers_data as $index => $bgp4_entry) {
    parse_bgp_peer_index($bgp4_entry, $index, $mib);
    $peer_ip = $bgp4_entry['bgpPeerRemoteAddr'];

    // Process vendor specific issues
    bgp_fix_peer($device, $bgp4_entry, $mib);
    $peer_as                               = $bgp4_entry['bgpPeerRemoteAs'];
    $peers_data[$index]['bgpPeerRemoteAs'] = $peer_as;
    $local_ip                              = $bgp4_entry['bgpPeerLocalAddr'];

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
