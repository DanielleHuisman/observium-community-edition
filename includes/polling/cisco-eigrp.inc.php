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

// Only run this on Cisco kit.
// Seems this MIB supported only in IOS Catalyst 6k/7k. See ftp://ftp.cisco.com/pub/mibs/supportlists/
// IOS 3560:  ftp://ftp.cisco.com/pub/mibs/supportlists/cat3560/cat3560-supportlist.html
// IOS 6k/7k: ftp://ftp.cisco.com/pub/mibs/supportlists/cisco7606/cisco7606-supportlist.html
// IOS-XE:    ftp://ftp.cisco.com/pub/mibs/supportlists/cat4000/cat4000-supportlist.html
//            ftp://ftp.cisco.com/pub/mibs/supportlists/asr1000/asr1000-supportlist.html
// IOS-XR:    ftp://ftp.cisco.com/pub/mibs/supportlists/asr9000/asr9000-supportlist.html
// ASA:       ftp://ftp.cisco.com/pub/mibs/supportlists/asa/asa-supportlist.html

if (is_device_mib($device, 'CISCO-EIGRP-MIB')) {

    // Poll VPNs

    print_cli_data("EIGRP VPNs");

    // cEigrpVpnInfo.cEigrpVpnTable.cEigrpVpnEntry.cEigrpVpnName.65536 = default

    foreach (dbFetchRows('SELECT * FROM `eigrp_vpns` WHERE `device_id` = ?', [$device['device_id']]) as $entry) {
        $vpn_db[$entry['eigrp_vpn']] = $entry;
    }

    $table = [];

    $vpn_poll = snmpwalk_multipart_oid($device, 'cEigrpVpnEntry', [], 'CISCO-EIGRP-MIB');

    foreach ($vpn_poll as $vpn_id => $vpn) {
        if (is_array($vpn_db[$vpn_id])) {
            if ($vpn_db[$vpn_id]['eigrp_vpn_name'] != $vpn['cEigrpVpnName']) {
                dbUpdate(['eigrp_vpn_name' => $vpn['cEigrpVpnName']], 'eigrp_vpns', '`eigrp_vpn` = ? AND `device_id` = ?', [$vpn_id, $device['device_id']]);
            }
            unset($vpn_db[$vpn_id]);
        } else {
            dbInsert(['eigrp_vpn' => $vpn_id, 'eigrp_vpn_name' => $vpn['cEigrpVpnName'], 'device_id' => $device['device_id']], 'eigrp_vpns');
        }
        $table[] = [$vpn_id, $vpn['cEigrpVpnName']];
    }

    foreach ($vpn_db as $entry) {
        dbDelete('eigrp_vpns', 'eigrp_vpn_id = ?', [$entry['eigrp_vpn_id']]);
    }

    print_cli_table($table, ['VPN ID', 'VPN Name']);
    unset($table);

    // End poll VPNs


    /////////////////////
    ///  Poll ASes    ///
    /////////////////////

    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpNbrCount.65536.2449 = 3
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpHellosSent.65536.2449 = 56609
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpHellosRcvd.65536.2449 = 56552
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpUpdatesSent.65536.2449 = 20
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpUpdatesRcvd.65536.2449 = 17
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpQueriesSent.65536.2449 = 0
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpQueriesRcvd.65536.2449 = 1
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpRepliesSent.65536.2449 = 1
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpRepliesRcvd.65536.2449 = 0
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpAcksSent.65536.2449 = 13
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpAcksRcvd.65536.2449 = 14
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpInputQHighMark.65536.2449 = 3
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpInputQDrops.65536.2449 = 0
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpSiaQueriesSent.65536.2449 = 0
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpSiaQueriesRcvd.65536.2449 = 0
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpAsRouterIdType.65536.2449 = ipv4
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpAsRouterId.65536.2449 = "x.x.x.x"
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpTopoRoutes.65536.2449 = 14
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpHeadSerial.65536.2449 = 1
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpNextSerial.65536.2449 = 27
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpXmitPendReplies.65536.2449 = 0
    // cEigrpAsInfo.cEigrpTraffStatsTable.cEigrpTraffStatsEntry.cEigrpXmitDummies.65536.2449 = 0

    print_cli_data("EIGRP ASes");

    foreach (dbFetchRows('SELECT * FROM `eigrp_ases` WHERE `device_id` = ?', [$device['device_id']]) as $entry) {
        $ases_db[$entry['eigrp_vpn'] . '-' . $entry['eigrp_as']] = $entry;
    }

    $table = [];

    // Only poll ASes if we found a VPN
    if (count($vpn_poll)) {
        $as_poll = snmpwalk_multipart_oid($device, 'cEigrpTraffStatsEntry', [], 'CISCO-EIGRP-MIB');
    }

    foreach ($as_poll as $vpn => $as_list) {
        foreach ($as_list as $as => $entry) {

            // Fix IP addresses because Cisco sometimes suck
            $entry['cEigrpAsRouterId'] = hex2ip($entry['cEigrpAsRouterId']);

            $db_data = [];

            foreach (['cEigrpNbrCount', 'cEigrpAsRouterIdType', 'cEigrpAsRouterId', 'cEigrpTopoRoutes'] as $datum) {
                $db_data[$datum] = $entry[$datum];
            }

            if (is_array($ases_db[$vpn . '-' . $as])) {
                $as_db = $ases_db[$vpn . '-' . $as];

                dbUpdate($db_data, 'eigrp_ases', '`eigrp_as_id` = ?', [$as_db['eigrp_as_id']]);

                // Remove port_db entry to keep track of what exists.
                unset ($ases_db[$vpn . '-' . $as]);

            } else {

                // Add extra data for insertion
                $db_data['eigrp_vpn'] = $vpn;
                $db_data['eigrp_as']  = $as;
                $db_data['device_id'] = $device['device_id'];

                dbInsert($db_data, 'eigrp_ases');
                echo('+');
            }

            $table[] = [$vpn, $as, $entry['cEigrpAsRouterId'], $entry['cEigrpNbrCount'], $entry['cEigrpTopoRoutes']];

            $rrd_fields = ['cEigrpNbrCount', 'cEigrpHellosSent', 'cEigrpHellosRcvd', 'cEigrpUpdatesSent', 'cEigrpUpdatesRcvd', 'cEigrpQueriesSent', 'cEigrpQueriesRcvd', 'cEigrpRepliesSent', 'cEigrpRepliesRcvd',
                           'cEigrpAcksSent', 'cEigrpAcksRcvd', 'cEigrpInputQHighMark', 'cEigrpInputQDrops', 'cEigrpSiaQueriesSent', 'cEigrpSiaQueriesRcvd', 'cEigrpTopoRoutes', 'cEigrpHeadSerial', 'cEigrpNextSerial',
                           'cEigrpXmitPendReplies', 'cEigrpXmitDummies'];

            $rrd_data = [];

            foreach ($rrd_fields as $field) {
                $rrd_field            = str_replace('cEigrp', '', $field);
                $rrd_data[$rrd_field] = $entry[$field];
            }

            // Write per-ASN EIGRP statistics
            rrdtool_update_ng($device, 'cisco-eigrp-as', $rrd_data, "$vpn-$as");

        }
    }

    foreach ($ases_db as $entry) {
        dbDelete('eigrp_ases', 'eigrp_as_id = ?', [$entry['eigrp_as_id']]);
    }

    print_cli_table($table, ['VPN ID', 'ASN', 'RTR ID', 'Nbrs', 'Routes']);
    unset($table);

    // End poll ASes


    /////////////////////
    ///  Poll Ports   ///
    /////////////////////

    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpPeerCount.65536.2449.10 = 1
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpXmitReliableQ.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpXmitUnreliableQ.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpMeanSrtt.65536.2449.10 = 32
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpPacingReliable.65536.2449.10 = 11
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpPacingUnreliable.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpMFlowTimer.65536.2449.10 = 139
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpPendingRoutes.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpHelloInterval.65536.2449.10 = 5
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpXmitNextSerial.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpUMcasts.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpRMcasts.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpUUcasts.65536.2449.10 = 4
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpRUcasts.65536.2449.10 = 10
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpMcastExcepts.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpCRpkts.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpAcksSuppressed.65536.2449.10 = 0
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpRetransSent.65536.2449.10 = 5
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpOOSrvcd.65536.2449.10 = 1
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpAuthMode.65536.2449.10 = none
    // cEigrpInterfaceInfo.cEigrpInterfaceTable.cEigrpInterfaceEntry.cEigrpAuthKeyChain.65536.2449.10 =

    print_cli_data("EIGRP Ports");

    $ports_db = [];
    foreach (dbFetchRows('SELECT * FROM `eigrp_ports` WHERE `device_id` = ?', [$device['device_id']]) as $db_port) {
        $ports_db[$db_port['eigrp_vpn'] . '-' . $db_port['eigrp_as'] . '-' . $db_port['eigrp_ifIndex']] = $db_port;
    }

    $table = [];

    // Only poll ports if we found a VPN
    if (count($vpn_poll)) {
        $ports_poll = snmpwalk_multipart_oid($device, 'CEigrpInterfaceEntry', [], 'CISCO-EIGRP-MIB');
    }

    foreach ($ports_poll as $vpn => $as_list) {
        foreach ($as_list as $as => $if_list) {
            foreach ($if_list as $ifIndex => $eigrp_port) {

                $port = get_port_by_index_cache($device['device_id'], $ifIndex);

                if (is_array($ports_db[$vpn . '-' . $as . '-' . $ifIndex])) {
                    $eigrp_update = NULL;

                    $port_db = $ports_db[$vpn . '-' . $as . '-' . $ifIndex];

                    if ($port['port_id'] != $port_db['port_id']) {
                        $eigrp_update['port_id'] = $port['port_id'];
                    }
                    if ($eigrp_port['cEigrpAuthMode'] != $port_db['eigrp_authmode']) {
                        $eigrp_update['eigrp_authmode'] = $eigrp_port['cEigrpAuthMode'];
                    }
                    if ($eigrp_port['cEigrpMeanSrtt'] != $port_db['eigrp_MeanSrtt']) {
                        $eigrp_update['eigrp_MeanSrtt'] = $eigrp_port['cEigrpMeanSrtt'];
                    }

                    if (is_array($eigrp_update)) {
                        dbUpdate($eigrp_update, 'eigrp_ports', '`eigrp_port_id` = ?', [$ports_db[$vpn . '-' . $as . '-' . $ifIndex]['eigrp_port_id']]);
                    }
                    unset ($eigrp_update);

                    // Remove port_db entry to keep track of what exists.
                    unset ($ports_db[$vpn . '-' . $as . '-' . $ifIndex]);

                } else {
                    dbInsert(['eigrp_vpn' => $vpn, 'eigrp_as' => $as, 'eigrp_ifIndex' => $ifIndex, 'port_id' => $port['port_id'], 'device_id' => $device['device_id'], 'eigrp_peer_count' => $eigrp_port['cEigrpPeerCount']], 'eigrp_ports');
                    echo('+');
                }

                // Write per-interface EIGRP statistics
                rrdtool_update_ng($device, 'cisco-eigrp-port', [
                  'MeanSrtt'       => $eigrp_port['cEigrpMeanSrtt'],
                  'UMcasts'        => $eigrp_port['cEigrpUMcasts'],
                  'RMcasts'        => $eigrp_port['cEigrpRMcasts'],
                  'UUcasts'        => $eigrp_port['cEigrpUUcasts'],
                  'RUcasts'        => $eigrp_port['cEigrpRUcasts'],
                  'McastExcepts'   => $eigrp_port['cEigrpMcastExcepts'],
                  'CRpkts'         => $eigrp_port['cEigrpCRpkts'],
                  'AcksSuppressed' => $eigrp_port['cEigrpAcksSuppressed'],
                  'RetransSent'    => $eigrp_port['cEigrpRetransSent'],
                  'OOSrvcd'        => $eigrp_port['cEigrpOOSrvcd'],
                ],                "$vpn-$as-$ifIndex");

                $table[] = [$vpn, $as, $port['port_label_short']];

                unset ($eigrp_update);
            }
        }
    }

    // Delete entries that no longer exist on the device
    foreach ($ports_db as $entry) {
        dbDelete('eigrp_ports', 'eigrp_port_id = ?', [$entry['eigrp_port_id']]);
    }

    print_cli_table($table, ['VPN ID', 'ASN', 'Port']);
    unset($table);

    /// Finish Polling Ports


    /////////////////////
    ///  Poll Peers   ///
    /////////////////////

    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpPeerAddrType.65536.2449.2 = ipv4
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpPeerAddr.65536.2449.2 = "x.x.x.x"
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpPeerIfIndex.65536.2449.2 = 11
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpHoldTime.65536.2449.2 = 10
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpUpTime.65536.2449.2 = 1d00h
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpSrtt.65536.2449.2 = 48
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpRto.65536.2449.2 = 288
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpPktsEnqueued.65536.2449.2 = 0
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpLastSeq.65536.2449.2 = 16
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpVersion.65536.2449.2 = 49.54/46.4847504648
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpRetrans.65536.2449.2 = 0
    // cEigrpPeerInfo.cEigrpPeerTable.cEigrpPeerEntry.cEigrpRetries.65536.2449.2 = 0

    print_cli_data("EIGRP Peers");

    $table = [];

    $peers_db = [];
    foreach (dbFetchRows('SELECT * FROM `eigrp_peers` WHERE `device_id` = ?', [$device['device_id']]) as $entry) {
        $peers_db[$entry['eigrp_vpn'] . '-' . $entry['eigrp_as'] . '-' . $entry['peer_addr']] = $entry;
    }

    // Only poll peers if we found a VPN
    if (count($vpn_poll)) {
        $peers_poll = snmpwalk_multipart_oid($device, 'cEigrpPeerEntry', [], 'CISCO-EIGRP-MIB');
    }

    foreach ($peers_poll as $vpn => $as_list) {
        foreach ($as_list as $as => $peers) {
            foreach ($peers as $peer_index => $peer) {
                // rewrite uptime value to seconds from weirdoformat
                $peer['cEigrpUpTime']   = uptime_to_seconds($peer['cEigrpUpTime']);
                $peer['cEigrpPeerAddr'] = hex2ip($peer['cEigrpPeerAddr']);

                $db_data = [];
                foreach (['cEigrpPeerAddrType' => 'peer_addrtype',
                          'cEigrpPeerAddr'     => 'peer_addr',
                          'cEigrpPeerIfIndex'  => 'peer_ifindex',
                          'cEigrpHoldTime'     => 'peer_holdtime',
                          'cEigrpUpTime'       => 'peer_uptime',
                          'cEigrpSrtt'         => 'peer_srtt',
                          'cEigrpRto'          => 'peer_rto',
                          'cEigrpVersion'      => 'peer_version'] as $datum => $field) {
                    $db_data[$field] = $peer[$datum];
                }

                if (is_array($peers_db[$vpn . '-' . $as . '-' . $peer['cEigrpPeerAddr']])) {
                    $peer_db = $peers_db[$vpn . '-' . $as . '-' . $peer['cEigrpPeerAddr']];

                    dbUpdate($db_data, 'eigrp_peers', '`eigrp_peer_id` = ?', [$peer_db['eigrp_peer_id']]);

                    // Remove port_db entry to keep track of what exists.
                    unset ($peers_db[$vpn . '-' . $as . '-' . $peer['cEigrpPeerAddr']]);

                } else {

                    // Add extra data for insertion
                    $db_data['eigrp_vpn'] = $vpn;
                    $db_data['eigrp_as']  = $as;
                    $db_data['device_id'] = $device['device_id'];

                    dbInsert($db_data, 'eigrp_peers');
                    echo('+');
                }

                // Build the array to send to RRD
                $rrd_fields = ['cEigrpHoldTime', 'cEigrpUpTime', 'cEigrpSrtt', 'cEigrpRto', 'cEigrpPktsEnqueued', 'cEigrpLastSeq', 'cEigrpRetrans', 'cEigrpRetries'];
                $rrd_data   = [];
                foreach ($rrd_fields as $field) {
                    $rrd_field            = str_replace('cEigrp', '', $field);
                    $rrd_data[$rrd_field] = $peer[$field];
                }

                // Write per-ASN EIGRP statistics
                rrdtool_update_ng($device, 'cisco-eigrp-peer', $rrd_data, "$vpn-$as-" . $peer['cEigrpPeerAddr']);

                $table[] = [$vpn, $as, $peer['cEigrpPeerAddr']];
            }
        }
    }

    foreach ($peers_db as $entry) {
        dbDelete('eigrp_peers', 'eigrp_peer_id = ?', [$entry['eigrp_peer_id']]);
    }

    print_cli_table($table, ['VPN ID', 'ASN', 'Address']);
    unset($table);

} // End if CISCO-EIGRP-MIB

// EOF
