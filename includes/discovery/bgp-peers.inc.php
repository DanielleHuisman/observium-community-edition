<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2022 Observium Limited
 *
 */

// 'BGP4-MIB', 'CISCO-BGP4-MIB', 'BGP4-V2-MIB-JUNIPER', 'FORCE10-BGP4-V2-MIB', 'ARISTA-BGP4V2-MIB', 'FOUNDRY-BGP4V2-MIB', 'HUAWEI-BGP-VPN-MIB'
if (!$config['enable_bgp'] || !is_device_mib($device, 'BGP4-MIB')) {
  // Note, BGP4-MIB is main MIB, without it, the rest will not be checked
  if ($device['bgpLocalAs']) {
    // Clean old discovered peers?
    log_event('BGP Local ASN removed: AS' . $device['bgpLocalAs'], $device, 'device', $device['device_id']);
    dbUpdate([ 'bgpLocalAs' => [ 'NULL' ] ], 'devices', 'device_id = ?', [ $device['device_id'] ]);
    print_cli_data("Updated ASN", $device['bgpLocalAs'] . " -> ''", 2);
  }
  return;
}
/* Moved to definitions
   $vendor_oids = array(
      // Juniper BGP4-V2 MIB
      'BGP4-V2-MIB-JUNIPER' => array('vendor_use_index'          => array('jnxBgpM2PeerRemoteAddr'     => 1,
                                                                          'jnxBgpM2PeerRemoteAddrType' => 1,
                                                                          'jnxBgpM2PeerLocalAddr'      => 1),
                                     'vendor_PeerTable'          => 'jnxBgpM2PeerTable',
                                     'vendor_PeerAdminStatus'    => 'jnxBgpM2PeerStatus',     //'jnxBgpM2CfgPeerAdminStatus' not exist in JunOS
                                     'vendor_PeerRemoteAs'       => 'jnxBgpM2PeerRemoteAs',
                                     'vendor_PeerRemoteAddr'     => 'jnxBgpM2PeerRemoteAddr',
                                     'vendor_PeerLocalAddr'      => 'jnxBgpM2PeerLocalAddr',
                                     'vendor_PeerLocalAs'        => 'jnxBgpM2PeerLocalAs',
                                     'vendor_PeerIdentifier'     => 'jnxBgpM2PeerIdentifier',
                                     'vendor_PeerIndex'          => 'jnxBgpM2PeerIndex',
                                     'vendor_PeerRemoteAddrType' => 'jnxBgpM2PeerRemoteAddrType',
                                     'vendor_PrefixCountersSafi' => 'jnxBgpM2PrefixCountersSafi'),
      // Force10 BGP4-V2 MIB
      'FORCE10-BGP4-V2-MIB' => array('vendor_PeerTable'          => 'f10BgpM2PeerTable',
                                     'vendor_PeerAdminStatus'    => 'f10BgpM2PeerStatus',
                                     'vendor_PeerRemoteAs'       => 'f10BgpM2PeerRemoteAs',
                                     'vendor_PeerRemoteAddr'     => 'f10BgpM2PeerRemoteAddr',
                                     'vendor_PeerLocalAddr'      => 'f10BgpM2PeerLocalAddr',
                                     'vendor_PeerLocalAs'        => 'f10BgpM2PeerLocalAs',
                                     'vendor_PeerIdentifier'     => 'f10BgpM2PeerIdentifier',
                                     'vendor_PeerIndex'          => 'f10BgpM2PeerIndex',
                                     'vendor_PeerRemoteAddrType' => 'f10BgpM2PeerRemoteAddrType',
                                     'vendor_PrefixCountersSafi' => 'f10BgpM2PrefixCountersSafi'),

      // Arista BGP4-V2 MIB
      'ARISTA-BGP4V2-MIB'   => array('vendor_use_index'          => array('aristaBgp4V2PeerRemoteAddr'     => 1,
                                                                          'aristaBgp4V2PeerRemoteAddrType' => 1),
                                     'vendor_PeerTable'          => 'aristaBgp4V2PeerTable',
                                     'vendor_PeerAdminStatus'    => 'aristaBgp4V2PeerAdminStatus',
                                     'vendor_PeerRemoteAs'       => 'aristaBgp4V2PeerRemoteAs',
                                     'vendor_PeerRemoteAddr'     => 'aristaBgp4V2PeerRemoteAddr',
                                     'vendor_PeerLocalAddr'      => 'aristaBgp4V2PeerLocalAddr',
                                     'vendor_PeerLocalAs'        => 'aristaBgp4V2PeerLocalAs',
                                     'vendor_PeerIdentifier'     => 'aristaBgp4V2PeerRemoteIdentifier',
                                     'vendor_PeerIndex'          => '',
                                     'vendor_PeerRemoteAddrType' => 'aristaBgp4V2PeerRemoteAddrType',
                                     'vendor_PrefixCountersSafi' => 'aristaBgp4V2PrefixInPrefixes'),
      # PrefixCountersSafi is not-accessible in draft-13, but we
      # only use the INDEX from it, so use aristaBgp4V2PrefixInPrefixes.
      // Brocade BGP4-V2 MIB
      'FOUNDRY-BGP4V2-MIB'  => array('vendor_use_index'          => array('bgp4V2PeerRemoteAddr'     => 1,
                                                                          'bgp4V2PeerRemoteAddrType' => 1,
                                                                          'bgp4V2PeerLocalAddr'      => 1),
                                     'vendor_PeerTable'          => 'bgp4V2PeerTable',
                                     'vendor_PeerAdminStatus'    => 'bgp4V2PeerAdminStatus',
                                     'vendor_PeerRemoteAs'       => 'bgp4V2PeerRemoteAs',
                                     'vendor_PeerRemoteAddr'     => 'bgp4V2PeerRemoteAddr',
                                     'vendor_PeerLocalAddr'      => 'bgp4V2PeerLocalAddr',
                                     'vendor_PeerLocalAs'        => 'bgp4V2PeerLocalAs',
                                     'vendor_PeerIdentifier'     => 'bgp4V2PeerRemoteIdentifier',
                                     'vendor_PeerIndex'          => '',
                                     'vendor_PeerRemoteAddrType' => 'bgp4V2PeerRemoteAddrType',
                                     'vendor_PrefixCountersSafi' => 'bgp4V2PrefixInPrefixes'),
      # PrefixCountersSafi is not-accessible in draft-13, but we
      # only use the INDEX from it, so use bgp4V2PrefixInPrefixes.
      'HUAWEI-BGP-VPN-MIB'  => array('vendor_use_index'          => array('hwBgpPeerRemoteAddr'     => 1,
                                                                          'hwBgpPeerRemoteAddrType' => 1,
                                                                          'hwBgpPeerRemoteIdentifier' => 1,
                                                                          'hwBgpPeerAdminStatus' => 1,
                                                                          'afi'  => 'hwBgpPeerAddrFamilyAfi',
                                                                          'safi' => 'hwBgpPeerAddrFamilySafi'),
                                     'vendor_PeerTable'          => 'hwBgpPeerTable',
                                     'vendor_PeerAdminStatus'    => 'hwBgpPeerAdminStatus', // really not exist, always set to start if entry exist
                                     'vendor_PeerRemoteAs'       => 'hwBgpPeerRemoteAs',
                                     'vendor_PeerRemoteAddr'     => 'hwBgpPeerRemoteAddr',
                                     'vendor_PeerLocalAddr'      => '',
                                     'vendor_PeerLocalAs'        => '',
                                     'vendor_PeerIdentifier'     => 'hwBgpPeerRemoteIdentifier', // really not exist, use hwBgpPeerRemoteAddr
                                     'vendor_PeerIndex'          => '',
                                     'vendor_PeerRemoteAddrType' => 'hwBgpPeerRemoteAddrType',
                                     'vendor_PrefixCountersSafi' => 'hwBgpPeerPrefixActiveCounter'),  # we only use the INDEX from it
   );
*/

// Get Local ASN

// Get array with exist MIBs on device with LocasAs-es
$local_as_array = get_bgp_localas_array($device);

$vendor_mib = FALSE; // CLEANME. Clear after full rewrite to definitions.
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

// Discover BGP peers

/// NOTE. PeerIdentifier != PeerRemoteAddr

if (is_numeric($bgpLocalAs) && $bgpLocalAs != 0) {
  print_cli_data("Local AS", "AS$bgpLocalAs ", 2);

  // Detect if Local AS changed
  if ($bgpLocalAs != $device['bgpLocalAs']) {
    if (!$device['bgpLocalAs']) {
      log_event('BGP Local ASN added: AS' . $bgpLocalAs, $device, 'device', $device['device_id']);
    } elseif (!$bgpLocalAs) {
      log_event('BGP Local ASN removed: AS' . $device['bgpLocalAs'], $device, 'device', $device['device_id']);
    } else {
      log_event('BGP ASN changed: AS' . $device['bgpLocalAs'] . ' -> AS' . $bgpLocalAs, $device, 'device', $device['device_id']);
    }
    dbUpdate(array('bgpLocalAs' => $bgpLocalAs), 'devices', 'device_id = ?', array($device['device_id']));
    print_cli_data("Updated ASN", $device['bgpLocalAs'] . " -> $bgpLocalAs", 2);
  }

  print_cli_data_field("Caching");

  // Init
  $p_list = []; // valid_peers
  $peerlist = [];
  $af_list = [];

  foreach ($local_as_array as $entry) {
    $mib = $entry['mib'];
    echo("$mib ");
    if ($check_vrfs = isset($entry['virtual_name'])) {
      // Keep original
      $device_original = $device;
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
      $device = $device_original;
      $bgpLocalAs = $bgpLocalAs_original;
    }
  }

} else {
  echo("No BGP on host");
  if (is_numeric($device['bgpLocalAs'])) {
    log_event('BGP ASN removed: AS' . $device['bgpLocalAs'], $device, 'device', $device['device_id']);
    dbUpdate(array('bgpLocalAs' => array('NULL')), 'devices', 'device_id = ?', array($device['device_id']));
    print_message('Removed ASN (' . $device['bgpLocalAs'] . ')');
  } # End if
} # End if

// Process discovered peers

global $table_rows;
$table_rows = array();

if (safe_count($peerlist)) {
  print_debug_vars($peerlist);

  echo(PHP_EOL);
  // Filter IP search by BGP enabled devices (without self)
  $bgp_device_ids = dbFetchColumn('SELECT `device_id` FROM `devices` WHERE `device_id` != ? AND `bgpLocalAs` > 0 AND `disabled` = 0 AND `status` = 1', [ $device['device_id'] ]);
  $peer_as_where  = generate_query_values_and($bgp_device_ids, 'device_id');

  $peer_devices = [];
  $peer_devices_ids = [];
  foreach (dbFetchRows('SELECT `device_id`, `bgpPeerLocalAddr`, `bgpPeerRemoteAddr` FROM `bgpPeers` WHERE `bgpPeerRemoteAs` = ?' . $peer_as_where, [ $bgpLocalAs ]) as $entry) {
    $peer_devices[$entry['bgpPeerLocalAddr']][$entry['bgpPeerRemoteAddr']] = $entry['device_id'];
    $peer_devices_ids[] = $entry['device_id'];
  }
  print_debug_vars($peer_devices);

  $peer_ip_where  = generate_query_values_and($peer_devices_ids, 'device_id') . generate_query_values_and('up', 'ifOperStatus');

  foreach ($peerlist as $peer) {

    $astext      = get_astext($peer['as']);
    $reverse_dns = gethostbyaddr6($peer['ip']);
    if ($reverse_dns == $peer['ip']) {
      unset($reverse_dns);
    }

    // Search remote device if possible
    $peer_addr_version = get_ip_version($peer['ip']);
    $peer_addr_type    = get_ip_type($peer['ip']);
    $peer_local_type   = get_ip_type($peer['local_ip']);
    $peer_device_id    = [ 'NULL' ];
    if (!in_array($peer_addr_type, [ 'unspecified', 'loopback' ]) &&
        !in_array($peer_local_type, [ 'unspecified', 'loopback' ])) {

      //if ($tmp_id = dbFetchCell('SELECT `device_id` FROM `bgpPeers` WHERE `bgpPeerLocalAddr` = ? AND `bgpPeerRemoteAs` = ? AND `bgpPeerRemoteAddr` = ?' . $peer_as_where, array($peer['ip'], $bgpLocalAs, $peer['local_ip'])))
      print_debug("bgpPeerLocalAddr: ".$peer['ip'].", bgpPeerRemoteAddr: ".$peer['local_ip']);
      if (isset($peer_devices[$peer['ip']][$peer['local_ip']])) {
        // Simple search remote device by Local IP and Local AS and Remote IP
        //$peer_device_id = $tmp_id;
        $peer_device_id = $peer_devices[$peer['ip']][$peer['local_ip']];

      } elseif ($ids = get_entity_ids_ip_by_network('device', $peer['ip'], $peer_ip_where)) {
        // Fetch all devices with peer IP
        // Peer device will found if device UP and NOT DISABLED, port with IP is UP, bgpLocalAs present on remote device

        $peer_device_id = array_shift($ids);
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

      }

    }

    if (is_numeric($peer_device_id)) {
      $peer_device = device_by_id_cache($peer_device_id);
    } else {
      unset($peer_device);
    }

    //$peer['local_as']        = (isset($peer['local_as']) && $peer['local_as'] != 0 && $peer['local_as'] != '') ? $peer['local_as'] : $bgpLocalAs;
    $local_as = $peer['local_as'];
    $virtual_name = [ 'NULL' ];
    if (isset($peer['virtual_name'])) {
      $local_as .= ' ('.$peer['virtual_name'].')';
      $virtual_name = $peer['virtual_name'];
    }
    $table_rows[$peer['ip']] = [ $local_as, $peer['local_ip'], $peer['as'], $peer['ip'], '', $reverse_dns, truncate($peer_device['hostname'], 30) ];
    $params = [
      'device_id'         => $device['device_id'],
      'bgpPeerIdentifier' => $peer['identifier'],
      'bgpPeerRemoteAddr' => $peer['ip'],
      'bgpPeerLocalAddr'  => $peer['local_ip'],
      'local_as'          => $peer['local_as'],
      'bgpPeerRemoteAs'   => $peer['as'],
      'astext'            => $astext,
      'reverse_dns'       => $reverse_dns,
      'virtual_name'      => $virtual_name,
      'peer_device_id'    => $peer_device_id
    ];

    $peer_db = dbFetchRow('SELECT * FROM `bgpPeers` WHERE `device_id` = ? AND `bgpPeerRemoteAddr` = ?', [ $device['device_id'], $peer['ip'] ]);
    if (safe_count($peer_db)) {
      $update_array = [];
      foreach ($params as $param => $value) {

        if ($value === [ 'NULL' ]) {
          if ($peer_db[$param] != '') {
            $update_array[$param] = $value;
          }
        } elseif ($value != $peer_db[$param]) {
          $update_array[$param] = $value;
        }
      }

      if (count($update_array)) {
        dbUpdate($update_array, 'bgpPeers', '`device_id` = ? AND `bgpPeerRemoteAddr` = ?', [ $device['device_id'], $peer['ip'] ]);
        if (isset($update_array['reverse_dns']) && count($update_array) === 1) {
          // Do not count updates if changed only reverse DNS
          $GLOBALS['module_stats'][$module]['unchanged']++;
        } else {
          $GLOBALS['module_stats'][$module]['updated']++;
        }
      } else {
        $GLOBALS['module_stats'][$module]['unchanged']++;
      }

      $peer_id = $peer_db['bgpPeer_id'];
    } else {
      $peer_id = dbInsert($params, 'bgpPeers');
      $GLOBALS['module_stats'][$module]['added']++;
    }

    $peer['id'] = $peer_id;
    $peer_ids[$peer['ip']] = $peer_id;

    // AFI/SAFI for specific vendors
    if (isset($peer_afis[$peer['ip']])) {
      $peer_index = $peer['index']; // keep original
      foreach ($peer_afis[$peer['ip']] as $af_entry) {
        $peer['index'] = isset($af_entry['index']) ? $af_entry['index'] : $peer_index;
        discovery_bgp_afisafi($device, $peer, $af_entry['afi'], $af_entry['safi'], $af_list);
      }
      $peer['index'] = $peer_index; // restore
    }

    // Autodiscovery for bgp neighbours
    if ($config['autodiscovery']['bgp']) {
      if (($peer['as'] == $device['bgpLocalAs']) || // ASN matches local router
          ($config['autodiscovery']['bgp_as_private'] && is_bgp_as_private($peer['as'])) || // ASN is private
          (is_array($config['autodiscovery']['bgp_as_whitelist']) && in_array($peer['as'], $config['autodiscovery']['bgp_as_whitelist']))) { // ASN is in bgp_as_whitelist

        // Try find remote device and check if already cached
        $remote_device_id = get_autodiscovery_device_id($device, $peer['id']);
        if (is_null($remote_device_id) &&       // NULL - never cached in other rounds
            check_autodiscovery($peer['id'])) { // Check all previous autodiscovery rounds
          // Neighbour never checked, try autodiscover
          $remote_device_id = autodiscovery_device($peer['id'], NULL, 'BGP', NULL, $device);
        }
      }
    }
  } # Foreach

  // Remove deleted AFI/SAFI
  unset($afi, $safi, $peer_ip, $peer_id);
  $cbgp_delete = [];
  $query = 'SELECT * FROM `bgpPeers_cbgp` WHERE `device_id` = ?';
  foreach (dbFetchRows($query, [ $device['device_id'] ]) as $entry) {
    $peer_id = $entry['bgpPeer_id'];
    $afi     = $entry['afi'];
    $safi    = $entry['safi'];
    $cbgp_id = $entry['cbgp_id'];

    if (!isset($af_list[$peer_id][$afi][$safi])) {
      $cbgp_delete[] = $cbgp_id;
      //dbDelete('bgpPeers_cbgp', '`cbgp_id` = ?', array($cbgp_id));
    }
  } # AF list
  if (safe_count($cbgp_delete)) {
    // Multi-delete
    dbDelete('bgpPeers_cbgp', generate_query_values_ng($cbgp_delete, 'cbgp_id'));
  }
  unset($af_list, $cbgp_delete);
} # end peerlist

// Delete removed peers
unset($peer_ip, $peer_as);
$peers_delete = [];
$query = 'SELECT * FROM `bgpPeers` WHERE `device_id` = ?';
foreach (dbFetchRows($query, [ $device['device_id'] ]) as $entry) {
  $peer_ip = $entry['bgpPeerRemoteAddr'];
  $peer_as = $entry['bgpPeerRemoteAs'];

  if (!isset($p_list[$peer_ip][$peer_as])) {
    // dbDelete('bgpPeers', '`bgpPeer_id` = ?', [ $entry['bgpPeer_id'] ]);
    $peers_delete[] = $entry['bgpPeer_id'];
    $GLOBALS['module_stats'][$module]['deleted']++;
  } else {
    // Unset, for exclude duplicate entries in DB
    unset($p_list[$peer_ip][$peer_as]);
  }
}
if (count($peers_delete)) {
  // Multi-delete
  dbDelete('bgpPeers', generate_query_values_ng($peers_delete, 'bgpPeer_id'));
  dbDelete('bgpPeers_cbgp', generate_query_values_ng($peers_delete, 'bgpPeer_id'));
}

$table_headers = array('%WLocal: AS (VRF)%n', '%WIP%n', '%WPeer: AS%n', '%WIP%n', '%WFamily%n', '%WrDNS%n', '%WRemote Device%n');
print_cli_table($table_rows, $table_headers);

unset($p_list, $peerlist, $vendor_mib, $cisco_version, $cisco_peers, $table_rows, $table_headers, $peer_devices, $peer_devices_ids);

// EOF
