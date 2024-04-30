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

// This is generic *-ISDP-MIB discovery.
$isdp_array = snmpwalk_cache_twopart_oid($device, 'agentIsdpCacheTable', [], $mib, NULL, OBS_SNMP_ALL_MULTILINE);
print_debug_vars($isdp_array);

if (empty($isdp_array)) {
    return;
}

foreach ($isdp_array as $ifIndex => $port_neighbours) {
    $port = get_port_by_index_cache($device, $ifIndex);

    foreach ($port_neighbours as $entry_id => $isdp) {
        if (safe_empty($isdp['agentIsdpCacheDevicePort']) && safe_empty($isdp['agentIsdpCachePlatform']) &&
            safe_empty($isdp['agentIsdpCacheAddress']) && safe_empty($isdp['agentIsdpCacheVersion'])) {
            // All neighbour fields are empty, ignore
            print_debug("Neighbour ignored: proto[isdp], " . $isdp['agentIsdpCacheDeviceId']);
            continue;
        }

        // Normally not possible, but I keep this ability for search local port
        if (!$port && !safe_empty($isdp['agentIsdpCacheLocalIntf'])) {
            $if    = $isdp['agentIsdpCacheLocalIntf'];
            $query = 'SELECT * FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
            $port  = dbFetchRow($query, [$if, $if, $if, $device['device_id'], 0]);
        }

        // Remote Hostname
        if (str_contains($isdp['agentIsdpCacheDeviceId'], '(')) {
            // Fix for Nexus ISDP neighbors: <hostname>(serial number)
            $isdp['agentIsdpCacheDeviceId'] = explode('(', $isdp['agentIsdpCacheDeviceId'])[0];
        }
        $remote_hostname = trim($isdp['agentIsdpCacheDeviceId']);

        // Remote address
        $remote_address = hex2ip($isdp['agentIsdpCacheAddress']);

        // Last change
        $last_change = timeticks_to_sec($isdp['agentIsdpCacheLastChange']);
        if ($last_change > 0) {
            $last_change = get_time() - $last_change;
        }

        // Try to find a remote device and check if already cached
        $remote_device_id = get_autodiscovery_device_id($device, $remote_hostname, $remote_address);
        if (is_null($remote_device_id) &&                           // NULL - never cached in other rounds
            check_autodiscovery($remote_hostname, $remote_address)) // Check all previous autodiscovery rounds
        {
            // Neighbour never checked, try autodiscovery
            $remote_device_id = autodiscovery_device($remote_hostname, $remote_address, 'ISDP', $isdp['agentIsdpCachePlatform'], $device, $port);
        }

        $remote_port_id = NULL;
        $if             = $isdp['agentIsdpCacheDevicePort'];
        if ($remote_device_id) {
            $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
            $remote_port_id = dbFetchCell($query, [$if, $if, $if, $remote_device_id, 0]);
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

        $neighbour = [
            'remote_device_id' => $remote_device_id,
            'remote_port_id'   => $remote_port_id,
            'remote_hostname'  => $remote_hostname,
            'remote_port'      => $isdp['agentIsdpCacheDevicePort'],
            'remote_platform'  => $isdp['agentIsdpCachePlatform'],
            'remote_version'   => $isdp['agentIsdpCacheVersion'],
            'remote_address'   => $remote_address,
            'last_change'      => $last_change
        ];
        discover_neighbour($port, 'isdp', $neighbour);
    }
}

// EOF
