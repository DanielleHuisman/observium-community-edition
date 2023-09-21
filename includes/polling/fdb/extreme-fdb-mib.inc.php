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

// Exit if already founded fdb entries
if (safe_count($fdbs)) {
    return;
}

// EXTREME-FDB-MIB::extremeFdbMacExosFdbPortIfIndex[0:4:96:52:f1:1d][1000053] = 1052
// EXTREME-FDB-MIB::extremeFdbMacExosFdbStatus[0:4:96:52:f1:1d][1000053] = learned
$entries = snmpwalk_cache_twopart_oid($device, 'extremeFdbMacExosFdbPortIfIndex', [], 'EXTREME-FDB-MIB', NULL, OBS_SNMP_ALL_TABLE);

if (snmp_status()) {
    $entries = snmpwalk_cache_twopart_oid($device, 'extremeFdbMacExosFdbStatus', $entries, 'EXTREME-FDB-MIB', NULL, OBS_SNMP_ALL_TABLE);
    print_debug_vars($entries);

    foreach ($entries as $mac => $data1) {
        foreach ($data1 as $vlan_index => $entry) {

            // Seach vlan number by discovered vlans
            $vlan_port = $port_ifIndex_table[$vlan_index];
            if (preg_match('/^VLAN\s*0*(\d+)/i', $vlan_port['ifDescr'], $matches)) {
                $vlan = $matches[1];
            } else {
                $vlan = $vlan_index; // Incorrect, but better than nothing
            }

            // Make sure the ifIndex is actually valid
            if ($entry['extremeFdbMacExosFdbPortIfIndex'] != 0 && is_array($port_ifIndex_table[$entry['extremeFdbMacExosFdbPortIfIndex']])) {
                $port = $port_ifIndex_table[$entry['extremeFdbMacExosFdbPortIfIndex']];

                $mac = mac_zeropad($mac);

                $data = [];

                $data['port_id']    = $port['port_id'];
                $data['port_index'] = $entry['extremeFdbMacExosFdbPortIfIndex'];
                $data['fdb_status'] = $entry['extremeFdbMacExosFdbStatus'];

                $fdbs[$vlan][$mac] = $data;

            }

        }
    }
}

unset($entries);

// EOF