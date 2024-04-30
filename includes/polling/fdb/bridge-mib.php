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

if (!safe_empty($fdbs) || !is_device_mib($device, 'BRIDGE-MIB')) {
    return;
}

// Note, BRIDGE-MIB not have Vlan information
$dot1dTpFdbEntry_table = snmpwalk_cache_oid($device, 'dot1dTpFdbPort', [], 'BRIDGE-MIB', NULL, OBS_SNMP_ALL_TABLE);

if (snmp_status()) {
    $dot1dTpFdbEntry_table = snmpwalk_cache_oid($device, 'dot1dTpFdbStatus', $dot1dTpFdbEntry_table, 'BRIDGE-MIB', NULL, OBS_SNMP_ALL_TABLE);
    print_debug_vars($dot1dTpFdbEntry_table);

    $dot1dBasePort_table = [];
    if ($device['os'] === 'routeros' && version_compare($device['version'], '6.47', '>=')) {
        // See: https://jira.observium.org/browse/OBS-4373

        // Build dot1dBasePort
        foreach (snmpwalk_cache_oid($device, 'dot1dBasePortIfIndex', [], 'BRIDGE-MIB') as $dot1dbaseport => $entry) {
            $dot1dBasePort_table[$dot1dbaseport] = $port_ifIndex_table[$entry['dot1dBasePortIfIndex']];
        }
        print_debug_vars($dot1dBasePort_table);
    }

    $vlan = 0; // BRIDGE-MIB not have Vlan information
    foreach ($dot1dTpFdbEntry_table as $mac => $entry) {
        $mac = mac_zeropad($mac);

        $fdb_port = $entry['dot1dTpFdbPort'];

        $data = [];
        if (isset($dot1dBasePort_table[$fdb_port])) {
            // See: https://jira.observium.org/browse/OBS-4373
            $data['port_id']    = $dot1dBasePort_table[$fdb_port]['port_id'];
            $data['port_index'] = $dot1dBasePort_table[$fdb_port]['ifIndex'];
        } else {
            $data['port_id']    = $port_ifIndex_table[$fdb_port]['port_id'];
            $data['port_index'] = $fdb_port;
        }
        $data['fdb_status'] = $entry['dot1dTpFdbStatus'];

        $fdbs[$vlan][$mac] = $data;
    }

}

unset($dot1dTpFdbEntry_table, $dot1dBasePort_table);

// EOF
