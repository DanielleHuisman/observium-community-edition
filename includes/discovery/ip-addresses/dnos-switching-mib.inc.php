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

// DNOS-SWITCHING-MIB::agentNetworkIPAddress.0 = IpAddress: 192.168.1.195
// DNOS-SWITCHING-MIB::agentNetworkSubnetMask.0 = IpAddress: 255.255.254.0
// DNOS-SWITCHING-MIB::agentNetworkDefaultGateway.0 = IpAddress: 192.168.1.100
// DNOS-SWITCHING-MIB::agentNetworkBurnedInMacAddress.0 = Hex-STRING: 68 4F 64 D6 DF BB
// DNOS-SWITCHING-MIB::agentNetworkLocalAdminMacAddress.0 = Hex-STRING: 00 00 00 00 00 00
// DNOS-SWITCHING-MIB::agentNetworkMacAddressType.0 = INTEGER: burned-in(1)
// DNOS-SWITCHING-MIB::agentNetworkConfigProtocol.0 = INTEGER: none(1)
// DNOS-SWITCHING-MIB::agentNetworkConfigProtocolDhcpRenew.0 = INTEGER: normalOperation(0)
// DNOS-SWITCHING-MIB::agentNetworkMgmtVlan.0 = INTEGER: 4
$oids = snmp_get_multi_oid($device, 'agentNetworkIPAddress.0 agentNetworkSubnetMask.0 agentNetworkBurnedInMacAddress.0 agentNetworkMgmtVlan.0', [], $mib);
print_debug_vars($oids);
$entry = $oids[0];
if ($port_id = get_port_id_by_ifDescr($device['device_id'], 'Vl' . $entry['agentNetworkMgmtVlan'])) {
    // By Vlan
    $port    = get_port_by_id_cache($port_id);
    $ifIndex = $port['ifIndex'];
} elseif ($port_id = get_port_id_by_mac($device['device_id'], $entry['agentNetworkBurnedInMacAddress'])) {
    // By MAC address
    $port    = get_port_by_id_cache($port_id);
    $ifIndex = $port['ifIndex'];
} else {
    $ifIndex = 0;
}

$data = [
  'ifIndex' => $ifIndex,
  'ip'      => $entry['agentNetworkIPAddress'],
  'mask'    => $entry['agentNetworkSubnetMask']
];
discover_add_ip_address($device, $mib, $data);

// DNOS-SWITCHING-MIB::agentNetworkIpv6AdminMode.0 = INTEGER: enabled(1)
// DNOS-SWITCHING-MIB::agentNetworkIpv6AddrPrefixLength."........jOd....." = INTEGER: 64
// DNOS-SWITCHING-MIB::agentNetworkIpv6AddrEuiFlag."........jOd....." = INTEGER: enabled(1)
// DNOS-SWITCHING-MIB::agentNetworkIpv6AddrStatus."........jOd....." = INTEGER: active(1)
// DNOS-SWITCHING-MIB::agentNetworkIpv6AddressAutoConfig.0 = INTEGER: disable(2)
// DNOS-SWITCHING-MIB::agentNetworkIpv6ConfigProtocol.0 = INTEGER: none(1)
if (snmp_get_oid($device, 'agentNetworkIpv6AdminMode.0', $mib) === 'enabled') {
    // IPv6
    $oids = snmpwalk_cache_oid($device, 'agentNetworkIpv6AddrPrefixLength', [], $mib, NULL, OBS_SNMP_ALL_TABLE);
    $oids = snmpwalk_cache_oid($device, 'agentNetworkIpv6AddrStatus', $oids, $mib, NULL, OBS_SNMP_ALL_TABLE);
    print_debug_vars($oids);
    foreach ($oids as $ip_address => $entry) {
        if ($entry['agentNetworkIpv6AddrStatus'] !== 'active') {
            continue;
        }

        $data = [
          'ifIndex' => $ifIndex,
          'ip'      => $ip_address,
          'prefix'  => $entry['agentNetworkIpv6AddrPrefixLength']
        ];
        discover_add_ip_address($device, $mib, $data);
    }
}

// EOF