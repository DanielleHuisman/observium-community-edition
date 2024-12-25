<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

/*
CISCO-LWAPP-CDP-MIB::clcCdpApCacheApName.'hqaoM '.1 = STRING: AP6871.6164.87FC
CISCO-LWAPP-CDP-MIB::clcCdpApCacheApAddressType.'hqaoM '.1 = INTEGER: ipv4(1)
CISCO-LWAPP-CDP-MIB::clcCdpApCacheApAddress.'hqaoM '.1 = Hex-STRING: 0A C9 07 04
CISCO-LWAPP-CDP-MIB::clcCdpApCacheLocalInterface.'hqaoM '.1 = INTEGER: 1
CISCO-LWAPP-CDP-MIB::clcCdpApCacheNeighName.'hqaoM '.1 = STRING: uk-hw-c9300-core.nbrown.root.local
CISCO-LWAPP-CDP-MIB::clcCdpApCacheNeighAddressType.'hqaoM '.1 = INTEGER: ipv4(1)
CISCO-LWAPP-CDP-MIB::clcCdpApCacheNeighAddress.'hqaoM '.1 = Hex-STRING: 0A C9 07 01
CISCO-LWAPP-CDP-MIB::clcCdpApCacheNeighInterface.'hqaoM '.1 = STRING: GigabitEthernet1/0/6
CISCO-LWAPP-CDP-MIB::clcCdpApCacheNeighVersion.'hqaoM '.1 = STRING: Cisco IOS Software [Cupertino], Catalyst L3 Switch Software (CAT9K_IOSXE), Version 17.9.4a, RELEASE SOFTWARE (fc3)
Technical Support: http://www.cisco.com/techsupport
Copyright (c) 1986-2023 by Cisco Systems, Inc.
Compiled Fri 20-Oct-23 10:44 by mcpre
CISCO-LWAPP-CDP-MIB::clcCdpApCacheAdvtVersion.'hqaoM '.1 = INTEGER: cdpv2(2)
CISCO-LWAPP-CDP-MIB::clcCdpApCachePlatform.'hqaoM '.1 = STRING: cisco C9300L-48P-4X
CISCO-LWAPP-CDP-MIB::clcCdpApCacheCapabilities.'hqaoM '.1 = STRING: "Router Switch IGMP"
CISCO-LWAPP-CDP-MIB::clcCdpApCacheHoldtimeLeft.'hqaoM '.1 = Gauge32: 1715786089 seconds
CISCO-LWAPP-CDP-MIB::clcCdpApCacheDuplex.'hqaoM '.1 = INTEGER: fullduplex(2)
CISCO-LWAPP-CDP-MIB::clcCdpApCacheInterfaceSpeed.'hqaoM '.1 = INTEGER: thousandMbps(4) Mbps
 */

$cdp_flags = OBS_SNMP_ALL_MULTILINE | OBS_SNMP_TABLE | OBS_SNMP_DISPLAY_HINT; // disable hints
$cdp_array = snmpwalk_cache_twopart_oid($device, "clcCdpApCacheEntry", [], "CISCO-LWAPP-CDP-MIB", NULL, $cdp_flags);
print_debug_vars($cdp_array);

if (safe_empty($cdp_array)) {
    return;
}

foreach ($cdp_array as $local_mac => $neighbours) {

    foreach ($neighbours as $cdpCacheDeviceIndex => $cdp_entry) {
        // Local port
        $port = get_port_by_index_cache($device, $cdp_entry['clcCdpApCacheLocalInterface']);

        // Remote hostname
        $remote_hostname = $cdp_entry['clcCdpApCacheNeighName'];

        // Remote address
        $remote_address = hex2ip($cdp_entry['clcCdpApCacheNeighAddress']);

        // Remote MAC on some devices
        $if = NULL;
        $remote_mac = NULL;
        if (preg_match('/^([A-F\d]{2}\s?){6}$/', $cdp_entry['clcCdpApCacheNeighInterface'])) {
            $remote_mac = $cdp_entry['clcCdpApCacheNeighInterface'];
        } else {
            $cdp_entry['clcCdpApCacheNeighInterface'] = snmp_hexstring($cdp_entry['clcCdpApCacheNeighInterface']);
            if (preg_match('/^[a-f\d]{12}$/i', $cdp_entry['clcCdpApCacheNeighInterface'])) {
                $remote_mac = $cdp_entry['clcCdpApCacheNeighInterface'];
            } else {
                $if = $cdp_entry['clcCdpApCacheNeighInterface'];
            }
        }

        // Try to find a remote device and check if already cached
        $remote_device_id = get_autodiscovery_device_id($device, $remote_hostname, $remote_address, $remote_mac);
        if (is_null($remote_device_id) &&                             // NULL - never cached in other rounds
            check_autodiscovery($remote_hostname, $remote_address)) { // Check all previous autodiscovery rounds
            // Neighbour never checked, try autodiscovery
            $remote_device_id = autodiscovery_device($remote_hostname, $remote_address, 'CDP', $cdp_entry['cdpCachePlatform'], $device, $port);
        }

        // Remote port (when a remote device found)
        $remote_port_id = NULL;
        if ($remote_device_id) {

            if (!is_null($if)) {
                $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
                $remote_port_id = dbFetchCell($query, [$if, $if, $if, $remote_device_id, 0]);

                // Aruba devices can report ifAlias instead ifDescr
                if (!$remote_port_id && !is_hex_string($if)) {
                    $query          = 'SELECT `port_id` FROM `ports` WHERE `ifAlias` = ? AND `device_id` = ? AND `deleted` = ?';
                    $remote_port_id = dbFetchCell($query, [$if, $remote_device_id, 0]);
                }
            }
            if (!$remote_port_id) {
                if (!is_null($remote_mac)) {
                    // By MAC
                    $remote_port_id = get_port_id_by_mac($remote_device_id, $remote_mac);
                } elseif (!is_null($if)) {
                    // Try by ifAlias
                    $query          = 'SELECT `port_id` FROM `ports` WHERE `ifAlias` = ? AND `device_id` = ? AND `deleted` = ?';
                    $remote_port_id = dbFetchCell($query, [$if, $remote_device_id, 0]);
                }

                if (!$remote_port_id) {
                    // Try by IP
                    $peer_where = generate_query_values_and($remote_device_id, 'device_id'); // Additional filter for include self IPs
                    // Fetch all ports with peer IP and filter by UP
                    if ($ids = get_entity_ids_ip_by_network('port', $remote_address, $peer_where)) {
                        $remote_port_id = $ids[0];
                        //$port = get_port_by_id_cache($ids[0]);
                    }
                }
            }
        }

        $neighbour = [
            'remote_device_id' => $remote_device_id,
            'remote_port_id'   => $remote_port_id,
            'remote_hostname'  => $remote_hostname,
            'remote_port'      => $cdp_entry['clcCdpApCacheNeighInterface'],
            'remote_platform'  => $cdp_entry['clcCdpApCachePlatform'],
            'remote_version'   => $cdp_entry['clcCdpApCacheNeighVersion'],
            'remote_address'   => $remote_address,
            //'last_change'      => $last_change
        ];
        discover_neighbour($port, 'cdp', $neighbour);
    }
}

// EOF
