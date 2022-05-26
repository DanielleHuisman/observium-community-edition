<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

/**
 * @var array $device
 * @var string $mib
 * @var array $entry
 * @var string $bgpLocalAs
 * @var integer $cisco_version Come from cisco-bgp4-mib.inc.php
 * @var array $p_list
 * @var array $peerlist
 * @var bool $check_vrfs
 * @var string $vrf_name
 */

// All MIBs except CISCO-BGP4-MIB (table version 2)
if (isset($cisco_version) && $cisco_version > 1) {
  return;
}
if (is_device_mib($device, 'CUMULUS-BGPUN-MIB')) {
  // CUMULUS-BGPUN-MIB is copy-paste of BGP4-MIB
  return;
}

$peers_data = snmpwalk_cache_oid($device, 'bgpPeerRemoteAs', [], 'BGP4-MIB');
//$peers_data = snmpwalk_cache_oid($device, 'bgpPeerRemoteAddr',  $peers_data, 'BGP4-MIB');
$peers_data = snmpwalk_cache_oid($device, 'bgpPeerLocalAddr', $peers_data, 'BGP4-MIB');
$peers_data = snmpwalk_cache_oid($device, 'bgpPeerIdentifier', $peers_data, 'BGP4-MIB');
$peers_data = snmpwalk_cache_oid($device, 'bgpPeerAdminStatus', $peers_data, 'BGP4-MIB');

foreach ($peers_data as $index => $bgp4_entry) {
  $index_parts = explode('.', $index);
  if (safe_count($index_parts) > 4) {
    // Aruba case:
    // BGP4-MIB::bgpPeerRemoteAddr.4.18.1.23.109 = IpAddress: 18.1.23.109
    $ip_len = array_shift($index_parts);
    $peer_ip = implode('.', $index_parts);
  } else {
    $peer_ip = $index;
  }
  $peer_as = snmp_dewrap32bit($bgp4_entry['bgpPeerRemoteAs']); // Dewrap for 32bit ASN
  if ($peer_as > $bgp4_entry['bgpPeerRemoteAs']) {
    $peers_data[$index]['bgpPeerRemoteAs'] = $peer_as;
  }
  $local_ip = $bgp4_entry['bgpPeerLocalAddr'];

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
