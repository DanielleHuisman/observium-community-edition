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

/// FIXME. Convert this module to MIB based, add other MIBs

$mib = 'CISCO-IPSEC-FLOW-MONITOR-MIB';

if (!is_device_mib($device, 'CISCO-IPSEC-FLOW-MONITOR-MIB')) {
    return;
}

// Indexes: peer_addr + local_address + endpoint_hash
$valid['ipsec_tunnels'] = [];

// Cache DB entries
$t_db = dbFetchRows('SELECT * FROM `ipsec_tunnels` WHERE `device_id` = ?', [$device['device_id']]);
foreach ($t_db as $t) {
    if (!empty($t['peer_addr']) && !empty($t['tunnel_endhash'])) {
        // Multiple tunnels with same peer_addr but different endpoints!
        $tunnels_db[$t['local_addr'] . '-' . $t['peer_addr']][$t['tunnel_endhash']] = $t;
    } else {
        // Remove ?
        dbDelete('ipsec_tunnels', '`tunnel_id` =  ?', [$t['tunnel_id']]);
    }
}
$t_db_count = count($t_db);

$device_context = $device;
if (!$t_db_count) {
    // Set retries to 0 for speedup first walking, only if previously polling also empty (DB empty)
    $device_context['snmp_retries'] = 0;
}

print_cli_data("Collecting", "CISCO-IPSEC-FLOW-MONITOR-MIB::cikeTunnelEntry", 3);

$flags = OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX;
//$flags = OBS_SNMP_ALL_HEX;
$ike_poll = snmpwalk_cache_oid($device_context, 'cikeTunnelEntry', [], 'CISCO-IPSEC-FLOW-MONITOR-MIB', NULL, $flags);
unset($device_context);
if ($GLOBALS['snmp_status']) {
    print_cli_data("Collecting", "CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecTunnelEntry", 3);
    $ipsec_poll = snmpwalk_cache_oid($device, 'cipSecTunnelEntry', [], 'CISCO-IPSEC-FLOW-MONITOR-MIB', NULL, $flags);

    // F.. cisco issue, some time it return incorrect multiline data, ie:
    //   cipSecEndPtLocalAddr1.6802.1 = "
    //   ^KL="
    // instead:
    //   cipSecEndPtLocalAddr1.6803.1 = "0A 0E 33 12 "
    print_cli_data("Collecting", "CISCO-IPSEC-FLOW-MONITOR-MIB::cipSecEndPtEntry", 3);
    $ipsec_endpt_poll = snmpwalk_cache_twopart_oid($device, 'cipSecEndPtEntry', [], 'CISCO-IPSEC-FLOW-MONITOR-MIB', NULL, $flags);

    //print_cli_data("Collecting", "CISCO-IPSEC-FLOW-MONITOR-MIB::cikePeerCorrTable", 3);
    //$ike_peer_poll = snmpwalk_cache_oid($device, 'cikePeerCorrTable', array(), 'CISCO-IPSEC-FLOW-MONITOR-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
}

// FIX for cisco issue, not correct IKE index
// https://bst.cloudapps.cisco.com/bugsearch/bug/CSCvb16714/
$ike_poll_index = [];
foreach ($ike_poll as $index => $entry) {
    foreach (['cikeTunLocalAddr', 'cikeTunRemoteAddr'] as $oid) {
        $entry[$oid]            = hex2ip($entry[$oid]);
        $ike_poll[$index][$oid] = $entry[$oid];
    }
    foreach (['cikeTunLocalValue', 'cikeTunLocalName', 'cikeTunRemoteValue', 'cikeTunRemoteName'] as $oid) {
        $entry[$oid]            = snmp_hexstring($entry[$oid]);
        $ike_poll[$index][$oid] = $entry[$oid];
    }
    $ike_poll_index[$entry['cikeTunLocalAddr']][$entry['cikeTunRemoteAddr']] = $index;
}

/* Yah, I create this for conceptual converting index into correct values, but not need this anyway (mike) :) */
/*
// Parse ike index to readable values
$ike_peer_corr = array();
foreach ($ike_peer_poll as $index => $entry)
{
  // 1.4.217.79.6.156.1.4.80.93.52.3.2.2
  $index_parts   = explode('.', $index);
  // Local
  $cikePeerCorrLocalType   = array_shift($index_parts) == 1 ? 'ipAddrPeer' : 'namePeer';
  $str_len   = array_shift($index_parts);
  $str_parts = array_splice($index_parts, 0, $str_len);
  $cikePeerCorrLocalValue  = implode('.', $str_parts);
  if ($cikePeerCorrLocalType == 'ipAddrPeer')
  {
    $str_ip = snmp_oid_to_string($str_len.'.'.$cikePeerCorrLocalValue);
    if (get_ip_version($str_ip))
    {
      $cikePeerCorrLocalValue  = $str_ip;
    }
    else if ($str_len == 16)
    {
      $cikePeerCorrLocalValue = snmp2ipv6($cikePeerCorrLocalValue);
    }
  } else {
    // display string
    $cikePeerCorrLocalValue = snmp_oid_to_string($str_len.'.'.$cikePeerCorrLocalValue);
  }
  // Remote
  $cikePeerCorrRemoteType  = array_shift($index_parts) == 1 ? 'ipAddrPeer' : 'namePeer';
  $str_len   = array_shift($index_parts);
  $str_parts = array_splice($index_parts, 0, $str_len);
  $cikePeerCorrRemoteValue  = implode('.', $str_parts);
  if ($cikePeerCorrRemoteType == 'ipAddrPeer')
  {
    $str_ip = snmp_oid_to_string($str_len.'.'.$cikePeerCorrRemoteValue);
    if (get_ip_version($str_ip))
    {
      $cikePeerCorrRemoteValue = $str_ip;
    }
    else if ($str_len == 16)
    {
      $cikePeerCorrRemoteValue = snmp2ipv6($cikePeerCorrRemoteValue);
    }
  } else {
    // display string
    $cikePeerCorrRemoteValue = snmp_oid_to_string($str_len.'.'.$cikePeerCorrRemoteValue);
  }
  // Index & Seq
  $cikePeerCorrIntIndex    = array_shift($index_parts);
  $cikePeerCorrSeqNum      = array_shift($index_parts);

  // Now correct array
  $ike_peer_corr[$cikePeerCorrLocalType][$cikePeerCorrLocalValue][$cikePeerCorrRemoteType][$cikePeerCorrRemoteValue][$cikePeerCorrIntIndex][$cikePeerCorrSeqNum] = $entry;
}
*/

if (OBS_DEBUG > 1 && count($ike_poll)) {
    print_vars($ipsec_poll);
    print_vars($ipsec_endpt_poll);
    print_vars($ike_poll);
    print_vars($ike_poll_index);
    //print_vars($ike_peer_poll);
    //print_vars($ike_peer_corr);
}

$json_oids = ['cipSecTunIkeTunnelIndex', 'cipSecTunIkeTunnelAlive', 'cipSecTunLocalAddr', 'cipSecTunRemoteAddr',
              'cipSecTunKeyType', 'cipSecTunEncapMode', 'cipSecTunLifeSize', 'cipSecTunLifeTime', /*'cipSecTunActiveTime',*/
              'cipSecTunSaLifeSizeThreshold', 'cipSecTunSaLifeTimeThreshold', /*'cipSecTunTotalRefreshes',*/
              /*'cipSecTunExpiredSaInstances', 'cipSecTunCurrentSaInstances',*/
              'cipSecTunInSaDiffHellmanGrp', 'cipSecTunInSaEncryptAlgo', 'cipSecTunInSaAhAuthAlgo', 'cipSecTunInSaEspAuthAlgo', 'cipSecTunInSaDecompAlgo',
              'cipSecTunOutSaDiffHellmanGrp', 'cipSecTunOutSaEncryptAlgo', 'cipSecTunOutSaAhAuthAlgo', 'cipSecTunOutSaEspAuthAlgo', 'cipSecTunOutSaCompAlgo',
              'cikeTunLocalType', 'cikeTunLocalValue', 'cikeTunLocalAddr', 'cikeTunLocalName',
              'cikeTunRemoteType', 'cikeTunRemoteValue', 'cikeTunRemoteAddr', 'cikeTunRemoteName',
              'cikeTunNegoMode', 'cikeTunDiffHellmanGrp', 'cikeTunEncryptAlgo', 'cikeTunHashAlgo', 'cikeTunAuthMethod',
              'cikeTunLifeTime', /*'cikeTunActiveTime',*/];

$table_rows    = [];
$table_headers = ['%WIndex%n', '%WLocal%n', '%WRemote%n', '%WName%n', '%WState%n', '%WIKE Alive%n', '%WHC%n'];

foreach ($ipsec_poll as $index => $tunnel) {
    foreach (['cipSecTunLocalAddr', 'cipSecTunRemoteAddr'] as $oid) {
        if (isset($tunnel[$oid])) {
            $tunnel[$oid] = hex2ip($tunnel[$oid]);
        }
    }
    $local_address = $tunnel['cipSecTunLocalAddr'];
    $peer_address  = $tunnel['cipSecTunRemoteAddr'];

    $ike_exist = isset($ike_poll[$tunnel['cipSecTunIkeTunnelIndex']]);
    if ($ike_exist) {
        $tunnel = array_merge($tunnel, $ike_poll[$tunnel['cipSecTunIkeTunnelIndex']]);
    } elseif (isset($ike_poll_index[$local_address][$peer_address])) {
        // FIX for cisco issue, not correct IKE index
        // https://bst.cloudapps.cisco.com/bugsearch/bug/CSCvb16714/
        $ike_exist                         = TRUE;
        $tunnel['cipSecTunIkeTunnelAlive'] = 'true';
        $tunnel['cipSecTunIkeTunnelIndex'] = $ike_poll_index[$local_address][$peer_address];
        $tunnel                            = array_merge($tunnel, $ike_poll[$tunnel['cipSecTunIkeTunnelIndex']]);

    } else {
        // not exist, what todo?
        continue;
    }

    // End points
    $tunnel_endpt_json = [];
    foreach ($ipsec_endpt_poll[$index] as $index_endpt => $entry) {
        $endpt          = [];
        $endpt['local'] = hex2ip($entry['cipSecEndPtLocalAddr1']);
        $ip2            = hex2ip($entry['cipSecEndPtLocalAddr2']);
        switch ($entry['cipSecEndPtLocalType']) {
            case 'ipSubnet':
                $endpt['local'] .= '/' . netmask2cidr($ip2);
                break;
            case 'ipAddrRange':
                $endpt['local'] .= ' - ' . $ip2;
                break;
        }
        $endpt['remote'] = hex2ip($entry['cipSecEndPtRemoteAddr1']);
        $ip2             = hex2ip($entry['cipSecEndPtRemoteAddr2']);
        switch ($entry['cipSecEndPtRemoteType']) {
            case 'ipSubnet':
                $endpt['remote'] .= '/' . netmask2cidr($ip2);
                break;
            case 'ipAddrRange':
                $endpt['remote'] .= ' - ' . $ip2;
                break;
        }
        $tunnel_endpt_json[$index_endpt] = $endpt;
    }
    $tunnel_endpt_json = json_encode($tunnel_endpt_json);
    $tunnel_endpt_hash = md5($tunnel_endpt_json); // Hash for index

    $tunnel_json = [];
    foreach ($json_oids as $oid) {
        if (isset($tunnel[$oid])) {
            $tunnel_json[$oid] = $tunnel[$oid];
        }
    }
    $tunnel_json = json_encode($tunnel_json);

    $db_index  = $local_address . '-' . $peer_address; // . '-' . $tunnel_endpt_hash;
    $db_insert = ['tunnel_index'        => $index,
                  'tunnel_ike_index'    => $tunnel['cipSecTunIkeTunnelIndex'],
                  'peer_addr'           => $tunnel['cipSecTunRemoteAddr'], //$tunnel['cikeTunRemoteValue'],
                  'local_addr'          => $tunnel['cipSecTunLocalAddr'],  //$tunnel['cikeTunLocalValue'],
                  //'local_port'          => get_port_id_by_ip_cache($device, $tunnel['cipSecTunLocalAddr']),
                  'tunnel_status'       => $tunnel['cipSecTunStatus'], // Yah, this is not really status! When tunnel exist, this always active!
                  'tunnel_lifetime'     => round($tunnel['cipSecTunActiveTime'] / 100),
                  'tunnel_ike_alive'    => $tunnel['cipSecTunIkeTunnelAlive'],
                  'tunnel_ike_lifetime' => round($tunnel['cikeTunActiveTime'] / 100),
                  'tunnel_json'         => $tunnel_json,
                  'tunnel_endpoint'     => $tunnel_endpt_json,
                  'tunnel_endhash'      => $tunnel_endpt_hash,
                  'tunnel_name'         => $tunnel['cikeTunLocalName'],
                  'mib'                 => $mib,
                  'tunnel_deleted'      => 0];

    $rrd_change_hash = FALSE;
    $tunnel_insert   = !is_array($tunnels_db[$db_index][$tunnel_endpt_hash]);
    // Check if local/remote pair exist, but endpoint changed
    if ($tunnel_insert && !empty($tunnels_db[$db_index])) {
        foreach ($tunnels_db[$db_index] as $old_hash => $entry) {
            if ($entry['tunnel_index'] == $index &&
                $entry['tunnel_ike_index'] == $tunnel['cipSecTunIkeTunnelIndex'] &&
                $entry['tunnel_name'] == $tunnel['cikeTunLocalName']) {
                // This is same tunnel, but endpoint changed!
                $tunnel_insert     = FALSE;
                $rrd_change_hash   = $tunnel_endpt_hash; // store new hash for rrd change
                $tunnel_endpt_hash = $old_hash;
                print_debug("IPSEC tunnel End Point changed hash: $old_hash -> $rrd_change_hash");
                break;
            }
        }
    }

    if ($tunnel_insert) {
        // Add new
        $db_insert['device_id']    = $device['device_id'];
        $db_insert['tunnel_added'] = time() - $db_insert['tunnel_ike_lifetime'];
        $tunnel_id                 = dbInsert($db_insert, 'ipsec_tunnels');
    } else {

        $tunnel_db = $tunnels_db[$db_index][$tunnel_endpt_hash];

        // Added unixtime
        if (empty($tunnel_db['tunnel_added'])) {
            $db_insert['tunnel_added'] = time() - $db_insert['tunnel_ike_lifetime'];
        }

        $db_update = [];

        foreach ($db_insert as $param => $value) {
            if ($tunnel_db[$param] != $value) {
                $db_update[$param] = $value;
            }
        }

        if ($db_update) {
            $updated = dbUpdate($db_update, 'ipsec_tunnels', '`tunnel_id` = ?', [$tunnel_db['tunnel_id']]);
        }
    }

    $table_row   = [];
    $table_row[] = $index;
    $table_row[] = $tunnel['cipSecTunLocalAddr'];
    $table_row[] = $tunnel['cipSecTunRemoteAddr'];
    $table_row[] = $tunnel['cikeTunLocalName'];
    $table_row[] = $tunnel['cipSecTunStatus'];
    $table_row[] = $tunnel['cipSecTunIkeTunnelAlive'];


    if (is_numeric($tunnel['cipSecTunHcInOctets']) && is_numeric($tunnel['cipSecTunHcInDecompOctets']) &&
        is_numeric($tunnel['cipSecTunHcOutOctets']) && is_numeric($tunnel['cipSecTunHcOutUncompOctets'])) {
        //echo('HC ');
        $table_row[]                        = "%gyes%n";
        $tunnel['cipSecTunInOctets']        = $tunnel['cipSecTunHcInOctets'];
        $tunnel['cipSecTunInDecompOctets']  = $tunnel['cipSecTunHcInDecompOctets'];
        $tunnel['cipSecTunOutOctets']       = $tunnel['cipSecTunHcOutOctets'];
        $tunnel['cipSecTunOutUncompOctets'] = $tunnel['cipSecTunHcOutUncompOctets'];
    } else {
        $table_row[] = "%rno%n";
    }

    if ($ike_exist) {
        //$rrd_index = $local_address . '-' . $peer_address . '-' . $tunnel_endpt_hash;
        $rrd_index = $db_index . '-' . $tunnel_endpt_hash;

        // FIXME, remove later
        //rename_rrd($device, 'ipsectunnel-'.$tunnel['cikeTunLocalName'], 'ipsectunnel-'.$rrd_index);
        rename_rrd($device, 'ipsectunnel-' . $peer_address, 'ipsectunnel-' . $rrd_index);

        if ($rrd_change_hash) {
            // rename if tunnel endpoints changed
            rename_rrd($device, 'ipsectunnel-' . $rrd_index, 'ipsectunnel-' . $db_index . '-' . $rrd_change_hash);
            $rrd_index = $db_index . '-' . $rrd_change_hash;
        }

        rrdtool_update_ng($device, 'cipsec-tunnels', [
          'TunInOctets'         => $tunnel['cipSecTunInOctets'],
          'TunInDecompOctets'   => $tunnel['cipSecTunInDecompOctets'],
          'TunInPkts'           => $tunnel['cipSecTunInPkts'],
          'TunInDropPkts'       => $tunnel['cipSecTunInDropPkts'],
          'TunInReplayDropPkts' => $tunnel['cipSecTunInReplayDropPkts'],
          'TunInAuths'          => $tunnel['cipSecTunInAuths'],
          'TunInAuthFails'      => $tunnel['cipSecTunInAuthFails'],
          'TunInDecrypts'       => $tunnel['cipSecTunInDecrypts'],
          'TunInDecryptFails'   => $tunnel['cipSecTunInDecryptFails'],
          'TunOutOctets'        => $tunnel['cipSecTunOutOctets'],
          'TunOutUncompOctets'  => $tunnel['cipSecTunOutUncompOctets'],
          'TunOutPkts'          => $tunnel['cipSecTunOutPkts'],
          'TunOutDropPkts'      => $tunnel['cipSecTunOutDropPkts'],
          'TunOutAuths'         => $tunnel['cipSecTunOutAuths'],
          'TunOutAuthFails'     => $tunnel['cipSecTunOutAuthFails'],
          'TunOutEncrypts'      => $tunnel['cipSecTunOutEncrypts'],
          'TunOutEncryptFails'  => $tunnel['cipSecTunOutEncryptFails'],
        ],                $rrd_index);

        $graphs['ipsec_tunnels'] = TRUE;
    }

    $table_rows[] = $table_row;
    unset($table_row);

    unset($tunnels_db[$db_index][$tunnel_endpt_hash]);
    $valid['ipsec_tunnels'][$db_index][$db_insert['tunnel_endhash']] = 1;
}

print_cli_table($table_rows, $table_headers);
unset($table_rows, $table_headers);

foreach ($tunnels_db as $entry) {
    foreach ($entry as $tunnel) {
        //echo "will delete tunnel: ".$tunnel['local_addr']."/".$tunnel['peer_addr']." with id:".$tunnel['tunnel_id']."". PHP_EOL;
        if (empty($tunnel['tunnel_endhash'])) {
            // old data
            dbDelete('ipsec_tunnels', '`tunnel_id` =  ?', [$tunnel['tunnel_id']]);
        } else {
            $updated = dbUpdate(['tunnel_deleted' => 1], 'ipsec_tunnels', '`tunnel_id` = ?', [$tunnel['tunnel_id']]);
        }
    }
}

unset($data, $oid, $tunnel, $tunnels_db, $tunnel_db, $ipsec_poll, $ike_poll, $ipsec_endpt_poll);

// EOF
