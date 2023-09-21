<?php
/*
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// NETAPP-MIB::nodeUuid.'WSLNetapp02-01' = STRING: d924dfcf-418e-11eb-91eb-d039ea255860
// NETAPP-MIB::nodeUuid.'WSLNetapp02-02' = STRING: 83eef39c-418e-11eb-b2bc-d039ea2575e5
$nodes = [];
foreach (snmpwalk_cache_oid($device, 'nodeUuid', [], $mib) as $node => $entry) {
    $nodes[$entry['nodeUuid']] = $node;
}
print_vars($nodes);

//$flags = OBS_SNMP_ALL;
$oid_data = [];
foreach (['logicalInterfaceNumericId', 'logicalInterfaceCurrNode', 'logicalInterfaceCurrPort',
          'logicalInterfaceAddress', 'logicalInterfaceNetmaskLength', 'logicalInterfaceRole'] as $oid) {
    $oid_data = snmpwalk_cache_oid($device, $oid, $oid_data, $mib);
    if ($oid === 'logicalInterfaceNumericId' && !snmp_status()) {
        break; // Stop walk, not exist table
    }
}

foreach ($oid_data as $index => $entry) {
    if (isset($nodes[$entry['logicalInterfaceCurrNode']])) {
        $ifDescr = $nodes[$entry['logicalInterfaceCurrNode']] . ':' . $entry['logicalInterfaceCurrPort'];
        if ($port_id = get_port_id_by_ifDescr($device, $ifDescr)) {
            $port    = get_port_by_id_cache($port_id);
            $ifIndex = $port['ifIndex'];
        } else {
            print_debug("Port $ifDescr not found.");
            $ifIndex = $entry['logicalInterfaceNumericId'];
            //continue;
        }
    }

    $data = [
      'ifIndex' => $ifIndex,
      'ip'      => $entry['logicalInterfaceAddress'],
      //'mask'    => $entry['logicalInterfaceNetmask'],
      'prefix'  => $entry['logicalInterfaceNetmaskLength'],
      'origin'  => $entry['logicalInterfaceRole'],
    ];
    discover_add_ip_address($device, $mib, $data);
}

// EOF
