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

$vlan_rows = [];

// Base port ifIndex association
$dot1d_baseports = snmpwalk_cache_oid($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB');
$use_baseports   = safe_count($dot1d_baseports) > 0;

$use_aruba = FALSE;
if (is_device_mib($device, 'ARUBAWIRED-PORTVLAN-MIB')) {
    echo("arubaWiredPortVlanMemberMode ");
    $ports_mode = snmpwalk_cache_oid($device, 'arubaWiredPortVlanMemberMode', [], 'ARUBAWIRED-PORTVLAN-MIB');
    $use_aruba  = safe_count($ports_mode) > 0;
}

// Faster (and easy) way for get untagged/primary vlans
$dot1q_ports = snmpwalk_cache_twopart_oid($device, 'ieee8021QBridgePvid', [], 'IEEE8021-Q-BRIDGE-MIB');

if (snmp_status() && $use_baseports) {
    if (!$use_aruba) {
        echo("ieee8021QBridgePortVlanTable ");
        $dot1q_ports = snmpwalk_cache_twopart_oid($device, 'ieee8021QBridgePortAcceptableFrameTypes', $dot1q_ports, 'IEEE8021-Q-BRIDGE-MIB');
        $dot1q_ports = snmpwalk_cache_twopart_oid($device, 'ieee8021QBridgePortIngressFiltering', $dot1q_ports, 'IEEE8021-Q-BRIDGE-MIB');

        // Collect trunk port ids and vlans
        //$trunk_ports = dbFetchColumn('SELECT DISTINCT `port_id` FROM `ports_vlans` WHERE `device_id` = ?', [ $device['device_id'] ]);
        $trunk_ports = [];
        foreach (dbFetchRows('SELECT `port_id`, `vlan` FROM `ports_vlans` WHERE `device_id` = ?', [$device['device_id']]) as $entry) {
            $trunk_ports[$entry['port_id']][] = $entry['vlan'];
        }
        print_debug_vars($trunk_ports);
    }
    print_debug_vars($dot1q_ports);

    foreach ($dot1q_ports as $domain_index => $vlans) {
        foreach ($vlans as $index => $entry) {
            $vlan_num = $entry['ieee8021QBridgePvid'];
            $ifIndex  = $dot1d_baseports[$index]['dot1dBasePortIfIndex'];
            if ($use_aruba && isset($ports_mode[$ifIndex])) {
                $trunk = $ports_mode[$ifIndex]['arubaWiredPortVlanMemberMode'] === 'trunk' ? 'dot1Q' : '';
            } elseif (isset($entry['ieee8021QBridgePortAcceptableFrameTypes']) && $entry['ieee8021QBridgePortAcceptableFrameTypes'] === 'admitTagged') {
                $trunk = 'dot1Q';
            } elseif ((isset($entry['ieee8021QBridgePortIngressFiltering']) && $entry['ieee8021QBridgePortIngressFiltering'] === 'true')) {
                // Additionally, check if port have trunk ports
                $port = get_port_by_index_cache($device, $ifIndex);
                print_debug("CHECK. ifIndex: $ifIndex, port_id: " . $port['port_id']);
                if (isset($trunk_ports[$port['port_id']]) && (safe_count($trunk_ports[$port['port_id']]) > 1 || $trunk_ports[$port['port_id']][0] != $vlan_num)) {
                    $trunk = 'dot1Q';
                } else {
                    $trunk = ''; // access
                }
            } else {
                $trunk = ''; // access
            }
            $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

            // Set Vlan and Trunk
            if (isset($port_stats[$ifIndex])) {
                $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
                $port_stats[$ifIndex]['ifTrunk'] = $trunk;
            }
        }
    }

} else {

    // Common ieee8021QBridgeVlanStaticUntaggedPorts
    $oid_name    = 'ieee8021QBridgeVlanStaticUntaggedPorts';
    $dot1q_ports = snmpwalk_cache_twopart_oid($device, 'ieee8021QBridgeVlanStaticUntaggedPorts', $dot1q_ports, 'IEEE8021-Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);

    // This is very slow (on some devices and with many ports), very hard to detect correct ports
    echo("$oid_name ");

    // Detect min ifIndex for vlan base ports
    // Why, see here: http://jira.observium.org/browse/OBS-963
    /*
    if ($use_baseports)
    {
      $vlan_ifindex_min = $dot1d_baseports[key($dot1d_baseports)]['dot1dBasePortIfIndex']; // First element
      foreach ($dot1d_baseports as $entry)
      {
        // But min ifIndex can be in any entry
        $vlan_ifindex_min = min($vlan_ifindex_min, $entry['dot1dBasePortIfIndex']);
      }

    } else {
      $vlan_ifindex_min = 0;
    }
    */

    foreach ($dot1q_ports as $domain_index => $vlans) {
        foreach ($vlans as $index => $entry) {
            $index_array = explode('.', $index);
            $vlan_num    = end($index_array); // need explode for dot1qVlanCurrentUntaggedPorts.0

            // Convert hex to binary map
            $binary = hex2binmap($entry[$oid_name]);

            $trunk = ''; // unknown

            // Assign binary vlans map to ports
            $length = strlen($binary);
            for ($i = 0; $i < $length; $i++) {
                if ($binary[$i]) {
                    //$ifIndex = $i + $vlan_ifindex_min; // This is incorrect ifIndex association!
                    if ($use_baseports) {
                        $ifIndex = $dot1d_baseports[$i + 1]['dot1dBasePortIfIndex'];
                    } else {
                        $ifIndex = $i;
                    }

                    if ($use_aruba && isset($ports_mode[$ifIndex])) {
                        $trunk = $ports_mode[$ifIndex]['arubaWiredPortVlanMemberMode'] === 'trunk' ? 'dot1Q' : '';
                    }

                    $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

                    // Set Vlan and Trunk
                    if (isset($port_stats[$ifIndex])) {
                        if (isset($port_stats[$ifIndex]['ifVlan'])) {
                            print_debug("WARNING. Oid dot1qVlanStaticUntaggedPorts pass incorrect vlan data.");
                        }
                        $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
                        $port_stats[$ifIndex]['ifTrunk'] = $trunk;
                    }
                }
            }
        }
    }
}

$headers = ['%WifIndex%n', '%WVlan%n', '%WTrunk%n'];
print_cli_table($vlan_rows, $headers);

if (safe_count($vlan_rows)) {
    // Disable poll by other MIBs (ie Q-BRIDGE-MIB)
    $ports_modules[$port_module] = FALSE;
}
//$process_port_functions[$port_module] = $GLOBALS['snmp_status'];

// Additional db fields for update
//$process_port_db[$port_module][] = 'ifVlan';
//$process_port_db[$port_module][] = 'ifTrunk';

// EOF
