<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * @var array  $device
 * @var string $mib
 * @var array  $def
 * @var array  $entry
 * @var string $bgpLocalAs
 * @var array  $bgp_oids
 * @var array  $p_list
 * @var array  $peerlist
 * @var bool   $check_vrfs
 * @var string $vrf_name
 */

// NOTE. This mib deprecated since Cumulus 5.1

$bgp4_peers = snmpwalk_cache_oid($device, 'bgpPeerRemoteAs', [], 'CUMULUS-BGPUN-MIB');

// #2 - Request not completed
// #1002 - Request timeout
if (in_array(snmp_error_code(), [ OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED,
                                  OBS_SNMP_ERROR_REQUEST_TIMEOUT,
                                  OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT ], TRUE)) {
    $snmp_incomplete = TRUE;
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
    $peer_ip = $index;
    $peer_as = snmp_dewrap32bit($bgp4_entry['bgpPeerRemoteAs']); // Dewrap for 32bit ASN
    if ($peer_as > $bgp4_entry['bgpPeerRemoteAs']) {
        $bgp4_entry['bgpPeerRemoteAs'] = $peer_as;
    }

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
