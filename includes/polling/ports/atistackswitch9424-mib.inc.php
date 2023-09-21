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

// Untagged/primary port vlans
$port_module = 'vlan';

if (!$ports_modules[$port_module]) {
    // Module disabled
    return FALSE; // False for do not collect stats
}

$start = microtime(TRUE); // Module timing start

/*
 AtiStackSwitch9424-MIB::atiStkSwVlanTaggedPortListModule1.10 = STRING:
 AtiStackSwitch9424-MIB::atiStkSwVlanUntaggedPortListModule1.10 = STRING: 1-2,4
*/

echo("atiStkSwVlanTaggedPortListModule1 atiStkSwVlanUntaggedPortListModule1 ");

$entries = snmpwalk_cache_oid($device, 'atiStkSwVlanTaggedPortListModule1', [], $mib);
$entries = snmpwalk_cache_oid($device, 'atiStkSwVlanUntaggedPortListModule1', $entries, $mib);
print_debug_vars($entries);

$ports_vlans = [];
foreach ($entries as $vlan_num => $vlan) {
    foreach (list_to_range($vlan['atiStkSwVlanTaggedPortListModule1']) as $ifIndex) {
        $ports_vlans[$ifIndex]['tagged'][] = $vlan_num;
    }
    foreach (list_to_range($vlan['atiStkSwVlanUntaggedPortListModule1']) as $ifIndex) {
        $ports_vlans[$ifIndex]['untagged'][] = $vlan_num;
    }
}

$vlan_rows = [];
foreach ($ports_vlans as $ifIndex => $vlan) {
    $trunk = '';
    if (isset($vlan['tagged'])) {
        $trunk = 'dot1Q';
    }

    foreach ($vlan['untagged'] as $vlan_num) {
        $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];
    }

    // Set Vlan and Trunk
    $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
    $port_stats[$ifIndex]['ifTrunk'] = $trunk;

}

$headers = ['%WifIndex%n', '%WVlan%n', '%WTrunk%n'];
print_cli_table($vlan_rows, $headers);

// EOF
