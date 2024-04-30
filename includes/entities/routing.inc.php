<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * Validate BGP peer
 *
 * @param array $peer   BGP peer array from discovery or polling process
 * @param array $device Common device array
 *
 * @return boolean TRUE if peer array valid
 */
function is_bgp_peer_valid($peer, $device)
{
    $valid = TRUE;

    if (isset($peer['admin_status']) && empty($peer['admin_status'])) {
        $valid = FALSE;
        print_debug("Peer ignored (by empty Admin Status).");
    }

    if ($valid && !(is_numeric($peer['as']) && $peer['as'] != 0)) {
        $valid = FALSE;
        print_debug("Peer ignored (by invalid AS number '" . $peer['as'] . "').");
    }

    if ($valid && !get_ip_version($peer['ip'])) {
        $valid = FALSE;
        print_debug("Peer ignored (by invalid Remote IP '" . $peer['ip'] . "').");
    }

    return $valid;
}

/**
 * Detect is BGP AS number in private range, see:
 * https://tools.ietf.org/html/rfc6996
 * https://tools.ietf.org/html/rfc7300
 *
 * @param string|int $as AS number
 *
 * @return boolean TRUE if AS number in private range
 */
function is_bgp_as_private($as)
{
    $as = bgp_asdot_to_asplain($as); // Convert ASdot to ASplain

    // Note 65535 and 5294967295 not really Private ASNs,
    // this is Reserved for use by Well-known Communities
    $private = ($as >= 64512 && $as <= 65535) ||         // 16-bit private ASn
               ($as >= 4200000000 && $as <= 5294967295); // 32-bit private ASn

    return $private;
}

/**
 * Convert AS number from asplain to asdot format (for 32bit ASn).
 *
 * @param string|int|array $as AS number in plain or dot format
 *
 * @return string|array AS number in dot format (for 32bit ASn)
 */
function bgp_asplain_to_asdot($as)
{
    if (is_array($as)) {
        // Recursive for arrays
        $return = [];
        foreach ($as as $entry) {
            $return[] = bgp_asplain_to_asdot($entry);
        }
        return $return;
    }

    if (str_contains($as, '.') || // Already asdot format
        ($as < 65536)) {          // 16bit ASn no need to formatting
        return $as;
    }

    $as2 = $as % 65536;
    $as1 = ($as - $as2) / 65536;

    return (int)$as1 . '.' . (int)$as2;
}

/**
 * Convert AS number from asdot to asplain format (for 32bit ASn).
 *
 * @param string|int|array $as AS number in plain or dot format
 *
 * @return string|array AS number in plain format (for 32bit ASn)
 */
function bgp_asdot_to_asplain($as)
{
    if (is_array($as)) {
        // Recursive for arrays
        $return = [];
        foreach ($as as $entry) {
            $return[] = bgp_asdot_to_asplain($entry);
        }
        return $return;
    }

    if (!str_contains($as, '.')) { // Already asplain format
        return $as;
    }

    [$as1, $as2] = explode('.', $as, 2);
    $as = $as1 * 65536 + $as2;

    return (string)$as;
}

/**
 * @param array $device
 *
 * @return array
 */
function get_bgp_localas_array($device)
{
    global $config, $attribs;

    if (OBS_PROCESS_NAME === 'poller') {
        // In poller return cached array
        if (isset($attribs['bgp_localas'])) {
            print_debug("Get Cached BGP LocalAs array.");
            $return = safe_json_decode($attribs['bgp_localas']);
            print_debug_vars($return);
            return $return;
        }
        if (safe_empty($device['bgpLocalAs']) || $device['bgpLocalAs'] == 0) {
            // Old empty peers (before rewrite or caching)
            print_debug("BGP not discovered on device.");
            return [];
        }
    } else {
        $db_bgp_localas = get_entity_attrib('device', $device['device_id'], 'bgp_localas');
    }

    // use correct MIBs order:
    // first always CISCO-BGP4-MIB (before BGP4-MIB)
    // second BGP4-MIB, then all others
    $bgp_mibs = [];
    foreach (get_device_mibs_permitted($device) as $mib) {
        // check bgp definitions
        if (!isset($config['mibs'][$mib]['bgp'])) {
            continue;
        }

        if ($mib === 'CISCO-BGP4-MIB') {
            array_unshift($bgp_mibs, $mib);
        } elseif ($mib === 'BGP4-MIB') {
            if ($bgp_mibs[0] === 'CISCO-BGP4-MIB') {
                // put BGP4-MIB after CISCO-BGP4-MIB
                array_splice($bgp_mibs, 1, 0, $mib);
            } else {
                array_unshift($bgp_mibs, $mib);
            }
        } else {
            $bgp_mibs[] = $mib;
        }
    }
    print_debug_vars($bgp_mibs);

    if (empty($bgp_mibs)) {
        if (!safe_empty($db_bgp_localas)) {
            // Clean cache when BGP removed
            del_entity_attrib('device', $device['device_id'], 'bgp_localas');
        }
        return [];
    }

    // Common LocalAs before all
    $bgp4_local_as = snmp_get_oid($device, 'bgpLocalAs.0', 'BGP4-MIB');
    if (!snmp_status() && $device['os'] === 'fortigate') {
        // Strange case on Fortigate devices
        // BGP4-MIB::bgpVersion.1 = Hex-STRING: 10
        // BGP4-MIB::bgpLocalAs.1 = INTEGER: 65403
        // BGP4-MIB::bgpIdentifier.1 = IpAddress: 4.118.140.2
        $bgp4_local_as = snmp_getnext_oid($device, 'bgpLocalAs', 'BGP4-MIB');
        //$bgp4_local_as = snmp_get_oid($device, 'bgpLocalAs.1', 'BGP4-MIB');
    }
    $bgp4_mib = is_numeric($bgp4_local_as);

    $vrf_contexts = get_device_vrf_contexts($device); // SNMP VRF context discovered for device
    $entries = [];
    foreach ($bgp_mibs as $mib) {
        $def = $config['mibs'][$mib]['bgp'];

        // Main
        if ($mib === 'CISCO-BGP4-MIB') {
            // Cisco can report Afi/Safi but without cbgpLocalAs.0 and cbgpPeer2RemoteAs,
            // Not sure, probably check cbgpPeerAcceptedPrefixes / cbgpPeer2AcceptedPrefixes here
            if ($local_as = snmp_get_oid($device, 'cbgpLocalAs.0', $mib)) {
                $ctest         = snmp_getnext_oid($device, 'cbgpPeer2AcceptedPrefixes', $mib);
                $cisco_version = safe_empty($ctest) ? 1 : 2;
                $entries[]     = ['mib' => $mib, 'oid' => 'cbgpLocalAs.0', 'LocalAs' => snmp_dewrap32bit($local_as), 'cisco_version' => $cisco_version];
            } else {
                $local_as = snmp_getnext_oid($device, 'cbgpPeer2LocalAs', $mib);
                // cbgpPeer2LocalAs in many devices is zero, but peers exist
                if (is_numeric($local_as)) {
                    $entries[] = [ 'mib' => $mib, 'oid' => 'cbgpPeer2LocalAs', 'LocalAs' => snmp_dewrap32bit($local_as), 'cisco_version' => 2 ];
                } elseif ($bgp4_mib) {
                    // Only check AcceptedPrefixes
                    $ctest         = snmp_getnext_oid($device, 'cbgpPeer2AcceptedPrefixes', $mib);
                    $cisco_version = 2;
                    if (!snmp_status()) {
                        $ctest         = snmp_getnext_oid($device, 'cbgpPeerAcceptedPrefixes', $mib);
                        $cisco_version = 1;
                        // CISCO-BGP4-MIB not exist
                        if (!snmp_status()) {
                            continue;
                        }
                    }
                    $entries[] = [ 'mib' => $mib, 'cisco_version' => $cisco_version ];
                }
            }
        } elseif ($mib === 'BGP4-MIB') {
            if ($bgp4_mib) {
                $entries[] = [ 'mib' => $mib, 'oid' => $def['oids']['LocalAs']['oid'], 'LocalAs' => snmp_dewrap32bit($bgp4_local_as) ];
            }
        } elseif ($mib === 'CUMULUS-BGPVRF-MIB') {
            // This mib use per-vrf indexes
            // CUMULUS-BGPVRF-MIB::bgpVrfTable.254 = INTEGER: 254
            // CUMULUS-BGPVRF-MIB::bgpVrfTable.1001 = INTEGER: 1001
            // CUMULUS-BGPVRF-MIB::bgpVrfId.254 = INTEGER: 0
            // CUMULUS-BGPVRF-MIB::bgpVrfId.1001 = INTEGER: 37
            // CUMULUS-BGPVRF-MIB::bgpVrfName.254 = STRING: default
            // CUMULUS-BGPVRF-MIB::bgpVrfName.1001 = STRING: mgmt
            foreach (snmpwalk_cache_oid($device, 'bgpVrfName', [], $mib) as $vrf_index => $entry) {
                $local_as = snmp_get_oid($device, $def['oids']['LocalAs']['oid'] . '.' . $vrf_index, $mib);
                if (is_numeric($local_as)) {
                    $entries[] = [ 'mib' => $mib, 'oid' => $def['oids']['LocalAs']['oid'], 'LocalAs' => snmp_dewrap32bit($local_as),
                        'vrf_index' => $vrf_index, 'vrf_name' => $entry['bgpVrfName'] ];
                }
            }
        } elseif (isset($def['oids']['LocalAs']['oid'])) {
            // Vendor definitions
            $local_as = snmp_get_oid($device, $def['oids']['LocalAs']['oid'], $mib);
            if (is_numeric($local_as)) {
                $entries[] = [ 'mib' => $mib, 'oid' => $def['oids']['LocalAs']['oid'], 'LocalAs' => snmp_dewrap32bit($local_as) ];
            }

        } elseif (isset($def['oids']['LocalAs']['oid_next'])) {
            // Vendor definitions
            $local_as = snmp_getnext_oid($device, $def['oids']['LocalAs']['oid_next'], $mib);
            if (is_numeric($local_as)) {
                $entries[] = [ 'mib' => $mib, 'oid' => $def['oids']['LocalAs']['oid_next'], 'LocalAs' => snmp_dewrap32bit($local_as) ];
            }

        } elseif ($bgp4_mib) {
            // When MIB specific LocalAs not exist, but BGP4-MIB already return valid LocasAs
            // append this mib to entries, ie HUAWEI-BGP-VPN-MIB, CUMULUS-BGPUN-MIB
            snmp_getnext_oid($device, $def['oids']['PeerRemoteAs']['oid'], $mib);
            if (snmp_status()) {
                $entry = [ 'mib' => $mib ];
                if ($mib === 'CUMULUS-BGPUN-MIB' && $local_as = snmp_get_oid($device, 'bgpLocalAs', $mib)) {
                    // Cumulus OS not always return LocalAs
                    $entry['oid']     = 'bgpLocalAs';
                    $entry['LocalAs'] = snmp_dewrap32bit($local_as); // .1.3.6.1.4.1.40310.4.2 = INTEGER: 64732
                }
                $entries[] = $entry;
                unset($entry);
            }
        }

        // Check in Virtual Routing
        if ($vrf_contexts) { // SNMP VRF context discovered for device

            $bgp4_mib_vrf = [];
            foreach ($vrf_contexts as $vrf_name => $snmp_virtual) {
                print_debug("Check LocalAs in Virtual Routing: $vrf_name...");

                $device_vrf = snmp_virtual_device($device, $snmp_virtual);

                // Common LocalAs before all
                $bgp4_local_as = snmp_get_oid($device_vrf, 'bgpLocalAs.0', 'BGP4-MIB');
                if (!snmp_status() && $device['os'] === 'fortigate') {
                    // Strange case on Fortigate devices
                    // BGP4-MIB::bgpVersion.1 = Hex-STRING: 10
                    // BGP4-MIB::bgpLocalAs.1 = INTEGER: 65403
                    // BGP4-MIB::bgpIdentifier.1 = IpAddress: 4.118.140.2
                    $bgp4_local_as = snmp_getnext_oid($device_vrf, 'bgpLocalAs', 'BGP4-MIB');
                    //$bgp4_local_as = snmp_get_oid($device_vrf, 'bgpLocalAs.1', 'BGP4-MIB');
                }
                $bgp4_mib_vrf[$vrf_name] = is_numeric($bgp4_local_as);

                if ($mib === 'CISCO-BGP4-MIB') {
                    // Cisco can report Afi/Safi but without cbgpLocalAs.0 and cbgpPeer2RemoteAs,
                    // Not sure, probably check cbgpPeerAcceptedPrefixes / cbgpPeer2AcceptedPrefixes here
                    if ($local_as = snmp_get_oid($device_vrf, 'cbgpLocalAs.0', $mib)) {
                        $ctest         = snmp_getnext_oid($device_vrf, 'cbgpPeer2AcceptedPrefixes', $mib);
                        $cisco_version = safe_empty($ctest) ? 1 : 2;
                        $entries[]     = [ 'mib'           => $mib,
                                           'oid'           => 'cbgpLocalAs.0',
                                           'LocalAs'       => snmp_dewrap32bit($local_as),
                                           'cisco_version' => $cisco_version,
                                           'virtual_name'  => $vrf_name,
                                           'snmp_context'  => $snmp_virtual ];
                    } else {
                        $local_as = snmp_getnext_oid($device_vrf, 'cbgpPeer2LocalAs', $mib);
                        // cbgpPeer2LocalAs in many devices is zero, but peers exist
                        if (is_numeric($local_as)) {
                            $entries[] = [ 'mib'           => $mib,
                                           'oid'           => 'cbgpPeer2LocalAs',
                                           'LocalAs'       => snmp_dewrap32bit($local_as),
                                           'cisco_version' => 2,
                                           'virtual_name'  => $vrf_name,
                                           'snmp_context'  => $snmp_virtual ];
                        } elseif ($bgp4_mib_vrf[$vrf_name]) {
                            // Only check AcceptedPrefixes
                            $ctest         = snmp_getnext_oid($device_vrf, 'cbgpPeer2AcceptedPrefixes', $mib);
                            $cisco_version = 2;
                            if (!snmp_status()) {
                                $ctest         = snmp_getnext_oid($device_vrf, 'cbgpPeerAcceptedPrefixes', $mib);
                                $cisco_version = 1;
                                // CISCO-BGP4-MIB not exist
                                if (!snmp_status()) {
                                    continue;
                                }
                            }
                            $entries[] = [ 'mib'           => $mib,
                                           'cisco_version' => $cisco_version,
                                           'virtual_name'  => $vrf_name,
                                           'snmp_context'  => $snmp_virtual ];
                        }
                    }
                } elseif ($mib === 'BGP4-MIB') {
                    if ($bgp4_mib_vrf[$vrf_name]) {
                        $entries[] = [ 'mib'          => $mib,
                                       'oid'          => $def['oids']['LocalAs']['oid'],
                                       'LocalAs'      => snmp_dewrap32bit($bgp4_local_as),
                                       'virtual_name' => $vrf_name,
                                       'snmp_context' => $snmp_virtual ];
                    }
                } elseif (isset($def['oids']['LocalAs']['oid'])) {

                    $local_as = snmp_get_oid($device_vrf, $def['oids']['LocalAs']['oid'], $mib);
                    if (is_numeric($local_as)) {
                        $entries[] = [ 'mib'          => $mib,
                                       'oid'          => $def['oids']['LocalAs']['oid'],
                                       'LocalAs'      => snmp_dewrap32bit($local_as),
                                       'virtual_name' => $vrf_name,
                                       'snmp_context' => $snmp_virtual];
                    }

                } elseif (isset($def['oids']['LocalAs']['oid_next'])) {

                    $local_as = snmp_getnext_oid($device_vrf, $def['oids']['LocalAs']['oid_next'], $mib);
                    if (is_numeric($local_as)) {
                        $entries[] = [ 'mib'          => $mib,
                                       'oid'          => $def['oids']['LocalAs']['oid_next'],
                                       'LocalAs'      => snmp_dewrap32bit($local_as),
                                       'virtual_name' => $vrf_name,
                                       'snmp_context' => $snmp_virtual];
                    }

                } elseif (isset($bgp4_mib_vrf[$vrf_name])) {
                    // When MIB specific LocalAs not exist, but BGP4-MIB already return valid LocasAs
                    // append this mib to entries, ie HUAWEI-BGP-VPN-MIB
                    snmp_getnext_oid($device_vrf, $def['oids']['PeerRemoteAs']['oid'], $mib);
                    if (snmp_status()) {
                        $entries[] = [ 'mib' => $mib, 'virtual_name' => $vrf_name, 'snmp_context' => $snmp_virtual ];
                    }
                }
            }
        }
    }
    print_debug_vars($entries);

    // Cache entries (in discovery)
    if (safe_count($entries)) {
        $attrib = safe_json_encode($entries);
        if ($attrib !== $db_bgp_localas) {
            set_entity_attrib('device', $device['device_id'], 'bgp_localas', safe_json_encode($entries));
            if (OBS_PROCESS_NAME === 'poller') {
                $attribs['bgp_localas'] = safe_json_encode($entries);
            }
        }
    } elseif (!safe_empty($db_bgp_localas)) {
        // Clean cache when BGP removed
        del_entity_attrib('device', $device['device_id'], 'bgp_localas');
    }

    return $entries;
}

/**
 * Convert BGP peer index to vendor MIB specific entries
 *
 * @param array  $peer  Array with walked peer oids
 * @param string $index Peer index
 * @param string $mib   MIB name
 */
function parse_bgp_peer_index(&$peer, $index, $mib = 'BGP4V2-MIB')
{
    global $config;

    $address_types = $config['mibs']['INET-ADDRESS-MIB']['rewrite']['InetAddressType'];
    $index_parts   = explode('.', $index);
    switch ($mib) {
        case 'BGP4-MIB':
            // bgpPeerRemoteAddr
            $peer_ip = $index;
            if (safe_count($index_parts) > 4) {
                if ($index_parts[0] == '4') {
                    // Aruba case:
                    // BGP4-MIB::bgpPeerRemoteAddr.4.18.1.23.109 = IpAddress: 18.1.23.109
                    array_shift($index_parts);
                } else {
                    // Fortigate case:
                    // BGP4-MIB::bgpPeerRemoteAddr.10.1.250.1.1 = IpAddress: 10.1.250.1
                    array_pop($index_parts);
                }
                $peer_ip = implode('.', $index_parts);
            }
            if (empty($peer['bgpPeerRemoteAddr']) && get_ip_version($peer_ip)) {
                $peer['bgpPeerRemoteAddr'] = $peer_ip;
            }
            break;

        case 'ARISTA-BGP4V2-MIB':
            // 1. aristaBgp4V2PeerInstance
            $peer['aristaBgp4V2PeerInstance'] = array_shift($index_parts);
            // 2. aristaBgp4V2PeerRemoteAddrType
            $peer_addr_type = array_shift($index_parts);
            if (safe_empty($peer['aristaBgp4V2PeerRemoteAddrType'])) {
                $peer['aristaBgp4V2PeerRemoteAddrType'] = $peer_addr_type;
            }
            if (isset($address_types[$peer['aristaBgp4V2PeerRemoteAddrType']])) {
                $peer['aristaBgp4V2PeerRemoteAddrType'] = $address_types[$peer['aristaBgp4V2PeerRemoteAddrType']];
            }
            // 3. length of the IP address
            $ip_len = array_shift($index_parts);
            // 4. IP address
            $ip_parts = array_slice($index_parts, 0, $ip_len);

            // 5. aristaBgp4V2PeerRemoteAddr
            $peer_ip = implode('.', $ip_parts);
            if ((int)$ip_len === 16) {
                $peer_ip = snmp2ipv6($peer_ip);
            }
            if ($peer_addr_type = get_ip_version($peer_ip)) {
                $peer['aristaBgp4V2PeerRemoteAddr']     = $peer_ip;
                $peer['aristaBgp4V2PeerRemoteAddrType'] = 'ipv' . $peer_addr_type; // FIXME. not sure, but seems as Arista use only ipv4/ipv6 for afi
            }
            break;

        case 'BGP4V2-MIB': // FRR
            // BGP4V2-MIB::bgp4V2PeerRemoteAddr.1.4.95.130.232.2 = Hex-STRING: 5F 82 E8 02
            // BGP4V2-MIB::bgp4V2PeerRemoteAddr.2.16.42.2.11.112.0.0.5.3.0.0.0.0.0.0.0.2 = Hex-STRING: 2A 02 0B 70 00 00 05 03 00 00 00 00 00 00 00 02
            // 1. bgp4V2PeerInstance
            $peer['bgp4V2PeerInstance'] = array_shift($index_parts);
            // 2. length of the IP address
            $ip_len = array_shift($index_parts);
            // 3. bgp4V2PeerRemoteAddr
            $peer_ip = implode('.', $index_parts);
            if ((int)$ip_len === 16) {
                $peer_ip = snmp2ipv6($peer_ip);
            }
            if ($peer_addr_type = get_ip_version($peer_ip)) {
                $peer['bgp4V2PeerRemoteAddr']     = $peer_ip;
                $peer['bgp4V2PeerRemoteAddrType'] = 'ipv' . $peer_addr_type;
            }
            break;

        case 'FOUNDRY-BGP4V2-MIB': // BGP4V2-MIB draft
            // 1. bgp4V2PeerInstance
            $peer['bgp4V2PeerInstance'] = array_shift($index_parts);
            // 2. bgp4V2PeerLocalAddrType
            $local_addr_type = array_shift($index_parts);
            if (safe_empty($peer['bgp4V2PeerLocalAddrType'])) {
                $peer['bgp4V2PeerLocalAddrType'] = $local_addr_type;
            }
            if (isset($address_types[$peer['bgp4V2PeerLocalAddrType']])) {
                $peer['bgp4V2PeerLocalAddrType'] = $address_types[$peer['bgp4V2PeerLocalAddrType']];
            }
            // 3. length of the local IP address
            $ip_len = array_shift($index_parts);
            // 4. IP address
            $ip_parts = array_slice($index_parts, 0, $ip_len);

            // 5. bgp4V2PeerLocalAddr
            $local_ip = implode('.', $ip_parts);
            if ((int)$ip_len === 16) {
                $local_ip = snmp2ipv6($local_ip);
            }
            if (get_ip_version($local_ip)) {
                $peer['bgp4V2PeerLocalAddr'] = $local_ip;
            }

            // Get second part of index
            $index_parts    = array_slice($index_parts, $ip_len);
            $peer_addr_type = array_shift($index_parts);
            if (safe_empty($peer['bgp4V2PeerRemoteAddrType'])) {
                $peer['bgp4V2PeerRemoteAddrType'] = $peer_addr_type;
            }
            if (isset($address_types[$peer['bgp4V2PeerRemoteAddrType']])) {
                $peer['bgp4V2PeerRemoteAddrType'] = $address_types[$peer['bgp4V2PeerRemoteAddrType']];
            }
            // 6. length of the IP address
            $ip_len = array_shift($index_parts);
            // 7. IP address
            $ip_parts = array_slice($index_parts, 0, $ip_len);

            // 8. bgp4V2PeerRemoteAddr
            $peer_ip = implode('.', $ip_parts);
            if ((int)$ip_len === 16) {
                $peer_ip = snmp2ipv6($peer_ip);
            }
            if ($peer_addr_type = get_ip_version($peer_ip)) {
                $peer['bgp4V2PeerRemoteAddr']     = $peer_ip;
                $peer['bgp4V2PeerRemoteAddrType'] = 'ipv' . $peer_addr_type;
            }
            break;

        case 'HUAWEI-BGP-VPN-MIB':
            // HUAWEI-BGP-VPN-MIB::hwBgpPeerVrfName.0.ipv4.unicast.ipv4."10.100.0.5" = STRING: "Public"
            // HUAWEI-BGP-VPN-MIB::hwBgpPeerVrfName.0.1.1.1.4.10.100.0.5 = STRING: "Public"
            // 1. hwBgpPeerInstanceId
            $peer['hwBgpPeerInstanceId'] = array_shift($index_parts);

            // 2. hwBgpPeerAddrFamilyAfi
            $afi  = array_shift($index_parts);
            $afis = [1 => 'ipv4', 2 => 'ipv6', 25 => 'vpls', 196 => 'l2vpn']; // Huawei specific AFI numbers (HWBgpAfi)
            if (isset($afis[$afi])) {
                $peer['hwBgpPeerAddrFamilyAfi'] = $afis[$afi];
            } else {
                // or use common afis
                $peer['hwBgpPeerAddrFamilyAfi'] = $config['routing_afis'][$afi];
            }

            // 3. hwBgpPeerAddrFamilySafi
            $safi  = array_shift($index_parts);
            $safis = [1 => 'unicast', 2 => 'multicast', 4 => 'mpls', 5 => 'mcast-vpn', 65 => 'vpls', 66 => 'mdt', 128 => 'vpn', 132 => 'route-target']; // Huawei specific SAFI numbers (HWBgpSafi)
            if (isset($safis[$safi])) {
                $peer['hwBgpPeerAddrFamilySafi'] = $safis[$safi];
            } else {
                // or use common safi
                $peer['hwBgpPeerAddrFamilySafi'] = $config['routing_safis'][$safi];
            }
            // 4. hwBgpPeerRemoteAddrType (hwBgpPeerType)
            $peer_addr_type = array_shift($index_parts);
            if (isset($address_types[$peer_addr_type])) {
                $peer['hwBgpPeerRemoteAddrType'] = $address_types[$peer_addr_type];
            }
            // 5. hwBgpPeerRemoteAddr
            $ip_len    = array_shift($index_parts);
            $ip_parts  = $index_parts;
            $remote_ip = implode('.', $ip_parts);
            if ((int)$ip_len === 16) {
                $remote_ip = snmp2ipv6($remote_ip);
            }
            if ($peer_addr_type = get_ip_version($remote_ip)) {
                $peer['hwBgpPeerRemoteAddr']       = $remote_ip;
                $peer['hwBgpPeerRemoteIdentifier'] = $peer['hwBgpPeerRemoteAddr'];
                if (safe_empty($peer['hwBgpPeerRemoteAddrType'])) {
                    $peer['hwBgpPeerRemoteAddrType'] = 'ipv' . $peer_addr_type;
                }
            }
            break;

        case 'BGP4-V2-MIB-JUNIPER':
            // 1. jnxBgpM2PeerRoutingInstance
            $peer['jnxBgpM2PeerRoutingInstance'] = array_shift($index_parts);
            // 2. jnxBgpM2PeerLocalAddrType
            $local_addr_type = array_shift($index_parts);
            if (safe_empty($peer['jnxBgpM2PeerLocalAddrType'])) {
                $peer['jnxBgpM2PeerLocalAddrType'] = $local_addr_type;
            }
            if (isset($address_types[$peer['jnxBgpM2PeerLocalAddrType']])) {
                $peer['jnxBgpM2PeerLocalAddrType'] = $address_types[$peer['jnxBgpM2PeerLocalAddrType']];
            }
            // 3. length of the local IP address
            $ip_len = str_contains($peer['jnxBgpM2PeerLocalAddrType'], 'ipv6') ? 16 : 4;
            // 4. IP address
            $ip_parts = array_slice($index_parts, 0, $ip_len);

            // 5. jnxBgpM2PeerLocalAddr
            $local_ip = implode('.', $ip_parts);
            if ((int)$ip_len === 16) {
                $local_ip = snmp2ipv6($local_ip);
            }
            if (get_ip_version($local_ip)) {
                $peer['jnxBgpM2PeerLocalAddr'] = $local_ip;
            }

            // Get second part of index
            $index_parts = array_slice($index_parts, $ip_len);
            // 6. jnxBgpM2PeerRemoteAddrType
            $peer_addr_type = array_shift($index_parts);
            if (safe_empty($peer['jnxBgpM2PeerRemoteAddrType'])) {
                $peer['jnxBgpM2PeerRemoteAddrType'] = $peer_addr_type;
            }
            if (isset($address_types[$peer['jnxBgpM2PeerRemoteAddrType']])) {
                $peer['jnxBgpM2PeerRemoteAddrType'] = $address_types[$peer['jnxBgpM2PeerRemoteAddrType']];
            }
            // 7. length of the remote IP address
            $ip_len = str_contains($peer['jnxBgpM2PeerRemoteAddrType'], 'ipv6') ? 16 : 4;
            // 8. IP address
            $ip_parts = array_slice($index_parts, 0, $ip_len);

            // 9. jnxBgpM2PeerRemoteAddr
            $peer_ip = implode('.', $ip_parts);
            if ((int)$ip_len === 16) {
                $peer_ip = snmp2ipv6($peer_ip);
            }
            if (get_ip_version($peer_ip)) {
                $peer['jnxBgpM2PeerRemoteAddr'] = $peer_ip;
            }
            break;

        case 'DELLEMC-OS10-BGP4V2-MIB':
            // 1. os10bgp4V2PeerInstance
            $peer['os10bgp4V2PeerInstance'] = array_shift($index_parts);
            // 2. os10bgp4V2PeerRemoteAddrType
            $peer_addr_type = array_shift($index_parts);
            if (safe_empty($peer['os10bgp4V2PeerRemoteAddrType'])) {
                $peer['os10bgp4V2PeerRemoteAddrType'] = $peer_addr_type;
            }
            if (isset($address_types[$peer['os10bgp4V2PeerRemoteAddrType']])) {
                $peer['os10bgp4V2PeerRemoteAddrType'] = $address_types[$peer['os10bgp4V2PeerRemoteAddrType']];
            }
            // 3. length of the remote IP address
            $ip_len = array_shift($index_parts);
            // 4. IP address
            $ip_parts = array_slice($index_parts, 0, $ip_len);
            // 5. os10bgp4V2PeerRemoteAddr
            $peer_ip = implode('.', $ip_parts);
            if ((int)$ip_len === 16) {
                $peer_ip = snmp2ipv6($peer_ip);
            }
            if (get_ip_version($peer_ip)) {
                $peer['os10bgp4V2PeerRemoteAddr'] = $peer_ip;
            }

            break;

        case 'FORCE10-BGP4-V2-MIB':
            // 1. f10BgpM2PeerInstance
            $peer['f10BgpM2PeerInstance'] = array_shift($index_parts);
            // 2. f10BgpM2PeerLocalAddrType
            $local_addr_type = array_shift($index_parts);
            if (safe_empty($peer['f10BgpM2PeerLocalAddrType'])) {
                $peer['f10BgpM2PeerLocalAddrType'] = $local_addr_type;
            }
            if (isset($address_types[$peer['f10BgpM2PeerLocalAddrType']])) {
                $peer['f10BgpM2PeerLocalAddrType'] = $address_types[$peer['f10BgpM2PeerLocalAddrType']];
            }
            // 3. length of the local IP address
            $ip_len = str_contains($peer['f10BgpM2PeerLocalAddrType'], 'ipv6') ? 16 : 4;
            // 4. IP address
            $ip_parts = array_slice($index_parts, 0, $ip_len);

            // 5. f10BgpM2PeerLocalAddr
            $local_ip = implode('.', $ip_parts);
            if ((int)$ip_len === 16) {
                $local_ip = snmp2ipv6($local_ip);
            }
            if (get_ip_version($local_ip)) {
                $peer['f10BgpM2PeerLocalAddr'] = $local_ip;
            }

            // Get second part of index
            $index_parts = array_slice($index_parts, $ip_len);
            // 6. f10BgpM2PeerRemoteAddrType
            $peer_addr_type = array_shift($index_parts);
            if (safe_empty($peer['f10BgpM2PeerRemoteAddrType'])) {
                $peer['f10BgpM2PeerRemoteAddrType'] = $peer_addr_type;
            }
            if (isset($address_types[$peer['f10BgpM2PeerRemoteAddrType']])) {
                $peer['f10BgpM2PeerRemoteAddrType'] = $address_types[$peer['f10BgpM2PeerRemoteAddrType']];
            }
            // 7. length of the remote IP address
            $ip_len = str_contains($peer['f10BgpM2PeerRemoteAddrType'], 'ipv6') ? 16 : 4;
            // 8. IP address
            $ip_parts = array_slice($index_parts, 0, $ip_len);

            // 9. f10BgpM2PeerRemoteAddr
            $peer_ip = implode('.', $ip_parts);
            if ((int)$ip_len === 16) {
                $peer_ip = snmp2ipv6($peer_ip);
            }
            if (get_ip_version($peer_ip)) {
                $peer['f10BgpM2PeerRemoteAddr'] = $peer_ip;
            }
            break;

        case 'VIPTELA-OPER-BGP':
            // 1. bgpBgpNeighborVpnId
            $peer['bgpBgpNeighborVpnId'] = array_shift($index_parts);

            // 2. hwBgpPeerRemoteAddr
            $ip_len  = safe_count($index_parts);
            $peer_ip = implode('.', $index_parts);
            if ((int)$ip_len === 16) {
                $peer_ip = snmp2ipv6($peer_ip);
            }
            if ($peer_addr_type = get_ip_version($peer_ip)) {
                $peer['bgpBgpNeighborPeerAddr']     = $peer_ip;
                $peer['bgpBgpNeighborPeerAddrType'] = 'ipv' . $peer_addr_type;
            }
            break;

        case 'FIREBRICK-BGP-MIB':
            $peer_type = array_shift($index_parts);
            if (isset($address_types[$peer_type])) {
                $peer['fbBgpPeerAddressType'] = $address_types[$peer_type];
            }
            $ip_len  = array_shift($index_parts);
            $peer_ip = implode('.', $index_parts);
            if ((int)$ip_len === 16) {
                $peer_ip = snmp2ipv6($peer_ip);
            }
            $peer['fbBgpPeerAddress'] = $peer_ip;
            break;
    }

}

function bgp_fix_peer($device, &$peer, $mib)
{
    switch ($mib) {
        case 'BGP4-MIB':
            if ($device['os'] === 'arista_eos' && !empty($device['version']) &&
                version_compare($device['version'], '4.23.0', '>=') && version_compare($device['version'], '4.25.0', '<')) {
                // It seems as firmware issue, always report as halted
                // See: https://jira.observium.org/browse/OBS-4382
                // Opposite case: https://jira.observium.org/browse/OBS-4398
                if ($peer['bgpPeerAdminStatus'] === 'stop' && $peer['bgpPeerState'] !== 'idle') {
                    // running: established, connect, active (not sure about opensent, openconfirm)
                    print_debug("Fixed Arista issue, BGP4-MIB::bgpPeerAdminStatus always report halted");
                    $peer['bgpPeerAdminStatus'] = 'start';
                } elseif ($peer['bgpPeerAdminStatus'] === 'start' && $peer['bgpPeerState'] === 'idle') {
                    print_debug("Fixed Arista issue, BGP4-MIB::bgpPeerAdminStatus report running for shutdown");
                    $peer['bgpPeerAdminStatus'] = 'stop';
                }
            }

            $peer_as = snmp_dewrap32bit($peer['bgpPeerRemoteAs']); // Dewrap for 32bit ASN
            if ($peer_as > $peer['bgpPeerRemoteAs']) {
                $peer['bgpPeerRemoteAs'] = $peer_as;
            }
            break;

        case 'ARISTA-BGP4V2-MIB':
            if (!empty($device['version']) &&
                version_compare($device['version'], '4.23.0', '>=') && version_compare($device['version'], '4.25.0', '<')) {
                // It seems as firmware issue, always report as halted
                // See: https://jira.observium.org/browse/OBS-4382
                // Opposite case: https://jira.observium.org/browse/OBS-4398
                if ($peer['aristaBgp4V2PeerAdminStatus'] === 'halted' && $peer['aristaBgp4V2PeerState'] !== 'idle') {
                    // running: established, connect, active (not sure about opensent, openconfirm)
                    print_debug("Fixed Arista issue, ARISTA-BGP4V2-MIB::aristaBgp4V2PeerAdminStatus always report halted");
                    $peer['aristaBgp4V2PeerAdminStatus'] = 'running';
                } elseif ($peer['aristaBgp4V2PeerAdminStatus'] === 'running' && $peer['aristaBgp4V2PeerState'] === 'idle') {
                    print_debug("Fixed Arista issue, ARISTA-BGP4V2-MIB::aristaBgp4V2PeerAdminStatus report running for shutdown");
                    $peer['aristaBgp4V2PeerAdminStatus'] = 'halted';
                }
            }
            break;

        case 'HUAWEI-BGP-VPN-MIB':
            if (!isset($peer['hwBgpPeerAdminStatus'])) {
                // Always set this Oid to start, while not really exist and while peer entry exist in this table
                $peer['hwBgpPeerAdminStatus'] = 'start';
            }
            break;

        case 'DELLEMC-OS10-BGP4V2-MIB':
            // Uptimes really reported with x100 multiplier
            if (isset($peer['os10bgp4V2PeerFsmEstablishedTime'])) {
                $peer['os10bgp4V2PeerFsmEstablishedTime'] *= 0.01;
            }
            if (isset($peer['os10bgp4V2PeerInUpdatesElapsedTime'])) {
                $peer['os10bgp4V2PeerInUpdatesElapsedTime'] *= 0.01;
            }

            // It seems as firmware issue, always report as halted
            // See: https://jira.observium.org/browse/OBS-4134
            if ($peer['os10bgp4V2PeerAdminStatus'] === 'halted' && $peer['os10bgp4V2PeerState'] !== 'idle') {
                // running: established, connect, active (not sure about opensent, openconfirm)
                print_debug("Fixed Dell OS10 issue, DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerAdminStatus always report halted");
                $peer['os10bgp4V2PeerAdminStatus'] = 'running';
            } elseif ($peer['os10bgp4V2PeerAdminStatus'] === 'running' && $peer['os10bgp4V2PeerState'] === 'idle') {
                // See: https://jira.observium.org/browse/OBS-4280
                /*
                 * DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerAdminStatus.1.ipv4."255.79.16.154" = INTEGER: halted(1)
                 * DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerAdminStatus.1.ipv4."255.118.129.119" = INTEGER: running(2)
                 * DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerAdminStatus.1.ipv4."254.145.182.212" = INTEGER: halted(1)
                 * DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerAdminStatus.1.ipv4."254.145.182.214" = INTEGER: halted(1)
                 * DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerState.1.ipv4."255.79.16.154" = INTEGER: established(6)
                 * DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerState.1.ipv4."255.118.129.119" = INTEGER: idle(1)
                 * DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerState.1.ipv4."254.145.182.212" = INTEGER: established(6)
                 * DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerState.1.ipv4."254.145.182.214" = INTEGER: established(6)
                 */
                print_debug("Fixed Dell OS10 issue, DELLEMC-OS10-BGP4V2-MIB::os10bgp4V2PeerAdminStatus report running for shutdown");
                $peer['os10bgp4V2PeerAdminStatus'] = 'halted';
            }
            break;

        case 'VIPTELA-OPER-BGP':
            if (!isset($peer['bgpBgpNeighborAdminState'])) {
                // Always set this Oid to start, while not really exist and while peer entry exist in this table
                $peer['bgpBgpNeighborAdminState'] = in_array($peer['bgpBgpNeighborState'], ['clearing', 'deleted']) ? 'stop' : 'start';
            }
            break;

        case 'FIREBRICK-BGP-MIB':
            if (!isset($peer['fbBgpPeerAdminState'])) {
                // Always set this Oid to start, while not really exist and while peer entry exist in this table
                $peer['fbBgpPeerAdminState'] = in_array($peer['fbBgpPeerState'], ['clearing', 'deleted']) ? 'stop' : 'start';
            }
            break;
    }
}

function discovery_bgp_afisafi($device, $peer, $afi, $safi, &$af_list)
{
    global $table_rows;

    $index   = $peer['index'];
    $peer_id = $peer['id'];
    $peer_ip = $peer['ip'];
    $peer_as = $peer['as'];
    if (isset($GLOBALS['config']['routing_afis'][$afi])) {
        $afi = $GLOBALS['config']['routing_afis'][$afi]['name'];
    }
    if (isset($GLOBALS['config']['routing_safis'][$safi])) {
        $safi = $GLOBALS['config']['routing_safis'][$safi]['name'];
    }
    print_debug("INDEX: $index, AS: $peer_as, IP: $peer_ip, AFI: $afi, SAFI: $safi");
    print_debug_vars($peer);
    $af_list[$peer_id][$afi][$safi] = 1;

    if (!safe_empty($table_rows[$peer_ip][4])) {
        $table_rows[$peer_ip][4] .= ', ';
    }
    $table_rows[$peer_ip][4] .= $afi . '.' . $safi;

    if (!dbExist('bgpPeers_cbgp', '`device_id` = ? AND `bgpPeer_id` = ? AND `afi` = ? AND `safi` = ?', [$device['device_id'], $peer_id, $afi, $safi])) {
        $params = ['bgpPeer_id' => $peer_id, 'device_id' => $device['device_id'], 'bgpPeerIndex' => $index, 'afi' => $afi, 'safi' => $safi];
        dbInsert($params, 'bgpPeers_cbgp');
    } elseif (!safe_empty($index)) {
        // Update Index
        dbUpdate(['bgpPeerIndex' => $index], 'bgpPeers_cbgp', 'device_id = ? AND `bgpPeer_id` = ? AND `afi` = ? AND `safi` = ?', [$device['device_id'], $peer_id, $afi, $safi]);
    }
}

/**
 * Get AS Number description.
 *
 * @param string|integer $asn
 * @return string
 */
function get_astext($asn) {
    global $config, $cache;

    if (!is_intnum($asn)) {
        $asn = ltrim($asn, 'asAS ');
        //$asn = bgp_asdot_to_asplain($asn);
    }

    // Fetch pre-set AS text from config first
    if (isset($config['astext'][$asn])) {
        return $config['astext'][$asn];
    }
    if (is_bgp_as_private($asn)) {
        return '';
    }

    // Not preconfigured, check cache before doing a new DNS request
    if (!isset($cache['astext'][$asn])) {
        $result = dns_get_record("AS$asn.asn.cymru.com", DNS_TXT);
        print_debug_vars($result);
        $txt                   = explode('|', $result[0]['txt']);
        $cache['astext'][$asn] = trim(str_replace('"', '', $txt[4]));
    }

    return $cache['astext'][$asn];
}

/**
 * Get ASN by IP address
 *
 * @param string $ip
 * @return string|false
 */
function get_ipas($ip) {
    $ip_type = get_ip_type($ip);

    if (!$ip_type || in_array($ip_type,  [ 'unspecified', 'loopback', 'private' ])) {
        return FALSE;
    }

    $ip = ip_uncompress($ip);
    if (!isset($cache['ipas'][$ip])) {

        if (str_contains($ip, ':')) {
            // IPv6
            $ip_array = str_split(str_replace(':', '', $ip));
            $dns_host = implode('.', array_reverse($ip_array)) . '.origin6.asn.cymru.com';
        } else {
            // IPv4
            $ip_array = explode('.', $ip);
            $dns_host = implode('.', array_reverse($ip_array)) . '.origin.asn.cymru.com';
        }

        $result = dns_get_record($dns_host, DNS_TXT);
        print_debug_vars($result);
        $txt                = explode('|', $result[0]['txt']);
        $cache['ipas'][$ip] = trim(str_replace('"', '', $txt[0]));
    }

    return $cache['ipas'][$ip];
}

function get_peer_remote_device_id($peer, $peer_devices, $peer_ip_where) {
    // Search a remote device if possible
    //$peer_addr_version = get_ip_version($peer['ip']);
    $peer_addr_type    = get_ip_type($peer['ip']);
    $peer_local_type   = get_ip_type($peer['local_ip']);

    if (!in_array($peer_addr_type,  [ 'unspecified', 'loopback' ]) &&
        !in_array($peer_local_type, [ 'unspecified', 'loopback' ])) {

        //if ($tmp_id = dbFetchCell('SELECT `device_id` FROM `bgpPeers` WHERE `bgpPeerLocalAddr` = ? AND `bgpPeerRemoteAs` = ? AND `bgpPeerRemoteAddr` = ?' . $peer_as_where, array($peer['ip'], $bgpLocalAs, $peer['local_ip'])))
        print_debug("bgpPeerLocalAddr: " . $peer['ip'] . ", bgpPeerRemoteAddr: " . $peer['local_ip']);
        if (isset($peer_devices[$peer['ip']][$peer['local_ip']])) {
            // Simple search remote device by Local IP and Local AS and Remote IP
            //$peer_device_id = $tmp_id;
            return $peer_devices[$peer['ip']][$peer['local_ip']];

        }

        if ($ids = get_entity_ids_ip_by_network('device', $peer['ip'], $peer_ip_where)) {
            // Fetch all devices with peer IP
            // Peer device will found if device UP and NOT DISABLED, port with IP is UP, bgpLocalAs present on remote device

            /*
            foreach($ids as $tmp_id)
            {
              //if (dbFetchCell('SELECT COUNT(*) FROM `bgpPeers` WHERE `device_id` = ? AND `bgpPeerRemoteAs` = ?', array($tmp_id, $bgpLocalAs)))
              if (dbExist('bgpPeers', '`device_id` = ? AND `bgpPeerRemoteAs` = ?', array($tmp_id, $bgpLocalAs)))
              {
                 // Validate if bgpLocalAs also present on remote device
                 $peer_device_id = $tmp_id;
                 break; // Found, stop loop
              }
            }
            */

            return array_shift($ids);
        }

    }

    return NULL;
}

function discover_vrf($device, $vrf)
{
    global $cache_discovery, $valid, $table_rows;

    $module = 'vrf';

    if (empty($vrf['vrf_name'])) {
        return;
    }
    $vrf_name = $vrf['vrf_name'];

    // Pre-cache VRFs from DB
    if (!isset($cache_discovery['vrf_db'])) {
        $cache_discovery['vrf_db'] = [];
        foreach (dbFetchRows("SELECT * FROM `vrfs` WHERE `device_id` = ?", [$device['device_id']]) as $entry) {
            // Strange case with duplicate entries: https://jira.observium.org/browse/OBS-3600
            if (isset($cache_discovery['vrf_db'][$entry['vrf_name']])) {
                print_debug("Duplicate VRF entry in DB found: " . $entry['vrf_name']);
                print_debug_vars($entry);
                dbDelete('vrfs', '`vrf_id` = ?', [$entry['vrf_id']]);
                continue;
            }
            $cache_discovery['vrf_db'][$entry['vrf_name']] = $entry;
        }
    }

    $params_main  = ['vrf_mib', 'vrf_name', 'vrf_descr', 'vrf_rd'];
    $params_state = ['vrf_admin_status', 'vrf_oper_status',
                     'vrf_active_ports', 'vrf_total_ports',
                     'vrf_added_routes', 'vrf_deleted_routes', 'vrf_total_routes'];

    $insert_array = ['device_id' => $device['device_id']];
    foreach ($params_main as $param) {
        $insert_array[$param] = isset($vrf[$param]) ? $vrf[$param] : '';
    }

    // Set added/changed params
    $current_time         = (int)time();
    $param                = 'vrf_added';
    $insert_array[$param] = isset($vrf[$param]) && $vrf[$param] < $current_time ? (int)$vrf[$param] : $device['last_rebooted'];

    if (!isset($cache_discovery['vrf_db'][$vrf['vrf_name']])) {
        // Added
        $param                = 'vrf_last_change';
        $insert_array[$param] = isset($vrf[$param]) && $vrf[$param] < $current_time ? (int)$vrf[$param] : $current_time;

        // When insert, also add state params
        foreach ($params_state as $param) {
            if (isset($vrf[$param])) {
                // When not set, use db default
                $insert_array[$param] = $vrf[$param];
            }
        }

        $vrf_id = dbInsert($insert_array, 'vrfs');
        $GLOBALS['module_stats'][$module]['added']++; //echo "+";
    } else {
        // Compare/update

        $update_array = [];
        $entry        = $cache_discovery['vrf_db'][$vrf['vrf_name']];
        $vrf_id       = $entry['vrf_id'];

        foreach ($params_main as $param) {
            if ($insert_array[$param] !== $entry[$param]) {
                $update_array[$param] = $insert_array[$param];
            }
        }

        // Update old entries (after migrate)
        if (empty($entry['vrf_added'])) {
            // State params
            foreach ($params_state as $param) {
                if (isset($vrf[$param])) {
                    // When not set, use db default
                    $update_array[$param] = $vrf[$param];
                }
            }

            $update_array['vrf_added']       = $insert_array['vrf_added'];
            $update_array['vrf_last_change'] = isset($vrf['vrf_last_change']) && $vrf['vrf_last_change'] < $current_time ? (int)$vrf['vrf_last_change'] : $current_time;
        } else {

            if (count($update_array)) {
                $update_array['vrf_last_change'] = isset($vrf['vrf_last_change']) && $vrf['vrf_last_change'] < $current_time ? (int)$vrf['vrf_last_change'] : $current_time;
            }
            // For old entries only validate added/changed times (more than 60 sec)
            /*foreach ([ 'vrf_added', 'vrf_last_change' ] as $param) {
            foreach ([ 'vrf_last_change' ] as $param) {
              if (abs($insert_array[$param] - $entry[$param]) > 60)
              {
                $update_array[$param] = $insert_array[$param];
                print_debug("$param: ".$insert_array[$param]." - ".$entry[$param]." = ".($insert_array[$param] - $entry[$param]));
              }
            }
            */
        }

        if (safe_count($update_array)) {
            dbUpdate($update_array, 'vrfs', '`vrf_id` = ?', [$vrf_id]);

            $GLOBALS['module_stats'][$module]['updated']++;
        } else {
            $GLOBALS['module_stats'][$module]['unchanged']++;
        }
    }

    $valid['vrf'][$vrf_name] = $vrf_id;

    // VRF ports
    if (safe_count($vrf['ifIndex'])) {
        $db_update = [];
        foreach ($vrf['ifIndex'] as $ifIndex) {
            if ($port = get_port_by_index_cache($device, $ifIndex)) {
                $db_update[] = ['port_id' => $port['port_id'], 'ifIndex' => $port['ifIndex'], 'device_id' => $device['device_id'], 'ifVrf' => $vrf_id];

                $valid['vrf-ports'][$vrf_name][$port['port_id']] = $vrf_id;
            }
        }

        dbUpdateMulti($db_update, 'ports', ['ifVrf']);
    }
}

/**
 * This array used by html_highlight()
 * @param $device
 *
 * @return void
 */
function bgp_links_cache($device) {
    global $cache;

    // Create entity links arrays
    if (!isset($cache['entity_links']['bgp'])) {
        $cache['entity_links']['bgp'] = [];
    }
    $bgp_links = &$cache['entity_links']['bgp'];

    // Highlight port links
    if (isset($bgp_links[$device['device_id']])) {
        return;
    }
    // if (!isset($device['os'])) {
    //     // Need os field.
    //     $device = device_by_id_cache($device['device_id']);
    // }

    $bgp_links[$device['device_id']] = [];

    //SELECT `bgpPeer_id`, `bgpPeerRemoteAs`, `bgpPeerIdentifier`, `bgpPeerRemoteAddr` FROM `bgpPeers` WHERE `device_id` = 2
    foreach (dbFetchRows('SELECT * FROM `bgpPeers` WHERE `device_id` = ?', [$device['device_id']]) as $bgp_descr) {
        $search = [];
        foreach ([ 'bgpPeerIdentifier', 'bgpPeerRemoteAddr' ] as $param) {
            if ($bgp_descr[$param] === '0.0.0.0') {
                continue;
            }

            // Cisco Specific (and common)
            $search[] = 'Nbr ' . $bgp_descr[$param];
            $search[] = 'Neighbor ' . $bgp_descr[$param];
            // JunOS specific
            $search[] = 'BGP peer ' . $bgp_descr[$param];
            if (get_ip_version($bgp_descr[$param]) === 6) {
                // For IPv6 append compressed form
                $bgp_descr[$param] = ip_compress($bgp_descr[$param]);
                $search[]          = 'Nbr ' . $bgp_descr[$param];
                $search[]          = 'Neighbor ' . $bgp_descr[$param];
                $search[]          = 'BGP peer ' . $bgp_descr[$param];
            }
        }
        $bgp_links[$device['device_id']][] = [
            'search'  => $search,
            'replace' => generate_entity_link('bgp_peer', $bgp_descr, '$2')
        ];

        // Additionally append AS text
        if ($bgp_descr['astext'] && !isset($bgp_links[$device['device_id']]['as' . $bgp_descr['bgpPeerRemoteAs']])) {
            $bgp_links[$device['device_id']]['as' . $bgp_descr['bgpPeerRemoteAs']] = [
                'search'  => [ 'AS ' . $bgp_descr['bgpPeerRemoteAs'], 'AS: ' . $bgp_descr['bgpPeerRemoteAs'], 'AS' . $bgp_descr['bgpPeerRemoteAs'] ],
                'replace' => generate_tooltip_link('', '$2', $bgp_descr['astext'])
            ];
        }
    }
}

// EOF
