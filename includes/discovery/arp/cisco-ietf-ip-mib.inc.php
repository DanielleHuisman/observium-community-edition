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

$oids = snmpwalk_cache_threepart_oid($device, 'cInetNetToMediaPhysAddress', [], 'CISCO-IETF-IP-MIB', NULL, OBS_SNMP_ALL_TABLE);
if (snmp_status()) {
    // Last check CISCO-IETF-IP-MIB::cInetNetToMediaPhysAddress (IPv6 only, Cisco only)
    //cInetNetToMediaPhysAddress[167][ipv6]["20:01:0b:08:0b:08:0b:08:00:00:00:00:00:00:00:b1"] 0:24:c4:db:9b:40:0:0
    print_debug("Used CISCO-IETF-IP-MIB::cInetNetToMediaPhysAddress");

    foreach ($oids as $ifIndex => $oids1) {
        foreach ($oids1 as $ip_version => $oids2) {
            $mac_found[$ip_version][$vrf_name] = 1;

            foreach ($oids2 as $ip => $entry) {
                $mac_array = explode(':', $entry['cInetNetToMediaPhysAddress']);
                switch (safe_count($mac_array)) {
                    case 4:
                        // Convert IPv4 to fake MAC for 6to4 tunnels
                        //cInetNetToMediaPhysAddress[27][ipv6]["20:02:c0:58:63:01:00:00:00:00:00:00:00:00:00:00"] 0:0:c0:58
                        $mac_array[] = 'ff';
                        $mac_array[] = 'fe';
                        $mac = implode(':', $mac_array);
                        break;
                    case 8:
                        array_pop($mac_array);
                        array_pop($mac_array);
                        $mac = implode(':', $mac_array);
                        break;
                    case 6:
                    default:
                        $mac = $entry['cInetNetToMediaPhysAddress'];
                }
                if (!is_valid_param($mac, 'mac')) {
                    print_debug("Invalid MAC address '$mac'");
                    print_debug_vars($entry);
                    continue;
                }
                $ip = ip_uncompress(hex2ip($ip));

                $mac_table[$vrf_name][$ip_version][$ifIndex][$ip] = mac_zeropad($mac);
            }
        }
    }
}

// EOF
