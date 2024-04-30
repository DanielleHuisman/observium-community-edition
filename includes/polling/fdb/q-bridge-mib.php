<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// NOTE. Do not include in mib way! Directly include.

if (!safe_empty($fdbs) || !is_device_mib($device, 'Q-BRIDGE-MIB')) {
    // Q-BRIDGE-MIB already blacklisted for vrp
    return;
}

//dot1qTpFdbPort[1][0:0:5e:0:1:1] 50
//dot1qTpFdbStatus[1][0:0:5e:0:1:1] learned

if ($device['os'] === 'junos' || $device['os'] === 'junos-evo') {
    // JUNOS doesn't use the actual vlan ids for much in Q-BRIDGE-MIB
    // but we can get the vlan names and use that to lookup the actual
    // vlan ids that were found with JUNIPER-VLAN-MIB during discovery

    // Fetch list of active VLANs
    $vlanidsbyname = [];
    foreach (dbFetchRows('SELECT `vlan_vlan`,`vlan_name` FROM `vlans` WHERE (`vlan_status` = ? OR `vlan_status` = ?) AND `device_id` = ?', ['active', 'operational', $device['device_id']]) as $entry) {
        $vlanidsbyname[$entry['vlan_name']] = $entry['vlan_vlan'];
    }

    // getting the names as listed by Q-BRIDGE-MIB
    // and making a mapping to the real vlan ids
    if (count($vlanidsbyname)) {
        foreach (snmpwalk_cache_oid($device, 'dot1qVlanStaticName', [], 'Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_TABLE) as $id => $entry) {
            $juniper_vlans[$id] = $vlanidsbyname[$entry['dot1qVlanStaticName']];
        }
    }
    unset($vlanidsbyname);
}

// Dell OS10 return strange additional num, see:
// https://jira.observium.org/browse/OBS-3213
//dot1qTpFdbPort[4][6:0:24:38:93:c8].0 = 59
//dot1qTpFdbPort[4][6:0:50:56:95:51].221 = 59
$dot1qTpFdbEntry_table = snmpwalk_cache_oid($device, 'dot1qTpFdbEntry', [], 'Q-BRIDGE-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
if (snmp_status()) {
    // Build dot1dBasePort
    foreach (snmpwalk_cache_oid($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB') as $dot1dbaseport => $entry) {
        $dot1dBasePort_table[$dot1dbaseport] = $port_ifIndex_table[$entry['dot1dBasePortIfIndex']];
    }

    foreach ($dot1qTpFdbEntry_table as $index => $entry) {
        $index_array = explode('.', $index);
        $vlan        = array_shift($index_array);
        if (count($index_array) > 6) {
            // Remove first (strange, incorrect) mac part
            array_shift($index_array);
        }
        // reimplode index to mac
        $mac = '';
        foreach ($index_array as $mac_num) {
            $mac .= dechex($mac_num) . ':';
        }
        $mac = mac_zeropad(trim($mac, ':'));

        // if we have a translated vlan id for Juniper, use it
        if (isset($juniper_vlans[$vlan])) {
            $vlan = $juniper_vlans[$vlan];
        }

        $fdb_port = $entry['dot1qTpFdbPort'];

        $data               = [];
        $data['port_id']    = $dot1dBasePort_table[$fdb_port]['port_id'];
        $data['port_index'] = isset($dot1dBasePort_table[$fdb_port]) ? $dot1dBasePort_table[$fdb_port]['ifIndex'] : $fdb_port;
        $data['fdb_status'] = $entry['dot1qTpFdbStatus'];

        $fdbs[$vlan][$mac] = $data;
    }
}

unset($juniper_vlans, $dot1qTpFdbEntry_table, $dot1dBasePort_table);

// EOF
