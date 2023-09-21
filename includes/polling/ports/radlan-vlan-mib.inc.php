<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
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

// Untagged/primary port vlan

$port_module = 'vlan';

if (!$ports_modules[$port_module]) {
    // Module disabled
    return FALSE; // False for do not collect stats
}

/* start polling vlans */
$vlan_ports = snmpwalk_cache_oid($device, 'vlanPortModeState', [], $mib);
if (!snmp_status()) {
    return;
}

// disable other vlan polling (by Q-BRIDGE-MIB)
$ports_modules[$port_module] = FALSE;

// get max vlan number
$vlan_max = get_entity_attrib('device', $device, 'radlan_vlan_max');
if (safe_empty($vlan_max)) {
    $vlan_max = 1;
    // vlans not discovered
    force_discovery($device, 'vlans');
}

$vlan_ports = snmpwalk_cache_oid($device, 'vlanAccessPortModeVlanId', $vlan_ports, $mib);
if (snmp_status()) {
    $vlan_ports = snmpwalk_cache_oid($device, 'vlanTrunkPortModeNativeVlanId', $vlan_ports, $mib);
    print_debug_vars($vlan_ports);

    foreach ($vlan_ports as $ifIndex => $entry) {
        // vlanPortModeState:
        // general(1), access(2), trunk(3), customer QinQ(7)
        // 11 ?
        switch ($entry['vlanPortModeState']) {
            case '1':
            case '11': // general is mostly same as 'trunk', only radlan type
            case '3':
            case '13':
                $trunk = 'dot1Q';
                $vlan_num = $entry['vlanTrunkPortModeNativeVlanId'];
                break;
            case '7':
            case '17':
                $trunk = 'qinq';
                $vlan_num = $entry['vlanTrunkPortModeNativeVlanId']; // FIXME. I not sure!
                break;
            default:
                $trunk = '';
                $vlan_num = $entry['vlanAccessPortModeVlanId'];
        }
        $port_stats[$ifIndex]['ifTrunk'] = $trunk;
        $port_stats[$ifIndex]['ifVlan']  = $vlan_num ?: 1;

        $vlan_rows[] = [ $ifIndex, $port_stats[$ifIndex]['ifVlan'], $trunk ];
    }
} else {
    // See: https://jira.observium.org/browse/OBS-4391,
    //      https://jira.observium.org/browse/OBS-4606
    //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticEgressList1to1024',         $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    $vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticUntaggedEgressList1to1024', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticForbiddenList1to1024',      $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    if ($vlan_max > 1024 && snmp_status()) {
        //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticEgressList1025to2048',         $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
        $vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticUntaggedEgressList1025to2048', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
        //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticForbiddenList1025to2048',      $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    }
    if ($vlan_max > 2048 && snmp_status()) {
        //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticEgressList2049to3072',         $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
        $vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticUntaggedEgressList2049to3072', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
        //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticForbiddenList2049to3072',      $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    }
    if ($vlan_max > 3072 && snmp_status()) {
        //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticEgressList3073to4094', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
        $vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticUntaggedEgressList3073to4094', $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
        //$vlan_ports = snmpwalk_cache_oid($device, 'rldot1qPortVlanStaticForbiddenList3073to4094',      $vlan_ports, $mib, NULL, OBS_SNMP_ALL_HEX);
    }
    print_debug_vars($vlan_ports);

    $vlan_oids = [
        1    => 'rldot1qPortVlanStaticUntaggedEgressList1to1024',
        1025 => 'rldot1qPortVlanStaticUntaggedEgressList1025to2048',
        2049 => 'rldot1qPortVlanStaticUntaggedEgressList2049to3072',
        3073 => 'rldot1qPortVlanStaticUntaggedEgressList3073to4094',
    ];
    foreach ($vlan_ports as $ifIndex => $entry) {
        // vlanPortModeState:
        // general(1), access(2), trunk(3), customer QinQ(7)
        // 11 ?
        switch ($entry['vlanPortModeState']) {
            case '1':
            case '11': // general is mostly same as 'trunk', only radlan type
            case '3':
            case '13':
                $trunk = 'dot1Q';
                break;
            case '7':
            case '17':
                $trunk = 'qinq';
                break;
            default:
                $trunk = '';
        }
        $port_stats[$ifIndex]['ifTrunk'] = $trunk;

        // Untagged vlans
        foreach ($vlan_oids as $vlan_start => $oid) {
            if (isset($entry[$oid]) && preg_match('/[1-9a-f]/i', $entry[$oid])) {
                // not default
                $binmap = hex2binmap($entry[$oid]);
                $vlan_len = strlen($binmap);
                $vlan_end = $vlan_start + $vlan_len - 1;
                $vlan_count = substr_count($binmap, '1');
                for ($i = 0; $i < $vlan_len; $i++) {
                    $vlan_num = $vlan_start + $i;
                    if ($binmap[$i]) {
                        // Set Vlan
                        $port_stats[$ifIndex]['ifVlan'] = $vlan_num;
                        //$port_stats[$ifIndex]['ifTrunk'] = $trunk;
                        $vlan_rows[] = [ $ifIndex, $vlan_num, $trunk ];

                        // decrease significant vlans and break if all found
                        $vlan_count--;
                        if ($vlan_count === 0) {
                            break;
                        }
                    }
                }
                print_debug("ifIndex $ifIndex Untagged ($vlan_start-$vlan_end): " . $binmap);
            }
        }

        // all unknown ports by default in Vlan 1
        if (!isset($port_stats[$ifIndex]['ifVlan'])) {
            $port_stats[$ifIndex]['ifVlan'] = '1';
            $vlan_rows[] = [ $ifIndex, $port_stats[$ifIndex]['ifVlan'], $trunk ];
        }
    }
}

/* end polling vlans */

$headers = [ '%WifIndex%n', '%WVlan%n', '%WTrunk%n' ];
print_cli_table($vlan_rows, $headers);

//$process_port_functions[$port_module] = $GLOBALS['snmp_status'];

// Additional db fields for update
//$process_port_db[$port_module][] = 'ifVlan';
//$process_port_db[$port_module][] = 'ifTrunk';

// EOF
