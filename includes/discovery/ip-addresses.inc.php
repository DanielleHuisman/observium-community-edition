<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * @var array    $config
 * @var array    $device
 * @var string   $module
 * @global array $ip_data
 */

global $ip_data;

$ip_data = ['ipv4' => [], 'ipv6' => []];
//$valid['ip-addresses'] = array();

$include_dir   = 'includes/discovery/ip-addresses';
$include_order = 'default'; // Use MIBs from default os definitions by first!

include($config['install_dir'] . "/includes/include-dir-mib.inc.php");

foreach (get_device_mibs_permitted($device) as $mib) {
    // Detect addresses by definitions
    if (is_array($config['mibs'][$mib]['ip-address'])) {
        print_cli_data_field($mib);
        foreach ($config['mibs'][$mib]['ip-address'] as $oid_data) {
            discover_ip_address_definition($device, $mib, $oid_data);
        }
        print_cli(PHP_EOL);
    }
}

// Try discovery IP addresses in VRF SNMP contexts (currently actual only on Cisco Nexus)
if ($vrf_contexts = get_device_vrf_contexts($device)) { // SNMP VRF context discovered for device
    // Keep original device array
    $device_original = $device;

    foreach ($vrf_contexts as $vrf_name => $snmp_virtual) {
        print_message("Addresses in Virtual Routing: $vrf_name...");
        $device = snmp_virtual_device($device_original, $snmp_virtual);
        //$device['snmp_context'] = $snmp_context;
        if (!$device['snmp_retries']) {
            // force less retries on vrf requests.. if not set in db
            $device['snmp_retries'] = 1;
        }

        $include_dir   = 'includes/discovery/ip-addresses';
        $include_order = 'default'; // Use MIBs from default os definitions by first!
        include($config['install_dir'] . "/includes/include-dir-mib.inc.php");
    }

    // Clean
    $device = $device_original;
    unset($device_original);
}

// Process IP Addresses
$table_rows     = [];
$check_networks = [];
foreach (['ipv4', 'ipv6'] as $ip_version) {
    print_debug_vars($ip_data[$ip_version]);

    // Caching old IP addresses table
    $query = 'SELECT * FROM `' . $ip_version . '_addresses`
            WHERE `device_id` = ?';
    foreach (dbFetchRows($query, [$device['device_id']]) as $entry) {
        if (safe_empty($entry['ifIndex'])) {
            // Compatibility
            $ifIndex = dbFetchCell('SELECT `ifIndex` FROM `ports` WHERE `port_id` = ? AND `deleted` = ?', [$entry['port_id'], 0]);
        } else {
            $ifIndex = $entry['ifIndex'];
        }
        $old_table[$ip_version][$ifIndex][$entry[$ip_version . '_address']] = $entry;
    }
    if (!safe_count($ip_data[$ip_version]) && !safe_count($old_table[$ip_version])) {
        // Skip if walk and DB empty
        continue;
    }

    // Process founded IP addresses
    foreach ($ip_data[$ip_version] as $ifIndex => $addresses) {
        $port = get_port_by_index_cache($device, $ifIndex);
        if (is_array($port) && !$port['deleted']) {
            $port_id = $port['port_id'];
        } else {
            // Allow to store IP addresses without associated port, but ifIndex available
            // ie, Nortel/Avaya devices have hidden vlan ifIndexes
            $port_id = '0';
        }

        print_debug_vars($port);
        print_debug_vars($addresses);

        foreach ($addresses as $ip_address => $entry) {

            if ($ip_version === 'ipv4') {
                // IPv4
                $ip_prefix     = $entry['prefix'];
                $ip_origin     = $entry['origin'];
                $ip_compressed = $ip_address; // just for compat with IPv6

                $ip_type      = $entry['type'];
                $addr         = Net_IPv4 ::parseAddress($ip_address . '/' . $ip_prefix);
                $ip_cidr      = $addr -> bitmask;
                $ip_network   = $addr -> network . '/' . $ip_cidr;
                $full_address = $ip_address . '/' . $ip_cidr;

                $new_address = [
                  'port_id'        => $port_id,
                  'ifIndex'        => $ifIndex,
                  'device_id'      => $device['device_id'],
                  'ipv4_address'   => $ip_address,
                  'ipv4_binary'    => inet_pton($ip_address),
                  'ipv4_prefixlen' => $ip_cidr,
                  'ipv4_type'      => $ip_type
                ];
            } else {
                // IPv6
                $ip_prefix     = $entry['prefix'];
                $ip_origin     = $entry['origin'];
                $ip_compressed = Net_IPv6 ::compress($ip_address, TRUE);
                $full_address  = $ip_compressed . '/' . $ip_prefix;
                $ip_type       = $entry['type'];
                $ip_network    = Net_IPv6 ::getNetmask($full_address) . '/' . $ip_prefix;
                //$full_compressed = $ip_compressed.'/'.$ipv6_prefixlen;
                $new_address = [
                  'port_id'         => $port_id,
                  'ifIndex'         => $ifIndex,
                  'device_id'       => $device['device_id'],
                  'ipv6_address'    => $ip_address,
                  'ipv6_binary'     => inet_pton($ip_address),
                  'ipv6_compressed' => $ip_compressed,
                  'ipv6_prefixlen'  => $ip_prefix,
                  'ipv6_type'       => $ip_type,
                  'ipv6_origin'     => $ip_origin
                ];
            }

            // VRFs
            $sql_vrf = "SELECT `vrf_id` FROM `vrfs` WHERE `device_id` = ? AND `vrf_name` = ?";
            if (!safe_empty($entry['vrf']) &&
                $vrf_id = dbFetchCell($sql_vrf, [$device['device_id'], $entry['vrf']])) {
                // VRF discovered as name in addresses discovery
                $new_address['vrf_id'] = $vrf_id;
            } elseif ($port_id && $port['ifVrf']) {
                // Port associated with VRF in vrf discovery
                $new_address['vrf_id'] = $port['ifVrf'];
            }

            // Check network
            $ip_network_id = dbFetchCell('SELECT `' . $ip_version . '_network_id` FROM `' . $ip_version . '_networks` WHERE `' . $ip_version . '_network` = ?', [$ip_network]);
            if (empty($ip_network_id)) {
                // Add new network
                $ip_network_id = dbInsert([$ip_version . '_network' => $ip_network], $ip_version . '_networks');
                //echo('N');
            }
            $new_address[$ip_version . '_network_id'] = $ip_network_id;

            // Add to display table
            $table_rows[] = [$ifIndex, truncate($port['ifDescr'], 30), nicecase($ip_version), $full_address, $ip_network, $entry['type'], $ip_origin];

            // Check IP in DB
            $update_array = [];
            if (isset($old_table[$ip_version][$ifIndex][$ip_address])) {
                $old_address   = &$old_table[$ip_version][$ifIndex][$ip_address];
                $ip_address_id = $old_address[$ip_version . '_address_id'];
                $params        = array_diff(array_keys($old_address), ['device_id', $ip_version . '_address_id']);
                foreach ($params as $param) {
                    if ($param === 'ipv6_binary') {
                        // Compare decoded binary IPv6 address
                        if (inet_ntop($old_address[$param]) != $new_address['ipv6_compressed']) {
                            //$update_array[$param] = $new_address[$param];
                            $update_array[$param] = [binary_to_hex($new_address[$param])];
                        }
                    } elseif ($param === 'ipv4_binary') {
                        // Compare decoded binary IPv4 address
                        if (inet_ntop($old_address[$param]) != $new_address['ipv4_address']) {
                            //$update_array[$param] = $new_address[$param];
                            $update_array[$param] = [binary_to_hex($new_address[$param])];
                        }
                    } else {
                        // All other params as string
                        if ($old_address[$param] != $new_address[$param]) {
                            if (in_array($param, ['vrf_id', 'ifIndex', $ip_version . '_type']) && !strlen($new_address[$param])) {
                                $update_array[$param] = ['NULL'];
                            } else {
                                $update_array[$param] = $new_address[$param];
                            }
                        }
                    }
                }

                $update_count = count($update_array);
                if ($update_count === 1 && (isset($update_array['ipv4_binary']) || isset($update_array['ipv6_binary']))) {
                    // Silent update binary address after upgrade
                    dbUpdate($update_array, $ip_version . '_addresses', '`' . $ip_version . '_address_id` = ?', [$old_address[$ip_version . '_address_id']]);
                    $GLOBALS['module_stats'][$module]['unchanged']++;
                } elseif ($update_count) {
                    // Updated
                    dbUpdate($update_array, $ip_version . '_addresses', '`' . $ip_version . '_address_id` = ?', [$old_address[$ip_version . '_address_id']]);
                    if (!$port_id) {
                        log_event("IP address changed: $ip_compressed/" . $old_address[$ip_version . '_prefixlen'] . " -> $full_address", $device, 'device', $device['device_id']);
                    } elseif (isset($update_array['port_id'])) {
                        // Address moved to another port
                        log_event("IP address removed: $ip_compressed/" . $old_address[$ip_version . '_prefixlen'], $device, 'port', $old_address['port_id']);
                        log_event("IP address added: $full_address", $device, 'port', $port_id);
                    } else {
                        // Changed prefix/cidr
                        log_event("IP address changed: $ip_compressed/" . $old_address[$ip_version . '_prefixlen'] . " -> $full_address", $device, 'port', $port_id);
                    }
                    $GLOBALS['module_stats'][$module]['updated']++; //echo "U";
                    $check_networks[$ip_version][$ip_network_id] = 1;
                } else {
                    // Not changed
                    $GLOBALS['module_stats'][$module]['unchanged']++; //echo ".";
                }
            } else {
                // New IP
                $update_array  = $new_address;
                $ip_address_id = dbInsert($update_array, $ip_version . '_addresses');
                if ($port_id) {
                    log_event("IP address added: $full_address", $device, 'port', $port_id);
                } else {
                    log_event("IP address added: $full_address", $device, 'device', $device['device_id']);
                }
                $GLOBALS['module_stats'][$module]['added']++; //echo "+";
            }

            $valid[$ip_version][$ip_address_id] = $full_address . ':' . $port_id;
        }
    }

    // Refetch and clean IP addresses from DB
    foreach (dbFetchRows($query, [$device['device_id']]) as $entry) {
        $ip_address_id = $entry[$ip_version . '_address_id'];
        if (!isset($valid[$ip_version][$ip_address_id])) {
            $full_address = ($ip_version === 'ipv4' ? $entry['ipv4_address'] : $entry['ipv6_compressed']);
            $full_address .= '/' . $entry[$ip_version . '_prefixlen'];

            // Delete IP
            dbDelete($ip_version . '_addresses', '`' . $ip_version . '_address_id` = ?', [$ip_address_id]);
            if ($port_id) {
                log_event("IP address removed: $full_address", $device, 'port', $entry['port_id']);
            } else {
                log_event("IP address removed: $full_address", $device, 'device', $entry['device_id']);
            }
            $GLOBALS['module_stats'][$module]['deleted']++; //echo "-";

            $check_networks[$ip_version][$entry[$ip_version . '_network_id']] = 1;
        }
    }

    // Clean networks
    foreach ($check_networks[$ip_version] as $ip_network_id => $n) {
        //$count = dbFetchCell('SELECT COUNT(*) FROM `'.$ip_version.'_addresses` WHERE `'.$ip_version.'_network_id` = ?', array($ip_network_id));
        //if (empty($count))
        if (!dbExist($ip_version . '_addresses', '`' . $ip_version . '_network_id` = ?', [$ip_network_id])) {
            dbDelete($ip_version . '_networks', '`' . $ip_version . '_network_id` = ?', [$ip_network_id]);
            //echo('n');
        }
    }
}

$table_headers = ['%WifIndex%n', '%WifDescr%n', '%WIP: Version%n', '%WAddress%n', '%WNetwork%n', '%WType%n', '%WOrigin%n'];
print_cli_table($table_rows, $table_headers);

// Clean
unset($ip_data, $check_networks, $check_ipv6_mib, $update_array, $old_table, $table_rows, $table_headers);

// EOF
