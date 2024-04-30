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
    // Really there is BRIDGE-MIB, but as in old way we check for Q-BRIDGE-MIB
    return;
}

// I think this is global, not per-VLAN. (in normal world..)
// But NOPE, this is Cisco way (probably for pvst) @mike
// See: https://jira.observium.org/browse/OBS-2813
//
// From same device example default and vlan 103:
// snmpbulkwalk -v2c community -m BRIDGE-MIB -M /srv/observium/mibs/rfc:/srv/observium/mibs/net-snmp sw-1917 dot1dBasePortIfIndex
//BRIDGE-MIB::dot1dBasePortIfIndex.49 = INTEGER: 10101
//BRIDGE-MIB::dot1dBasePortIfIndex.50 = INTEGER: 10102
// snmpbulkwalk -v2c community@103 -m BRIDGE-MIB -M /srv/observium/mibs/rfc:/srv/observium/mibs/net-snmp sw-1917 dot1dBasePortIfIndex
//BRIDGE-MIB::dot1dBasePortIfIndex.1 = INTEGER: 10001
//BRIDGE-MIB::dot1dBasePortIfIndex.3 = INTEGER: 10003
//BRIDGE-MIB::dot1dBasePortIfIndex.4 = INTEGER: 10004
//...
// But I will try to pre-cache, this fetch port association for default (1) vlan only!
$dot1dBasePort_table = [];
$dot1dBasePortIfIndex[1] = snmpwalk_cache_oid($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB');
foreach ($dot1dBasePortIfIndex[1] as $base_port => $data) {
    $dot1dBasePort_table[$base_port] = $port_ifIndex_table[$data['dot1dBasePortIfIndex']];
}

// Fetch list of active VLANs (with vlan context exist)
$sql = 'SELECT DISTINCT `vlan_vlan` FROM `vlans` WHERE `device_id` = ? AND `vlan_context` = ? AND (`vlan_status` = ? OR `vlan_status` = ?)';
foreach (dbFetchRows($sql, [ $device['device_id'], 1, 'active', 'operational']) as $cisco_vlan) {
    $vlan = $cisco_vlan['vlan_vlan'];

    // Set per-VLAN context
    $device_context = $device;
    // Add vlan context for snmp auth
    if ($device['snmp_version'] === 'v3') {
        $device_context['snmp_context'] = 'vlan-' . $vlan;
    } else {
        $device_context['snmp_context'] = $vlan;
    }
    //$device_context['snmp_retries'] = 1;         // Set retries to 0 for speedup walking

    //dot1dTpFdbAddress[0:7:e:6d:55:41] 0:7:e:6d:55:41
    //dot1dTpFdbPort[0:7:e:6d:55:41] 28
    //dot1dTpFdbStatus[0:7:e:6d:55:41] learned
    $dot1dTpFdbEntry_table = snmpwalk_multipart_oid($device_context, 'dot1dTpFdbEntry', [], 'BRIDGE-MIB', NULL, OBS_SNMP_ALL_TABLE);

    if (!snmp_status()) {
        // Continue if no entries for vlan
        unset($device_context);
        continue;
    }

    foreach ($dot1dTpFdbEntry_table as $mac => $entry) {
        $mac      = mac_zeropad($mac);
        $fdb_port = $entry['dot1dTpFdbPort'];

        // If not exist ifIndex associations from previous walks, fetch association for current vlan context
        // This is derp, but I not know better speedup this walks
        if (!isset($dot1dBasePort_table[$fdb_port]) && !isset($dot1dBasePortIfIndex[$vlan])) {
            print_debug("Cache dot1dBasePort -> IfIndex association table by vlan $vlan");
            // Need to walk port association for this vlan context
            $dot1dBasePortIfIndex[$vlan] = snmpwalk_cache_oid($device_context, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB');
            foreach ($dot1dBasePortIfIndex[$vlan] as $base_port => $data) {
                $dot1dBasePort_table[$base_port] = $port_ifIndex_table[$data['dot1dBasePortIfIndex']];
            }
            // Prevent rewalk in cycle if empty output
            if (is_null($dot1dBasePortIfIndex[$vlan])) {
                $dot1dBasePortIfIndex[$vlan] = FALSE;
            }
        }

        $data = [];

        $data['port_id']    = $dot1dBasePort_table[$fdb_port]['port_id'];
        $data['port_index'] = isset($dot1dBasePort_table[$fdb_port]) ? $dot1dBasePort_table[$fdb_port]['ifIndex'] : $fdb_port;
        $data['fdb_status'] = $entry['dot1dTpFdbStatus'];

        $fdbs[$vlan][$mac] = $data;
    }
}

unset($dot1dBasePortIfIndex, $dot1dTpFdbEntry_table, $dot1dBasePort_table);

// EOF
