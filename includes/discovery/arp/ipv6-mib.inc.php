<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if ($mac_found['ipv6'][$vrf_name]) { return; }

if ($oids = snmpwalk_cache_twopart_oid($device, 'ipv6NetToMediaPhysAddress', [], 'IPV6-MIB', NULL, OBS_SNMP_ALL_TABLE)) {
    // Or check IPV6-MIB::ipv6NetToMediaPhysAddress (IPv6 only, deprecated, junos)
    //ipv6NetToMediaPhysAddress[18][fe80:0:0:0:200:ff:fe00:4] 2:0:0:0:0:4
    print_debug("Used IPV6-MIB::ipv6NetToMediaPhysAddress");
    $ip_version = 'ipv6';
    $mac_found[$ip_version][$vrf_name] = 1;

    foreach ($oids as $ifIndex => $oids1) {
        foreach ($oids1 as $ip => $entry) {
            if (!is_valid_param($entry['ipv6NetToMediaPhysAddress'], 'mac')) {
                print_debug("Invalid MAC address '{$entry['ipv6NetToMediaPhysAddress']}'");
                print_debug_vars($entry);
                continue;
            }

            if (str_contains($ip, ':') && str_contains($ip, '.')) {
                // Windows return incorrect index:
                // ipv6NetToMediaPhysAddress[1][10ff:200:0:0:0:0:0:100].2 =
                // ipv6NetToMediaPhysAddress[5][10ff:200:0:0:0:0:0:0].1 = 33:33:0:0:0:1
                $ip = explode('.', $ip)[0];
            } else {
                $ip = hex2ip($ip);
            }
            $ip = ip_uncompress($ip);
            $mac_table[$vrf_name][$ip_version][$ifIndex][$ip] = mac_zeropad($entry['ipv6NetToMediaPhysAddress']);
        }
    }
}

// EOF
