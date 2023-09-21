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

/*
AtiStackSwitch9424-MIB::atiStkSwMacAddrPortId[0:1:80:4a:17:19][30] = 23
*/

$entries = snmpwalk_cache_twopart_oid($device, 'atiStkSwMacAddrPortId', [], $mib, NULL, OBS_SNMP_ALL_TABLE);
print_debug_vars($entries);

foreach ($entries as $mac => $data1) {
    foreach ($data1 as $vlan => $entry) {
        // Make sure the ifIndex is actually valid
        if ($entry['atiStkSwMacAddrPortId'] != 0 && is_array($port_ifIndex_table[$entry['atiStkSwMacAddrPortId']])) {
            $port = $port_ifIndex_table[$entry['atiStkSwMacAddrPortId']];

            $mac = mac_zeropad($mac);

            $data = [];

            $data['port_id']    = $port['port_id'];
            $data['port_index'] = $entry['atiStkSwMacAddrPortId'];
            $data['fdb_status'] = 'learned';

            $fdbs[$vlan][$mac] = $data;

        }

    }
}

unset($entries);

// EOF