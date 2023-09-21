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

// F5-BIGIP-SYSTEM-MIB

//if (!count($ip_data['ipv4']) && !count($ip_data['ipv6']))
//{
// Note, F5-BIGIP support IP-MIB, but on some firmware versions return not correct ifIndexes, this MIB uses correct indexes

$mib = 'F5-BIGIP-SYSTEM-MIB';

// Get IP addresses from F5-BIGIP-SYSTEM-MIB
//F5-BIGIP-SYSTEM-MIB::sysSelfIpAddrType."/Common/157.122.148.244" = INTEGER: ipv4(1)
//F5-BIGIP-SYSTEM-MIB::sysSelfIpAddrType."/Common/vlan2422HeFei-CU1G" = INTEGER: ipv4(1)
//F5-BIGIP-SYSTEM-MIB::sysSelfIpAddr."/Common/157.122.148.244" = Hex-STRING: 9D 7A 94 F4
//F5-BIGIP-SYSTEM-MIB::sysSelfIpAddr."/Common/vlan2422HeFei-CU1G" = Hex-STRING: 2A 9D 09 42
//F5-BIGIP-SYSTEM-MIB::sysSelfIpNetmask."/Common/vlan2422HeFei-CU1G" = Hex-STRING: FF FF FF F8
//F5-BIGIP-SYSTEM-MIB::sysSelfIpNetmask."/Common/vlan3682HeFei-CU2G" = Hex-STRING: FF FF FF F0
//F5-BIGIP-SYSTEM-MIB::sysSelfIpVlanName."/Common/157.122.148.244" = STRING: /Common/vlan3617
//F5-BIGIP-SYSTEM-MIB::sysSelfIpVlanName."/Common/vlan2422HeFei-CU1G" = STRING: /Common/vlan2422HeFei_CU1G
//F5-BIGIP-SYSTEM-MIB::sysSelfIpUnitId."/Common/157.122.148.244" = INTEGER: 1
//F5-BIGIP-SYSTEM-MIB::sysSelfIpUnitId."/Common/vlan2422HeFei-CU1G" = INTEGER: 0
//$flags = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
$flags    = OBS_SNMP_ALL;
$oid_data = [];
foreach (['sysSelfIpAddrType', 'sysSelfIpVlanName', 'sysSelfIpUnitId', 'sysSelfIpAddr', 'sysSelfIpNetmask'] as $oid) {
    $oid_data = snmpwalk_cache_oid($device, $oid, $oid_data, $mib, NULL, $flags);
    if ($oid == 'sysSelfIpAddrType' && $GLOBALS['snmp_status'] === FALSE) {
        break; // Stop walk, not exist table
    }
}
//print_vars($oid_data);

$query = 'SELECT `port_id`, `ifIndex`, `ifDescr` FROM `ports` WHERE `device_id` = ? AND `deleted` = ?';
foreach (dbFetchRows($query, [$device['device_id'], 0]) as $entry) {
    $ports_ifDescr[$entry['ifDescr']] = $entry;
}

// Rewrite F5-BIGIP-SYSTEM-MIB array
foreach ($oid_data as $ip_name => $entry) {
    if ($entry['sysSelfIpUnitId'] != '0') {
        continue;
    } // Skip all except self Unit addresses

    $ifName = $entry['sysSelfIpVlanName'];
    if (isset($ports_ifDescr[$ifName])) {
        $ifIndex    = $ports_ifDescr[$ifName]['ifIndex'];
        $ip_version = $entry['sysSelfIpAddrType'];
        $ip_address = hex2ip($entry['sysSelfIpAddr']);
        $ip_mask    = hex2ip($entry['sysSelfIpNetmask']);

        if ($ip_version == 'ipv4') {
            $ip_mask_fix = explode('.', $ip_mask);
            if (empty($ip_mask) || count($ip_mask_fix) != 4) {
                $ip_mask = '255.255.255.255';
            }
        } else {
            // IPv6 - not tested
            $prefix = 0;
            $m      = str_split($ip_mask);
            foreach ($m as $c) {
                if ($c == ":") {
                    continue;
                }
                if ($c == "0") {
                    break;
                }
                $bin    = base_convert($c, 16, 2);
                $bin    = trim($bin, "0");
                $prefix += strlen($bin);
            }
        }
        /*
        $ip_data[$ip_version][$ifIndex][$ip_address] = array('ifIndex' => $ifIndex,
                                                              'ip'      => $ip_address,
                                                              'mask'    => $ip_mask,
                                                              'prefix'  => $prefix);
        */
        $data = [
          'ifIndex' => $ifIndex,
          'ip'      => $ip_address,
          'mask'    => $ip_mask,
          'prefix'  => $prefix
        ];
        discover_add_ip_address($device, $mib, $data);
    }
    unset($ifName, $ifIndex, $ip_version, $ip_address, $ip_mask, $prefix);
}
//}

unset($ports_ifDescr, $ifIndex, $ip_address, $ip_mask_fix, $oid_data, $flags);

// EOF
