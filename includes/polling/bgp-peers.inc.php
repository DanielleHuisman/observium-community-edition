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

// 'BGP4-MIB', 'CISCO-BGP4-MIB', 'BGP4-V2-MIB-JUNIPER', 'FORCE10-BGP4-V2-MIB', 'ARISTA-BGP4V2-MIB', 'FOUNDRY-BGP4V2-MIB', 'HUAWEI-BGP-VPN-MIB'
if (!$config['enable_bgp'] || !is_device_mib($device, 'BGP4-MIB')) {
    // Note, BGP4-MIB is main MIB, without it, the rest will not be checked
    return;
} // End check for BGP support


$bgp_oids  = [ 'bgpPeerState', 'bgpPeerAdminStatus', 'bgpPeerInUpdates', 'bgpPeerOutUpdates',
               'bgpPeerInTotalMessages', 'bgpPeerOutTotalMessages', 'bgpPeerFsmEstablishedTime',
               'bgpPeerInUpdateElapsedTime' ]; // , 'bgpPeerLocalAddr', 'bgpPeerIdentifier' ];
$cbgp_defs = [ 'PeerAcceptedPrefixes', 'PeerDeniedPrefixes', 'PeerPrefixAdminLimit', 'PeerPrefixThreshold',
               'PeerPrefixClearThreshold', 'PeerAdvertisedPrefixes', 'PeerSuppressedPrefixes', 'PeerWithdrawnPrefixes' ];

// Get array with exist MIBs on device with LocasAs-es (in poller return cached array)
$local_as_array = get_bgp_localas_array($device);

$vendor_mib    = FALSE; // CLEANME. Clear after full rewrite to definitions.
$bgpVrfLocalAs = [];
foreach ($local_as_array as $entry) {

    // CLEANME. Clear after full rewrite to definitions.
    if ($entry['mib'] !== 'BGP4-MIB' && $entry['mib'] !== 'CISCO-BGP4-MIB') {
        $vendor_mib = $entry['mib'];
    }

    // LocalAs by first non-zero
    if ($entry['LocalAs'] != 0) {
        if (!isset($bgpLocalAs) &&
            $entry['oid'] !== 'cbgpPeer2LocalAs') {
            // Do not use Cisco Peer LocalAs as device bgp, see https://jira.observium.org/browse/OBS-4116
            $bgpLocalAs = $entry['LocalAs'];
        }
        if (isset($entry['virtual_name']) && !isset($bgpVrfLocalAs[$entry['virtual_name']])) {
            // Set per vrf LocalAs
            $bgpVrfLocalAs[$entry['virtual_name']] = $entry['LocalAs'];
        }
    }
}

if (is_numeric($bgpLocalAs) && $bgpLocalAs != 0) {
    print_cli_data("Local AS", "AS$bgpLocalAs ", 2);

    // Init
    $p_list          = [];    // Init founded peers list
    $af_list         = [];
    $force_discovery = FALSE; // Flag for force or not rediscover bgp peers
    $snmp_incomplete = FALSE; // Flag for detect if snmpwalk fetch complete data (required for force_discovery)
    $table_rows      = [];
    $c_table_rows    = [];

    print_cli_data_field("Caching");

    foreach ($local_as_array as $entry) {
        $mib = $entry['mib'];
        $def = $config['mibs'][$mib]['bgp'];
        echo("$mib ");
        if ($check_vrfs = isset($entry['virtual_name'])) {
            // Keep original
            $device_original     = $device;
            $bgpLocalAs_original = $bgpLocalAs;
            if (isset($bgpVrfLocalAs[$entry['virtual_name']])) {
                // Need in per VRF discovery
                $bgpLocalAs = $bgpVrfLocalAs[$entry['virtual_name']];
            }
            $vrf_name     = $entry['virtual_name'];
            $snmp_virtual = $entry['snmp_context'];
            // Set device for VRF tables
            $device = snmp_virtual_device($device_original, $snmp_virtual);
            echo("(Virtual Routing $vrf_name) ");
        } elseif ($entry['vrf_name']) {
            // only stored VRF name, ie CUMULUS-BGPVRF-MIB
            $vrf_name = $entry['vrf_name'];
            echo("(Virtual Routing $vrf_name) ");
        }

        /* Start caching bgp peers */
        $include = __DIR__ . '/bgp/' . strtolower($mib) . '.inc.php';
        if (!is_file($include)) {
            // Common for vendor mibs
            $include = __DIR__ . '/bgp/bgp4v2-mib.inc.php';
        }
        include $include;
        /* End caching bgp peers */

        if ($check_vrfs) {
            // Restore device array
            $device     = $device_original;
            $bgpLocalAs = $bgpLocalAs_original;
        }
        unset($vrf_name, $snmp_virtual);
    }

}

// Polled time after snmpwalks
$polled = time();

if (OBS_DEBUG > 1) {
    print_vars($bgp_peers);
    print_vars($cisco_peers);
    print_vars($vendor_peers);
    print_vars($p_list);
    print_vars($af_list);
}

$sql = 'SELECT * FROM `bgpPeers` WHERE `device_id` = ?';

foreach (dbFetchRows($sql, [$device['device_id']]) as $peer) {
    $peer_as   = $peer['bgpPeerRemoteAs'];
    $peer_ip   = $peer['bgpPeerRemoteAddr'];
    $remote_ip = ip_compress($peer_ip); // Compact IPv6. Used only for log.

    // Check if peers exist in SNMP data
    if (isset($p_list[$peer_ip][$peer_as])) {
        // OK
        unset($p_list[$peer_ip][$peer_as]);
    } else {
        // This peer removed from table, force rediscover peers
        $force_discovery = TRUE;
    }

    if (isset($bgp_peers[$peer_ip]) && !isset($cbgp2_peers[$peer_ip][$peer_as])) {
        // Common IPv4 BGP4 MIB (exclude Cisco BGP v2)
        foreach ($bgp_oids as $bgp_oid) {
            $$bgp_oid = $bgp_peers[$peer_ip][$bgp_oid];
        }
    } elseif (isset($cbgp2_peers[$peer_ip][$peer_as])) {
        // Cisco BGP4 V2 MIB
        foreach ($bgp_oids as $bgp_oid) {
            $$bgp_oid = $cbgp2_peers[$peer_ip][$peer_as][$bgp_oid];
        }

    } elseif (isset($vendor_peers[$peer_ip][$peer_as])) {
        // Vendor BGP4-V2 MIB
        foreach ($bgp_oids as $bgp_oid) {
            $$bgp_oid = $vendor_peers[$peer_ip][$peer_as][$bgp_oid];
        }
    }
    print_debug(PHP_EOL . "Peer: $peer_ip (State = $bgpPeerState, AdminStatus = $bgpPeerAdminStatus)");

    $bgpPeerChange = 'unchanged';
    if ($bgpPeerFsmEstablishedTime &&
        ($bgpPeerFsmEstablishedTime < $peer['bgpPeerFsmEstablishedTime'] || $bgpPeerState !== $peer['bgpPeerState'])) {
        print_debug_vars($peer, 1);
        if ($peer['bgpPeerState'] === $bgpPeerState) {
            $bgpPeerChange = 'flapped';
            //log_event('BGP Session flapped: ' . $remote_ip . ' (AS' . $peer['bgpPeerRemoteAs'] . '), time '. format_uptime($bgpPeerFsmEstablishedTime) . ' ago', $device, 'bgp_peer', $peer['bgpPeer_id']);
        } elseif ($bgpPeerState === "established") {
            // new state is up
            $bgpPeerChange = 'up';
            //log_event('BGP Session Up: ' . $remote_ip . ' (AS' . $peer['bgpPeerRemoteAs'] . '), time '. format_uptime($bgpPeerFsmEstablishedTime) . ' ago', $device, 'bgp_peer', $peer['bgpPeer_id'], 'warning');
        } elseif ($peer['bgpPeerState'] === "established") {
            // previous state was up
            $bgpPeerChange = 'down';
            //log_event('BGP Session Down: ' . $remote_ip . ' (AS' . $peer['bgpPeerRemoteAs'] . '), time '. format_uptime($bgpPeerFsmEstablishedTime) . ' ago.', $device, 'bgp_peer', $peer['bgpPeer_id'], 'warning');
        } elseif (!safe_empty($bgpPeerState) && !safe_empty($peer['bgpPeerState'])) {
            // state changed
            $bgpPeerChange = 'changed';
        }
    } elseif (!safe_empty($bgpPeerState) && !safe_empty($peer['bgpPeerState']) && $bgpPeerState !== $peer['bgpPeerState']) {
        // state changed (no EstablishedTime provided)
        $bgpPeerChange = 'changed';
    }

    // FIXME I left the eventlog code for now, as soon as alerts send an entry to the eventlog this can go.
    if (!str_contains($bgpPeerChange, 'changed') && !in_array($peer['bgpPeerRemoteAs'], (array)$config['alerts']['bgp']['whitelist'], TRUE)) {
        log_event('BGP Session ' . nicecase($bgpPeerChange) . ': ' . $remote_ip . ' (AS' . $peer['bgpPeerRemoteAs'] . '), time ' . format_uptime($bgpPeerFsmEstablishedTime) . ' ago', $device, 'bgp_peer', $peer['bgpPeer_id'], 'warning');
    }

    //$polled = time();
    $polled_period = $polled - $peer['bgpPeer_polled'];

    print_debug("[ polled $polled -> period $polled_period ]");

    rrdtool_update_ng($device, 'bgp', [
      'bgpPeerOutUpdates'  => $bgpPeerOutUpdates,
      'bgpPeerInUpdates'   => $bgpPeerInUpdates,
      'bgpPeerOutTotal'    => $bgpPeerOutTotalMessages,
      'bgpPeerInTotal'     => $bgpPeerInTotalMessages,
      'bgpPeerEstablished' => $bgpPeerFsmEstablishedTime,
    ],                $peer_ip);

    //$graphs['bgp_updates'] = TRUE; // not a device graph

    // Update states
    $peer['update'] = [];
    //foreach (array('bgpPeerState', 'bgpPeerAdminStatus', 'bgpPeerLocalAddr', 'bgpPeerIdentifier') as $oid)
    foreach (['bgpPeerState', 'bgpPeerAdminStatus'] as $bgp_oid) {
        if ($$bgp_oid != $peer[$bgp_oid]) {
            $peer['update'][$bgp_oid] = $$bgp_oid;
        }
    }

    //if (count($peer['update']))
    //{
    //  dbUpdate($peer['update'], 'bgpPeers', '`bgpPeer_id` = ?', array($peer['bgpPeer_id']));
    //}

    $check_metrics = [
      'bgpPeerState'              => $bgpPeerState,
      'bgpPeerChange'             => $bgpPeerChange,
      'bgpPeerAdminStatus'        => $bgpPeerAdminStatus,
      'bgpPeerFsmEstablishedTime' => $bgpPeerFsmEstablishedTime
    ];

    // Update metrics
    $metrics = ['bgpPeerInUpdates', 'bgpPeerOutUpdates', 'bgpPeerInTotalMessages', 'bgpPeerOutTotalMessages'];
    foreach ($metrics as $oid) {
        $peer['update'][$oid] = $$oid;
        if (isset($peer[$oid]) && $peer[$oid] != "0") {
            $peer['update'][$oid . '_delta'] = $peer['update'][$oid] - $peer[$oid];
            $peer['update'][$oid . '_rate']  = float_div($peer['update'][$oid . '_delta'], $polled_period);
            if ($peer['update'][$oid . '_rate'] < 0) {
                $peer['update'][$oid . '_rate'] = '0';
                print_debug($oid . " went backwards.");
            }

            $check_metrics[$oid . '_delta'] = $peer['update'][$oid . '_delta'];
            $check_metrics[$oid . '_rate']  = $peer['update'][$oid . '_rate'];

            if ($config['statsd']['enable'] == TRUE) {
                // Update StatsD/Carbon
                StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'bgp' . '.' . str_replace(".", "_", $peer_ip) . '.' . $oid, $$oid);
            }
        }
    }

    check_entity('bgp_peer', $peer, $check_metrics);

    //if (!is_numeric($peer['bgpPeer_polled']))
    //{
    //  dbInsert(array('bgpPeer_id' => $peer['bgpPeer_id']), 'bgpPeers-state');
    //}

    $peer['update']['bgpPeerFsmEstablishedTime']  = $bgpPeerFsmEstablishedTime;
    $peer['update']['bgpPeerInUpdateElapsedTime'] = $bgpPeerInUpdateElapsedTime;
    $peer['update']['bgpPeer_polled']             = $polled;

    // FIXME. Convert to multi-update
    dbUpdate($peer['update'], 'bgpPeers', '`bgpPeer_id` = ?', [$peer['bgpPeer_id']]);

    $table_row    = [];
    $table_row[]  = $peer_ip;
    $table_row[]  = $peer['bgpPeerRemoteAs'];
    $table_row[]  = truncate($peer['astext'], 15);
    $table_row[]  = $bgpPeerAdminStatus;
    $table_row[]  = $bgpPeerState;
    $table_row[]  = $peer['bgpPeerLocalAddr'];
    $table_row[]  = format_uptime($bgpPeerFsmEstablishedTime);
    $table_row[]  = format_uptime($bgpPeerInUpdateElapsedTime);
    $table_rows[] = $table_row;
    unset($table_row);

    if ($cisco_version || $vendor_mib) {

        // Check each AFI/SAFI for this peer
        $query_afis = 'SELECT * FROM `bgpPeers_cbgp` WHERE `device_id` = ? AND `bgpPeer_id` = ?';
        foreach (dbFetchRows($query_afis, [$device['device_id'], $peer['bgpPeer_id']]) as $peer_afi) {
            $afi  = $peer_afi['afi'];
            $safi = $peer_afi['safi'];
            print_debug("$afi $safi");

            if (isset($af_list[$peer_ip][$afi][$safi])) {
                foreach ($cbgp_defs as $def_oid) {
                    $cbgp_oid = 'cbgp' . $def_oid; // PeerAcceptedPrefixes -> cbgpPeerAcceptedPrefixes

                    $$cbgp_oid = $af_list[$peer_ip][$afi][$safi][$def_oid];
                }
            } elseif ($vendor_mib) {
                // FIXME. Still hard to migrate to file include
                $def = $config['mibs'][$vendor_mib]['bgp'];

                // Missing: cbgpPeerAdminLimit cbgpPeerPrefixThreshold cbgpPeerPrefixClearThreshold cbgpPeerSuppressedPrefixes cbgpPeerWithdrawnPrefixes

                // See posible AFI/SAFI here: https://www.juniper.net/techpubs/en_US/junos12.3/topics/topic-map/bgp-multiprotocol.html
                $afi_num  = is_numeric($afi) ? $afi : $config['routing_afis_name'][$afi];
                $safi_num = is_numeric($safi) ? $safi : $config['routing_safis_name'][$safi];

                //$peer_index = $vendor_peers[$peer_ip][$peer_as][$vendor_PeerIndex];
                $peer_index = $peer_afi['bgpPeerIndex'];
                if (isset($vendor_counters[$peer_index])) {
                    // HUAWEI-BGP-VPN-MIB
                    $index = $peer_index;
                } elseif ($vendor_mib === 'VIPTELA-OPER-BGP') {
                    // This mib has only one possible AFI/SAFI
                    $index = $peer_index . '.0';
                } elseif (isset($vendor_counters[$peer_index . '.' . $afi_num . '.' . $safi_num])) {
                    $index = $peer_index . '.' . $afi_num . '.' . $safi_num;
                    print_debug_vars($vendor_counters[$index]);
                } else {
                    // unknown index
                    $index = NULL;
                    print_debug("Unknown AFI/SAFI index");
                    print_debug_vars($peer_afi);
                    //$index = $peer_index . '.' . $afis[$afi] . '.' . $safis[$safi];
                }

                foreach ($cbgp_defs as $def_oid) {
                    $cbgp_oid   = 'cbgp' . $def_oid; // PeerAcceptedPrefixes -> cbgpPeerAcceptedPrefixes
                    $vendor_oid = $def['oids'][$def_oid]['oid'];

                    if (!safe_empty($index) && !safe_empty($vendor_oid)) {
                        $$cbgp_oid = $vendor_counters[$index][$vendor_oid];
                    } else {
                        $$cbgp_oid = ''; // FIXME. Should be 0
                    }
                }

                // $cbgpPeerAcceptedPrefixes   = $vendor_counters[$index][$vendor_PeerAcceptedPrefixes];
                // $cbgpPeerDeniedPrefixes     = $vendor_counters[$index][$vendor_PeerDeniedPrefixes];
                // $cbgpPeerAdvertisedPrefixes = $vendor_counters[$index][$vendor_PeerAdvertisedPrefixes];
                // $cbgpPeerSuppressedPrefixes = "";
                // $cbgpPeerWithdrawnPrefixes  = "";
            }

            // Update cbgp states
            $peer['c_update']['AcceptedPrefixes']     = $cbgpPeerAcceptedPrefixes;
            $peer['c_update']['DeniedPrefixes']       = $cbgpPeerDeniedPrefixes;
            $peer['c_update']['PrefixAdminLimit']     = $cbgpPeerPrefixAdminLimit;
            $peer['c_update']['PrefixThreshold']      = $cbgpPeerPrefixThreshold;
            $peer['c_update']['PrefixClearThreshold'] = $cbgpPeerPrefixClearThreshold;
            $peer['c_update']['AdvertisedPrefixes']   = $cbgpPeerAdvertisedPrefixes;
            $peer['c_update']['SuppressedPrefixes']   = $cbgpPeerSuppressedPrefixes;
            $peer['c_update']['WithdrawnPrefixes']    = $cbgpPeerWithdrawnPrefixes;

            // FIXME. Convert to multi-update
            dbUpdate($peer['c_update'], 'bgpPeers_cbgp', '`cbgp_id` = ?', [$peer_afi['cbgp_id']]);

            // Update cbgp StatsD
            if ($config['statsd']['enable'] == TRUE) {
                foreach (['AcceptedPrefixes', 'DeniedPrefixes', 'AdvertisedPrefixes', 'SuppressedPrefixes', 'WithdrawnPrefixes'] as $oid) {
                    // Update StatsD/Carbon
                    $r_oid = 'cbgpPeer' . $oid;
                    StatsD ::gauge(str_replace('.', '_', $device['hostname']) . '.' . 'bgp' . '.' . str_replace('.', '_', $peer_ip) . ".$afi.$safi" . '.' . $oid, $$r_oid);
                }
            }

            // Update RRD
            rrdtool_update_ng($device, 'cbgp', [
              'AcceptedPrefixes'   => $cbgpPeerAcceptedPrefixes,
              'DeniedPrefixes'     => $cbgpPeerDeniedPrefixes,
              'AdvertisedPrefixes' => $cbgpPeerAdvertisedPrefixes,
              'SuppressedPrefixes' => $cbgpPeerSuppressedPrefixes,
              'WithdrawnPrefixes'  => $cbgpPeerWithdrawnPrefixes,
            ],                $peer_ip . ".$afi.$safi");

            //$graphs['bgp_prefixes_'.$afi.$safi] = TRUE; // Not a device graph

            $check_metrics = [
              'AcceptedPrefixes'   => $cbgpPeerAcceptedPrefixes,
              'DeniedPrefixes'     => $cbgpPeerDeniedPrefixes,
              'AdvertisedPrefixes' => $cbgpPeerAdvertisedPrefixes,
              'SuppressedPrefixes' => $cbgpPeerSuppressedPrefixes,
              'WithdrawnPrefixes'  => $cbgpPeerWithdrawnPrefixes
            ];

            check_entity('bgp_peer_af', $peer_afi, $check_metrics);

            $c_table_row    = [];
            $c_table_row[]  = $peer_ip;
            $c_table_row[]  = $peer['bgpPeerRemoteAs'];
            $c_table_row[]  = $afi . "-" . $safi;
            $c_table_row[]  = $cbgpPeerAcceptedPrefixes;
            $c_table_row[]  = $cbgpPeerDeniedPrefixes;
            $c_table_row[]  = $cbgpPeerAdvertisedPrefixes;
            $c_table_rows[] = $c_table_row;
            unset($c_table_row);

        } # while
    } # os_group=cisco | vendors

} // End While loop on peers

if (!safe_empty($table_rows)) {
    echo(PHP_EOL);
    $headers = ['%WPeer IP%n', '%WASN%n', '%WAS%n', '%WAdmin%n', '%WState%n', '%WLocal IP%n', '%WEstablished Time%n', '%WLast Update%n'];
    print_cli_table($table_rows, $headers, "Sessions");

    $headers = ['%WPeer IP%n', '%WASN%n', '%WAFI/SAFI%n', '%WAccepted Pfx%n', '%WDenied Pfx%n', '%WAdvertised Pfx%n'];
    print_cli_table($c_table_rows, $headers, "Address Families");

}

foreach ($p_list as $peer_ip => $entry) {
    // Check if new peers found
    $force_discovery = $force_discovery || !empty($entry);
}

if ($snmp_incomplete) {
    print_debug("WARNING! BGP snmpwalk did not complete.");
    log_event("WARNING! BGP snmpwalk did not complete. Try to increase SNMP timeout on the device properties page.", $device, 'device', $device['device_id'], 7);
} elseif ($force_discovery) {
    // Force rediscover bgp peers
    print_debug("BGP peers list for this device changed, force rediscover BGP.");
    print_debug_vars($p_list, 1);
    force_discovery($device, 'bgp-peers');
}

// Clean
unset($bgp_peers, $vendor_peers, $vendor_mib, $cisco_version, $cisco_peers, $af_list, $def, $c_table_rows);

// EOF
