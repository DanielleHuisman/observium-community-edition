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

if (!$has_ifEntry) {
    return FALSE;
}

// Ports Duplex, Secure
echo("portOperDuplex ");
$entries = snmpwalk_cache_oid($device, 'portOperDuplex', [], 'Es2952-MIB');

if (!snmp_status()) {
    return;
}

//$entries = snmpwalk_cache_oid($device, 'portSecurity', $entries, 'Es2952-MIB');
foreach ($entries as $ifIndex => $entry) {
    // Set ifDuplex
    switch ($entry['portOperDuplex']) {
        case 'full':
            $port_stats[$ifIndex]['ifDuplex'] = 'fullDuplex';
            break;
        case 'half':
            $port_stats[$ifIndex]['ifDuplex'] = 'halfDuplex';
            break;
        default:
            $port_stats[$ifIndex]['ifDuplex'] = 'unknown';
    }
}

// Untagged/primary port vlans
$port_module = 'vlan';

if (!$ports_modules[$port_module]) {
    // Module disabled
    return;
}

/*
Es2952-MIB::portPvid.12 = INTEGER: 1
Es2952-MIB::portPvid.13 = INTEGER: 500
Es2952-MIB::isPortInTrunk.12 = INTEGER: false(2)
Es2952-MIB::isPortInTrunk.13 = INTEGER: false(2)
*/

echo("portPvid isPortInTrunk ");

$entries = snmpwalk_cache_oid($device, 'portPvid', [], 'Es2952-MIB');
$entries = snmpwalk_cache_oid($device, 'isPortInTrunk', $entries, 'Es2952-MIB');

print_debug_vars($ports_vlans_oids);

$vlan_rows = [];
foreach ($entries as $ifIndex => $vlan) {
    $vlan_num    = $vlan['portPvid'];
    $trunk       = $vlan['isPortInTrunk'] == 'true' ? 'dot1Q' : '';
    $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

    // Set Vlan and Trunk
    $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
    $port_stats[$ifIndex]['ifTrunk'] = $trunk;

}

$headers = ['%WifIndex%n', '%WVlan%n', '%WTrunk%n'];
print_cli_table($vlan_rows, $headers);

// EOF
