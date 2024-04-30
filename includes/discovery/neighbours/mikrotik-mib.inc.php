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

// mtxrNeighborIpAddress.1 = IpAddress: 192.168.4.27
// mtxrNeighborMacAddress.1 = STRING: 0:23:ac:53:3:28
// mtxrNeighborVersion.1 = STRING: Cisco IOS Software, C2960 Software (C2960-LANBASEK9-M), Version 15.0(1)SE2, RELEASE SOFTWARE (fc3)
// Technical Support: http://www.cisco.com/techsupport
// Copyright (c) 1986-2011 by Cisco Systems, Inc.
// Compiled Thu 22-Dec-11 00:46 by prod_rel_team
// mtxrNeighborPlatform.1 = STRING: cisco WS-C2960G-48TC-L
// mtxrNeighborIdentity.1 = STRING: switch.example.com
// mtxrNeighborSoftwareID.1 = STRING:
// mtxrNeighborInterfaceID.1 = INTEGER: 2

/*
MIKROTIK-MIB:
[1502] => array(
  [mtxrNeighborIpAddress]   => string(11) "10.24.99.65"
  [mtxrNeighborMacAddress]  => string(15) "0:4:56:ef:f1:c9"
  [mtxrNeighborVersion]     => string(5) "3.5.2"
  [mtxrNeighborPlatform]    => string(18) "5G Force 200 (ROW)"
  [mtxrNeighborIdentity]    => string(20) "Fortuna_Skladskaya28"
  [mtxrNeighborSoftwareID]  => string(10) "MAC-Telnet"
  [mtxrNeighborInterfaceID] => string(1) "9"
)

LLDP-MIB:
[1502] => array(
  [lldpRemChassisIdSubtype] => string(10) "macAddress"
  [lldpRemChassisId]        => string(17) "00:04:56:EF:F1:C9"
  [lldpRemPortIdSubtype]    => string(13) "interfaceName"
  [lldpRemPortId]           => string(9) "br-lan.98"
  [lldpRemSysName]          => string(20) "Fortuna_Skladskaya28"
  [lldpRemManAddr]          => string(11) "10.24.99.65"
  [lldpRemSysDesc]          => string(0) ""
)
 */
$mtxr_array = snmpwalk_cache_oid($device, "mtxrNeighbor", [], "MIKROTIK-MIB", NULL, OBS_SNMP_ALL | OBS_SNMP_CONCAT);

if (safe_empty($mtxr_array)) {
    return;
}

// Extend remote port names by discovery in LLDP-MIB (but do not use this MIB self, mikrotik not reports local port there)
$lldp_array = snmpwalk_cache_oid($device, 'lldpRemChassisId', [], "LLDP-MIB");
if (snmp_status()) {
    $lldp_array = snmpwalk_cache_oid($device, 'lldpRemPortIdSubtype', $lldp_array, "LLDP-MIB");
    $lldp_array = snmpwalk_cache_oid($device, 'lldpRemPortId', $lldp_array, "LLDP-MIB");
    $lldp_array = snmpwalk_cache_oid($device, 'lldpRemSysDesc', $lldp_array, "LLDP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);

    // lldpRemManAddrTable
    fetch_oids_lldp_addr($device, $lldp_array);

    print_debug_vars($lldp_array, 1);

    // Merge LLDP to MNDP
    foreach ($lldp_array as $lldp_key => $lldp) {
        $mtxr_key = end(explode('.', $lldp_key));

        if (isset($mtxr_array[$lldp_key])) {
            // older fw versions
            $mtxr_array[$lldp_key] = array_merge($mtxr_array[$lldp_key], $lldp);
            print_debug("MNDP associated with LLDP: $lldp_key -> $lldp_key");
        } elseif (isset($mtxr_array[$mtxr_key])) { // $lldp_key === '0.0.' . $mtxr_key) {
            // latest fw versions
            //$entry = array_merge($entry, $lldp_array['0.0.' . $key]);
            $mtxr_array[$mtxr_key] = array_merge($mtxr_array[$mtxr_key], $lldp);
            print_debug("MNDP associated with LLDP: $mtxr_key -> $lldp_key");
        } elseif (str_ends_with($lldp_key, ".$mtxr_key")) {
            print_debug("MNDP association with LLDP not found : $mtxr_key -> $lldp_key");
        }
    }
}
print_debug_vars($mtxr_array);
unset($lldp_array);

foreach ($mtxr_array as $key => $entry) {

    // Need to straighten out the MAC first for use later. Mikrotik does not pad the numbers! (i.e. 0:12:23:3:5c:6b)
    //$remote_mac = mac_zeropad($entry['mtxrNeighborMacAddress']);
    $entry['mtxrNeighborMacAddress'] = format_mac($entry['mtxrNeighborMacAddress']);

    // Note, mtxrNeighborInterfaceID really hex number, ie:
    // mtxrNeighborInterfaceID.1 = a
    $ifIndex         = hexdec($entry['mtxrNeighborInterfaceID']);
    $remote_platform = strlen($entry['mtxrNeighborPlatform']) ? $entry['mtxrNeighborPlatform'] : $entry['lldpRemSysDesc'];
    $remote_port     = strlen($entry['lldpRemPortId']) ? $entry['lldpRemPortId'] : format_mac($entry['mtxrNeighborMacAddress'], ' ');

    // Get the port using BRIDGE-MIB (Why without Vlan?)
    //$port = dbFetchRow("SELECT * FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ? AND `ifDescr` NOT LIKE 'Vlan%'", array($device['device_id'], $ifIndex));
    $port = get_port_by_index_cache($device, $ifIndex);

    $remote_device_id = NULL;
    $remote_port_id   = NULL;

    // Try to find a remote device and check if already cached
    $remote_device_id = get_autodiscovery_device_id($device, $entry['mtxrNeighborIdentity'], $entry['mtxrNeighborIpAddress'], $entry['mtxrNeighborMacAddress']);
    if (is_null($remote_device_id) &&                                                           // NULL - never cached in other rounds
        check_autodiscovery($entry['mtxrNeighborIdentity'], $entry['mtxrNeighborIpAddress'])) { // Check all previous autodiscovery rounds
        // Neighbour never checked, try autodiscovery
        $remote_device_id = autodiscovery_device($entry['mtxrNeighborIdentity'], $entry['mtxrNeighborIpAddress'], 'MNDP', $remote_platform, $device, $port);
    }

    // Check by LLDP address (probably IPv6 only)
    if (is_null($remote_device_id) && $entry['lldpRemManAddr'] &&
        $entry['mtxrNeighborIpAddress'] != $entry['lldpRemManAddr']) {
        $lldp_device_id = get_autodiscovery_device_id($device, $entry['mtxrNeighborIdentity'], $entry['lldpRemManAddr'], $entry['mtxrNeighborMacAddress']);
        if (is_null($lldp_device_id) &&                                                    // NULL - never cached in other rounds
            check_autodiscovery($entry['mtxrNeighborIdentity'], $entry['lldpRemManAddr'])) { // Check all previous autodiscovery rounds
            // Neighbour never checked, try autodiscovery
            $lldp_device_id = autodiscovery_device($entry['mtxrNeighborIdentity'], $entry['lldpRemManAddr'], 'MNDP', $remote_platform, $device, $port);
        }
        if (!is_null($lldp_device_id)) {
            $remote_device_id = $lldp_device_id;
            $entry['mtxrNeighborIpAddress'] == $entry['lldpRemManAddr'];
        }
    }

    if ($remote_device_id) {
        // Detect remote port by LLDP
        if (strlen($entry['lldpRemPortId'])) {
            $id = $entry['lldpRemPortId'];
            switch ($entry['lldpRemPortIdSubtype']) {
                case 'interfaceAlias':
                    $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ports` WHERE `ifAlias` = ? AND `device_id` = ?", [$id, $remote_device_id]);
                    break;
                case 'interfaceName':
                    // Try lldpRemPortId
                    $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ?';
                    $remote_port_id = dbFetchCell($query, [$id, $id, $id, $remote_device_id]);
                    if (!$remote_port_id && str_contains($id, '/')) {
                        $id = explode('/', $id, 2)[1]; // bridge/ether1 -> ether1
                        $remote_port_id = dbFetchCell($query, [$id, $id, $id, $remote_device_id]);
                    }
                    break;
                case 'macAddress':
                    $remote_port_id = get_port_id_by_mac($remote_device_id, $id);
                    break;
                case 'networkAddress':
                    $ip_version = get_ip_version($id);
                    if ($ip_version) {
                        $ip             = ip_uncompress($id);
                        $remote_port_id = dbFetchCell("SELECT `port_id` FROM `ipv" . $ip_version . "_addresses` LEFT JOIN `ports` USING (`port_id`) WHERE `ipv" . $ip_version . "_address` = ? AND `device_id` = ?",
                                                      [$ip, $remote_device_id]);
                    }
                    break;
                case 'local':
                    // local not always ifIndex or FIXME (see: http://jira.observium.org/browse/OBSERVIUM-1716)
                    if (!ctype_digit($id)) {
                        // Not sure what should be if $id ifName and it just numeric
                        $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ?';
                        $remote_port_id = dbFetchCell($query, [$id, $id, $id, $remote_device_id]);
                    }
                    break;
            }
        } else {
            // No way to find a remote port other than by MAC address, with the data we're getting from Mikrotik. Only proceed when only one remote port matches...
            $remote_port_id = get_port_id_by_mac($remote_device_id, $entry['mtxrNeighborMacAddress']);
        }
    }

    $neighbour = [
        'remote_device_id' => $remote_device_id,
        'remote_port_id'   => $remote_port_id,
        'remote_hostname'  => $entry['mtxrNeighborIdentity'],
        'remote_port'      => $remote_port,
        'remote_platform'  => $remote_platform,
        'remote_version'   => $entry['mtxrNeighborVersion'],
        'remote_address'   => $entry['mtxrNeighborIpAddress']
    ];
    discover_neighbour($port, 'mndp', $neighbour);

}

// EOF
