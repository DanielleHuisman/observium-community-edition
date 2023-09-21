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

$oids = snmpwalk_cache_threepart_oid($device, 'ipNetToPhysicalPhysAddress', [], 'IP-MIB', NULL, OBS_SNMP_ALL_TABLE);
if (snmp_status()) {
    // First check IP-MIB::ipNetToPhysicalPhysAddress (IPv4 & IPv6)
    //ipNetToPhysicalPhysAddress[5][ipv4]["80.93.52.129"] 0:23:ab:64:d:42
    //ipNetToPhysicalPhysAddress[34][ipv6]["2a:01:00:d8:00:00:00:01:00:00:00:00:00:00:00:03"] 0:15:63:e8:fb:31:0:0
    print_debug("Used IP-MIB::ipNetToPhysicalPhysAddress");

    foreach ($oids as $ifIndex => $oids1) {
        foreach ($oids1 as $ip_version => $oids2) {
            $mac_found[$ip_version][$vrf_name] = 1;

            foreach ($oids2 as $ip => $entry) {
                $mac_array = explode(':', $entry['ipNetToPhysicalPhysAddress']);
                switch (safe_count($mac_array)) {
                    case 4:
                        // Convert IPv4 to fake MAC for 6to4 tunnels
                        //ipNetToPhysicalPhysAddress[27][ipv6]["20:02:c0:58:63:01:00:00:00:00:00:00:00:00:00:00"] 0:0:c0:58
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
                        $mac = $entry['ipNetToPhysicalPhysAddress'];
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
} elseif ($oids = snmpwalk_cache_twopart_oid($device, 'ipNetToMediaPhysAddress', [], 'IP-MIB', NULL, OBS_SNMP_ALL_TABLE)) {
    // Check IP-MIB::ipNetToMediaPhysAddress (IPv4 only)
    //ipNetToMediaPhysAddress[213][10.0.0.162] 70:81:5:ec:f9:bf
    print_debug("Used IP-MIB::ipNetToMediaPhysAddress");
    $ip_version = 'ipv4';
    $mac_found[$ip_version][$vrf_name] = 1;

    foreach ($oids as $ifIndex => $oids1) {
        foreach ($oids1 as $ip => $entry) {
            if (!is_valid_param($entry['ipNetToMediaPhysAddress'], 'mac')) {
                print_debug("Invalid MAC address '{$entry['ipNetToMediaPhysAddress']}'");
                print_debug_vars($entry);
                continue;
            }
            $mac_table[$vrf_name][$ip_version][$ifIndex][$ip] = mac_zeropad($entry['ipNetToMediaPhysAddress']);
        }
    }
}
//print_debug_vars($oids);

// EOF