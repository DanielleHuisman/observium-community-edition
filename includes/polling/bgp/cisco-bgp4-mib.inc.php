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
 * @var bool   $check_vrfs
 * @var string $vrf_name
 */

$cisco_version = $entry['cisco_version'];

if ($check_vrfs) {
    // Derp, VRFs need separate versions, need in BGP4-MIB
    $cisco_version_vrf[$vrf_name] = $entry['cisco_version'];
}

// Prefixes (Cisco v1/2)
$c_prefixes = [];
// $cbgp_defs = [ 'PeerAcceptedPrefixes', 'PeerDeniedPrefixes', 'PeerPrefixAdminLimit', 'PeerPrefixThreshold',
//                'PeerPrefixClearThreshold', 'PeerAdvertisedPrefixes', 'PeerSuppressedPrefixes', 'PeerWithdrawnPrefixes' ];
foreach ($cbgp_defs as $def_oid) {
    $c_oid = $def['oids'][$def_oid]['oid'];
    if ($cisco_version < 2) {
        $c_oid = str_replace('cbgpPeer2', 'cbgpPeer', $c_oid);
    }
    $c_prefixes = snmpwalk_cache_oid($device, $c_oid, $c_prefixes, 'CISCO-BGP4-MIB');
}

foreach ($c_prefixes as $c_index => $c_entry) {
    $index_array = explode('.', $c_index);
    if ($cisco_version < 2) {
        // v1 - IPv4 only
        // cbgpPeerAcceptedPrefixes.10.255.0.1.ipv4.unicast
        $safi    = array_pop($index_array);
        $afi     = array_pop($index_array);
        $peer_ip = implode('.', $index_array);
    } else {
        // v2
        $safi       = array_pop($index_array);
        $afi        = array_pop($index_array);
        $ip_version = array_shift($index_array);
        if ($ip_version === 'ipv6') {
            // IPv6
            // cbgpPeer2DeniedPrefixes.ipv6."20:01:07:f8:00:20:02:01:00:00:00:00:01:00:01:00".ipv6.unicast
            $peer_ip = array_shift($index_array);
        } else {
            // IPv4
            // cbgpPeer2AcceptedPrefixes.ipv4."10.255.0.1".ipv4.unicast
            $peer_ip = implode('.', $index_array);
        }
        $peer_ip = hex2ip($peer_ip);
    }
    if (isset($GLOBALS['config']['routing_afis'][$afi])) {
        $afi = $GLOBALS['config']['routing_afis'][$afi]['name'];
    }
    if (isset($GLOBALS['config']['routing_safis'][$safi])) {
        $safi = $GLOBALS['config']['routing_safis'][$safi]['name'];
    }

    foreach ($cbgp_defs as $def_oid) {
        $c_oid = $def['oids'][$def_oid]['oid'];
        if ($cisco_version < 2) {
            $c_oid = str_replace('cbgpPeer2', 'cbgpPeer', $c_oid);
        }
        $af_list[$peer_ip][$afi][$safi][$def_oid] = $c_entry[$c_oid];
    }
}

unset($c_prefixes);

if ($cisco_version > 1) {
    // Check Cisco cbgpPeer2Table
    $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2RemoteAs', [], 'CISCO-BGP4-MIB');

    if (safe_empty($cisco_peers)) {
        return;
    }

    // #2 - Request not completed
    // #1002 - Request timeout
    if (in_array(snmp_error_code(), [OBS_SNMP_ERROR_REQUEST_NOT_COMPLETED,
                                     OBS_SNMP_ERROR_REQUEST_TIMEOUT,
                                     OBS_SNMP_ERROR_BULK_REQUEST_TIMEOUT], TRUE)) {
        $snmp_incomplete = TRUE;
    }

    $cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerState']['oid'], $cisco_peers, $mib);
    $cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerAdminStatus']['oid'], $cisco_peers, $mib);
    $cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerInUpdates']['oid'], $cisco_peers, $mib);
    $cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerOutUpdates']['oid'], $cisco_peers, $mib);
    $cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerInTotalMessages']['oid'], $cisco_peers, $mib);
    $cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerOutTotalMessages']['oid'], $cisco_peers, $mib);
    $cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerFsmEstablishedTime']['oid'], $cisco_peers, $mib);
    $cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerInUpdateElapsedTime']['oid'], $cisco_peers, $mib);
    //$cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerLocalAddr']['oid'],          $cisco_peers, $mib);
    //$cisco_peers = snmpwalk_cache_oid($device, $def['oids']['PeerIdentifier']['oid'],         $cisco_peers, $mib);

    // Collect founded peers
    foreach ($cisco_peers as $index => $cisco_entry) {
        [, $peer_ip] = explode('.', $index, 2);
        $peer_ip = hex2ip($peer_ip);

        $peer_as = $cisco_entry['cbgpPeer2RemoteAs'];
        $peer    = [
          'index'        => $index,
          'ip'           => $peer_ip === '0.0.0.0' ? '' : $peer_ip,
          'as'           => $peer_as,
          'admin_status' => $cisco_entry['cbgpPeer2AdminStatus']
        ];
        if ($check_vrfs) {
            $peer['virtual_name'] = $vrf_name;
        }
        if (is_bgp_peer_valid($peer, $device)) {
            $p_list[$peer_ip][$peer_as] = 1;

            // Unify peer
            $cbgp2_peers[$peer_ip][$peer_as] = [];
            foreach ($bgp_oids as $bgp_oid) {
                $def_oid                                   = str_replace('bgp', '', $bgp_oid); // bgpPeerState -> PeerState
                $cbgp2_peers[$peer_ip][$peer_as][$bgp_oid] = $cisco_entry[$def['oids'][$def_oid]['oid']];
            }
            if ($check_vrfs) {
                $cbgp2_peers[$peer_ip][$peer_as]['virtual_name'] = $vrf_name;
            }
        } else {
            unset($cisco_peers[$index]); // Remove invalid entry for suppress force rediscover
        }
    }
    // And anyway get bgpPeerLocalAddr for fix Cisco issue with incorrect random data in cbgpPeer2LocalAddr
    //$cisco_fix   = snmpwalk_cache_oid($device, 'bgpPeerLocalAddr', array(), 'BGP4-MIB');
}

// EOF
