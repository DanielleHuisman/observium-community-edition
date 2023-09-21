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

// Ports Duplex, Secure
$entries = snmpwalk_cache_oid($device, 'rcPortOperDuplex', [], 'RAPID-CITY');
//$entries = snmpwalk_cache_oid($device, 'rcPortHighSecureEnable', $entries, 'RAPID-CITY');
foreach ($entries as $ifIndex => $entry) {
    // Set ifDuplex
    $port_stats[$ifIndex]['ifDuplex'] = $entry['rcPortOperDuplex'];
}

/*
// Nortel/Avaya have "hidden" ifIndexes for Vlans, which required for discovery IP addresses

$ports_vlans = snmpwalk_cache_oid($device, 'rcVlanIfIndex',         array(), 'RAPID-CITY');
$ports_vlans = snmpwalk_cache_oid($device, 'rcVlanName',       $ports_vlans, 'RAPID-CITY');
$ports_vlans = snmpwalk_cache_oid($device, 'rcVlanMacAddress', $ports_vlans, 'RAPID-CITY');
foreach ($ports_vlans as $vlan_num => $entry)
{
  $ifIndex = $entry['rcVlanIfIndex'];
  if (!isset($port_stats[$ifIndex]))
  {
    // Add hidden/ignored Vlan port
    $port_stats[$ifIndex] = array('ifIndex' => $ifIndex,
                                  'ifDescr' => $entry['rcVlanName'],
                                  'ifType'  => 'l2vlan',
                                  'ifAlias' => '',
                                  'ifPhysAddress' => $entry['rcVlanMacAddress'],
                                  'ifOperStatus'  => 'unknown', // Hardcode Oper status
                                  'ifAdminStatus' => 'up',      // Hardcode Admin status
                                  'ignore'        => '1',       // Set this ports ignored and disabled
                                  'disabled'      => '1');
  }
}
*/

// Untagged/primary port vlans
$port_module = 'vlan';

if (!$ports_modules[$port_module]) {
    // Module disabled
    return;
}

/*
RAPID-CITY::rcVlanPortType.513 = INTEGER: access(1)
RAPID-CITY::rcVlanPortType.514 = INTEGER: access(1)
RAPID-CITY::rcVlanPortDefaultVlanId.513 = INTEGER: 8
RAPID-CITY::rcVlanPortDefaultVlanId.514 = INTEGER: 6
*/

// Base vlan IDs
$ports_vlans_oids = snmpwalk_cache_oid($device, 'rcVlanPortDefaultVlanId', [], 'RAPID-CITY');

if (snmp_status()) {
    echo("rcVlanPortDefaultVlanId ");

    $ports_vlans_oids = snmpwalk_cache_oid($device, 'rcVlanPortType', $ports_vlans_oids, 'RAPID-CITY');

    print_debug_vars($ports_vlans_oids);

    $vlan_rows = [];
    foreach ($ports_vlans_oids as $ifIndex => $vlan) {
        $vlan_num    = $vlan['rcVlanPortDefaultVlanId'];
        $trunk       = $vlan['rcVlanPortType'] != 'access' ? 'dot1Q' : $vlan['rcVlanPortType'];
        $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

        // Set Vlan and Trunk
        $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
        $port_stats[$ifIndex]['ifTrunk'] = $trunk;

    }

}

$headers = ['%WifIndex%n', '%WVlan%n', '%WTrunk%n'];
print_cli_table($vlan_rows, $headers);

//$process_port_functions[$port_module] = $GLOBALS['snmp_status'];

// Additional db fields for update
//$process_port_db[$port_module][] = 'ifVlan';
//$process_port_db[$port_module][] = 'ifTrunk';

// EOF
