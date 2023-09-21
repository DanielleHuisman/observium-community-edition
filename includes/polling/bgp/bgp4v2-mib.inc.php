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
 * @var array  $peers_data from BGP4-MIB
 * @var array  $p_list
 * @var array  $peerlist
 * @var bool   $check_vrfs
 * @var string $vrf_name
 */

// Common vendor specific
$vendor_mib = $mib;

$vendor_bgp = snmpwalk_cache_oid($device, $def['oids']['PeerRemoteAs']['oid'], [], $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
if (safe_empty($vendor_bgp)) {
    $vendor_mib = FALSE; // Unset vendor_mib since not found on device

    return;
}

$v_oids = [
  'PeerState', 'PeerAdminStatus', 'PeerInUpdates', 'PeerOutUpdates',
  'PeerInTotalMessages', 'PeerOutTotalMessages',
  'PeerFsmEstablishedTime', 'PeerInUpdateElapsedTime'
];
foreach ($v_oids as $oid) {
    $vendor_oid = $def['oids'][$oid]['oid'];
    //print_vars($oid);
    if (!isset($def['index'][$vendor_oid]) && !safe_empty($vendor_oid)) {
        $vendor_bgp = snmpwalk_cache_oid($device, $vendor_oid, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    }
}
// Fetch vendor specific counters
$vendor_counters = [];
$v_oids          = ['PeerAcceptedPrefixes', 'PeerDeniedPrefixes', 'PeerAdvertisedPrefixes'];
foreach ($v_oids as $oid) {
    $vendor_oid = $def['oids'][$oid]['oid'];
    //print_vars($oid);
    if (!safe_empty($vendor_oid)) {
        $vendor_counters = snmpwalk_cache_oid($device, $vendor_oid, $vendor_counters, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    }
}

unset($vendor_oid);

// Collect founded peers and rewrite to pretty array.
foreach ($vendor_bgp as $idx => $vendor_entry) {
    if (!safe_empty($def['index'])) {
        parse_bgp_peer_index($vendor_entry, $idx, $vendor_mib);
    }
    $peer_ip = hex2ip($vendor_entry[$def['oids']['PeerRemoteAddr']['oid']]);

    // Process vendor specific issues
    bgp_fix_peer($device, $vendor_entry, $vendor_mib);

    //$vendor_entry[$vendor_PeerLocalAddr] = hex2ip($vendor_entry[$vendor_PeerLocalAddr]);
    $vendor_entry['idx'] = $idx;
    $peer_as             = $vendor_entry[$def['oids']['PeerRemoteAs']['oid']];
    $peer                = [
      'ip'           => $peer_ip === '0.0.0.0' ? '' : $peer_ip,
      'as'           => $peer_as,
      'admin_status' => $vendor_entry[$def['oids']['PeerAdminStatus']['oid']]
    ];
    if ($check_vrfs) {
        $peer['virtual_name'] = $vrf_name;
    }

    if (is_bgp_peer_valid($peer, $device)) {
        // Fix possible 32bit ASN for peers from BGP4-MIB
        // Brocade example:
        //                                     BGP4-MIB::bgpPeerRemoteAs.27.122.122.4 = 23456
        //  FOUNDRY-BGPV2-MIB::bgp4V2PeerRemoteAs.1.1.4.27.122.122.5.1.4.27.122.122.4 = 133189
        if (isset($p_list[$peer_ip]) && !isset($p_list[$peer_ip][$peer_as])) {
            unset($p_list[$peer_ip]);                                 // Clean old peer list
            $bgp4_peer_as = $bgp_peers[$peer_ip]['bgpPeerRemoteAs'];  // BGP4-MIB
            if ($peer_as > $bgp4_peer_as) {
                print_debug("Fixed incorrect BGP4-MIB::bgpPeerRemoteAs on peer $peer_ip: $bgp4_peer_as -> $peer_as");
                $bgp_peers[$peer_ip]['bgpPeerRemoteAs'] = $peer_as;
            }
        }
        if (isset($bgp_peers[$peer_ip]) && $bgp_peers[$peer_ip]['bgpPeerRemoteAs'] == $peer_as) {
            // Compare and fix some timers
            $bgp4_time   = $bgp_peers[$peer_ip]['bgpPeerFsmEstablishedTime'];
            $vendor_time = $vendor_entry[$def['oids']['PeerFsmEstablishedTime']['oid']];
            if (($bgp4_time > $vendor_time) && (($bgp4_time - $vendor_time) > 100000)) {
                //                                      BGP4-MIB::bgpPeerFsmEstablishedTime.91.210.44.226 = 4207739831
                // FOUNDRY-BGP4V2-MIB::bgp4V2PeerFsmEstablishedTime.1.1.4.91.210.44.225.1.4.91.210.44.226 = 20146719
                print_debug("Fixed incorrect BGP4-MIB::bgpPeerFsmEstablishedTime on peer $peer_ip: $bgp4_time -> $vendor_time");
                $bgp_peers[$peer_ip]['bgpPeerFsmEstablishedTime'] = $vendor_time;
            }
            $bgp4_time   = $bgp_peers[$peer_ip]['bgpPeerInUpdateElapsedTime'];
            $vendor_time = $vendor_entry[$def['oids']['PeerInUpdateElapsedTime']['oid']];
            if (($bgp4_time > $vendor_time) && (($bgp4_time - $vendor_time) > 100000)) {
                //                                       BGP4-MIB::bgpPeerInUpdateElapsedTime.91.210.44.226 = 1219919
                // FOUNDRY-BGP4V2-MIB::bgp4V2PeerInUpdatesElapsedTime.1.1.4.91.210.44.225.1.4.91.210.44.226 = 1219920
                print_debug("Fixed incorrect BGP4-MIB::bgpPeerInUpdateElapsedTime on peer $peer_ip: $bgp4_time -> $vendor_time");
                $bgp_peers[$peer_ip]['bgpPeerInUpdateElapsedTime'] = $vendor_time;
            }
        }

        $p_list[$peer_ip][$peer_as] = 1;
        //$vendor_peers[$peer_ip][$peer_as] = $vendor_entry;

        // Unify peer
        $vendor_peers[$peer_ip][$peer_as] = ['idx' => $idx];
        foreach ($bgp_oids as $bgp_oid) {
            $def_oid = str_replace('bgp', '', $bgp_oid); // bgpPeerState -> PeerState
            if (isset($def['oids'][$def_oid]['transform'])) {
                $vendor_peers[$peer_ip][$peer_as][$bgp_oid] = string_transform($vendor_entry[$def['oids'][$def_oid]['oid']], $def['oids'][$def_oid]['transform']);
            } else {
                $vendor_peers[$peer_ip][$peer_as][$bgp_oid] = $vendor_entry[$def['oids'][$def_oid]['oid']];
            }
        }
        if ($check_vrfs) {
            $vendor_peers[$peer_ip][$peer_as]['virtual_name'] = $vrf_name;
        }
    }
}

// EOF
