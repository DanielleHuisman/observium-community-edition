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

// TPLINK-L2BRIDGE-MIB::tpl2BridgeManagePortIndex.49153 = STRING: 1/0/1
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManagePortIndex.49154 = STRING: 1/0/2
$fdb_ports = [];
foreach (snmpwalk_cache_oid($device, 'tpl2BridgeManagePortIndex', [], 'TPLINK-L2BRIDGE-MIB') as $ifIndex => $entry) {
    $fdb_ports[$entry['tpl2BridgeManagePortIndex']] = $ifIndex;
}
if (!snmp_status()) {
    return;
}

// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynMac.20.88.208.75.9.0.10 = STRING: "14-58-d0-4b-09-00"
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynMac.20.88.208.75.9.0.744 = STRING: "14-58-d0-4b-09-00"
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynVlanId.20.88.208.75.9.0.10 = INTEGER: 10
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynVlanId.20.88.208.75.9.0.744 = INTEGER: 744
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynPort.20.88.208.75.9.0.10 = STRING: "1/0/4"
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynPort.20.88.208.75.9.0.744 = STRING: "1/0/28"
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynAgeStatus.20.88.208.75.9.0.10 = INTEGER: aging(1)
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynAgeStatus.20.88.208.75.9.0.744 = INTEGER: aging(1)
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynStatus.20.88.208.75.9.0.10 = INTEGER: active(1)
// TPLINK-L2BRIDGE-MIB::tpl2BridgeManageDynStatus.20.88.208.75.9.0.744 = INTEGER: active(1)
foreach (snmpwalk_cache_oid($device, 'tpl2BridgeManageDynAddrCtrlTable', [], 'TPLINK-L2BRIDGE-MIB', NULL, OBS_SNMP_ALL_NUMERIC_INDEX) as $index => $entry) {
    if ($entry['tpl2BridgeManageDynAgeStatus'] !== 'aging') {
        continue;
    }
    if (!isset($fdb_ports[$entry['tpl2BridgeManageDynPort']])) {
        print_debug("Unknown port '" . $entry['tpl2BridgeManageDynPort'] . "'.");
        print_debug_vars($entry);
        continue;
    }

    $ifIndex = $fdb_ports[$entry['tpl2BridgeManageDynPort']];
    $vlan    = $entry['tpl2BridgeManageDynVlanId'];

    // Make sure the ifIndex is actually valid
    if ($ifIndex != 0 && is_array($port_ifIndex_table[$ifIndex])) {
        $port = $port_ifIndex_table[$ifIndex];
        $mac  = mac_zeropad($entry['tpl2BridgeManageDynMac']);

        $data = [];

        $data['port_id']    = $port['port_id'];
        $data['port_index'] = $ifIndex;
        $data['fdb_status'] = 'learned'; // Hardcoded for this MIB

        $fdbs[$vlan][$mac] = $data;
    }
}

// EOF
