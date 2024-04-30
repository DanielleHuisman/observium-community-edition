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

$fdp_array = snmpwalk_cache_twopart_oid($device, "snFdpCacheEntry", [], "FOUNDRY-SN-SWITCH-GROUP-MIB", NULL, OBS_SNMP_ALL_MULTILINE);
print_debug_vars($fdp_array);

foreach ($fdp_array as $ifIndex => $entry) {
    $port = get_port_by_index_cache($device, $ifIndex);

    foreach ($entry as $fdp) {
        // Remote Hostname
        $remote_hostname = $fdp['snFdpCacheDeviceId'];

        // Remote address
        $remote_address = hex2ip($fdp['snFdpCacheAddress']);

        // Try find remote device and check if already cached
        $remote_device_id = get_autodiscovery_device_id($device, $remote_hostname, $remote_address);
        if (is_null($remote_device_id) &&                           // NULL - never cached in other rounds
            check_autodiscovery($remote_hostname, $remote_address)) // Check all previous autodiscovery rounds
        {
            // Neighbour never checked, try autodiscovery
            $remote_device_id = autodiscovery_device($remote_hostname, $remote_address, 'FDP', $fdp['snFdpCachePlatform'], $device, $port);
        }

        $remote_port_id = NULL;
        $if             = $fdp['snFdpCacheDevicePort'];
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
            'remote_port'      => $fdp['snFdpCacheDevicePort'],
            'remote_platform'  => $fdp['snFdpCachePlatform'],
            'remote_version'   => $fdp['snFdpCacheVersion'],
            'remote_address'   => $remote_address,
            //'last_change'      => $last_change
        ];
        discover_neighbour($port, 'fdp', $neighbour);
    }
}

// EOF
