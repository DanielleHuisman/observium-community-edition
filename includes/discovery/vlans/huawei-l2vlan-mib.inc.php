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


$vlan_oids = snmpwalk_cache_oid($device, 'hwL2VlanDescr', [], 'HUAWEI-L2VLAN-MIB');

if (!snmp_status()) {
    return;
}

$vlan_oids = snmpwalk_cache_oid($device, 'hwL2VlanIfIndex', $vlan_oids, 'HUAWEI-L2VLAN-MIB');
$vlan_oids = snmpwalk_cache_oid($device, 'hwL2VlanRowStatus', $vlan_oids, 'HUAWEI-L2VLAN-MIB');
$vlan_oids = snmpwalk_cache_oid($device, 'hwL2VlanType', $vlan_oids, 'HUAWEI-L2VLAN-MIB');
$vlan_oids = snmpwalk_cache_oid($device, 'hwL2VlanCreateStatus', $vlan_oids, 'HUAWEI-L2VLAN-MIB');
//$vlan_oids = snmpwalk_cache_oid($device, 'hwL2VlanPortList',     $vlan_oids, 'HUAWEI-L2VLAN-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
print_debug_vars($vlan_oids);

$vtp_domain_index = '1'; // Yep, always use domain index 1

foreach ($vlan_oids as $vlan_num => $vlan) {
    // Skip not exist vlans
    if (in_array($vlan['hwL2VlanRowStatus'], ['notInService', 'notReady', 'destroy'])) {
        continue;
    }

    $vlan_array                                    = ['ifIndex'     => $vlan['hwL2VlanIfIndex'],
                                                      'vlan_domain' => $vtp_domain_index,
                                                      'vlan_vlan'   => $vlan_num,
                                                      'vlan_name'   => $vlan['hwL2VlanDescr'],
                                                      //'vlan_mtu'    => $vlan[''],
                                                      'vlan_type'   => $vlan['hwL2VlanType'],
                                                      'vlan_status' => $vlan['hwL2VlanCreateStatus']];
    $discovery_vlans[$vtp_domain_index][$vlan_num] = $vlan_array;

}

if (!is_device_mib($device, 'HUAWEI-L2IF-MIB')) {
    // Skip ports vlans if this mib disabled
    return;
}

$vlan_max = max(array_keys($discovery_vlans[$vtp_domain_index])); // Detect maximum vlan number on device

// port index map
$ports_vlans_indexes = snmpwalk_cache_oid($device, 'hwL2IfPortIfIndex', [], 'HUAWEI-L2IF-MIB');
// Normal Trunk ports
$ports_vlans_oids = snmpwalk_cache_oid($device, 'hwL2IfTrunkAllowPassVlanListLow', [], 'HUAWEI-L2IF-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
if ($vlan_max > 2047) {
    $ports_vlans_oids = snmpwalk_cache_oid($device, 'hwL2IfTrunkAllowPassVlanListHigh', $ports_vlans_oids, 'HUAWEI-L2IF-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
}
// Hybrid tagged ports
$ports_vlans_oids = snmpwalk_cache_oid($device, 'hwL2IfHybridTaggedVlanListLow', $ports_vlans_oids, 'HUAWEI-L2IF-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
if ($vlan_max > 2047) {
    $ports_vlans_oids = snmpwalk_cache_oid($device, 'hwL2IfHybridTaggedVlanListHigh', $ports_vlans_oids, 'HUAWEI-L2IF-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
}

print_debug_vars($ports_vlans_indexes);
print_debug_vars($ports_vlans_oids);

foreach ($ports_vlans_oids as $index => $vlan) {
    if (!isset($ports_vlans_indexes[$index])) {
        continue;
    } // Skip unknown
    $ifIndex = $ports_vlans_indexes[$index]['hwL2IfPortIfIndex'];

    foreach (['TrunkAllowPass', 'HybridTagged'] as $oid) {
        $oid_name = 'hwL2If' . $oid . 'VlanListLow';
        // 0-2047 VLAN list. Value 0 is reserved
        $binary = hex2binmap($vlan[$oid_name]);

        // Assign binary vlans map to ports
        $length = strlen($binary);
        for ($i = 0; $i < $length; $i++) {
            if ($binary[$i] && $i > 0) {
                $vlan_num = $i;

                if (isset($discovery_vlans[$vtp_domain_index][$vlan_num])) {
                    $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
                }
            }
        }

        $oid_name = 'hwL2If' . $oid . 'VlanListHigh';
        // 2048-4095 VLAN list. Value 4095 is reserved
        if (isset($vlan[$oid_name])) {
            $binary = hex2binmap($vlan[$oid_name]);

            // Assign binary vlans map to ports
            $length = strlen($binary);
            for ($i = 0; $i < $length; $i++) {
                if ($binary[$i] && $i < 2047) {
                    $vlan_num = 2048 + $i;

                    if (isset($discovery_vlans[$vtp_domain_index][$vlan_num])) {
                        $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
                    }
                }
            }
        }

    }
}


// EOF
