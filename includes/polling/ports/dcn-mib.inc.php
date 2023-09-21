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
    return FALSE;  // False for do not collect stats
}

/*
DCN-MIB::portMode.1 = INTEGER: access(1)
DCN-MIB::portMode.2 = INTEGER: trunk(2)
DCN-MIB::portMode.3 = INTEGER: access(1)
DCN-MIB::pvid.1 = INTEGER: 1
DCN-MIB::pvid.2 = INTEGER: 1
DCN-MIB::pvid.3 = INTEGER: 1
*/

echo("portMode pvid ");

$entries = snmpwalk_cache_oid($device, 'portMode', [], 'DCN-MIB');
$entries = snmpwalk_cache_oid($device, 'pvid', $entries, 'DCN-MIB');
print_debug_vars($entries);

$vlan_rows = [];
foreach ($entries as $ifIndex => $vlan) {
    $vlan_num = $vlan['pvid'];

    $trunk = '';
    if ($vlan['portMode'] == 'trunk') {
        $trunk = 'dot1Q';
    } elseif ($vlan['portMode'] == 'hybrid') {
        $trunk = 'hybrid';
    }
    $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

    // Set Vlan and Trunk
    $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
    $port_stats[$ifIndex]['ifTrunk'] = $trunk;

}

$headers = ['%WifIndex%n', '%WVlan%n', '%WTrunk%n'];
print_cli_table($vlan_rows, $headers);

// EOF
