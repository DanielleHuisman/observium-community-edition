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
 * @var array   $device
 * @var string  $mib
 * @var array   $def
 * @var array   $entry
 * @var string  $bgpLocalAs
 * @var array   $bgp_oids
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

$bgp4_peers = snmpwalk_cache_oid($device, 'bgpPeerRemoteAs', [], 'BGP4-MIB');

// #2 - Request not completed
// #1002 - Request timeout
if (in_array(snmp_error_code(), [OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED,
                                 OBS_SNMP_ERROR_REQUEST_TIMEOUT,
                                 OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT], TRUE)) {
    $snmp_incomplete = TRUE;
}

if ($device['os'] === 'fortigate') {
    // Fetch remote addr on fortigate and aruba devices, because incorrect indexes
    $bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerRemoteAddr']['oid'], $bgp4_peers, $mib);
}
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerState']['oid'], $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerAdminStatus']['oid'], $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerInUpdates']['oid'], $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerOutUpdates']['oid'], $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerInTotalMessages']['oid'], $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerOutTotalMessages']['oid'], $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerFsmEstablishedTime']['oid'], $bgp4_peers, $mib);
$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerInUpdateElapsedTime']['oid'], $bgp4_peers, $mib);
//$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerLocalAddr']['oid'],          $bgp4_peers, $mib);
//$bgp4_peers = snmpwalk_cache_oid($device, $def['oids']['PeerIdentifier']['oid'],         $bgp4_peers, $mib);

// Collect founded peers
if (!is_array($bgp_peers)) {
    $bgp_peers = []; // need rewrite array for fix incorrect indexes
}
foreach ($bgp4_peers as $index => $bgp4_entry) {
    parse_bgp_peer_index($bgp4_entry, $index, $mib);
    $peer_ip = $bgp4_entry['bgpPeerRemoteAddr'];

    // Process vendor specific issues
    bgp_fix_peer($device, $bgp4_entry, $mib);
    $peer_as = $bgp4_entry['bgpPeerRemoteAs'];

    $peer = [
      'index'        => $index,
      'ip'           => $peer_ip === '0.0.0.0' ? '' : $peer_ip,
      'as'           => $peer_as,
      'admin_status' => $bgp4_entry['bgpPeerAdminStatus']
    ];
    if ($check_vrfs) {
        $peer['virtual_name'] = $vrf_name;
    }

    if (is_bgp_peer_valid($peer, $device)) {
        $p_list[$peer_ip][$peer_as] = 1;
        $bgp_peers[$peer_ip]        = $bgp4_entry;

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
