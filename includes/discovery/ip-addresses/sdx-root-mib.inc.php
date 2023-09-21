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

// IF-MIB::ifDescr.15 = STRING: LA/1
// IF-MIB::ifDescr.16 = STRING: LA/2

// SDX-ROOT-MIB::systemSvmIPAddressType.0 = INTEGER: ipv4(1)
// SDX-ROOT-MIB::systemSvmIPAddress.0 = STRING: "10.48.4.184"
// SDX-ROOT-MIB::systemXenIPAddressType.0 = INTEGER: ipv4(1)
// SDX-ROOT-MIB::systemXenIPAddress.0 = STRING: "10.48.4.186"
// SDX-ROOT-MIB::systemNetmaskType.0 = INTEGER: ipv4(1)
// SDX-ROOT-MIB::systemNetmask.0 = STRING: "255.255.255.0"
// SDX-ROOT-MIB::systemGatewayType.0 = INTEGER: ipv4(1)
// SDX-ROOT-MIB::systemGateway.0 = STRING: "10.48.4.1"
// SDX-ROOT-MIB::systemNetworkInterface.0 = STRING: "LA Management"

if ($data = snmp_get_multi_oid($device, 'systemSvmIPAddress.0 systemXenIPAddress.0 systemNetmask.0 systemNetworkInterface.0', [], 'SDX-ROOT-MIB')) {
    $entry   = $data[0];
    $ifIndex = 0;
    if ($port_id = get_port_id_by_ifDescr($device['device_id'], $entry['systemNetworkInterface'])) {
        $port    = get_port_by_id_cache($port_id);
        $ifIndex = $port['ifIndex'];
    } else {
        [$if] = explode(' ', $entry['systemNetworkInterface']);
        if ($port_id = get_port_id_by_ifDescr($device['device_id'], "$if/1")) {
            $port    = get_port_by_id_cache($port_id);
            $ifIndex = $port['ifIndex'];
        }
        $data = [
          'ifIndex' => $ifIndex,
          'ip'      => $entry['systemXenIPAddress'],
          'mask'    => $entry['systemNetmask'],
        ];
        discover_add_ip_address($device, $mib, $data);

        if ($port_id = get_port_id_by_ifDescr($device['device_id'], "$if/2")) {
            $port    = get_port_by_id_cache($port_id);
            $ifIndex = $port['ifIndex'];
        }
        $data = [
          'ifIndex' => $ifIndex,
          'ip'      => $entry['systemSvmIPAddress'],
          'mask'    => $entry['systemNetmask'],
        ];
        discover_add_ip_address($device, $mib, $data);

        return;
    }

    $data = [
      'ifIndex' => $ifIndex,
      'ip'      => $entry['systemXenIPAddress'],
      'mask'    => $entry['systemNetmask'],
    ];
    discover_add_ip_address($device, $mib, $data);

    $data = [
      'ifIndex' => $ifIndex,
      'ip'      => $entry['systemSvmIPAddress'],
      'mask'    => $entry['systemNetmask'],
    ];
    discover_add_ip_address($device, $mib, $data);
}

// EOF
