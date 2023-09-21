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
    // Module disabled
    return FALSE; // False for do not collect stats
}

//HUAWEI-L2IF-MIB::hwL2IfPortIfIndex.39 = INTEGER: 92
//HUAWEI-L2IF-MIB::hwL2IfPortIfIndex.40 = INTEGER: 93
//HUAWEI-L2IF-MIB::hwL2IfPortIfIndex.46 = INTEGER: 99
//HUAWEI-L2IF-MIB::hwL2IfPVID.39 = INTEGER: 0
//HUAWEI-L2IF-MIB::hwL2IfPVID.40 = INTEGER: 1
//HUAWEI-L2IF-MIB::hwL2IfPVID.46 = INTEGER: 1
//HUAWEI-L2IF-MIB::hwL2IfActivePortType.39 = INTEGER: invalid(0)
//HUAWEI-L2IF-MIB::hwL2IfActivePortType.40 = INTEGER: trunk(1)
//HUAWEI-L2IF-MIB::hwL2IfActivePortType.46 = INTEGER: access(2)

// Base port ifIndex association
$vlan_oids = snmpwalk_cache_oid($device, 'hwL2IfPortIfIndex', [], 'HUAWEI-L2IF-MIB');

if (snmp_status()) {
    echo("dot1qPortVlanTable ");
    $vlan_oids = snmpwalk_cache_oid($device, 'hwL2IfPVID', $vlan_oids, 'HUAWEI-L2IF-MIB');
    //$vlan_oids = snmpwalk_cache_oid($device, 'hwL2IfActivePortType', $vlan_oids, 'HUAWEI-L2IF-MIB');
    //if (!snmp_status())
    //{
    $vlan_oids = snmpwalk_cache_oid($device, 'hwL2IfPortType', $vlan_oids, 'HUAWEI-L2IF-MIB');
    //}
    print_debug_vars($vlan_oids);

    $vlan_rows = [];
    foreach ($vlan_oids as $index => $entry) {
        $vlan_num = $entry['hwL2IfPVID'];
        $ifIndex  = $entry['hwL2IfPortIfIndex'];

        //$port_type = (isset($entry['hwL2IfActivePortType'])) ? $entry['hwL2IfActivePortType'] : $entry['hwL2IfPortType'];
        $port_type = $entry['hwL2IfPortType'];
        switch ($port_type) {
            case 'trunk':
                $trunk = 'dot1Q';
                break;
            case 'qinq':
                $trunk = 'QinQ';
                break;
            case 'hybrid':
            case 'fabric':
                $trunk = $port_type;
                break;
            case 'invalid':
                // Skip invalid Vlan 0
                continue 2;
            default:
                $trunk = '';
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
