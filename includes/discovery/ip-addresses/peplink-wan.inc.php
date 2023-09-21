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

// IF-MIB::ifDescr.1 = STRING: LAN
// IF-MIB::ifDescr.2 = STRING: WAN 1-SACOFA
// IF-MIB::ifDescr.3 = STRING: WAN 2-TM
// IF-MIB::ifDescr.4 = STRING: WAN 3

// PEPLINK-WAN::wanName.0 = STRING: "WAN 1-SACOFA"
// PEPLINK-WAN::wanName.1 = STRING: "WAN 2-TM"
// PEPLINK-WAN::wanName.2 = STRING: "WAN 3"
// PEPLINK-WAN::wanName.3 = STRING: "Mobile Internet"

// PEPLINK-WAN::wanNetworkIpType.0.0 = INTEGER: static(1)
// PEPLINK-WAN::wanNetworkIpType.1.0 = INTEGER: static(1)
// PEPLINK-WAN::wanNetworkIpType.2.0 = INTEGER: dhcp(0)
// PEPLINK-WAN::wanNetworkIpType.3.0 = INTEGER: pppoe(2)
// PEPLINK-WAN::wanNetworkIpAddress.0.0 = IpAddress: 103.60.24.211
// PEPLINK-WAN::wanNetworkIpAddress.1.0 = IpAddress: 219.93.31.99
// PEPLINK-WAN::wanNetworkIpAddress.2.0 = IpAddress: 0.0.0.0
// PEPLINK-WAN::wanNetworkIpAddress.3.0 = IpAddress: 0.0.0.0
// PEPLINK-WAN::wanNetworkSubnetMask.0.0 = IpAddress: 255.255.255.240
// PEPLINK-WAN::wanNetworkSubnetMask.1.0 = IpAddress: 255.255.255.224
// PEPLINK-WAN::wanNetworkSubnetMask.2.0 = IpAddress: 0.0.0.0
// PEPLINK-WAN::wanNetworkSubnetMask.3.0 = IpAddress: 0.0.0.0

$ip_version = 'ipv4';
if (!safe_count($ip_data[$ip_version])) {

    $oids = snmpwalk_cache_twopart_oid($device, 'wanNetworkIpTable', [], 'PEPLINK-WAN');
    if (snmp_status()) {
        $oids_int = snmpwalk_cache_oid($device, 'wanName', [], 'PEPLINK-WAN');

        foreach ($oids as $wanid => $wan) {
            $ifIndex = 0;
            if (isset($oids_int[$wanid]) &&
                $port_id = get_port_id_by_ifDescr($device['device_id'], $oids_int[$wanid]['wanName'])) {
                $port    = get_port_by_id_cache($port_id);
                $ifIndex = $port['ifIndex'];
            }

            foreach ($wan as $entry) {
                $data = [
                  'ifIndex' => $ifIndex,
                  'ip'      => $entry['wanNetworkIpAddress'],
                  'mask'    => $entry['wanNetworkSubnetMask'],
                  //'type'    => $entry[''],
                  'origin'  => $entry['wanNetworkIpType']
                ];
                discover_add_ip_address($device, $mib, $data);
            }
        }
    }
}

// EOF
