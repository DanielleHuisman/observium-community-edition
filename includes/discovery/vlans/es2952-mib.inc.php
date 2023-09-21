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
 *
 */

/*
Es2952-MIB::vlanTaggedPorts.1 = STRING: 4848484848484848484848484848
Es2952-MIB::vlanTaggedPorts.2 = STRING: 4848484848484848484848484848
Es2952-MIB::vlanTaggedPorts.500 = STRING: 4848484848514848484848484848
Es2952-MIB::vlanName.1 = ""
Es2952-MIB::vlanName.2 = ""
Es2952-MIB::vlanName.500 = ""
Es2952-MIB::vlanAdminStatus.1 = INTEGER: enable(1)
Es2952-MIB::vlanAdminStatus.2 = INTEGER: disable(2)
Es2952-MIB::vlanAdminStatus.500 = INTEGER: enable(1)
*/

$oids      = [];
$vlan_oids = [];

foreach (snmpwalk_cache_oid($device, 'vlanAdminStatus', [], 'Es2952-MIB') as $vlan_num => $vlan) {
    // Filter only enabled Vlans
    if ($vlan['vlanAdminStatus'] != 'enable') {
        continue;
    }

    $vlan_oids[$vlan_num] = $vlan;

    // Additional Oids for multiget:
    $oids[] = 'vlanName.' . $vlan_num;
    $oids[] = 'vlanTaggedPorts.' . $vlan_num;
}

$vlan_oids = snmp_get_multi_oid($device, $oids, $vlan_oids, 'Es2952-MIB', NULL, OBS_SNMP_ALL_HEX);
print_debug_vars($vlan_oids);

$vtp_domain_index = '1'; // Yep, always use domain index 1

foreach ($vlan_oids as $vlan_num => $vlan) {
    // Skip not exist vlans
    if ($vlan['vlanAdminStatus'] != 'enable') {
        continue;
    }

    $vlan_array = [//'ifIndex'     => $vlan[''], // ??
                   'vlan_domain' => $vtp_domain_index,
                   'vlan_vlan'   => $vlan_num,
                   'vlan_name'   => strlen($vlan['vlanName']) ? snmp_hexstring($vlan['vlanName']) : "Vlan $vlan_num",
                   //'vlan_mtu'    => $vlan[''],
                   'vlan_type'   => 'ethernet',
                   'vlan_status' => 'operational'];

    $discovery_vlans[$vtp_domain_index][$vlan_num] = $vlan_array;

}

// EOF
