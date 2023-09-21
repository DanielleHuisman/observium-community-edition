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

$vtp_domain_index = '1'; // Yep, always use domain index 1

//EXTREME-VLAN-MIB::extremeVlanIfDescr.1000004 = STRING: "Default"
//EXTREME-VLAN-MIB::extremeVlanIfDescr.1000005 = STRING: "Mgmt"
//EXTREME-VLAN-MIB::extremeVlanIfType.1000004 = INTEGER: vlanLayer2(1)
//EXTREME-VLAN-MIB::extremeVlanIfType.1000005 = INTEGER: vlanLayer2(1)
//EXTREME-VLAN-MIB::extremeVlanIfStatus.1000004 = INTEGER: active(1)
//EXTREME-VLAN-MIB::extremeVlanIfStatus.1000005 = INTEGER: active(1)
//EXTREME-VLAN-MIB::extremeVlanIfVlanId.1000004 = INTEGER: 1
//EXTREME-VLAN-MIB::extremeVlanIfVlanId.1000005 = INTEGER: 4095
//EXTREME-VLAN-MIB::extremeVlanIfEncapsType.1000031 = INTEGER: vlanEncaps8021q(1)
//EXTREME-VLAN-MIB::extremeVlanIfEncapsType.1000033 = INTEGER: vlanEncapsNone(2)
//EXTREME-VLAN-MIB::extremeVlanIfAdminStatus.1000008 = INTEGER: true(1)
//EXTREME-VLAN-MIB::extremeVlanIfAdminStatus.1000011 = INTEGER: false(2)

$vlan_oids = snmpwalk_cache_oid($device, 'extremeVlanIfDescr', [], 'EXTREME-VLAN-MIB');

if (!snmp_status()) {
    // New Extreme SLX devices have very limited VLANs information

    // EXTREME-VLAN-MIB::extremeStatsPortIfIndex.201334784."99" = INTEGER: 201334784
    // EXTREME-VLAN-MIB::extremeStatsVlanNameIndex.201334784."99" = STRING: 99
    $vlan_stats = snmpwalk_cache_twopart_oid($device, 'extremeStatsVlanNameIndex', [], 'EXTREME-VLAN-MIB');
    if (!snmp_status()) {
        return;
    }

    foreach ($vlan_stats as $ifIndex => $tmp) {
        foreach ($tmp as $vlan_num => $vlan) {
            // I'm not sure if this string is always vlan number string
            if (!is_numeric($vlan_num)) {
                continue;
            }

            // Now find port from ifDescr
            // IF-MIB::ifDescr.201334784 = STRING: Ethernet 0/1
            // IF-MIB::ifDescr.1207959651 = STRING: Ve 99
            $vlan_name  = 'Ve ' . $vlan_num;
            $vlan_index = dbFetchCell("SELECT `ifIndex` FROM `ports` WHERE `device_id` = ? AND `ifDescr` = ? AND `deleted` = ? LIMIT 1", [$device['device_id'], $vlan_name, 0]);

            $vlan_array = ['ifIndex'     => $vlan_index,
                           'vlan_domain' => $vtp_domain_index,
                           'vlan_vlan'   => $vlan_num,
                           'vlan_name'   => $vlan_name,
                           //'vlan_mtu'    => $vlan[''],
                           'vlan_type'   => 'ethernet',
                           'vlan_status' => 'operational'];
            // Device Vlans
            $discovery_vlans[$vtp_domain_index][$vlan_num] = $vlan_array;

            // Port Vlans
            $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
        }
    }

    // End new SLX devices
    return;
}

$vlan_oids = snmpwalk_cache_oid($device, 'extremeVlanIfVlanId', $vlan_oids, 'EXTREME-VLAN-MIB');
$vlan_oids = snmpwalk_cache_oid($device, 'extremeVlanIfStatus', $vlan_oids, 'EXTREME-VLAN-MIB');
$vlan_oids = snmpwalk_cache_oid($device, 'extremeVlanIfType', $vlan_oids, 'EXTREME-VLAN-MIB');
$vlan_oids = snmpwalk_cache_oid($device, 'extremeVlanIfAdminStatus', $vlan_oids, 'EXTREME-VLAN-MIB');
print_debug_vars($vlan_oids);

foreach ($vlan_oids as $index => $vlan) {
    // Skip not exist vlans
    if (in_array($vlan['extremeVlanIfStatus'], ['notInService', 'notReady', 'destroy'])) {
        continue;
    }

    $vlan_num                                      = $vlan['extremeVlanIfVlanId'];
    $vlan_array                                    = ['ifIndex'     => $index,
                                                      'vlan_domain' => $vtp_domain_index,
                                                      'vlan_vlan'   => $vlan_num,
                                                      'vlan_name'   => $vlan['extremeVlanIfDescr'],
                                                      //'vlan_mtu'    => $vlan[''],
                                                      'vlan_type'   => $vlan['extremeVlanIfType'],
                                                      'vlan_status' => $vlan['extremeVlanIfAdminStatus']];
    $discovery_vlans[$vtp_domain_index][$vlan_num] = $vlan_array;

}

//EXTREME-VLAN-MIB::extremeVlanOpaqueTaggedPorts.1000056.1 = Hex-STRING: 00 00 00 00 00 00 0C 00 00 00 00 00 00 00 00 00
//00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
//EXTREME-VLAN-MIB::extremeVlanOpaqueTaggedPorts.1000056.2 = Hex-STRING: 00 00 00 00 00 00 08 00 00 00 00 00 00 00 00 00
//00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
//EXTREME-VLAN-MIB::extremeVlanOpaqueTaggedPorts.1000056.3 = Hex-STRING: 00 00 40 00 00 00 00 00 00 00 00 00 00 00 00 00
//00 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00
$ports_vlans_oids = snmpwalk_cache_twopart_oid($device, 'extremeVlanOpaqueTaggedPorts', [], 'EXTREME-VLAN-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
print_debug_vars($ports_vlans_oids);

foreach ($ports_vlans_oids as $index => $entry) {

    $vlan_num = $vlan_oids[$index]['extremeVlanIfVlanId'];
    foreach ($entry as $slot => $vlan) {
        $binary = hex2binmap($vlan['extremeVlanOpaqueTaggedPorts']);

        // Assign binary vlans map to ports
        $length = strlen($binary);
        for ($i = 0; $i < $length; $i++) {
            if ($binary[$i] && $i > 0) {
                $port_map = $slot . ':' . ($i + 1);
                $ifIndex  = dbFetchCell("SELECT `ifIndex` FROM `ports` WHERE `device_id` = ? AND (`ifDescr` LIKE ? OR `ifName` = ?) AND `deleted` = ? LIMIT 1", [$device['device_id'], '% ' . $port_map, $port_map, 0]);

                if (isset($discovery_vlans[$vtp_domain_index][$vlan_num])) {
                    $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
                }
            }
        }
    }
}

// EOF
