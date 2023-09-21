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
if ($check_ipv6_mib || !safe_count($ip_data[$ip_version])) { // Note, $check_ipv6_mib set in IP-MIB discovery

    // Get IP addresses from IPV6-MIB
    //ipv6AddrPfxLength.20.254.192.0.0.0.0.0.0.0.10.0.0.0.0.0.4 = 64
    //ipv6AddrPfxLength.573.42.1.183.64.0.1.130.32.0.0.0.0.0.0.0.2 = 126
    //ipv6AddrType.20.254.192.0.0.0.0.0.0.0.10.0.0.0.0.0.4 = stateful
    //ipv6AddrType.573.42.1.183.64.0.1.130.32.0.0.0.0.0.0.0.2 = stateful
    //ipv6AddrAnycastFlag.20.254.192.0.0.0.0.0.0.0.10.0.0.0.0.0.4 = false
    //ipv6AddrAnycastFlag.573.42.1.183.64.0.1.130.32.0.0.0.0.0.0.0.2 = false
    //ipv6AddrStatus.20.254.192.0.0.0.0.0.0.0.10.0.0.0.0.0.4 = preferred
    //ipv6AddrStatus.573.42.1.183.64.0.1.130.32.0.0.0.0.0.0.0.2 = preferred
    //Types: stateless(1), stateful(2), unknown(3)
    $oid_data = snmpwalk_cache_twopart_oid($device, 'ipv6AddrEntry', [], 'IPV6-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    //print_vars($oid_data);

    // Rewrite IPV6-MIB array
    foreach ($oid_data as $ifIndex => $entry1) {
        foreach ($entry1 as $ip_snmp => $entry) {
            $ip_address = snmp2ipv6($ip_snmp);

            /*
            $ip_data[$ip_version][$ifIndex][$ip_address] = array('ifIndex' => $ifIndex,
                                                                 'ip'     => $ip_address,
                                                                 'prefix' => $entry['ipv6AddrPfxLength'],
                                                                 'origin' => $entry['ipv6AddrType']);
            */
            $data = [
              'ifIndex' => $ifIndex,
              'ip'      => $ip_address,
              'prefix'  => $entry['ipv6AddrPfxLength'],
              'origin'  => $entry['ipv6AddrType']
            ];
            discover_add_ip_address($device, $mib, $data);
        }
    }
}

unset($ifIndex, $ip_address, $ip_snmp, $entry1, $oid_data);

// EOF
