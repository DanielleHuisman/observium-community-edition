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

$oid_data = snmpwalk_cache_oid($device, 'ipAdEntIfIndex', [], 'IP-MIB');
if (snmp_status()) {
    $oid_data = snmpwalk_cache_oid($device, 'ipAdEntNetMask', $oid_data, 'IP-MIB');
    if (is_numeric(array_key_first($oid_data))) {
        // Some devices report just numbers instead ip address:
        // IP-MIB::ipAdEntAddr.0 = IpAddress: 0.0.0.0
        // IP-MIB::ipAdEntAddr.2130706433 = IpAddress: 127.0.0.1
        // IP-MIB::ipAdEntIfIndex.0 = INTEGER: 6620672
        // IP-MIB::ipAdEntIfIndex.2130706433 = INTEGER: 6625280
        // IP-MIB::ipAdEntNetMask.0 = IpAddress: 255.255.255.0
        // IP-MIB::ipAdEntNetMask.2130706433 = IpAddress: 255.255.255.255
        // IP-MIB::ipAdEntBcastAddr.0 = INTEGER: 1
        // IP-MIB::ipAdEntBcastAddr.2130706433 = INTEGER: 0
        $oid_data = snmpwalk_cache_oid($device, 'ipAdEntAddr', $oid_data, 'IP-MIB');
    }
    print_debug_vars($oid_data);
}

// Rewrite IP-MIB array
foreach ($oid_data as $ip_address => $entry) {
    $ifIndex        = $entry['ipAdEntIfIndex'];
    $ip_address_fix = explode('.', $ip_address);
    switch (count($ip_address_fix)) {
        case 4:
            break; // Just normal IPv4 address
        case 5:
            if (in_array($device['os_group'], ['bintec', 'fortinet'])) {
                // Bintec Elmeg, see: http://jira.observium.org/browse/OBSERVIUM-1958
                // Same trouble on Fortinet devices
                // ipAdEntNetMask.10.3.250.111.1 = 255.255.255.255
                // ipAdEntNetMask.10.111.6.1.1 = 255.255.255.0
                unset($ip_address_fix[4]);
            } else {
                // Cisco Nexus, see: http://jira.observium.org/browse/OBSERVIUM-728
                unset($ip_address_fix[0]);
            }
            $ip_address = implode('.', $ip_address_fix);
            break;
        default:
            if (isset($entry['ipAdEntAddr']) && get_ip_version($entry['ipAdEntAddr'])) {
                $ip_address = $entry['ipAdEntAddr'];
            } else {
                print_debug("Detected unknown IPv4 address: $ip_address");
                continue 2;
            }
    }
    $ip_mask_fix = explode('.', $entry['ipAdEntNetMask']);
    if ($ip_mask_fix[0] < 255 && $ip_mask_fix[1] <= '255' && $ip_mask_fix[2] <= '255' && $ip_mask_fix[3] == '255') {
        // On some D-Link used wrong masks: 252.255.255.255, 0.255.255.255
        $entry['ipAdEntNetMask'] = $ip_mask_fix[3] . '.' . $ip_mask_fix[2] . '.' . $ip_mask_fix[1] . '.' . $ip_mask_fix[0];
    }
    if (empty($entry['ipAdEntNetMask']) || safe_count($ip_mask_fix) != 4) {
        $entry['ipAdEntNetMask'] = '255.255.255.255';
    }

    $data = [
      'ifIndex' => $ifIndex,
      'ip'      => $ip_address,
      'mask'    => $entry['ipAdEntNetMask']
    ];
    discover_add_ip_address($device, $mib, $data);
}

// Get IP addresses from IP-MIB (new table, both IPv4/IPv6)
$flags    = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
$oid_data = snmpwalk_cache_twopart_oid($device, 'ipAddressIfIndex', [], 'IP-MIB', NULL, $flags);
if (snmp_status()) {
    foreach (['ipAddressType', 'ipAddressPrefix', 'ipAddressOrigin'] as $oid) {
        $oid_data = snmpwalk_cache_twopart_oid($device, $oid, $oid_data, 'IP-MIB', NULL, $flags);
    }
    print_debug_vars($oid_data);
}

// IPv4 addresses
if (safe_empty($ip_data[$ip_version])) {
    //IP-MIB::ipAddressIfIndex.ipv4."198.237.180.2" = 8
    //IP-MIB::ipAddressPrefix.ipv4."198.237.180.2" = ipAddressPrefixOrigin.8.ipv4."198.237.180.2".32
    //IP-MIB::ipAddressOrigin.ipv4."198.237.180.2" = manual
    //Origins: 1:other, 2:manual, 4:dhcp, 5:linklayer, 6:random

    // Fortigate example:
    // IP-MIB::ipAddressIfIndex.ipv4."10.200.100.184".1 = INTEGER: 1
    // IP-MIB::ipAddressType.ipv4."10.200.100.184".1 = INTEGER: unicast(1)
    // IP-MIB::ipAddressPrefix.ipv4."10.200.100.184".1 = OID: SNMPv2-SMI::zeroDotZero.0
    // IP-MIB::ipAddressOrigin.ipv4."10.200.100.184".1 = INTEGER: manual(2)
    // IP-MIB::ipAddressStatus.ipv4."10.200.100.184".1 = INTEGER: preferred(1)

    // IPv4z (not sure, never seen)
    if (isset($oid_data[$ip_version . 'z'])) {
        $oid_data[$ip_version] = array_merge((array)$oid_data[$ip_version], $oid_data[$ip_version . 'z']);
    }

    // Rewrite IP-MIB array
    foreach ($oid_data[$ip_version] as $ip_address => $entry) {
        //$ip_address = str_replace($ip_version.'.', '', $key);
        $ifIndex = $entry['ipAddressIfIndex'];

        // ipAddressOrigin.ipv4.169.254.1.1.23 = manual
        $ip_address_fix = explode('.', $ip_address);
        $index_prefix   = NULL;
        switch (safe_count($ip_address_fix)) {
            case 5:
                // get last number as prefix
                $index_prefix = array_pop($ip_address_fix);
                if ($ifIndex == $index_prefix) {
                    // Fortigate report ifIndex as last index part
                    // See: https://jira.observium.org/browse/OBS-4341
                    $index_prefix = NULL;
                }
                $ip_address = implode('.', $ip_address_fix);
                break;
            case 4:
                // Common, no need for changes
                break;
            default:
                print_debug("Unknown IP index: $ip_address");
                continue 2;
        }
        if (!str_contains($entry['ipAddressPrefix'], 'zeroDotZero')) {
            $tmp_prefix               = explode('.', $entry['ipAddressPrefix']);
            $entry['ipAddressPrefix'] = end($tmp_prefix);
            unset($tmp_prefix);
        }
        if (!is_intnum($entry['ipAddressPrefix']) && is_intnum($index_prefix)) {
            $entry['ipAddressPrefix'] = $index_prefix;
        }

        $data = [
          'ifIndex' => $ifIndex,
          'ip'      => $ip_address,
          'prefix'  => $entry['ipAddressPrefix'],
          'type'    => $entry['ipAddressType'],
          'origin'  => $entry['ipAddressOrigin']
        ];
        discover_add_ip_address($device, $mib, $data);
    }

}

// IPv6 addresses
$ip_version = 'ipv6';

//ipAddressIfIndex.ipv6."00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:01" = 1
//ipAddressPrefix.ipv6."00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:01" = ipAddressPrefixOrigin.1.ipv6."00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:01".128
//ipAddressOrigin.ipv6."00:00:00:00:00:00:00:00:00:00:00:00:00:00:00:01" = manual
//Origins: 1:other, 2:manual, 4:dhcp, 5:linklayer, 6:random

// IPv6z
if (isset($oid_data[$ip_version . 'z'])) {
    $oid_data[$ip_version] = array_merge((array)$oid_data[$ip_version], $oid_data[$ip_version . 'z']);
}

// Rewrite IP-MIB array
$check_ipv6_mib = FALSE; // Flag for additionally check IPv6-MIB
foreach ($oid_data[$ip_version] as $ip_snmp => $entry) {
    $ifIndex = $entry['ipAddressIfIndex'];

    if (str_contains($ip_snmp, '.')) {
        // incorrect indexes with prefix
        // ipAddressOrigin.ipv6.65152.0.0.0.521.4095.65033.51218.27 = manual
        $ip_address_fix = explode('.', $ip_snmp);
        $index_prefix   = NULL;
        switch (count($ip_address_fix)) {
            case 9:
                // get last number as prefix
                $index_prefix = array_pop($ip_address_fix);
                if ($ifIndex == $index_prefix) {
                    // Fortigate report ifIndex as last index part
                    // See: https://jira.observium.org/browse/OBS-4341
                    $index_prefix = NULL;
                }
            //break; // Do not break here!
            case 8:
                $ip_address_fix = array_map('dechex', $ip_address_fix);
                $ip_address     = ip_uncompress(implode(':', $ip_address_fix));
                break;
            default:
                print_debug("Unknown IP index: $ip_snmp");
                continue 2;
        }
    } else {
        // Common address index
        $ip_address = hex2ip($ip_snmp);
    }

    if (str_contains($entry['ipAddressPrefix'], 'zeroDotZero')) {
        // Additionally walk IPV6-MIB, especially in JunOS because they spit at world standards
        // See: http://jira.observium.org/browse/OBSERVIUM-1271
        $check_ipv6_mib = TRUE;
    } else {
        $tmp_prefix               = explode('.', $entry['ipAddressPrefix']);
        $entry['ipAddressPrefix'] = end($tmp_prefix);
    }
    if (!is_intnum($entry['ipAddressPrefix']) && is_intnum($index_prefix)) {
        $entry['ipAddressPrefix'] = $index_prefix;
        $check_ipv6_mib           = FALSE;
    }

    $data = [
      'ifIndex' => $ifIndex,
      'ip'      => $ip_address,
      'prefix'  => $entry['ipAddressPrefix'],
      'type'    => $entry['ipAddressType'],
      'origin'  => $entry['ipAddressOrigin']
    ];
    discover_add_ip_address($device, $mib, $data);
}

unset($ifIndex, $ip_address, $tmp_prefix, $oid_data);

// EOF
