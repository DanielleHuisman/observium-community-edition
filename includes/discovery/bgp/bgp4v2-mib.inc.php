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
 * @var array  $config
 * @var array  $device
 * @var string $mib
 * @var array  $entry
 * @var string $bgpLocalAs
 * @var array  $peers_data from BGP4-MIB
 * @var array  $p_list
 * @var array  $peerlist
 * @var bool   $check_vrfs
 * @var string $vrf_name
 */

// Common vendor specific
$def        = $config['mibs'][$mib]['bgp'];
$vendor_mib = $mib;

$vendor_bgp = snmpwalk_cache_oid($device, $def['oids']['PeerRemoteAs']['oid'], [], $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
if (safe_empty($vendor_bgp)) {
    $vendor_mib = FALSE; // Unset vendor_mib since not found on device

    return;
}

// PeerRemoteAddr
$vendor_oid = $def['oids']['PeerRemoteAddr']['oid'];
if (!isset($def['index'][$vendor_oid])) {
    $vendor_bgp = snmpwalk_cache_oid($device, $vendor_oid, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
}
// PeerLocalAddr
$local_ips  = [];
$vendor_oid = $def['oids']['PeerLocalAddr']['oid'];
if (!isset($def['index'][$vendor_oid]) && strlen($vendor_oid)) {
    $vendor_bgp = snmpwalk_cache_oid($device, $vendor_oid, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
} else {
    // LocalAddr trick, when not available in vendor table, by known device ip/network
    foreach (dbFetchRows('SELECT * FROM `ipv4_addresses` LEFT JOIN `ipv4_networks` USING(`ipv4_network_id`) WHERE `device_id` = ?', [$device['device_id']]) as $ipv4) {
        $local_ips['ipv4'][$ipv4['ipv4_address']] = $ipv4['ipv4_network'];
    }
    foreach (dbFetchRows('SELECT * FROM `ipv6_addresses` LEFT JOIN `ipv6_networks` USING(`ipv6_network_id`) WHERE `device_id` = ?', [$device['device_id']]) as $ipv6) {
        $local_ips['ipv6'][$ipv6['ipv6_address']] = $ipv6['ipv6_network'];
    }
    print_debug_vars($local_ips);
}
// PeerIdentifier
$vendor_oid = $def['oids']['PeerIdentifier']['oid'];
if (!isset($def['index'][$vendor_oid]) && !safe_empty($vendor_oid)) {
    $vendor_bgp = snmpwalk_cache_oid($device, $vendor_oid, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
}
// PeerLocalAs
$vendor_oid = $def['oids']['PeerLocalAs']['oid'];
if (!isset($def['index'][$vendor_oid]) && !safe_empty($vendor_oid)) {
    $vendor_bgp = snmpwalk_cache_oid($device, $vendor_oid, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
}
// PeerAdminStatus
$vendor_oid = $def['oids']['PeerAdminStatus']['oid'];
if (!isset($def['index'][$vendor_oid]) && !safe_empty($vendor_oid)) {
    $vendor_bgp = snmpwalk_cache_oid($device, $vendor_oid, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
}
// PeerRemoteAddrType
$vendor_oid = $def['oids']['PeerRemoteAddrType']['oid'];
if (!isset($def['index'][$vendor_oid])) {
    $vendor_bgp = snmpwalk_cache_oid($device, $vendor_oid, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
}
// PeerIndex
$vendor_oid = $def['oids']['PeerIndex']['oid'];
if (!isset($def['index'][$vendor_oid]) && !safe_empty($vendor_oid)) {
    $vendor_bgp = snmpwalk_cache_oid($device, $vendor_oid, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
}

// PrefixCountersSafi
$vendor_oid = $def['oids']['PrefixCountersSafi']['oid'];
if (!safe_empty($vendor_oid)) {
    $vendor_counters = snmpwalk_cache_oid($device, $vendor_oid, [], $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
} else {
    $vendor_counters = NULL;
}
//print_vars($vendor_counters);

// See possible AFI/SAFI here: https://www.juniper.net/techpubs/en_US/junos12.3/topics/topic-map/bgp-multiprotocol.html
$afis['1']    = 'ipv4';
$afis['2']    = 'ipv6';
$afis['ipv4'] = '1';
$afis['ipv6'] = '2';

//print_vars($vendor_bgp);
print_debug("$vendor_mib Peers: ");
foreach ($vendor_bgp as $idx => $vendor_entry) {
    if (!safe_empty($def['index'])) {
        parse_bgp_peer_index($vendor_entry, $idx, $vendor_mib);
    }
    $peer_ip        = hex2ip($vendor_entry[$def['oids']['PeerRemoteAddr']['oid']]);
    $peer_addr_type = 'ipv' . get_ip_version($peer_ip);
    $local_ip       = '';
    if (strlen($def['oids']['PeerLocalAddr']['oid'])) {
        $local_ip = hex2ip($vendor_entry[$def['oids']['PeerLocalAddr']['oid']]);
    } elseif (isset($local_ips[$peer_addr_type])) {
        // Trick for detect local ip by matching between device IPs network and peer ip
        // Actually for Huawei BGP peers
        foreach ($local_ips[$peer_addr_type] as $ip => $network) {
            if (match_network($peer_ip, $network)) {
                $local_ip = $ip;
                print_debug("Local IP: $ip. Matched Peer IP [$peer_ip] with device network [$network].");
                break;
            }
        }
    }

    // Process vendor specific issues
    bgp_fix_peer($device, $vendor_entry, $vendor_mib);

    $local_as = isset($vendor_entry[$def['oids']['PeerLocalAs']['oid']]) ? snmp_dewrap32bit($vendor_entry[$def['oids']['PeerLocalAs']['oid']]) : $bgpLocalAs;
    $peer_as  = $vendor_entry[$def['oids']['PeerRemoteAs']['oid']];

    // index
    $vendor_oid = $def['oids']['PeerIndex']['oid'];
    if (empty($vendor_oid)) {
        $index = $idx;
    } else {
        $index = $vendor_entry[$vendor_oid];
    }
    $peer = [
      'mib'          => $mib,
      'index'        => $index,
      'identifier'   => $vendor_entry[$def['oids']['PeerIdentifier']['oid']],
      'local_ip'     => $local_ip,
      'local_as'     => $local_as,
      'ip'           => $peer_ip === '0.0.0.0' ? '' : $peer_ip,
      'as'           => $peer_as,
      'admin_status' => $vendor_entry[$def['oids']['PeerAdminStatus']['oid']]
    ];
    if ($check_vrfs) {
        $peer['virtual_name'] = $vrf_name;
    }
    if (!isset($p_list[$peer_ip][$peer_as]) && is_bgp_peer_valid($peer, $device)) {
        // Fix possible 32bit ASN for peers from BGP4-MIB
        // Brocade example:
        //                                     BGP4-MIB::bgpPeerRemoteAs.27.122.122.4 = 23456
        //  FOUNDRY-BGPV2-MIB::bgp4V2PeerRemoteAs.1.1.4.27.122.122.5.1.4.27.122.122.4 = 133189
        if (isset($p_list[$peer_ip])) {
            unset($p_list[$peer_ip]);                                 // Clean old peer list
            $bgp4_peer_as = $peers_data[$peer_ip]['bgpPeerRemoteAs']; // BGP4-MIB
            if ($peer_as > $bgp4_peer_as) {
                //$peers_data[$peer_ip]['bgpPeerRemoteAs'] = $peer_as;
                // Yah, need to found and remove duplicate peer from $peerlist
                foreach ($peerlist as $key => $tmp) {
                    if ($tmp['ip'] == $peer_ip && $tmp['as'] == $bgp4_peer_as) {
                        unset($peerlist[$key]);
                        break;
                    }
                }
            }
        }

        $p_list[$peer_ip][$peer_as] = 1;
        $peerlist[]                 = $peer;
        print_debug("Found peer IP: $peer_ip (AS$peer_as, LocalIP: $local_ip)");
    } elseif (isset($p_list[$peer_ip][$peer_as]) && $local_as != $bgpLocalAs) {
        // Find and replace local_as key in peer list if different local as
        // FIXME, Yah, $peerlist stored as simple array without indexed key, that why used derp per-peer loop
        foreach ($peerlist as $key => $tmp) {
            if ($tmp['ip'] == $peer_ip && $tmp['as'] == $peer_as) {
                $peerlist[$key]['local_as'] = $local_as;
                print_debug("Replaced Local AS for peer: $peer_ip (AS$peer_as, LocalIP: $local_ip) - AS$bgpLocalAs -> AS$local_as");
                break;
            }
        }
    } else {
        print_debug("Vendor peer already found: $peer_ip");
        print_debug_vars($peer);
    }

    // AFI/SAFI
    $vendor_oid = isset($def['index']['afi']) ? $def['index']['afi'] : $def['oids']['PeerRemoteAddrType']['oid'];
    $afi        = $vendor_entry[$vendor_oid];

    if (isset($def['index']['safi'])) {
        $safi = $vendor_entry[$def['index']['safi']];
        // Here each table entry is uniq afi/safi, see HUAWEI-BGP-VPN-MIB
        if (isset($vendor_counters[$index])) {
            //$peer_afis[$peer_ip][$afi][$safi] = 1;
            $peer_afis[$peer_ip][] = ['afi' => $afi, 'safi' => $safi, 'index' => $index];
            //discovery_bgp_afisafi($device, $entry, $afi, $safi, $af_list);
        }
        continue;
    }
    if ($vendor_mib === 'VIPTELA-OPER-BGP') {
        // This mib has only one possible AFI/SAFI
        if (isset($vendor_counters[$index . '.0'])) {
            //$peer_afis[$peer_ip][$afi][$safi] = 1;
            $peer_afis[$peer_ip][] = ['afi' => 'ipv4', 'safi' => 'unicast'];
        }
        continue;
    }

    // Here can be multiple table entries for different afi/safi
    foreach ($config['routing_safis'] as $i => $safi_def) {
        $safi = $safi_def['name'];
        if (is_numeric($afi)) {
            $afi_num = $afi;
            $afi     = $config['routing_afis'][$afi]['name'];
        } else {
            $afi_num = $config['routing_afis_name'][$afi];
        }

        if (isset($vendor_counters["$index.$afi_num.$i"])) {
            //$peer_afis[$peer_ip][$afi][$safi] = 1;
            $peer_afis[$peer_ip][] = ['afi' => $afi, 'safi' => $safi, 'index' => $index];
            //discovery_bgp_afisafi($device, $entry, $afi, $safi, $af_list);
        } else {
            print_debug("Did not find AFI/SAFI with index $index.$afi_num.$i");
        }
    }
}

// EOF
