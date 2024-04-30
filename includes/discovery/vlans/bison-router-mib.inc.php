<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

$vlan_oids = snmpwalk_cache_oid($device, 'vifName', [], 'BISON-ROUTER-MIB');

if (!snmp_status()) {
    return;
}

$vlan_oids = snmpwalk_cache_oid($device, 'vifCvid', $vlan_oids, 'BISON-ROUTER-MIB');
print_debug_vars($vlan_oids);

$vtp_domain_index = '1'; // Yep, always use domain index 1

foreach ($vlan_oids as $vifIndex => $vlan) {
    $vlan_num    = $vlan['vifCvid'];

    $vlan_array = [
        'ifIndex'     => $vifIndex,
        'vlan_domain' => $vtp_domain_index,
        'vlan_vlan'   => $vlan['vifCvid'],
        'vlan_name'   => $vlan['vifName'],
        //'vlan_mtu'    => $vlan[''],
        'vlan_type'   => 'ethernet',
        'vlan_status' => 'operational'
    ];
    $discovery_vlans[$vtp_domain_index][$vlan_num] = $vlan_array;
}

// EOF
