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

// Untagged/primary port vlan

$port_module = 'vlan';

if (!$ports_modules[$port_module]) {
    return FALSE; // False for do not collect stats
}

//CISCO-VLAN-MEMBERSHIP-MIB::vmVlan.35 = INTEGER: 15
//CISCO-VLAN-MEMBERSHIP-MIB::vmVlan.37 = INTEGER: 15
//CISCO-VTP-MIB::vlanTrunkPortEncapsulationOperType.35 = INTEGER: notApplicable(6)
//CISCO-VTP-MIB::vlanTrunkPortEncapsulationOperType.36 = INTEGER: dot1Q(4)
//CISCO-VTP-MIB::vlanTrunkPortEncapsulationOperType.37 = INTEGER: notApplicable(6)
//CISCO-VTP-MIB::vlanTrunkPortNativeVlan.35 = INTEGER: 15
//CISCO-VTP-MIB::vlanTrunkPortNativeVlan.36 = INTEGER: 1
//CISCO-VTP-MIB::vlanTrunkPortNativeVlan.37 = INTEGER: 15

$vlan_oids = snmpwalk_cache_oid($device, "vmVlan", [], "CISCO-VLAN-MEMBERSHIP-MIB"); // Non trunk ports only

if (snmp_status()) {
    echo("vmVlan ");
    $vlan_oids = snmpwalk_cache_oid($device, "vlanTrunkPortEncapsulationOperType", $vlan_oids, "CISCO-VTP-MIB");
    $vlan_oids = snmpwalk_cache_oid($device, "vlanTrunkPortNativeVlan", $vlan_oids, "CISCO-VTP-MIB"); // Trunk ports only
    print_debug_vars($vlan_oids);

    $vlan_rows = [];
    foreach ($vlan_oids as $ifIndex => $entry) {

        $trunk = '';

        if (isset($entry['vlanTrunkPortEncapsulationOperType']) && $entry['vlanTrunkPortEncapsulationOperType'] != "notApplicable") {
            $trunk = $entry['vlanTrunkPortEncapsulationOperType'];
            if (isset($entry['vlanTrunkPortNativeVlan'])) {
                $vlan_num = $entry['vlanTrunkPortNativeVlan'];
            }
        }

        if (isset($entry['vmVlan'])) {
            $vlan_num = $entry['vmVlan'];
        }
        $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

        // Set Vlan and Trunk
        if (isset($port_stats[$ifIndex])) {
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
