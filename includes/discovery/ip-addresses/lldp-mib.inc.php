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

if (!safe_count($ip_data['ipv4']) && !safe_count($ip_data['ipv6'])) {

    // arubaos-cx not have any other place for fetch IP addresses

    $oid_data = snmpwalk_cache_twopart_oid($device, 'lldpLocManAddrTable', [], 'LLDP-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    print_debug_vars($oid_data);

    // lldpLocManAddrLen.1.4.10.30.3.12 = 4
    // lldpLocManAddrIfSubtype.1.4.10.30.3.12 = ifIndex
    // lldpLocManAddrIfId.1.4.10.30.3.12 = 326
    // lldpLocManAddrOID.1.4.10.30.3.12 = ifName.326


    foreach ($oid_data as $ipv => $entry1) {
        if ((int)$ipv === 1) {
            $ip_version = 'ipv4';
        } elseif ((int)$ipv === 2) {
            $ip_version = 'ipv6';
        } else {
            // other types unknown
            continue;
        }

        foreach ($entry1 as $ip_snmp => $entry) {

            [$len, $ip_snmp] = explode('.', $ip_snmp, 2);
            $ip_address = $ip_version === 'ipv6' ? snmp2ipv6($ip_snmp) : $ip_snmp;

            if ($entry['lldpLocManAddrIfSubtype'] === 'ifIndex') {
                $ifIndex = $entry['lldpLocManAddrIfId'];
            } elseif ($port_id = get_port_id_by_ifDescr($device['device_id'], $entry['lldpLocManAddrIfId'])) {
                $port    = get_port_by_id_cache($port_id);
                $ifIndex = $port['ifIndex'];
            } else {
                continue;
            }
            $data = [
              'ifIndex' => $ifIndex,
              'ip'      => $ip_address
            ];
            discover_add_ip_address($device, $mib, $data);
        }
    }

    unset($ifIndex, $ip_address, $ip_snmp, $entry1, $oid_data);
}

// EOF