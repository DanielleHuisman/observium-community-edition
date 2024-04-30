<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// TPLINK-LLDPINFO-MIB::lldpNeighborPortId.49175.1 = STRING: "1/0/23"
// TPLINK-LLDPINFO-MIB::lldpNeighborPortIndexId.49175.1 = INTEGER: 1
// TPLINK-LLDPINFO-MIB::lldpNeighborChassisIdType.49175.1 = STRING: "MAC address"
// TPLINK-LLDPINFO-MIB::lldpNeighborChassisId.49175.1 = STRING: "A4:B9:84:7E:9Z:4B"
// TPLINK-LLDPINFO-MIB::lldpNeighborPortIdType.49175.1 = STRING: "Interface name"
// TPLINK-LLDPINFO-MIB::lldpNeighborPortIdDescr.49175.1 = STRING: "eth1"
// TPLINK-LLDPINFO-MIB::lldpNeighborTtl.49175.1 = INTEGER: 120
// TPLINK-LLDPINFO-MIB::lldpNeighborPortDescr.49175.1 = STRING: "eth1"
// TPLINK-LLDPINFO-MIB::lldpNeighborDeviceName.49175.1 = STRING: "Honda-WR200"
// TPLINK-LLDPINFO-MIB::lldpNeighborDeviceDescr.49175.1 = STRING: "OpenWrt 19.07.7"
// TPLINK-LLDPINFO-MIB::lldpNeighborCapAvailable.49175.1 = STRING: "Bridge WLAN Access Point Router Station Only"
// TPLINK-LLDPINFO-MIB::lldpNeighborCapEnabled.49175.1 = STRING: "Bridge WLAN Access Point Router"
// TPLINK-LLDPINFO-MIB::lldpNeighborManageIpAddr.49175.1 = Wrong Type (should be IpAddress): STRING: "19.254.254.149"
// TPLINK-LLDPINFO-MIB::lldpNeighborManageAddrType.49175.1 = STRING: "ipv4"
// TPLINK-LLDPINFO-MIB::lldpNeighborManageAddrInterfaceType.49175.1 = STRING: "IfIndex"
// TPLINK-LLDPINFO-MIB::lldpNeighborManageAddrInterfaceId.49175.1 = INTEGER: 5
// TPLINK-LLDPINFO-MIB::lldpNeighborManageAddrOID.49175.1 = STRING: "0"
// TPLINK-LLDPINFO-MIB::lldpNeighborPortAndProtocolVlanID.49175.1 = ""
// TPLINK-LLDPINFO-MIB::lldpNeighborVlanName.49175.1 = STRING: "vid: 100, VLAN name: eth1.100; vid: 2, VLAN name: eth1.2; vid: 200, VLAN name: eth1.200; vid: 254, VLAN name: eth1.254;"
// TPLINK-LLDPINFO-MIB::lldpNeighborProtocolIdentity.49175.1 = ""
// TPLINK-LLDPINFO-MIB::lldpNeighborAutoNegotiationSupported.49175.1 = INTEGER: enable(1)
// TPLINK-LLDPINFO-MIB::lldpNeighborAutoNegotiationEnabled.49175.1 = INTEGER: enable(1)
// TPLINK-LLDPINFO-MIB::lldpNeighborOperMau.49175.1 = STRING: "speed(100)/duplex(Full)"
// TPLINK-LLDPINFO-MIB::lldpNeighborLinkAggregationSupported.49175.1 = INTEGER: enable(1)
// TPLINK-LLDPINFO-MIB::lldpNeighborLinkAggregationEnabled.49175.1 = INTEGER: disable(0)
// TPLINK-LLDPINFO-MIB::lldpNeighborAggregationPortId.49175.1 = INTEGER: 0
// TPLINK-LLDPINFO-MIB::lldpNeighborPowerPortClass.49175.1 = ""
// TPLINK-LLDPINFO-MIB::lldpNeighborPsePowerSupported.49175.1 = INTEGER: disable(0)
// TPLINK-LLDPINFO-MIB::lldpNeighborPsePowerEnabled.49175.1 = INTEGER: disable(0)
// TPLINK-LLDPINFO-MIB::lldpNeighborPsePairsControlAbility.49175.1 = INTEGER: disable(0)
// TPLINK-LLDPINFO-MIB::lldpNeighborMaximumFrameSize.49175.1 = INTEGER: 0

$lldp_array = snmpwalk_cache_oid($device, 'lldpNeighborInfoTable', [], "TPLINK-LLDPINFO-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
print_debug_vars($lldp_array);

foreach ($lldp_array as $index => $lldp) {

    // Local port
    [$ifIndex, $lldpNeighborPortIndexId] = explode('.', $index, 2);
    $ifName = $lldp['lldpNeighborPortId'];
    $port   = get_port_by_index_cache($device, $ifIndex);
    if (!$port) {
        $port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `deleted` = ? AND (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?)", [$device['device_id'], 0, $ifName, $ifName, $ifName]);
    }

    // Remote device & port
    $remote_device_id = FALSE;
    $remote_port_id   = NULL;

    // Try find remote device and check if already cached
    $remote_device_id = get_autodiscovery_device_id($device, $lldp['lldpNeighborDeviceName'], $lldp['lldpNeighborManageIpAddr'], $lldp['lldpNeighborChassisId']);
    if (is_null($remote_device_id) &&                                                              // NULL - never cached in other rounds
        check_autodiscovery($lldp['lldpNeighborDeviceName'], $lldp['lldpNeighborManageIpAddr'])) { // Check all previous autodiscovery rounds
        // Neighbour never checked, try autodiscovery
        $remote_device_id = autodiscovery_device($lldp['lldpNeighborDeviceName'], $lldp['lldpNeighborManageIpAddr'], 'LLDP', $lldp['lldpNeighborDeviceDescr'], $device, $port);
    }

    if ($remote_device_id) {
        // Try lldpNeighborPortIdDescr
        $if             = $lldp['lldpNeighborPortIdDescr'];
        $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
        $remote_port_id = dbFetchCell($query, [$if, $if, $if, $remote_device_id, 0]);
        if (!$remote_port_id && $lldp['lldpNeighborPortDescr'] !== $if) {
            $if = $lldp['lldpNeighborPortDescr'];
            // Try same by lldpNeighborPortDescr
            $remote_port_id = dbFetchCell($query, [$if, $if, $if, $remote_device_id, 0]);
        }
        //$id = $lldp['lldpNeighborManageAddrInterfaceId'];
    }

    $neighbour = [
        'remote_device_id' => $remote_device_id,
        'remote_port_id'   => $remote_port_id,
        'remote_hostname'  => $lldp['lldpNeighborDeviceName'],
        'remote_port'      => $lldp['lldpNeighborPortIdDescr'],
        'remote_platform'  => $lldp['lldpNeighborDeviceDescr'],
        'remote_version'   => NULL,
        'remote_address'   => $lldp['lldpNeighborManageIpAddr']
    ];
    discover_neighbour($port, 'lldp', $neighbour);
}

// EOF
