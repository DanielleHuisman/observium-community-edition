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

/*
TPLINK-DOT1Q-VLAN-MIB::vlanPortPvid.49154 = INTEGER: 999
TPLINK-DOT1Q-VLAN-MIB::vlanPortPvid.49155 = INTEGER: 744
TPLINK-DOT1Q-VLAN-MIB::vlanPortPvid.49156 = INTEGER: 2616
*/

// Base vlan IDs
$ports_vlans_oids = snmpwalk_cache_oid($device, 'vlanPortPvid', [], 'TPLINK-DOT1Q-VLAN-MIB');
print_debug_vars($ports_vlans_oids);

if (snmp_status()) {
    echo("vlanPortPvid ");

    // TPLINK-DOT1Q-VLAN-MIB::vlanPortNumber.49154 = STRING: "1/0/2"
    $port_descr = [];
    foreach (snmpwalk_cache_oid($device, 'vlanPortNumber', [], 'TPLINK-DOT1Q-VLAN-MIB') as $index => $entry) {
        $port_descr[$entry['vlanPortNumber']] = $index;
    }
    // TPLINK-DOT1Q-VLAN-MIB::vlanTagPortMemberAdd.8 = ""
    // TPLINK-DOT1Q-VLAN-MIB::vlanTagPortMemberAdd.10 = STRING: "1/0/4,1/0/11,1/0/13,1/0/15,1/0/19-20"
    $port_trunks = [];
    foreach (snmpwalk_cache_oid($device, 'vlanTagPortMemberAdd', [], 'TPLINK-DOT1Q-VLAN-MIB') as $vlan_num => $vlan) {
        if (safe_empty($vlan['vlanTagPortMemberAdd'])) {
            continue;
        }

        // See: https://jira.observium.org/browse/OBS-3827
        // TPLINK-DOT1Q-VLAN-MIB::vlanTagPortMemberAdd.10 = STRING: "1/0/4,1/0/11,1/0/13,1/0/15,1/0/19-20"
        // TPLINK-DOT1Q-VLAN-MIB::vlanTagPortMemberAdd.2 = STRING: "Gi1/0/3-24,Te1/0/27-28,Gi2/0/3-24,Te2/0/27-28,Po1-3,7,11,13-14"
        $members = [];
        $i       = -1;
        foreach (explode(',', $vlan['vlanTagPortMemberAdd']) as $member) {
            if (!preg_match('/^\d+(\-\d+)?$/', $member)) {
                $i++;
                $members[$i] = $member;
            } else {
                // append to previous
                $members[$i] .= ',' . $member;
            }
        }
        $member_base = '';
        foreach ($members as $member) {
            if (isset($port_descr[$member])) {
                $ifIndex                          = $port_descr[$member];
                $port_trunks[$ifIndex][$vlan_num] = $vlan_num;
                continue;
            }

            if (preg_match('/^([^\d]+)(.+)$/', $member, $matches)) {
                $member_base = $matches[1];
            }
            if (str_contains_array($member, ['-', ','])) {
                // Expand list to individual ports
                if (str_contains($member, '/')) {
                    // 1/0/19-20,30
                    // Te2/0/27-28,30
                    $split_char   = '/';
                    $member_array = explode($split_char, $member);
                } elseif (preg_match('/^([^\d]+)(.+)$/', $member, $matches)) {
                    // Po1-3,7,11,13-14
                    $split_char   = '';
                    $member_array = [$matches[1], $matches[2]];
                }
                $numbers = array_pop($member_array);
                foreach (list_to_range($numbers) as $number) {
                    $member_new   = $member_array;
                    $member_new[] = $number;
                    $member_name  = implode($split_char, $member_new);

                    if (isset($port_descr[$member_name])) {
                        $ifIndex                          = $port_descr[$member_name];
                        $port_trunks[$ifIndex][$vlan_num] = $vlan_num;
                        continue;
                    }
                    if (isset($port_descr[$member_base . $member_name])) {
                        $ifIndex                                    = $port_descr[$member_base . $member_name];
                        $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
                        continue;
                    }
                }
            } elseif (isset($port_descr[$member_base . $member])) {
                $ifIndex                                    = $port_descr[$member_base . $member];
                $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
                continue;
            }
            print_debug("Unknown port name for Tagged Vlans: $member");
        }
    }

    $vlan_rows = [];
    foreach ($ports_vlans_oids as $ifIndex => $vlan) {
        $vlan_num    = $vlan['vlanPortPvid'];
        $trunk       = isset($port_trunks[$ifIndex]) ? 'dot1Q' : 'access';
        $vlan_rows[] = [$ifIndex, $vlan_num, $trunk];

        // Set Vlan and Trunk
        $port_stats[$ifIndex]['ifVlan']  = $vlan_num;
        $port_stats[$ifIndex]['ifTrunk'] = $trunk;

    }

}

$headers = ['%WifIndex%n', '%WVlan%n', '%WTrunk%n'];
print_cli_table($vlan_rows, $headers);

// EOF
