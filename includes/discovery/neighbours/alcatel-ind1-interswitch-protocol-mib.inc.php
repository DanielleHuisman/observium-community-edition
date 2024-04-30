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

// $mib is "ALCATEL-IND1-INTERSWITCH-PROTOCOL-MIB" or "ALCATEL-ENT1-INTERSWITCH-PROTOCOL-MIB"
// Same mib type, but different Oid tree
$amap_array = snmpwalk_cache_threepart_oid($device, "aipAMAPportConnectionTable", [], $mib, NULL, OBS_SNMP_ALL_TABLE);

if (safe_empty($amap_array)) {
    return;
}

$amap_hosts = snmpwalk_cache_twopart_oid($device, 'aipAMAPIpAddr', [], $mib, NULL, OBS_SNMP_ALL_TABLE);
print_debug_vars($amap_array);
print_debug_vars($amap_hosts);

foreach ($amap_array as $aipAMAPLocalConnectionIndex => $entry1) {
    foreach ($entry1 as $remote_mac => $entry2) {
        foreach ($entry2 as $aipAMAPRemConnectionIndex => $amap) {
            $port = get_port_by_index_cache($device, $amap['aipAMAPLocalIfindex']);

            // Remote Hostname
            $remote_hostname = $amap['aipAMAPRemHostname'];

            // Remote address(es)
            $remote_address = NULL;
            if (isset($amap_hosts[$remote_mac])) {
                // Can be multiple?
                $addresses = array_keys($amap_hosts[$remote_mac]);
                if (count($addresses) > 1) {
                    foreach ($addresses as $addr) {
                        $addr_version = get_ip_version($addr);
                        $addr_type    = get_ip_type($addr);
                        if (in_array($addr_type, ['unspecified', 'loopback', 'reserved', 'multicast'])) {
                            continue;
                        } elseif ($addr_version == 6 && $addr_type == 'link-local') {
                            continue;
                        } elseif ($addr_type == 'unicast') {
                            // Prefer IPv4/IPv6 unicast
                            $remote_address = $addr;
                            break;
                        } elseif ($addr_version == 4) {
                            // Than prefer IPv4
                            $remote_address = $addr;
                            break;
                        }
                        $remote_address = $addr;
                    }
                    print_debug("Multiple remote IP addresses detect, selected: $remote_address");
                } else {
                    $remote_address = array_shift($addresses);
                }
            }

            //$remote_device_id = NULL;

            // Try find remote device and check if already cached
            $remote_device_id = get_autodiscovery_device_id($device, $remote_hostname, $remote_address, $remote_mac);
            if (is_null($remote_device_id) &&                           // NULL - never cached in other rounds
                check_autodiscovery($remote_hostname, $remote_address)) // Check all previous autodiscovery rounds
            {
                // Neighbour never checked, try autodiscovery
                $remote_device_id = autodiscovery_device($remote_hostname, $remote_address, 'AMAP', $amap['aipAMAPRemDevModelName'], $device, $port);
            }

            $remote_port_id = NULL;
            $if             = $amap['aipAMAPRemSlot'] . "/" . $amap['aipAMAPRemPort'];
            if ($remote_device_id) {
                $query          = 'SELECT `port_id` FROM `ports` WHERE (`ifName` = ? OR `ifDescr` = ? OR `port_label_short` = ?) AND `device_id` = ? AND `deleted` = ?';
                $remote_port_id = dbFetchCell($query, [$if, $if, $if, $remote_device_id, 0]);
                if (!$remote_port_id) {
                    if (!is_null($remote_mac)) {
                        // By MAC
                        $remote_port_id = get_port_id_by_mac($remote_device_id, $remote_mac);
                    } else {
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
                'remote_port'      => $amap['aipAMAPRemSlot'] . "/" . $amap['aipAMAPRemPort'],
                'remote_platform'  => $amap['aipAMAPRemDevModelName'],
                'remote_version'   => NULL,
                'remote_address'   => $remote_address,
                //'last_change'      => $last_change
            ];
            discover_neighbour($port, 'amap', $neighbour);
        }
    }
}

// EOF
