<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage entities
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

/**
 * Validate BGP peer
 *
 * @param array $peer BGP peer array from discovery or polling process
 * @param array $device Common device array
 * @return boolean TRUE if peer array valid
 */
function is_bgp_peer_valid($peer, $device)
{
  $valid = TRUE;

  if (isset($peer['admin_status']) && empty($peer['admin_status']))
  {
    $valid = FALSE;
    print_debug("Peer ignored (by empty Admin Status).");
  }

  if ($valid && !(is_numeric($peer['as']) && $peer['as'] != 0))
  {
    $valid = FALSE;
    print_debug("Peer ignored (by invalid AS number '".$peer['as']."').");
  }

  if ($valid && !get_ip_version($peer['ip']))
  {
    $valid = FALSE;
    print_debug("Peer ignored (by invalid Remote IP '".$peer['ip']."').");
  }

  return $valid;
}

/**
 * Detect is BGP AS number in private range, see:
 * https://tools.ietf.org/html/rfc6996
 * https://tools.ietf.org/html/rfc7300
 *
 * @param string|int $as AS number
 * @return boolean TRUE if AS number in private range
 */
function is_bgp_as_private($as)
{
  $as = bgp_asdot_to_asplain($as); // Convert ASdot to ASplain

  // Note 65535 and 5294967295 not really Private ASNs,
  // this is Reserved for use by Well-known Communities
  $private = ($as >= 64512      && $as <= 65535) ||    // 16-bit private ASn
    ($as >= 4200000000 && $as <= 5294967295); // 32-bit private ASn

  return $private;
}

/**
 * Convert AS number from asplain to asdot format (for 32bit ASn).
 *
 * @param string|int $as AS number in plain or dot format
 * @return string AS number in dot format (for 32bit ASn)
 */
function bgp_asplain_to_asdot($as)
{
  if (str_contains($as, '.') || // Already asdot format
      ($as < 65536))            // 16bit ASn no need to formatting
  {
    return $as;
  }

  $as2 = $as % 65536;
  $as1 = ($as - $as2) / 65536;

  return intval($as1) . '.' . intval($as2);
}

/**
 * Convert AS number from asdot to asplain format (for 32bit ASn).
 *
 * @param string|int $as AS number in plain or dot format
 * @return string AS number in plain format (for 32bit ASn)
 */
function bgp_asdot_to_asplain($as)
{
  if (!str_contains($as, '.'))   // Already asplain format
  {
    return $as;
  }

  list($as1, $as2) = explode('.', $as, 2);
  $as = $as1 * 65536 + $as2;

  return "$as";
}

/**
 * Convert BGP peer index to vendor MIB specific entries
 *
 * @param array $peer Array with walked peer oids
 * @param string $index Peer index
 * @param string $mib MIB name
 */
function parse_bgp_peer_index(&$peer, $index, $mib = 'BGP4V2-MIB')
{
  global $config;

  $address_types = $config['mibs']['INET-ADDRESS-MIB']['rewrite']['InetAddressType'];
  $index_parts   = explode('.', $index);
  switch ($mib)
  {
    case 'BGP4-MIB':
      // bgpPeerRemoteAddr
      if (get_ip_version($index))
      {
        $peer['bgpPeerRemoteAddr'] = $index;
      }
      break;

    case 'ARISTA-BGP4V2-MIB':
      // 1. aristaBgp4V2PeerInstance
      $peer['aristaBgp4V2PeerInstance'] = array_shift($index_parts);
      // 2. aristaBgp4V2PeerRemoteAddrType
      $peer_addr_type = array_shift($index_parts);
      if (strlen($peer['aristaBgp4V2PeerRemoteAddrType']) == 0)
      {
        $peer['aristaBgp4V2PeerRemoteAddrType'] = $peer_addr_type;
      }
      if (isset($address_types[$peer['aristaBgp4V2PeerRemoteAddrType']]))
      {
        $peer['aristaBgp4V2PeerRemoteAddrType'] = $address_types[$peer['aristaBgp4V2PeerRemoteAddrType']];
      }
      // 3. length of the IP address
      $ip_len = array_shift($index_parts);
      // 4. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 5. aristaBgp4V2PeerRemoteAddr
      $peer_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $peer_ip = snmp2ipv6($peer_ip);
      }
      if ($peer_addr_type = get_ip_version($peer_ip))
      {
        $peer['aristaBgp4V2PeerRemoteAddr']     = $peer_ip;
        $peer['aristaBgp4V2PeerRemoteAddrType'] = 'ipv' . $peer_addr_type; // FIXME. not sure, but seems as Arista use only ipv4/ipv6 for afi
      }
      break;

    case 'BGP4V2-MIB':
    case 'FOUNDRY-BGP4V2-MIB': // BGP4V2-MIB draft
      // 1. bgp4V2PeerInstance
      $peer['bgp4V2PeerInstance'] = array_shift($index_parts);
      // 2. bgp4V2PeerLocalAddrType
      $local_addr_type = array_shift($index_parts);
      if (strlen($peer['bgp4V2PeerLocalAddrType']) == 0)
      {
        $peer['bgp4V2PeerLocalAddrType'] = $local_addr_type;
      }
      if (isset($address_types[$peer['bgp4V2PeerLocalAddrType']]))
      {
        $peer['bgp4V2PeerLocalAddrType'] = $address_types[$peer['bgp4V2PeerLocalAddrType']];
      }
      // 3. length of the local IP address
      $ip_len = array_shift($index_parts);
      // 4. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 5. bgp4V2PeerLocalAddr
      $local_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $local_ip = snmp2ipv6($local_ip);
      }
      if (get_ip_version($local_ip))
      {
        $peer['bgp4V2PeerLocalAddr'] = $local_ip;
      }

      // Get second part of index
      $index_parts = array_slice($index_parts, $ip_len);
      $peer_addr_type = array_shift($index_parts);
      if (strlen($peer['bgp4V2PeerRemoteAddrType']) == 0)
      {
        $peer['bgp4V2PeerRemoteAddrType'] = $peer_addr_type;
      }
      if (isset($address_types[$peer['bgp4V2PeerRemoteAddrType']]))
      {
        $peer['bgp4V2PeerRemoteAddrType'] = $address_types[$peer['bgp4V2PeerRemoteAddrType']];
      }
      // 6. length of the IP address
      $ip_len = array_shift($index_parts);
      // 7. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 8. bgp4V2PeerRemoteAddr
      $peer_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $peer_ip = snmp2ipv6($peer_ip);
      }
      if ($peer_addr_type = get_ip_version($peer_ip))
      {
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
      $afi = array_shift($index_parts);
      $afis = [ 1 => 'ipv4', 2 => 'ipv6', 25 => 'vpls', 196 => 'l2vpn' ]; // Huawei specific AFI numbers (HWBgpAfi)
      if (isset($afis[$afi]))
      {
        $peer['hwBgpPeerAddrFamilyAfi'] = $afis[$afi];
      } else {
        // or use common afis
        $peer['hwBgpPeerAddrFamilyAfi'] = $config['routing_afis'][$afi];
      }

      // 3. hwBgpPeerAddrFamilySafi
      $safi = array_shift($index_parts);
      $safis = [ 1 => 'unicast', 2 => 'multicast', 4 => 'mpls', 5 => 'mcast-vpn', 65 => 'vpls', 66 => 'mdt', 128 => 'vpn', 132 => 'route-target' ]; // Huawei specific SAFI numbers (HWBgpSafi)
      if (isset($safis[$safi]))
      {
        $peer['hwBgpPeerAddrFamilySafi'] = $safis[$safi];
      } else {
        // or use common safi
        $peer['hwBgpPeerAddrFamilySafi'] = $config['routing_safis'][$safi];
      }
      // 4. hwBgpPeerRemoteAddrType (hwBgpPeerType)
      $peer_addr_type = array_shift($index_parts);
      if (isset($address_types[$peer_addr_type]))
      {
        $peer['hwBgpPeerRemoteAddrType'] = $address_types[$peer_addr_type];
      }
      // 5. hwBgpPeerRemoteAddr
      $ip_len = array_shift($index_parts);
      $ip_parts = $index_parts;
      $remote_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $remote_ip = snmp2ipv6($remote_ip);
      }
      if ($peer_addr_type = get_ip_version($remote_ip))
      {
        $peer['hwBgpPeerRemoteAddr'] = $remote_ip;
        $peer['hwBgpPeerRemoteIdentifier'] = $peer['hwBgpPeerRemoteAddr'];
        if (empty($peer['hwBgpPeerRemoteAddrType']))
        {
          $peer['hwBgpPeerRemoteAddrType'] = 'ipv' . $peer_addr_type;
        }
      }
      // 6. hwBgpPeerAdminStatus
      if (!isset($peer['hwBgpPeerAdminStatus']))
      {
        // Always set this Oid to start, while not really exist and while peer entry exist in this table
        $peer['hwBgpPeerAdminStatus'] = 'start';
      }
      break;

    case 'BGP4-V2-MIB-JUNIPER':
      // 1. jnxBgpM2PeerRoutingInstance
      $peer['jnxBgpM2PeerRoutingInstance'] = array_shift($index_parts);
      // 2. jnxBgpM2PeerLocalAddrType
      $local_addr_type = array_shift($index_parts);
      if (strlen($peer['jnxBgpM2PeerLocalAddrType']) == 0)
      {
        $peer['jnxBgpM2PeerLocalAddrType'] = $local_addr_type;
      }
      if (isset($address_types[$peer['jnxBgpM2PeerLocalAddrType']]))
      {
        $peer['jnxBgpM2PeerLocalAddrType'] = $address_types[$peer['jnxBgpM2PeerLocalAddrType']];
      }
      // 3. length of the local IP address
      $ip_len = str_contains($peer['jnxBgpM2PeerLocalAddrType'], 'ipv6') ? 16 : 4;
      // 4. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 5. jnxBgpM2PeerLocalAddr
      $local_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $local_ip = snmp2ipv6($local_ip);
      }
      if (get_ip_version($local_ip))
      {
        $peer['jnxBgpM2PeerLocalAddr'] = $local_ip;
      }

      // Get second part of index
      $index_parts = array_slice($index_parts, $ip_len);
      // 6. jnxBgpM2PeerRemoteAddrType
      $peer_addr_type = array_shift($index_parts);
      if (strlen($peer['jnxBgpM2PeerRemoteAddrType']) == 0)
      {
        $peer['jnxBgpM2PeerRemoteAddrType'] = $peer_addr_type;
      }
      if (isset($address_types[$peer['jnxBgpM2PeerRemoteAddrType']]))
      {
        $peer['jnxBgpM2PeerRemoteAddrType'] = $address_types[$peer['jnxBgpM2PeerRemoteAddrType']];
      }
      // 7. length of the remote IP address
      $ip_len = str_contains($peer['jnxBgpM2PeerRemoteAddrType'], 'ipv6') ? 16 : 4;
      // 8. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 9. jnxBgpM2PeerRemoteAddr
      $peer_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $peer_ip = snmp2ipv6($peer_ip);
      }
      if (get_ip_version($peer_ip))
      {
        $peer['jnxBgpM2PeerRemoteAddr'] = $peer_ip;
      }
      break;

    case 'FORCE10-BGP4-V2-MIB':
      // 1. f10BgpM2PeerInstance
      $peer['f10BgpM2PeerInstance'] = array_shift($index_parts);
      // 2. f10BgpM2PeerLocalAddrType
      $local_addr_type = array_shift($index_parts);
      if (strlen($peer['f10BgpM2PeerLocalAddrType']) == 0)
      {
        $peer['f10BgpM2PeerLocalAddrType'] = $local_addr_type;
      }
      if (isset($address_types[$peer['f10BgpM2PeerLocalAddrType']]))
      {
        $peer['f10BgpM2PeerLocalAddrType'] = $address_types[$peer['f10BgpM2PeerLocalAddrType']];
      }
      // 3. length of the local IP address
      $ip_len = str_contains($peer['f10BgpM2PeerLocalAddrType'], 'ipv6') ? 16 : 4;
      // 4. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 5. f10BgpM2PeerLocalAddr
      $local_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $local_ip = snmp2ipv6($local_ip);
      }
      if (get_ip_version($local_ip))
      {
        $peer['f10BgpM2PeerLocalAddr'] = $local_ip;
      }

      // Get second part of index
      $index_parts = array_slice($index_parts, $ip_len);
      // 6. f10BgpM2PeerRemoteAddrType
      $peer_addr_type = array_shift($index_parts);
      if (strlen($peer['f10BgpM2PeerRemoteAddrType']) == 0)
      {
        $peer['f10BgpM2PeerRemoteAddrType'] = $peer_addr_type;
      }
      if (isset($address_types[$peer['f10BgpM2PeerRemoteAddrType']]))
      {
        $peer['f10BgpM2PeerRemoteAddrType'] = $address_types[$peer['f10BgpM2PeerRemoteAddrType']];
      }
      // 7. length of the remote IP address
      $ip_len = str_contains($peer['f10BgpM2PeerRemoteAddrType'], 'ipv6') ? 16 : 4;
      // 8. IP address
      $ip_parts = array_slice($index_parts, 0, $ip_len);

      // 9. f10BgpM2PeerRemoteAddr
      $peer_ip = implode('.', $ip_parts);
      if ($ip_len == 16)
      {
        $peer_ip = snmp2ipv6($peer_ip);
      }
      if (get_ip_version($peer_ip))
      {
        $peer['f10BgpM2PeerRemoteAddr'] = $peer_ip;
      }
      break;

  }

}

function discovery_bgp_afisafi($device, $entry, $afi, $safi, &$af_list)
{
  global $table_rows;

  $index = $entry['index'];
  $peer_id = $entry['peer_id'];
  $peer_ip = $entry['peer_ip'];
  $peer_as = $entry['peer_as'];

  print_debug("INDEX: $index, AS: $peer_as, IP: $peer_ip, AFI: $afi, SAFI: $safi");
  print_debug_vars($entry);
  $af_list[$peer_id][$afi][$safi] = 1;

  if (strlen($table_rows[$peer_ip][4]))
  {
    $table_rows[$peer_ip][4] .= ', ';
  }
  $table_rows[$peer_ip][4] .= $afi . '.' . $safi;

  //if (dbFetchCell('SELECT COUNT(*) FROM `bgpPeers_cbgp` WHERE `device_id` = ? AND `bgpPeer_id` = ? AND `afi` = ? AND `safi` = ?', array($device['device_id'], $peer_id, $afi, $safi)) == 0)
  if (!dbExist('bgpPeers_cbgp', '`device_id` = ? AND `bgpPeer_id` = ? AND `afi` = ? AND `safi` = ?', array($device['device_id'], $peer_id, $afi, $safi)))
  {
    $params = array('bgpPeer_id' => $peer_id, 'device_id' => $device['device_id'], 'bgpPeerIndex' => $index, 'afi' => $afi, 'safi' => $safi);
    dbInsert($params, 'bgpPeers_cbgp');
  }
  elseif ($index >= 0)
  {
    // Update Index
    dbUpdate(array('bgpPeerIndex' => $index), 'bgpPeers_cbgp', 'device_id = ? AND `bgpPeer_id` = ? AND `afi` = ? AND `safi` = ?', array($device['device_id'], $peer_id, $afi, $safi));
  }
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function get_astext($asn)
{
  global $config, $cache;

  // Fetch pre-set AS text from config first
  if (isset($config['astext'][$asn]))
  {
    return $config['astext'][$asn];
  } else {
    // Not preconfigured, check cache before doing a new DNS request
    if (!isset($cache['astext'][$asn]))
    {
      $result = dns_get_record("AS$asn.asn.cymru.com", DNS_TXT);
      print_debug_vars($result);
      $txt = explode('|', $result[0]['txt']);
      $cache['astext'][$asn] = trim(str_replace('"', '', $txt[4]));
    }

    return $cache['astext'][$asn];
  }
}

function discover_vrf($device, $vrf)
{
  global $cache_discovery, $valid, $table_rows;

  $module = 'vrf';

  if (empty($vrf['vrf_name'])) { return; }
  $vrf_name = $vrf['vrf_name'];

  // Pre-cache VRFs from DB
  if (!isset($cache_discovery['vrf_db']))
  {
    $cache_discovery['vrf_db'] = [];
    foreach (dbFetchRows("SELECT * FROM `vrfs` WHERE `device_id` = ?", [ $device['device_id'] ]) as $entry)
    {
      // Strange case with duplicate entries: https://jira.observium.org/browse/OBS-3600
      if (isset($cache_discovery['vrf_db'][$entry['vrf_name']])) {
        print_debug("Duplicate VRF entry in DB found: ".$entry['vrf_name']);
        print_debug_vars($entry);
        dbDelete('vrfs', '`vrf_id` = ?', [ $entry['vrf_id'] ]);
        continue;
      }
      $cache_discovery['vrf_db'][$entry['vrf_name']] = $entry;
    }
  }

  $params_main = [ 'vrf_mib', 'vrf_name', 'vrf_descr', 'vrf_rd' ];
  $params_state = [ 'vrf_admin_status', 'vrf_oper_status',
                    'vrf_active_ports', 'vrf_total_ports',
                    'vrf_added_routes', 'vrf_deleted_routes', 'vrf_total_routes' ];

  $insert_array = [ 'device_id' => $device['device_id'] ];
  foreach ($params_main as $param)
  {
    $insert_array[$param] = isset($vrf[$param]) ? $vrf[$param] : '';
  }

  // Set added/changed params
  $current_time = (int)time();
  $param = 'vrf_added';
  $insert_array[$param] = isset($vrf[$param]) && $vrf[$param] < $current_time ? (int)$vrf[$param] : $device['last_rebooted'];

  if (!isset($cache_discovery['vrf_db'][$vrf['vrf_name']]))
  {
    // Added
    $param = 'vrf_last_change';
    $insert_array[$param] = isset($vrf[$param]) && $vrf[$param] < $current_time ? (int)$vrf[$param] : $current_time;

    // When insert, also add state params
    foreach ($params_state as $param)
    {
      if (isset($vrf[$param]))
      {
        // When not set, use db default
        $insert_array[$param] = $vrf[$param];
      }
    }

    $vrf_id = dbInsert($insert_array, 'vrfs');
    $GLOBALS['module_stats'][$module]['added']++; //echo "+";
  } else {
    // Compare/update

    $update_array = [];
    $entry = $cache_discovery['vrf_db'][$vrf['vrf_name']];
    $vrf_id = $entry['vrf_id'];

    foreach ($params_main as $param)
    {
      if ($insert_array[$param] !== $entry[$param])
      {
        $update_array[$param] = $insert_array[$param];
      }
    }

    // Update old entries (after migrate)
    if (empty($entry['vrf_added']))
    {
      // State params
      foreach ($params_state as $param)
      {
        if (isset($vrf[$param]))
        {
          // When not set, use db default
          $update_array[$param] = $vrf[$param];
        }
      }

      $update_array['vrf_added'] = $insert_array['vrf_added'];
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

    if (safe_count($update_array))
    {
      dbUpdate($update_array, 'vrfs', '`vrf_id` = ?', [ $vrf_id ]);

      $GLOBALS['module_stats'][$module]['updated']++;
    } else {
      $GLOBALS['module_stats'][$module]['unchanged']++;
    }
  }

  $valid['vrf'][$vrf_name] = $vrf_id;

  // VRF ports
  if (safe_count($vrf['ifIndex']))
  {
    $db_update = [];
    foreach ($vrf['ifIndex'] as $ifIndex)
    {
      if ($port = get_port_by_index_cache($device, $ifIndex))
      {
        $db_update[] = [ 'port_id' => $port['port_id'], 'ifIndex' => $port['ifIndex'], 'device_id' => $device['device_id'], 'ifVrf' => $vrf_id ];

        $valid['vrf-ports'][$vrf_name][$port['port_id']] = $vrf_id;
      }
    }

    dbUpdateMulti($db_update, 'ports', [ 'ifVrf' ]);
  }
}

// EOF
