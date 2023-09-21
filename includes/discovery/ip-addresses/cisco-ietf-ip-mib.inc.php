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

$ip_version = 'ipv6';
if (!safe_count($ip_data[$ip_version])) {
    // Get IP addresses from CISCO-IETF-IP-MIB
    //cIpAddressIfIndex.ipv6."20:01:04:70:00:15:00:bb:00:00:00:00:00:00:00:02" = 450
    //cIpAddressPrefix.ipv6."20:01:04:70:00:15:00:bb:00:00:00:00:00:00:00:02" = cIpAddressPfxOrigin.450.ipv6."20:01:04:70:00:15:00:bb:00:00:00:00:00:00:00:00".64
    //cIpAddressOrigin.ipv6."20:01:04:70:00:15:00:bb:00:00:00:00:00:00:00:02" = manual
    //Origins: 1:other, 2:manual, 4:dhcp, 5:linklayer, 6:random
    $flags    = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
    $oid_data = [];
    foreach (['cIpAddressIfIndex', 'cIpAddressPrefix', 'cIpAddressType', 'cIpAddressOrigin'] as $oid) {
        $oid_data = snmpwalk_cache_twopart_oid($device, $oid . '.' . $ip_version, $oid_data, 'CISCO-IETF-IP-MIB', NULL, $flags);
        if ($oid == 'cIpAddressIfIndex' && snmp_status() === FALSE) {
            break; // Stop walk, not exist table
        }
    }
    //print_vars($oid_data);

    // IPv6z
    if (isset($oid_data[$ip_version . 'z'])) {
        $oid_data[$ip_version] = array_merge((array)$oid_data[$ip_version], $oid_data[$ip_version . 'z']);
    }

    // Rewrite CISCO-IETF-IP-MIB array
    foreach ($oid_data[$ip_version] as $ip_snmp => $entry) {
        $ip_address                = hex2ip($ip_snmp);
        $ifIndex                   = $entry['cIpAddressIfIndex'];
        $tmp_prefix                = explode('.', $entry['cIpAddressPrefix']);
        $entry['cIpAddressPrefix'] = end($tmp_prefix);

        /*
        $ip_data[$ip_version][$ifIndex][$ip_address] = array('ifIndex' => $ifIndex,
                                                             'ip'     => $ip_address,
                                                             'prefix' => $entry['cIpAddressPrefix'],
                                                             'type'   => $entry['cIpAddressType'],
                                                             'origin' => $entry['cIpAddressOrigin']);
        */
        $data = [
          'ifIndex' => $ifIndex,
          'ip'      => $ip_address,
          'prefix'  => $entry['cIpAddressPrefix'],
          'type'    => $entry['cIpAddressType'],
          'origin'  => $entry['cIpAddressOrigin']
        ];
        discover_add_ip_address($device, $mib, $data);
    }
}

unset($ifIndex, $ip_address, $tmp_prefix, $oid_data, $flags);

// EOF
