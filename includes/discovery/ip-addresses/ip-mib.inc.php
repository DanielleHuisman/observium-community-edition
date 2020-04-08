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

// IPv4 addresses
$ip_version = 'ipv4';

// NOTE. By default used old tables IP-MIB, because some weird vendors use "random" data in new tables:
//ipAddressIfIndex.ipv4."94.142.242.194" = 2
//ipAddressIfIndex.ipv4."127.0.0.1" = 1
//ipAddressPrefix.ipv4."94.142.242.194" = ipAddressPrefixOrigin.2.ipv4."88.0.0.0".5
//ipAddressPrefix.ipv4."127.0.0.1" = ipAddressPrefixOrigin.1.ipv4."51.101.48.0".0

// Get IP addresses from IP-MIB (old table)
// Normal:
//IP-MIB::ipAdEntIfIndex.10.0.0.130 = 193
//IP-MIB::ipAdEntNetMask.10.0.0.130 = 255.255.255.252
// Cisco Nexus (seems as first number is interface index):
//IP-MIB::ipAdEntIfIndex.4.10.44.44.110 = 151192525
//IP-MIB::ipAdEntNetMask.4.10.44.44.110 = 255.255.255.0
// Bintec Elmeg (seems as last number is counter number 1,2,3,etc)
//IP-MIB::ipAdEntIfIndex.192.168.1.254.0 = 1000
//IP-MIB::ipAdEntNetMask.192.168.1.254.0 = 255.255.255.0
$oid_data = array();
foreach (array('ipAdEntIfIndex', 'ipAdEntNetMask') as $oid)
{
  $oid_data = snmpwalk_cache_oid($device, $oid, $oid_data, 'IP-MIB');
}

// Rewrite IP-MIB array
foreach ($oid_data as $ip_address => $entry)
{
  $ifIndex = $entry['ipAdEntIfIndex'];
  $ip_address_fix = explode('.', $ip_address);
  switch (count($ip_address_fix))
  {
    case 4:
      break; // Just normal IPv4 address
    case 5:
      if ($device['os_group'] == 'bintec')
      {
        // Bintec Elmeg, see: http://jira.observium.org/browse/OBSERVIUM-1958
        unset($ip_address_fix[4]);
      } else {
        // Cisco Nexus, see: http://jira.observium.org/browse/OBSERVIUM-728
        unset($ip_address_fix[0]);
      }
      $ip_address = implode('.', $ip_address_fix);
      break;
    default:
      print_debug("Detected unknown IPv4 address: $ip_address");
      continue 2;
  }
  $ip_mask_fix = explode('.', $entry['ipAdEntNetMask']);
  if ($ip_mask_fix[0] < 255 && $ip_mask_fix[1] <= '255' && $ip_mask_fix[2] <= '255' && $ip_mask_fix[3] == '255')
  {
    // On some D-Link used wrong masks: 252.255.255.255, 0.255.255.255
    $entry['ipAdEntNetMask'] = $ip_mask_fix[3] . '.' . $ip_mask_fix[2] . '.' . $ip_mask_fix[1] . '.' . $ip_mask_fix[0];
  }
  if (empty($entry['ipAdEntNetMask']) || count($ip_mask_fix) != 4)
  {
    $entry['ipAdEntNetMask'] = '255.255.255.255';
  }

  $ip_data[$ip_version][$ifIndex][$ip_address] = array('ifIndex' => $ifIndex,
                                                       'ip'      => $ip_address,
                                                       'mask'    => $entry['ipAdEntNetMask']);
}

// Get IP addresses from IP-MIB (new table, both IPv4/IPv6)
$flags = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
$oid_data = array();
foreach (array('ipAddressIfIndex', 'ipAddressType', 'ipAddressPrefix', 'ipAddressOrigin') as $oid)
{
  $oid_data = snmpwalk_cache_twopart_oid($device, $oid, $oid_data, 'IP-MIB', NULL, $flags);
  if ($oid == 'ipAddressIfIndex' && snmp_status() === FALSE)
  {
    break; // Stop walk, not exist table
  }
}
//print_vars($oid_data);

if (!count($ip_data[$ip_version]))
{
  //IP-MIB::ipAddressIfIndex.ipv4."198.237.180.2" = 8
  //IP-MIB::ipAddressPrefix.ipv4."198.237.180.2" = ipAddressPrefixOrigin.8.ipv4."198.237.180.2".32
  //IP-MIB::ipAddressOrigin.ipv4."198.237.180.2" = manual
  //Origins: 1:other, 2:manual, 4:dhcp, 5:linklayer, 6:random

  // IPv4z (not sure, never seen)
  if (isset($oid_data[$ip_version . 'z']))
  {
    $oid_data[$ip_version] = array_merge((array)$oid_data[$ip_version], $oid_data[$ip_version . 'z']);
  }

  // Rewrite IP-MIB array
  foreach ($oid_data[$ip_version] as $ip_address => $entry)
  {
    if (in_array($entry['ipAddressType'], $GLOBALS['config']['ip-address']['ignore_type'])) { continue; } // Skip broadcasts
    //$ip_address = str_replace($ip_version.'.', '', $key);
    $ifIndex = $entry['ipAddressIfIndex'];
    $tmp_prefix = explode('.', $entry['ipAddressPrefix']);
    $entry['ipAddressPrefix'] = end($tmp_prefix);

    $ip_data[$ip_version][$ifIndex][$ip_address] = array('ifIndex' => $ifIndex,
                                                         'ip'      => $ip_address,
                                                         'prefix'  => $entry['ipAddressPrefix'],
                                                         'type'    => $entry['ipAddressType'],
                                                         'origin'  => $entry['ipAddressOrigin']);
  }

}

// IPv6 addresses
$ip_version = 'ipv6';

//ipAddressIfIndex.ipv6."00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:01" = 1
//ipAddressPrefix.ipv6."00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:01" = ipAddressPrefixOrigin.1.ipv6."00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:01".128
//ipAddressOrigin.ipv6."00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:01" = manual
//Origins: 1:other, 2:manual, 4:dhcp, 5:linklayer, 6:random

// IPv6z
if (isset($oid_data[$ip_version . 'z']))
{
  $oid_data[$ip_version] = array_merge((array)$oid_data[$ip_version], $oid_data[$ip_version . 'z']);
}

// Rewrite IP-MIB array
$check_ipv6_mib = FALSE; // Flag for additionally check IPv6-MIB
foreach ($oid_data[$ip_version] as $ip_snmp => $entry)
{
  $ip_address = hex2ip($ip_snmp);
  $ifIndex = $entry['ipAddressIfIndex'];
  if ($entry['ipAddressPrefix'] == 'zeroDotZero')
  {
    // Additionally walk IPV6-MIB, especially in JunOS because they spit at world standards
    // See: http://jira.observium.org/browse/OBSERVIUM-1271
    $check_ipv6_mib = TRUE;
  }
  $tmp_prefix = explode('.', $entry['ipAddressPrefix']);
  $entry['ipAddressPrefix'] = end($tmp_prefix);

  $ip_data[$ip_version][$ifIndex][$ip_address] = array('ifIndex' => $ifIndex,
                                                       'ip'     => $ip_address,
                                                       'prefix' => $entry['ipAddressPrefix'],
                                                       'type'   => $entry['ipAddressType'],
                                                       'origin' => $entry['ipAddressOrigin']);
}

unset($ifIndex, $ip_address, $tmp_prefix, $oid_data);

// EOF
