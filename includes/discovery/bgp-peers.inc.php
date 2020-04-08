<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// 'BGP4-MIB', 'CISCO-BGP4-MIB', 'BGP4-V2-MIB-JUNIPER', 'FORCE10-BGP4-V2-MIB', 'ARISTA-BGP4V2-MIB', 'FOUNDRY-BGP4V2-MIB'
if ($config['enable_bgp'] && is_device_mib($device, 'BGP4-MIB')) // Note, BGP4-MIB is main MIB, without it, the rest will not be checked
{
   // Get Local ASN
   $bgpLocalAs = snmp_get_oid($device, 'bgpLocalAs.0', 'BGP4-MIB');

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
   );

   $vendor_mib = FALSE;
   foreach ($vendor_oids as $v_mib => $v_array)
   {
      if (is_device_mib($device, $v_mib))
      {
         $vendor_mib = $v_mib; // Set to current vendor mib
         //echo(" $v_mib ");
         foreach ($v_array as $v => $val)
         {
            $$v = $val;
         }

         if ($v_mib === 'BGP4-V2-MIB-JUNIPER' && $bgpLocalAs === '0')
         {
            // On JunOS BGP4-MIB::bgpLocalAs.0 is always '0'.
            $v_bgpLocalAs = snmpwalk_values($device, 'jnxBgpM2PeerLocalAs', array(), 'BGP4-V2-MIB-JUNIPER');
            $bgpLocalAs   = array_shift($v_bgpLocalAs);
         }
         break;
      }
   }

   // Some Old IOS-XR (ie 4.3.2) also return BGP4-MIB::bgpLocalAs.0 as '0'.
   //if ($vendor_mib === FALSE && $bgpLocalAs === '0' && is_device_mib($device, 'CISCO-BGP4-MIB'))

   // Use cbgpLocalAs on Cisco devices since bgpLocalAs doesn't handle 32bit ASNs
   if (is_device_mib($device, 'CISCO-BGP4-MIB'))
   {
      $v_bgpLocalAs = snmp_get_oid($device, 'cbgpLocalAs.0', 'CISCO-BGP4-MIB');
      if (is_numeric($v_bgpLocalAs) && $v_bgpLocalAs > 0)
      {
         $bgpLocalAs = $v_bgpLocalAs;
      }
   }

   // Discover BGP peers

   /// NOTE. PeerIdentifier != PeerRemoteAddr

   if (is_numeric($bgpLocalAs) && $bgpLocalAs != '0')
   {
      $bgpLocalAs = snmp_dewrap32bit($bgpLocalAs); // Dewrap for 32bit ASN
      print_cli_data("Local AS", "AS$bgpLocalAs ", 2);

      // Detect if Local AS changed
      if ($bgpLocalAs != $device['bgpLocalAs'])
      {
         if (!$device['bgpLocalAs'])
         {
            log_event('BGP Local ASN added: AS' . $bgpLocalAs, $device, 'device', $device['device_id']);
         }
         elseif (!$bgpLocalAs)
         {
            log_event('BGP Local ASN removed: AS' . $device['bgpLocalAs'], $device, 'device', $device['device_id']);
         }
         else
         {
            log_event('BGP ASN changed: AS' . $device['bgpLocalAs'] . ' -> AS' . $bgpLocalAs, $device, 'device', $device['device_id']);
         }
         dbUpdate(array('bgpLocalAs' => $bgpLocalAs), 'devices', 'device_id = ?', array($device['device_id']));
         print_cli_data("Updated ASN", $device['bgpLocalAs'] . " -> $bgpLocalAs", 2);
      }

      print_cli_data_field("Caching", 2);
      print_debug("BGP4-MIB ");

      $cisco_version = FALSE;
      if (is_device_mib($device, 'CISCO-BGP4-MIB'))
      {
         $cisco_version = 1;
         // Check Cisco cbgpPeer2Table
         $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2RemoteAs', array(), 'CISCO-BGP4-MIB');
         if (count($cisco_peers) > 0)
         {
            echo("CISCO-BGP4-MIB ");
            $cisco_version = 2;
            $cisco_peers   = snmpwalk_cache_oid($device, 'cbgpPeer2LocalAddr', $cisco_peers, 'CISCO-BGP4-MIB');

            // Cisco vendor mib LocalAddr issue:
            // cbgpPeer2LocalAddr.ipv4."10.0.1.1" = "0B 8E 95 38 " --> 11.142.149.56
            // but should:
            // bgpPeerLocalAddr.10.0.1.1 = 10.0.1.3
            // Yah, Cisco you again added extra work for me? What mean this random numbers?
            $cisco_fix = snmpwalk_cache_oid($device, 'bgpPeerLocalAddr', array(), 'BGP4-MIB');

            $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2RemoteIdentifier', $cisco_peers, 'CISCO-BGP4-MIB');
            $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2AdminStatus', $cisco_peers, 'CISCO-BGP4-MIB');
            $cisco_peers = snmpwalk_cache_oid($device, 'cbgpPeer2LocalAs', $cisco_peers, 'CISCO-BGP4-MIB');

            print_debug("CISCO-BGP4-MIB Peers: ");
            foreach ($cisco_peers as $peer_ip => $entry)
            {
               list(, $peer_ip) = explode('.', $peer_ip, 2);
               $peer_ip = hex2ip($peer_ip);
               if (isset($cisco_fix[$peer_ip]) && strlen($cisco_fix[$peer_ip]['bgpPeerLocalAddr']))
               {
                  // Fix incorrect IPv4 local IPs
                  $local_ip = $cisco_fix[$peer_ip]['bgpPeerLocalAddr'];
               }
               else
               {
                  $local_ip = hex2ip($entry['cbgpPeer2LocalAddr']);
               }

               if ($peer_ip == '0.0.0.0')
               {
                  $peer_ip = '';
               }
               $peer_as  = $entry['cbgpPeer2RemoteAs'];
               $local_as = snmp_dewrap32bit($entry['cbgpPeer2LocalAs']);
               if ($local_as == 0)
               {
                  // Per session local ASN can be zero if session down
                  $local_as = $bgpLocalAs;
               }
               $peer     = array('id'           => $entry['cbgpPeer2RemoteIdentifier'],
                                 'local_ip'     => $local_ip,
                                 'local_as'     => $local_as,
                                 'ip'           => $peer_ip,
                                 'as'           => $peer_as,
                                 'admin_status' => $entry['cbgpPeer2AdminStatus']);

               if (!isset($p_list[$peer_ip][$peer_as]) && is_bgp_peer_valid($peer, $device))
               {
                  $p_list[$peer_ip][$peer_as] = 1;
                  $peerlist[]                 = $peer;
                  print_debug("Found peer IP: $peer_ip (AS$peer_as, LocalIP: $local_ip)");
               }
               else if (isset($p_list[$peer_ip][$peer_as]) && $local_as != $bgpLocalAs)
               {
                  // Find and replace local_as key in peer list if different local as
                  // FIXME, Yah, $peerlist stored as simple array without indexed key, that why used derp per-peer loop
                  foreach ($peerlist as $key => $tmp)
                  {
                     if ($tmp['ip'] == $peer_ip && $tmp['as'] == $peer_as)
                     {
                        $peerlist[$key]['local_as'] = $local_as;
                        print_debug("Replaced Local AS for peer: $peer_ip (AS$peer_as, LocalIP: $local_ip) - AS$bgpLocalAs -> AS$local_as");
                        break;
                     }
                  }
               }
            }
         }
      }

      if ($cisco_version !== 2)
      {
         // All MIBs except CISCO-BGP4-MIB
         $peers_data = snmpwalk_cache_oid($device, 'bgpPeerRemoteAs', array(), 'BGP4-MIB');
         //$peers_data = snmpwalk_cache_oid($device, 'bgpPeerRemoteAddr',  $peers_data, 'BGP4-MIB');
         $peers_data = snmpwalk_cache_oid($device, 'bgpPeerLocalAddr', $peers_data, 'BGP4-MIB');
         $peers_data = snmpwalk_cache_oid($device, 'bgpPeerIdentifier', $peers_data, 'BGP4-MIB');
         $peers_data = snmpwalk_cache_oid($device, 'bgpPeerAdminStatus', $peers_data, 'BGP4-MIB');
         echo("BGP4-MIB ");
         foreach ($peers_data as $peer_ip => $entry)
         {
            $peer_as = snmp_dewrap32bit($entry['bgpPeerRemoteAs']); // Dewrap for 32bit ASN
            if ($peer_as > $entry['bgpPeerRemoteAs'])
            {
               $peers_data[$peer_ip]['bgpPeerRemoteAs'] = $peer_as;
            }
            $local_ip = $entry['bgpPeerLocalAddr'];
            if ($peer_ip == '0.0.0.0')
            {
               $peer_ip = '';
            }
            $peer = array('id'           => $entry['bgpPeerIdentifier'],
                          'local_ip'     => $local_ip,
                          'ip'           => $peer_ip,
                          'local_as'     => $bgpLocalAs,
                          'as'           => $peer_as,
                          'admin_status' => $entry['bgpPeerAdminStatus']);
            if (!isset($p_list[$peer_ip][$peer_as]) && is_bgp_peer_valid($peer, $device))
            {
               print_debug("Found peer IP: $peer_ip (AS$peer_as, LocalIP: $local_ip)");
               $peerlist[]                 = $peer;
               $p_list[$peer_ip][$peer_as] = 1;
            }
         }
      }

      if ($vendor_mib)
      {
         $vendor_bgp = snmpwalk_cache_oid($device, $vendor_PeerRemoteAs, array(), $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
         if (count($vendor_bgp) > 0)
         {
            echo("$vendor_mib ");
            if (!isset($vendor_use_index[$vendor_PeerRemoteAddr]))
            {
               $vendor_bgp = snmpwalk_cache_oid($device, $vendor_PeerRemoteAddr, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
            }
            if (!isset($vendor_use_index[$vendor_PeerLocalAddr]))
            {
               $vendor_bgp = snmpwalk_cache_oid($device, $vendor_PeerLocalAddr, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
            }
            $vendor_bgp = snmpwalk_cache_oid($device, $vendor_PeerIdentifier, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
            $vendor_bgp = snmpwalk_cache_oid($device, $vendor_PeerAdminStatus, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
            $vendor_bgp = snmpwalk_cache_oid($device, $vendor_PeerLocalAs, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

            //print_vars($vendor_bgp);
            print_debug("$vendor_mib Peers: ");
            foreach ($vendor_bgp as $idx => $entry)
            {
               if (count($vendor_use_index))
               {
                  parse_bgp_peer_index($entry, $idx, $vendor_mib);
               }
               $peer_ip  = hex2ip($entry[$vendor_PeerRemoteAddr]);
               $local_ip = hex2ip($entry[$vendor_PeerLocalAddr]);
               $local_as = isset($entry[$vendor_PeerLocalAs]) ? snmp_dewrap32bit($entry[$vendor_PeerLocalAs]) : $bgpLocalAs;
               $peer_as  = $entry[$vendor_PeerRemoteAs];
               if ($peer_ip == '0.0.0.0')
               {
                  $peer_ip = '';
               }
               $peer = array('id'           => $entry[$vendor_PeerIdentifier],
                             'local_ip'     => $local_ip,
                             'local_as'     => $local_as,
                             'ip'           => $peer_ip,
                             'as'           => $peer_as,
                             'admin_status' => $entry[$vendor_PeerAdminStatus]);
               if (!isset($p_list[$peer_ip][$peer_as]) && is_bgp_peer_valid($peer, $device))
               {
                  // Fix possible 32bit ASN for peers from BGP4-MIB
                  // Brocade example:
                  //                                     BGP4-MIB::bgpPeerRemoteAs.27.122.122.4 = 23456
                  //  FOUNDRY-BGPV2-MIB::bgp4V2PeerRemoteAs.1.1.4.27.122.122.5.1.4.27.122.122.4 = 133189
                  if (isset($p_list[$peer_ip]))
                  {
                     unset($p_list[$peer_ip]); // Clean old peer list
                     $bgp4_peer_as = $peers_data[$peer_ip]['bgpPeerRemoteAs'];
                     if ($peer_as > $bgp4_peer_as)
                     {
                        //$peers_data[$peer_ip]['bgpPeerRemoteAs'] = $peer_as;
                        // Yah, need to found and remove duplicate peer from peerlist
                        foreach ($peerlist as $key => $tmp)
                        {
                           if ($tmp['ip'] == $peer_ip && $tmp['as'] == $bgp4_peer_as)
                           {
                              unset($peerlist[$key]);
                              break;
                           }
                        }
                     }
                  }

                  $p_list[$peer_ip][$peer_as] = 1;
                  $peerlist[]                 = $peer;
                  print_debug("Found peer IP: $peer_ip (AS$peer_as, LocalIP: $local_ip)");
               }
               elseif (isset($p_list[$peer_ip][$peer_as]) && $local_as != $bgpLocalAs)
               {
                  // Find and replace local_as key in peer list if different local as
                  // FIXME, Yah, $peerlist stored as simple array without indexed key, that why used derp per-peer loop
                  foreach ($peerlist as $key => $tmp)
                  {
                     if ($tmp['ip'] == $peer_ip && $tmp['as'] == $peer_as)
                     {
                        $peerlist[$key]['local_as'] = $local_as;
                        print_debug("Replaced Local AS for peer: $peer_ip (AS$peer_as, LocalIP: $local_ip) - AS$bgpLocalAs -> AS$local_as");
                        break;
                     }
                  }
               }
            }
         }
         else
         {
            $vendor_mib = FALSE; // Unset vendor_mib since not found on device
         }
      } # Vendors

   }
   else
   {
      echo("No BGP on host");
      if (is_numeric($device['bgpLocalAs']))
      {
         log_event('BGP ASN removed: AS' . $device['bgpLocalAs'], $device, 'bgp');
         dbUpdate(array('bgpLocalAs' => array('NULL')), 'devices', 'device_id = ?', array($device['device_id']));
         print_message('Removed ASN (' . $device['bgpLocalAs'] . ')');
      } # End if
   } # End if

   // Process discovered peers

   $table_rows = array();

   if (OBS_DEBUG > 1)
   {
      print_vars($peerlist);
   }

   if (isset($peerlist))
   {
      // Walk vendor oids
      if ($vendor_mib)
      {
         if (!isset($vendor_use_index[$vendor_PeerRemoteAddrType]))
         {
            $vendor_bgp = snmpwalk_cache_oid($device, $vendor_PeerRemoteAddrType, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
         }
         if ($vendor_PeerIndex && !isset($vendor_use_index[$vendor_PeerIndex]))
         {
            $vendor_bgp = snmpwalk_cache_oid($device, $vendor_PeerIndex, $vendor_bgp, $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
         }
         $vendor_counters = snmpwalk_cache_oid($device, $vendor_PrefixCountersSafi, array(), $vendor_mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
      }

      echo(PHP_EOL);
      // Filter IP search by BGP enabled devices (without self)
      $bgp_device_ids = dbFetchColumn('SELECT `device_id` FROM `devices` WHERE `device_id` != ? AND `bgpLocalAs` > 0 AND `disabled` = 0 AND `status` = 1', array($device['device_id']));
      $peer_as_where  = generate_query_values($bgp_device_ids, 'device_id');
      $peer_ip_where  = $peer_as_where . generate_query_values('up', 'ifOperStatus');
      foreach ($peerlist as $peer)
      {
         $astext      = get_astext($peer['as']);
         $reverse_dns = gethostbyaddr6($peer['ip']);
         if ($reverse_dns == $peer['ip'])
         {
            unset($reverse_dns);
         }

         // Search remote device if possible
         $peer_addr_version = get_ip_version($peer['ip']);
         $peer_device_id    = array('NULL');
         if ($peer_addr_version)
         {
            $peer_addr_type = get_ip_type($peer['ip']);
            if (in_array($peer_addr_type, array('unspecified', 'loopback')))
            {
               // Ignore 127.0.0.1, ::1, 0.0.0.0, ::
            }
            else if ($tmp_id = dbFetchCell('SELECT `device_id` FROM `bgpPeers` WHERE `bgpPeerLocalAddr` = ? AND `bgpPeerRemoteAs` = ? AND `bgpPeerRemoteAddr` = ?' . $peer_as_where, array($peer['ip'], $bgpLocalAs, $peer['local_ip'])))
            {
               // Simple search remote device by Local IP and Local AS and Remote IP
               $peer_device_id = $tmp_id;
            }
            else if ($ids = get_entity_ids_ip_by_network('device', $peer['ip'], $peer_ip_where))
            {
               // Fetch all devices with peer IP
               // Peer device will found if device UP and NOT DISABLED, port with IP is UP, bgpLocalAs present on remote device

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

            }

         }

         if (is_numeric($peer_device_id))
         {
            $peer_device = device_by_id_cache($peer_device_id);
         } else {
            unset($peer_device);
         }

         $peer['local_as']        = (isset($peer['local_as']) && $peer['local_as'] != 0 && $peer['local_as'] != '') ? $peer['local_as'] : $bgpLocalAs;
         $table_rows[$peer['ip']] = array($peer['local_as'], $peer['local_ip'], $peer['as'], $peer['ip'], '', $reverse_dns, truncate($peer_device['hostname'], 30));
         $params                  = array('device_id'         => $device['device_id'],
                                          'bgpPeerIdentifier' => $peer['id'],
                                          'bgpPeerRemoteAddr' => $peer['ip'],
                                          'bgpPeerLocalAddr'  => $peer['local_ip'],
                                          'local_as'          => $peer['local_as'],
                                          'bgpPeerRemoteAs'   => $peer['as'],
                                          'astext'            => $astext,
                                          'reverse_dns'       => $reverse_dns,
                                          'peer_device_id'    => $peer_device_id);

         $peer_db = dbFetchRow('SELECT * FROM `bgpPeers` WHERE `device_id` = ? AND `bgpPeerRemoteAddr` = ?', array($device['device_id'], $peer['ip']));
         if (is_array($peer_db) && count($peer_db))
         {
            $update_array = array();
            foreach ($params as $param => $value)
            {
               if ($value === array('NULL'))
               {
                  if ($peer_db[$param] != '')
                  {
                     $update_array[$param] = $value;
                  }
               }
               elseif ($value != $peer_db[$param])
               {
                  $update_array[$param] = $value;
               }
            }
            if (count($update_array))
            {
               dbUpdate($update_array, 'bgpPeers', '`device_id` = ? AND `bgpPeerRemoteAddr` = ?', array($device['device_id'], $peer['ip']));
               if (isset($update_array['reverse_dns']) && count($update_array) == 1)
               {
                  // Do not count updates if changed only reverse DNS
                  $GLOBALS['module_stats'][$module]['unchanged']++;
               }
               else
               {
                  $GLOBALS['module_stats'][$module]['updated']++;
               }
            }
            else
            {
               $GLOBALS['module_stats'][$module]['unchanged']++;
            }

            $peer_id = $peer_db['bgpPeer_id'];
         }
         else
         {
            $peer_id = dbInsert($params, 'bgpPeers');
            $GLOBALS['module_stats'][$module]['added']++;
         }

         $peer_ids[$peer['ip']] = $peer_id;

         // Autodiscovery for bgp neighbours
         if ($config['autodiscovery']['bgp'])
         {
           if (
                ($peer['as'] == $device['bgpLocalAs']) || // ASN matches local router
                ($config['autodiscovery']['bgp_as_private'] && is_bgp_as_private($peer['as'])) || // ASN is private
                (is_array($config['autodiscovery']['bgp_as_whitelist']) && in_array($peer['as'], $config['autodiscovery']['bgp_as_whitelist'])) // ASN is in bgp_as_whitelist

              )
           {
              discover_new_device($peer['id'], 'bgp', 'BGP', $device);
           }
         }
      } # Foreach

      // AFI/SAFI for specific vendors
      if ($cisco_version || $vendor_mib)
      {
         if ($cisco_version)
         {
            // Get afi/safi and populate cbgp on cisco ios (xe/xr)

            if ($cisco_version === 2)
            {
               $af_data = snmpwalk_cache_oid($device, 'cbgpPeer2AddrFamilyName', $cbgp, 'CISCO-BGP4-MIB');
            }
            else
            {
               $af_data = snmpwalk_cache_oid($device, 'cbgpPeerAddrFamilyName', $cbgp, 'CISCO-BGP4-MIB');
            }

            foreach ($af_data as $af => $entry)
            {
               if ($cisco_version === 2)
               {
                  list(, $af) = explode('.', $af, 2);
                  $text = $entity['cbgpPeer2AddrFamilyName'];
               }
               else
               {
                  $text = $entity['cbgpPeerAddrFamilyName'];
               }
               $afisafi = explode('.', $af);
               $c       = count($afisafi);
               $afi     = $afisafi[$c - 2];
               $safi    = $afisafi[$c - 1];
               $peer_ip = hex2ip(str_replace(".$afi.$safi", '', $af));
               print_debug("Peer IP: $peer_ip, AFI: $afi, SAFI: $safi");
               if ($afi && $safi)
               {

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
                     $params = array('bgpPeer_id' => $peer_id, 'device_id' => $device['device_id'], 'bgpPeer_id' => $peer_id, 'afi' => $afi, 'safi' => $safi);
                     dbInsert($params, 'bgpPeers_cbgp');
                  }
               }
            }
         } # cisco_version

         if ($vendor_mib)
         {
            // See posible AFI/SAFI here: https://www.juniper.net/techpubs/en_US/junos12.3/topics/topic-map/bgp-multiprotocol.html
            $afis['1']    = 'ipv4';
            $afis['2']    = 'ipv6';
            $afis['ipv4'] = '1';
            $afis['ipv6'] = '2';
            $safis        = array(1   => 'unicast',
                                  2   => 'multicast',
                                  4   => 'mpls',
                                  66  => 'mdt',
                                  128 => 'vpn',
                                  129 => 'vpn multicast');

            //print_vars($vendor_counters);
            foreach ($vendor_bgp as $idx => $entry)
            {
               if (empty($vendor_PeerIndex))
               {
                  $index = $idx;
               }
               else
               {
                  $index = $entry[$vendor_PeerIndex];
               }
               if (count($vendor_use_index))
               {
                  parse_bgp_peer_index($entry, $idx, $vendor_mib);
               }
               $peer_ip = hex2ip($entry[$vendor_PeerRemoteAddr]);
               $peer_id = $peer_ids[$peer_ip];

               $afi     = $entry[$vendor_PeerRemoteAddrType];
               $peer_as = $entry[$vendor_PeerRemoteAs];

               foreach ($safis as $i => $safi)
               {
                  if (isset($vendor_counters[$index . '.' . $afi . ".$i"]) || isset($vendor_counters[$index . '.' . $afis[$afi] . ".$i"]))
                  {
                     if (is_numeric($afi))
                     {
                        $afi = $afis[$afi];
                     }
                     print_debug("INDEX: $index, AS: $peer_as, IP: $peer_ip, AFI: $afi, SAFI: $safi");
                     if (OBS_DEBUG > 1)
                     {
                        var_dump($entry);
                     }
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
                  else
                  {
                     print_debug("Did not find $index.$afi.$i or $index.$afis[$afi].$i");
                  }
               }
            }
         } # Vendors

         // Remove deleted AFI/SAFI
         unset($afi, $safi, $peer_ip, $peer_id);
         $query = 'SELECT * FROM bgpPeers_cbgp WHERE `device_id` = ?';
         foreach (dbFetchRows($query, array($device['device_id'])) as $entry)
         {
            $peer_id = $entry['bgpPeer_id'];
            $afi     = $entry['afi'];
            $safi    = $entry['safi'];
            $cbgp_id = $entry['cbgp_id'];

            if (!isset($af_list[$peer_id][$afi][$safi]))
            {
               dbDelete('bgpPeers_cbgp', '`cbgp_id` = ?', array($cbgp_id));
            }
         } # AF list
         unset($af_list);

      } # os=cisco|some vendors

   } # isset

   // Delete removed peers
   unset($peer_ip, $peer_as);
   $query = 'SELECT * FROM `bgpPeers` WHERE `device_id` = ?';
   foreach (dbFetchRows($query, array($device['device_id'])) as $entry)
   {
      $peer_ip = $entry['bgpPeerRemoteAddr'];
      $peer_as = $entry['bgpPeerRemoteAs'];

      if (!isset($p_list[$peer_ip][$peer_as]))
      {
         dbDelete('bgpPeers', '`bgpPeer_id` = ?', array($entry['bgpPeer_id']));
         $GLOBALS['module_stats'][$module]['deleted']++;
      }
      else
      {
         // Unset, for exclude duplicate entries in DB
         unset($p_list[$peer_ip][$peer_as]);
      }
   }

   $table_headers = array('%WLocal: AS%n', '%WIP%n', '%WPeer: AS%n', '%WIP%n', '%WFamily%n', '%WrDNS%n', '%WRemote Device%n');
   print_cli_table($table_rows, $table_headers);

   unset($p_list, $peerlist, $vendor_mib, $cisco_version, $cisco_peers, $table_rows, $table_headers);
}

// EOF
