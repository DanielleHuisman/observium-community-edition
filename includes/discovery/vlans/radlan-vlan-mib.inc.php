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

// This can use for any RADLAN mib, just tested with cisco sb
// CISCOSB-vlan-MIB
// Dell-vlan-MIB
// DLINK-3100-vlan-MIB
// EDGECORE-vlan-MIB
// NETGEAR-RADLAN-vlan-MIB
// RADLAN-vlan-MIB

$vlan_list = snmpwalk_cache_oid($device, 'rldot1qVlanStaticName', [], $mib);
print_debug_vars($vlan_list);

if (!snmp_status()) {
    return;
}

$domain_index = '1';

// Vlan 1 always exist on device, but not always discovered in snmp :/
$discovery_vlans[$domain_index]['1'] = [
    //'ifIndex'     => $entry['ifIndex'],
    'vlan_domain' => $domain_index,
    'vlan_vlan'   => '1',
    'vlan_name'   => 'Vlan 1',
    //'vlan_mtu'    => '',
    'vlan_type'   => 'ethernet',
    'vlan_status' => 'operational'
];

$sql = 'SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` >= 100000 AND `ifIndex` < 200000 AND `ifType` = ? AND `deleted` = ?';
foreach (dbFetchRows($sql, [$device['device_id'], 'propVirtual', '0']) as $entry) {
    if (is_intnum($entry['ifDescr'])) {
        $vlan_num = $entry['ifDescr'];
    } elseif (str_istarts($entry['ifName'], 'Vlan')) {
        $vlan_num = str_ireplace(['Vlan', ' '], '', $entry['ifName']);
    } else {
        print_debug("Vlan port not found:");
        print_debug_vars($entry);
        continue;
    }
    if (isset($vlan_list[$vlan_num])) {
        $vlan_list[$vlan_num]['ifIndex']      = $entry['ifIndex'];
        $vlan_list[$vlan_num]['ifOperStatus'] = $entry['ifOperStatus'];
    } elseif ($vlan_num == 1) {
        $discovery_vlans[$domain_index]['1']['ifIndex'] = $entry['ifIndex'];
    }
}
$vlan_max = 1;
foreach ($vlan_list as $vlan_num => $entry) {
    if ($vlan_num > $vlan_max) {
        // Store max vlan number, for fetch only relevant vlan data
        $vlan_max = (int)$vlan_num;
    }

    $vlan_array                                = [
      'ifIndex'     => $entry['ifIndex'] ?: ['NULL'],
      'vlan_domain' => $domain_index,
      'vlan_vlan'   => $vlan_num,
      'vlan_name'   => $entry['rldot1qVlanStaticName'] ?: "Vlan $vlan_num",
      //'vlan_mtu'    => '',
      'vlan_type'   => 'ethernet',
      'vlan_status' => 'operational'
    ];
    $discovery_vlans[$domain_index][$vlan_num] = $vlan_array;
}
// keep max vlan for polling
set_entity_attrib('device', $device, 'radlan_vlan_max', $vlan_max);
/* End vlans discovery */

$vlan_ports = snmpwalk_cache_oid($device, 'vlanPortModeState', [], $mib);
if (!snmp_status()) {
    return;
}

$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticEgressList1to1024', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
$vlan_ports = snmpwalk_cache_oid($device, 'vlanTrunkModeList1to1024', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
//$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticUntaggedEgressList1to1024', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
//$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticForbiddenList1to1024',      $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
if ($vlan_max > 1024) {
    $vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticEgressList1025to2048', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    $vlan_ports = snmpwalk_cache_oid($device, 'vlanTrunkModeList1025to2048', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticUntaggedEgressList1025to2048', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticForbiddenList1025to2048',      $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
}
if ($vlan_max > 2048) {
    $vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticEgressList2049to3072', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    $vlan_ports = snmpwalk_cache_oid($device, 'vlanTrunkModeList2049to3072', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticUntaggedEgressList2049to3072', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticForbiddenList2049to3072',      $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
}
if ($vlan_max > 3072) {
    $vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticEgressList3073to4094', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    $vlan_ports = snmpwalk_cache_oid($device, 'vlanTrunkModeList3073to4094', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticUntaggedEgressList3073to4094', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticForbiddenList3073to4094',      $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
}
print_debug_vars($vlan_ports);

$vlan_access_oids = [
  1    => 'rldot1qPortVlanStaticEgressList1to1024',
  1025 => 'rldot1qPortVlanStaticEgressList1025to2048',
  2049 => 'rldot1qPortVlanStaticEgressList2049to3072',
  3073 => 'rldot1qPortVlanStaticEgressList3073to4094',
];
$vlan_trunk_oids  = [
  1    => 'vlanTrunkModeList1to1024',
  1025 => 'vlanTrunkModeList1025to2048',
  2049 => 'vlanTrunkModeList2049to3072',
  3073 => 'vlanTrunkModeList3073to4094',
];
foreach ($vlan_ports as $ifIndex => $entry) {
    switch ($entry['vlanPortModeState']) {
        case '1':
        case '11': // general is mostly same as 'trunk', only radlan type
        case '3':
        case '13':
            $trunk     = 'dot1Q';
            $vlan_oids = $vlan_trunk_oids;
            break;
        case '7':
        case '17':
            $trunk     = 'qinq';
            $vlan_oids = $vlan_trunk_oids; // FIXME. I not sure!
            break;
        default:
            $trunk     = 'Egress';
            $vlan_oids = $vlan_access_oids;
    }

    foreach ($vlan_oids as $vlan_start => $oid) {
        if (isset($entry[$oid]) && preg_match('/[1-9a-f]/i', $entry[$oid])) {
            // not default
            $binmap     = hex2binmap($entry[$oid]);
            $vlan_len   = strlen($binmap);
            $vlan_end   = $vlan_start + $vlan_len - 1;
            $vlan_count = substr_count($binmap, '1');
            for ($i = 0; $i < $vlan_len; $i++) {
                $vlan_num = $vlan_start + $i;
                if ($binmap[$i]) {
                    $discovery_ports_vlans[$ifIndex][$vlan_num] = [
                      'vlan' => $vlan_num,
                    ];

                    // decrease significant vlans and break if all found
                    $vlan_count--;
                    if ($vlan_count === 0) {
                        break;
                    }
                }
            }
            print_debug("ifIndex $ifIndex $trunk ($vlan_start-$vlan_end): " . $binmap);
        }
    }
}

// EOF
