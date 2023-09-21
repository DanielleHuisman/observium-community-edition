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

// Untagged/routed port vlan

$port_module = 'vlan';

if (!$ports_modules[$port_module]) {
    return FALSE; // False for do not collect stats
}

//CISCO-VLAN-IFTABLE-RELATIONSHIP-MIB::cviRoutedVlanIfIndex.1.8 = INTEGER: 8
//CISCO-VLAN-IFTABLE-RELATIONSHIP-MIB::cviRoutedVlanIfIndex.200.8 = INTEGER: 11
//CISCO-VLAN-IFTABLE-RELATIONSHIP-MIB::cviRoutedVlanIfIndex.201.8 = INTEGER: 13
//CISCO-VLAN-IFTABLE-RELATIONSHIP-MIB::cviRoutedVlanIfIndex.202.8 = INTEGER: 14
//CISCO-VLAN-IFTABLE-RELATIONSHIP-MIB::cviRoutedVlanIfIndex.210.8 = INTEGER: 16
//CISCO-VLAN-IFTABLE-RELATIONSHIP-MIB::cviRoutedVlanIfIndex.222.8 = INTEGER: 24

$vlan_oids = snmpwalk_cache_oid($device, "cviRoutedVlanIfIndex", [], "CISCO-VLAN-IFTABLE-RELATIONSHIP-MIB"); // Routed ports only

if (snmp_status()) {
    echo("cviRoutedVlanIfIndex ");
    print_debug_vars($vlan_oids);

    $vlan_rows = [];
    foreach ($vlan_oids as $index => $entry) {

        [$vlan_num, $phyifIndex] = explode('.', $index);
        $ifIndex = $entry['cviRoutedVlanIfIndex'];
        $trunk   = 'routed';

        $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

        // Set Vlan and Trunk
        if (isset($port_stats[$ifIndex]) && !is_numeric($port_stats[$ifIndex]['ifVlan'])) {
            $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
            $port_stats[$ifIndex]['ifTrunk'] = $trunk;
        }
    }

}

$headers = ['%WifIndex%n', '%WVlan%n', '%WTrunk%n'];
print_cli_table($vlan_rows, $headers);

//$process_port_functions[$port_module] = $GLOBALS['snmp_status'];

// Additional db fields for update
//$process_port_db[$port_module][] = 'ifVlan';
//$process_port_db[$port_module][] = 'ifTrunk';

// EOF
