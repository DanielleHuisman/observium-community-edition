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

// AtiStackSwitch9424-MIB::atiStkSwVlanName.1 = STRING: Default_VLAN
// AtiStackSwitch9424-MIB::atiStkSwVlanName.10 = STRING: white
// AtiStackSwitch9424-MIB::atiStkSwVlanTaggedPortListModule1.1 = STRING:
// AtiStackSwitch9424-MIB::atiStkSwVlanTaggedPortListModule1.10 = STRING:
// AtiStackSwitch9424-MIB::atiStkSwVlanUntaggedPortListModule1.1 = STRING:
// AtiStackSwitch9424-MIB::atiStkSwVlanUntaggedPortListModule1.10 = STRING: 1-2,4
// AtiStackSwitch9424-MIB::atiStkSwVlanConfigEntryStatus.1 = INTEGER: active(1)
// AtiStackSwitch9424-MIB::atiStkSwVlanConfigEntryStatus.10 = INTEGER: active(1)

// Note, same for AtiStackSwitch9000-MIB
$vlan_oids = snmpwalk_cache_oid($device, 'atiStkSwVlanName', [], $mib);
$vlan_oids = snmpwalk_cache_oid($device, 'atiStkSwVlanConfigEntryStatus', $vlan_oids, $mib);
$vlan_oids = snmpwalk_cache_oid($device, 'atiStkSwVlanTaggedPortListModule1', $vlan_oids, $mib);
//$vlan_oids = snmpwalk_cache_oid($device, 'atiStkSwVlanUntaggedPortListModule1', $vlan_oids, $mib);
print_debug_vars($vlan_oids);

$vtp_domain_index = '1'; // Yep, always use domain index 1
//$ports_vlans = [];
foreach ($vlan_oids as $vlan_num => $vlan) {
    // Skip not exist vlans
    if ($vlan['atiStkSwVlanConfigEntryStatus'] != 'active') {
        continue;
    }

    $vlan_array = [
        //'ifIndex'     => $vlan['vlanPortNumber'],
        'vlan_domain' => $vtp_domain_index,
        'vlan_vlan'   => $vlan_num,
        'vlan_name'   => strlen($vlan['atiStkSwVlanName']) ? $vlan['atiStkSwVlanName'] : "Vlan $vlan_num",
        //'vlan_mtu'    => $vlan[''],
        'vlan_type'   => 'ethernet',
        'vlan_status' => 'operational'
    ];

    $discovery_vlans[$vtp_domain_index][$vlan_num] = $vlan_array;

    foreach (list_to_range($vlan['atiStkSwVlanTaggedPortListModule1']) as $ifIndex) {
        //$ports_vlans[$ifIndex]['tagged'][] = $vlan_num;
        $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
    }
    // foreach (list_to_range($vlan['atiStkSwVlanUntaggedPortListModule1']) as $ifIndex)
    // {
    //   $ports_vlans[$ifIndex]['untagged'][] = $vlan_num;
    // }
}

//print_vars($ports_vlans);

// EOF
