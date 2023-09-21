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

if (!$ports_modules[$port_module] || is_device_mib($device, 'CISCO-VTP-MIB')) {
    // Module disabled, or Cisco device
    // Q-BRIDGE-MIB is default mib, need excludes
    return FALSE; // False for do not collect stats
}

// Vendor specific
$is_juniper = is_device_mib($device, 'JUNIPER-VLAN-MIB');
//$is_hpe = $device['os'] == 'hh3c';

//BRIDGE-MIB::dot1dBaseNumPorts.0 = INTEGER: 9 ports
//BRIDGE-MIB::dot1dBaseType.0 = INTEGER: transparent-only(2)
//BRIDGE-MIB::dot1dBasePortIfIndex.1 = INTEGER: 1
//BRIDGE-MIB::dot1dBasePortIfIndex.2 = INTEGER: 2
//BRIDGE-MIB::dot1dBasePortIfIndex.3 = INTEGER: 3
//Q-BRIDGE-MIB::dot1qVlanVersionNumber.0 = INTEGER: version1(1)
//Q-BRIDGE-MIB::dot1qMaxVlanId.0 = INTEGER: 4094
//Q-BRIDGE-MIB::dot1qMaxSupportedVlans.0 = Gauge32: 4094
//Q-BRIDGE-MIB::dot1qNumVlans.0 = Gauge32: 1
//Q-BRIDGE-MIB::dot1qVlanStaticName.1 = STRING: VLAN 0001
//Q-BRIDGE-MIB::dot1qVlanStaticEgressPorts.1 = Hex-STRING: 00 00 00 00 00 00 00 00
//Q-BRIDGE-MIB::dot1qVlanForbiddenEgressPorts.1 = Hex-STRING: 00 00 00 00 00 00 00 00
//Q-BRIDGE-MIB::dot1qVlanStaticUntaggedPorts.1 = Hex-STRING: 00 00 00 00 00 00 00 00
//Q-BRIDGE-MIB::dot1qVlanStaticRowStatus.1 = INTEGER: active(1)

// Base port ifIndex association
$dot1d_baseports = snmpwalk_cache_oid($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB');
$use_baseports   = count($dot1d_baseports) > 0;

// Faster (and easy) way for get untagged/primary vlans
//$dot1q_ports = snmpwalk_cache_oid($device, 'dot1qPortVlanTable', array(), 'Q-BRIDGE-MIB');
$dot1q_ports = snmpwalk_cache_oid($device, 'dot1qPvid', [], 'Q-BRIDGE-MIB');

if (snmp_status() && $use_baseports) {
    echo("dot1qPortVlanTable ");
    if ($is_juniper) // EX switches. Unsure if other Juniper platforms "affected"
    {
        //JUNIPER-VLAN-MIB::jnxExVlanPortAccessMode.22.549 = INTEGER: access(1)
        //JUNIPER-VLAN-MIB::jnxExVlanPortAccessMode.25.3 = INTEGER: trunk(2)
        //JUNIPER-VLAN-MIB::jnxExVlanPortAccessMode.25.513 = INTEGER: access(1)
        foreach (snmpwalk_cache_oid($device, 'jnxExVlanPortAccessMode', [], 'JUNIPER-VLAN-MIB') as $index => $entry) {
            [, $index] = explode('.', $index);
            $dot1q_ports[$index]['jnxExVlanPortAccessMode'] = $entry['jnxExVlanPortAccessMode'];
        }
    } else {
        $dot1q_ports = snmpwalk_cache_oid($device, 'dot1qPortAcceptableFrameTypes', $dot1q_ports, 'Q-BRIDGE-MIB');
        $dot1q_ports = snmpwalk_cache_oid($device, 'dot1qPortIngressFiltering', $dot1q_ports, 'Q-BRIDGE-MIB');
    }
    print_debug_vars($dot1q_ports);

    // Collect trunk port ids and vlans
    //$trunk_ports = dbFetchColumn('SELECT DISTINCT `port_id` FROM `ports_vlans` WHERE `device_id` = ?', [ $device['device_id'] ]);
    $trunk_ports = [];
    foreach (dbFetchRows('SELECT `port_id`, `vlan` FROM `ports_vlans` WHERE `device_id` = ?', [$device['device_id']]) as $entry) {
        $trunk_ports[$entry['port_id']][] = $entry['vlan'];
    }
    print_debug_vars($trunk_ports);

    $vlan_rows = [];
    foreach ($dot1q_ports as $index => $entry) {
        $vlan_num = $entry['dot1qPvid'];
        $ifIndex  = $dot1d_baseports[$index]['dot1dBasePortIfIndex'];
        if (isset($entry['jnxExVlanPortAccessMode']) && $entry['jnxExVlanPortAccessMode'] === 'trunk') {
            $trunk = 'dot1Q';
        } elseif (isset($entry['dot1qPortAcceptableFrameTypes']) && $entry['dot1qPortAcceptableFrameTypes'] === 'admitOnlyVlanTagged') {
            $trunk = 'dot1Q';
        } elseif ((isset($entry['dot1qPortIngressFiltering']) && $entry['dot1qPortIngressFiltering'] === 'true')) {
            // Additionally, check if port have trunk ports
            $port = get_port_by_index_cache($device, $ifIndex);
            print_debug("CHECK. ifIndex: $ifIndex, port_id: " . $port['port_id']);
            if (isset($trunk_ports[$port['port_id']]) && (count($trunk_ports[$port['port_id']]) > 1 || $trunk_ports[$port['port_id']][0] != $vlan_num)) {
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

} elseif ($is_juniper) {
    // For juniper Q-BRIDGE is derp, but still required for trunk ports
    // skipped here, use only dot1qPvid
} else {

    if ($device['os'] == 'zyxeles') {
        // On this devices dot1qVlanStaticUntaggedPorts store incorrect vlan data, ie;
        // Note, dot1qVlanStaticEgressPorts - fine!
        //Q-BRIDGE-MIB::dot1qVlanStaticUntaggedPorts.609 = Hex-STRING: FF FF FF FF FF FF C0 00 00 00 00 00 00 00 00 00
        //Q-BRIDGE-MIB::dot1qVlanStaticUntaggedPorts.862 = Hex-STRING: FF FF FF FF FF FF C0 00 00 00 00 00 00 00 00 00
        //Q-BRIDGE-MIB::dot1qVlanStaticUntaggedPorts.917 = Hex-STRING: FF FF FF FF FF FF F0 00 00 00 00 00 00 00 00 00

        // Use dot1qVlanCurrentUntaggedPorts instead
        //Q-BRIDGE-MIB::dot1qVlanCurrentUntaggedPorts.0.609 = Hex-STRING: 00 00 40 00 00 00 00 00 00 00 00 00 00 00 00 00
        //Q-BRIDGE-MIB::dot1qVlanCurrentUntaggedPorts.0.862 = Hex-STRING: 00 00 00 20 00 00 00 00 00 00 00 00 00 00 00 00
        //Q-BRIDGE-MIB::dot1qVlanCurrentUntaggedPorts.0.917 = Hex-STRING: 00 00 00 00 00 00 80 00 00 00 00 00 00 00 00 00

        $oid_name    = 'dot1qVlanCurrentUntaggedPorts';
        $dot1q_ports = snmpwalk_cache_oid($device, 'dot1qVlanCurrentUntaggedPorts.0', $dot1q_ports, 'Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
    } else {
        // Common dot1qVlanStaticUntaggedPorts
        $oid_name    = 'dot1qVlanStaticUntaggedPorts';
        $dot1q_ports = snmpwalk_cache_oid($device, 'dot1qVlanStaticUntaggedPorts', $dot1q_ports, 'Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_MULTILINE | OBS_SNMP_HEX);
    }

    if ($is_juniper) // EX switches. Unsure if other Juniper platforms "affected"
    {
        // Fetch Juniper VLAN table for correct tag
        $dot1q_ports = snmpwalk_cache_oid($device, 'jnxExVlanTag', $dot1q_ports, 'JUNIPER-VLAN-MIB');
    }

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

    foreach ($dot1q_ports as $index => $entry) {
        if (is_device_mib($device, 'JUNIPER-VLAN-MIB')) {
            $vlan_num = $entry['jnxExVlanTag'];
        } else {
            $index_array = explode('.', $index);
            $vlan_num    = end($index_array); // need explode for dot1qVlanCurrentUntaggedPorts.0
        }

        // Convert hex to binary map
        $binary = hex2binmap($entry[$oid_name]);

        $trunk = ''; // unknown

        if ($device['os'] === 'ftos') { // FTOS specific
            // FTOS devices use harder way for detect VLANs and associate ports
            // See: https://www.force10networks.com/CSPortal20/TechTips/0041B_displaying_vlan_ports.aspx
            // Q-BRIDGE-MIB::dot1qVlanStaticUntaggedPorts.1107787777, where 1107787777 is ifIndex for Vlan interface
            // Port associations based on slot/port, each 12 hex pair (96 bin) is slot
            //IF-MIB::ifDescr.1107787777 = STRING: Vlan 1
            //IF-MIB::ifDescr.1107787998 = STRING: Vlan 222
            [, $vlan_num] = explode(' ', $port_stats[$index]['ifDescr']);
            if (!is_numeric($vlan_num)) {
                continue;
            } // Skip unknown

            foreach (str_split($binary, 96) as $slot => $binary_map) {
                $length = strlen($binary_map);
                for ($i = 0; $i < $length; $i++) {
                    if ($binary_map[$i]) {
                        // Now find slot/port from ifDescr
                        $port_map = ' ' . $slot . '/' . ($i + 1);
                        foreach ($port_stats as $ifIndex => $entry) {
                            if (str_ends($entry['ifDescr'], $port_map)) {
                                $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

                                // Set Vlan and Trunk
                                $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
                                $port_stats[$ifIndex]['ifTrunk'] = $trunk;

                                break; // Stop ports loop
                            }
                        }
                    }
                }
            }

        } else { // All other

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

//$process_port_functions[$port_module] = $GLOBALS['snmp_status'];

// Additional db fields for update
//$process_port_db[$port_module][] = 'ifVlan';
//$process_port_db[$port_module][] = 'ifTrunk';

// EOF
