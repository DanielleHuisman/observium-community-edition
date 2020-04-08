<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// Cisco SB also has IPv4 table, but IPv4 supported by standard IP-MIB
//CISCOSB-IP::rsIpAdEntAddr.69.84.249.79 = IpAddress: 69.84.249.79
//CISCOSB-IP::rsIpAdEntAddr.127.0.0.1 = IpAddress: 127.0.0.1
//CISCOSB-IP::rsIpAdEntIfIndex.69.84.249.79 = INTEGER: 300000
//CISCOSB-IP::rsIpAdEntIfIndex.127.0.0.1 = INTEGER: 20000
//CISCOSB-IP::rsIpAdEntNetMask.69.84.249.79 = IpAddress: 255.255.254.0
//CISCOSB-IP::rsIpAdEntNetMask.127.0.0.1 = IpAddress: 0.0.0.0
//CISCOSB-IP::rsIpAdEntForwardIpBroadcast.69.84.249.79 = INTEGER: disable(2)
//CISCOSB-IP::rsIpAdEntForwardIpBroadcast.127.0.0.1 = INTEGER: disable(2)
//CISCOSB-IP::rsIpAdEntStatus.69.84.249.79 = INTEGER: valid(1)
//CISCOSB-IP::rsIpAdEntStatus.127.0.0.1 = INTEGER: valid(1)
//CISCOSB-IP::rsIpAdEntBcastAddr.69.84.249.79 = INTEGER: 1
//CISCOSB-IP::rsIpAdEntBcastAddr.127.0.0.1 = INTEGER: 1
//CISCOSB-IP::rsIpAdEntArpServer.69.84.249.79 = INTEGER: disable(2)
//CISCOSB-IP::rsIpAdEntArpServer.127.0.0.1 = INTEGER: disable(2)
//CISCOSB-IP::rsIpAdEntName.69.84.249.79 = STRING:
//CISCOSB-IP::rsIpAdEntName.127.0.0.1 = STRING:
//CISCOSB-IP::rsIpAdEntOwner.69.84.249.79 = INTEGER: static(1)
//CISCOSB-IP::rsIpAdEntOwner.127.0.0.1 = INTEGER: internal(3)
//CISCOSB-IP::rsIpAdEntAdminStatus.69.84.249.79 = INTEGER: up(1)
//CISCOSB-IP::rsIpAdEntAdminStatus.127.0.0.1 = INTEGER: up(1)
//CISCOSB-IP::rsIpAdEntOperStatus.69.84.249.79 = INTEGER: active(1)
//CISCOSB-IP::rsIpAdEntOperStatus.127.0.0.1 = INTEGER: active(1)
//CISCOSB-IP::rsIpAdEntPrecedence.69.84.249.79 = INTEGER: 1
//CISCOSB-IP::rsIpAdEntPrecedence.127.0.0.1 = INTEGER: 1
//CISCOSB-IP::rsIpAdEntUniqueStatus.69.84.249.79 = INTEGER: valid(1)
//CISCOSB-IP::rsIpAdEntUniqueStatus.127.0.0.1 = INTEGER: valid(1)

//CISCOSB-IPv6::rlIpAddressPrefixLength.ipv6z."fe:80:00:00:00:00:00:00:5a:35:d9:ff:fe:98:3e:d1%100000" = Gauge32: 64
//CISCOSB-IPv6::rlIpAddressPrefixLength.ipv6z."ff:02:00:00:00:00:00:00:00:00:00:00:00:00:00:01%100000" = Gauge32: 0
//CISCOSB-IPv6::rlIpAddressPrefixLength.ipv6z."ff:02:00:00:00:00:00:00:00:00:00:01:ff:98:3e:d1%100000" = Gauge32: 0
//CISCOSB-IPv6::rlIpAddressType.ipv6z."fe:80:00:00:00:00:00:00:5a:35:d9:ff:fe:98:3e:d1%100000" = INTEGER: unicast(1)
//CISCOSB-IPv6::rlIpAddressType.ipv6z."ff:02:00:00:00:00:00:00:00:00:00:00:00:00:00:01%100000" = INTEGER: multicast(4)
//CISCOSB-IPv6::rlIpAddressType.ipv6z."ff:02:00:00:00:00:00:00:00:00:00:01:ff:98:3e:d1%100000" = INTEGER: multicast(4)

$ip_version = 'ipv6';

if ($check_ipv6_mib || !count($ip_data[$ip_version])) // Note, $check_ipv6_mib set in IP-MIB discovery
{
  $mib = 'CISCOSB-IPv6';

  // Get IP addresses from CISCOSB-IPv6
  $oid_data = snmpwalk_cache_twopart_oid($device, 'rlIpAddressEntry', array(), $mib, NULL, OBS_SNMP_ALL ^ OBS_QUOTES_STRIP);
  //print_vars($oid_data);

  // Rewrite CISCOSB-IPv6 array
  foreach ($oid_data as $ip_type => $entry1)
  {
    foreach ($entry1 as $ip_snmp => $entry)
    {
      if ($entry['rlIpAddressType'] == 'broadcast') { continue; } // Skip broadcasts
      list($ip_snmp, $ifIndex) = explode('%', $ip_snmp, 2);
      $ip_address = hex2ip($ip_snmp);

      $ip_data[$ip_version][$ifIndex][$ip_address] = array('ifIndex' => $ifIndex,
                                                           'ip'      => $ip_address,
                                                           'prefix'  => $entry['rlIpAddressPrefixLength'],
                                                           'origin'  => 'unknown',
                                                           'type'    => $entry['rlIpAddressType']);
    }
  }
}

unset($ifIndex, $ip_address, $ip_snmp, $entry1, $oid_data);

// EOF
