<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// TPLINK-DOT1Q-VLAN-MIB::dot1qVlanId.8 = INTEGER: 8
// TPLINK-DOT1Q-VLAN-MIB::dot1qVlanId.10 = INTEGER: 10
// TPLINK-DOT1Q-VLAN-MIB::dot1qVlanDescription.8 = STRING: "LAN Cantemir"
// TPLINK-DOT1Q-VLAN-MIB::dot1qVlanDescription.10 = STRING: "MNG"
// TPLINK-DOT1Q-VLAN-MIB::vlanTagPortMemberAdd.8 = ""
// TPLINK-DOT1Q-VLAN-MIB::vlanTagPortMemberAdd.10 = STRING: "1/0/4,1/0/11,1/0/13,1/0/15,1/0/19-20"
// TPLINK-DOT1Q-VLAN-MIB::vlanUntagPortMemberAdd.8 = ""
// TPLINK-DOT1Q-VLAN-MIB::vlanUntagPortMemberAdd.10 = STRING: "1/0/12,1/0/14,1/0/16,1/0/23-24"
// TPLINK-DOT1Q-VLAN-MIB::vlanPortMemberRemove.8 = ""
// TPLINK-DOT1Q-VLAN-MIB::vlanPortMemberRemove.10 = ""
// TPLINK-DOT1Q-VLAN-MIB::dot1qVlanStatus.8 = INTEGER: active(1)
// TPLINK-DOT1Q-VLAN-MIB::dot1qVlanStatus.10 = INTEGER: active(1)

$vlan_oids = snmpwalk_cache_oid($device, 'vlanConfigTable', [], 'TPLINK-DOT1Q-VLAN-MIB');
print_debug_vars($vlan_oids);

if (!snmp_status()) {
    return;
}

// TPLINK-DOT1Q-VLAN-MIB::vlanPortNumber.49153 = STRING: "1/0/1"
// TPLINK-DOT1Q-VLAN-MIB::vlanPortNumber.49154 = STRING: "1/0/2"
$port_descr = [];
foreach (snmpwalk_cache_oid($device, 'vlanPortNumber', [], 'TPLINK-DOT1Q-VLAN-MIB') as $index => $entry) {
    $port_descr[$entry['vlanPortNumber']] = $index;
}


$vtp_domain_index = '1'; // Yep, always use domain index 1

foreach ($vlan_oids as $vlan_num => $vlan) {
    // Skip not exist vlans
    if ($vlan['dot1qVlanStatus'] !== 'active') {
        continue;
    }

    $vlan_array = [
      'ifIndex'     => $vlan_num, // Vlan ifIndex same as Vlan ID
      'vlan_domain' => $vtp_domain_index,
      'vlan_vlan'   => $vlan_num,
      'vlan_name'   => safe_empty($vlan['dot1qVlanDescription']) ? "Vlan $vlan_num" : $vlan['dot1qVlanDescription'],
      //'vlan_mtu'    => $vlan[''],
      'vlan_type'   => 'ethernet',
      'vlan_status' => 'operational'
    ];

    $discovery_vlans[$vtp_domain_index][$vlan_num] = $vlan_array;

    // Tagged ports
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
            $ifIndex                                    = $port_descr[$member];
            $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
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
                    $ifIndex                                    = $port_descr[$member_name];
                    $discovery_ports_vlans[$ifIndex][$vlan_num] = ['vlan' => $vlan_num];
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

// EOF
