<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * @var array $device
 * @var string $mib
 * @var array $def
 * @var array $entry
 * @var string $bgpLocalAs
 * @var array $bgp_oids
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

$bgp4_peers = snmpwalk_cache_oid($device, 'bgpPeerRemoteAs', [], 'BGP4-MIB');

// #2 - Request not completed
// #1002 - Request timeout
if (in_array(snmp_error_code(), [ OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED,
                                  OBS_SNMP_ERROR_REQUEST_TIMEOUT,
                                  OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT ], TRUE)) {
  $snmp_incomplete = TRUE;
}

$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerState']['oid'],              $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerAdminStatus']['oid'],        $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerInUpdates']['oid'],          $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerOutUpdates']['oid'],         $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerInTotalMessages']['oid'],    $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerOutTotalMessages']['oid'],   $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerFsmEstablishedTime']['oid'], $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerInUpdateElapsedTime']['oid'], $bgp4_peers, $mib);
//$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerLocalAddr']['oid'],          $bgp4_peers, $mib);
//$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerIdentifier']['oid'],         $bgp4_peers, $mib);

// Collect founded peers
if (!is_array($bgp_peers)) {
  $bgp_peers = []; // need rewrite array for fix incorrect indexes
}
foreach ($bgp4_peers as $index => $bgp4_entry) {
  $index_parts = explode('.', $index);
  if (safe_count($index_parts) > 4) {
    // Aruba case:
    // BGP4-MIB::bgpPeerRemoteAddr.4.18.1.23.109 = IpAddress: 18.1.23.109
    $ip_len = array_shift($index_parts);
    $peer_ip = implode('.', $index_parts);
  } else {
    $peer_ip = $index;
  }

  if ($device['os'] === 'arista_eos') {
    // It seems as firmware issue, always report as halted
    // See: https://jira.observium.org/browse/OBS-4382
    if ($bgp4_entry['bgpPeerAdminStatus'] === 'stop' && $bgp4_entry['bgpPeerState'] !== 'idle') {
      // running: established, connect, active (not sure about opensent, openconfirm)
      print_debug("Fixed Arista issue, BGP4-MIB::bgpPeerAdminStatus always report halted");
      $bgp4_entry['bgpPeerAdminStatus'] = 'start';
    } elseif ($bgp4_entry['bgpPeerAdminStatus'] === 'start' && $bgp4_entry['bgpPeerState'] === 'idle') {
      print_debug("Fixed Arista issue, BGP4-MIB::bgpPeerAdminStatus report running for shutdown");
      $bgp4_entry['bgpPeerAdminStatus'] = 'stop';
    }
  }

  $peer_as  = snmp_dewrap32bit($bgp4_entry['bgpPeerRemoteAs']); // Dewrap for 32bit ASN
  if ($peer_as > $bgp4_entry['bgpPeerRemoteAs']) {
    $bgp4_entry['bgpPeerRemoteAs'] = $peer_as;
  }

  $peer = [
    'index'         => $index,
    'ip'            => $peer_ip === '0.0.0.0' ? '' : $peer_ip,
    'as'            => $peer_as,
    'admin_status'  => $bgp4_entry['bgpPeerAdminStatus']
  ];
  if ($check_vrfs) {
    $peer['virtual_name'] = $vrf_name;
  }

  if (is_bgp_peer_valid($peer, $device)) {
    $p_list[$peer_ip][$peer_as] = 1;
    $bgp_peers[$peer_ip] = $bgp4_entry;

    // Unification peer (do not use for bgp4-mib)
    /*
    $peerlist[$peer_ip][$peer_as] = [];
    foreach ($bgp_oids as $bgp_oid) {
      $def_oid = str_replace('bgp', '', $bgp_oid); // bgpPeerState -> PeerState
      $peerlist[$peer_ip][$peer_as][$bgp_oid] = $cisco_entry[$def['oids'][$def_oid]['oid']];
    }
    if ($check_vrfs) {
      $peerlist[$peer_ip][$peer_as]['virtual_name'] = $vrf_name;
    }
    */
  }
}

unset($bgp4_peers);

// EOF
