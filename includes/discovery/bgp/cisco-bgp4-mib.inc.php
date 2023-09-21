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
 * @var array  $device
 * @var string $mib
 * @var array  $entry
 * @var string $bgpLocalAs
 * @var array  $p_list
 * @var array  $peerlist
 * @var bool   $check_vrfs
 * @var string $vrf_name
 */

$cisco_version = $entry['cisco_version'];

if ($check_vrfs) {
    // Derp, VRFs need separate versions, need in BGP4-MIB
    $cisco_version_vrf[$vrf_name] = $entry['cisco_version'];
}

if ($cisco_version < 2) {
    // Get afi/safi and populate cbgp on cisco ios (xe/xr)
    $af_data = snmpwalk_cache_oid($device, 'cbgpPeerAddrFamilyName', [], 'CISCO-BGP4-MIB');
} else {

    // Check Cisco cbgpPeer2Table
    $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2RemoteAs', [], 'CISCO-BGP4-MIB');
    $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2LocalAddr', $cisco_peers, 'CISCO-BGP4-MIB');

    // Cisco vendor mib LocalAddr issue:
    // cbgpPeer2LocalAddr.ipv4."10.0.1.1" = "0B 8E 95 38 " --> 11.142.149.56
    // but should:
    // bgpPeerLocalAddr.10.0.1.1 = 10.0.1.3
    // Yah, Cisco you again added extra work for me? What mean this random numbers?
    $cisco_fix = snmpwalk_cache_oid($device, 'bgpPeerLocalAddr', [], 'BGP4-MIB');

    $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2RemoteIdentifier', $cisco_peers, 'CISCO-BGP4-MIB');
    $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2AdminStatus', $cisco_peers, 'CISCO-BGP4-MIB');
    $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2LocalAs', $cisco_peers, 'CISCO-BGP4-MIB');

    print_debug("CISCO-BGP4-MIB Peers: ");
    foreach ($cisco_peers as $index => $cisco_entry) {
        [, $peer_ip] = explode('.', $index, 2);
        $peer_ip = hex2ip($peer_ip);
        if (isset($cisco_fix[$peer_ip]) && strlen($cisco_fix[$peer_ip]['bgpPeerLocalAddr'])) {
            // Fix incorrect IPv4 local IPs
            $local_ip = $cisco_fix[$peer_ip]['bgpPeerLocalAddr'];
        } else {
            $local_ip = hex2ip($cisco_entry['cbgpPeer2LocalAddr']);
        }

        $peer_as  = $cisco_entry['cbgpPeer2RemoteAs'];
        $local_as = snmp_dewrap32bit($cisco_entry['cbgpPeer2LocalAs']);
        if ($local_as == 0) {
            // Per session local ASN can be zero if session down
            $local_as = $bgpLocalAs;
        }
        $peer = [
          'mib'          => $mib,
          'index'        => $index,
          'identifier'   => $cisco_entry['cbgpPeer2RemoteIdentifier'],
          'local_ip'     => $local_ip,
          'local_as'     => $local_as,
          'ip'           => $peer_ip === '0.0.0.0' ? '' : $peer_ip,
          'as'           => $peer_as,
          'admin_status' => $cisco_entry['cbgpPeer2AdminStatus']
        ];
        if ($check_vrfs) {
            $peer['virtual_name'] = $vrf_name;
        }

        if (!isset($p_list[$peer_ip][$peer_as]) && is_bgp_peer_valid($peer, $device)) {
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
        }
    }

    // Get afi/safi and populate cbgp on cisco ios (xe/xr)
    $af_data = snmpwalk_cache_oid($device, 'cbgpPeer2AddrFamilyName', [], 'CISCO-BGP4-MIB');
}

// Process AFI/SAFI
foreach ($af_data as $af => $af_entry) {
    if ($cisco_version === 2) {
        [, $af] = explode('.', $af, 2);
        $text = $af_entry['cbgpPeer2AddrFamilyName'];
    } else {
        $text = $af_entry['cbgpPeerAddrFamilyName'];
    }
    $afisafi = explode('.', $af);
    $c       = count($afisafi);
    $afi     = $afisafi[$c - 2];
    $safi    = $afisafi[$c - 1];
    $peer_ip = hex2ip(str_replace(".$afi.$safi", '', $af));
    print_debug("Peer IP: $peer_ip, AFI: $afi, SAFI: $safi");
    if ($afi && $safi) {

        //$peer_afis[$peer_ip][$afi][$safi] = $text;
        $peer_afis[$peer_ip][] = ['afi' => $afi, 'safi' => $safi];
        /*
        if (strlen($table_rows[$peer_ip][4]))
        {
          $table_rows[$peer_ip][4] .= ', ';
        }
        $table_rows[$peer_ip][4] .= $afi . '.' . $safi;

        $peer_id                        = $peer_ids[$peer_ip];
        $af_list[$peer_id][$afi][$safi] = 1;

        //if (dbFetchCell('SELECT COUNT(*) FROM `bgpPeers_cbgp` WHERE `device_id` = ? AND `bgpPeer_id` = ? AND `afi` = ? AND `safi` = ?', array($device['device_id'], $peer_id, $afi, $safi)) == 0)
        if (!dbExist('bgpPeers_cbgp', '`device_id` = ? AND `bgpPeer_id` = ? AND `afi` = ? AND `safi` = ?', array($device['device_id'], $peer_id, $afi, $safi)))
        {
          $params = [ 'device_id' => $device['device_id'], 'bgpPeer_id' => $peer_id, 'afi' => $afi, 'safi' => $safi ];
          dbInsert($params, 'bgpPeers_cbgp');
        }
        */
    }
}

// EOF
