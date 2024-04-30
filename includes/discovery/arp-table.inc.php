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

// IP-MIB contains two tables:
//  ipNetToMediaTable     -- deprecated, as it contains only entries for IPv4.
//  ipNetToPhysicalTable  -- current, address version agnostic table
// IPV6-MIB has been abandoned in favor of the revised IP-MIB, but has one table:
//  ipv6NetToMediaTable
// CISCO-IETF-IP-MIB      -- based on an early draft of the revised IP-MIB
//  cInetNetToMediaTable

$mac_table = [];

$include_dir   = 'includes/discovery/arp';
$include_order = 'default'; // Use MIBs from default os definitions by first!

$vrf_name = '';
include($config['install_dir'] . "/includes/include-dir-mib.inc.php");

// Try discovery ARP addresses in VRF SNMP contexts
if ($vrf_contexts = get_device_vrf_contexts($device)) { // SNMP VRF context discovered for device
    // Keep original device array
    $device_original = $device;

    foreach ($vrf_contexts as $vrf_name => $snmp_virtual) {
        print_message("ARP addresses in Virtual Routing: $vrf_name...");
        $device = snmp_virtual_device($device_original, $snmp_virtual);
        //$device['snmp_context'] = $snmp_context;
        if (!$device['snmp_retries']) {
            // force less retries on vrf requests.. if not set in db
            $device['snmp_retries'] = 1;
        }

        $include_dir   = 'includes/discovery/arp';
        $include_order = 'default'; // Use MIBs from default os definitions by first!
        include($config['install_dir'] . "/includes/include-dir-mib.inc.php");
    }

    // Clean
    $device = $device_original;
    unset($device_original);
}

print_debug_vars($mac_table);

// Caching old ARP/NDP table
if (get_db_version() < 482) {
    print_error("Need update DB schema: ./discovery.php -u");
}
$query     = 'SELECT * FROM `ip_mac` WHERE `device_id` = ?';
$old_table = [];
foreach (dbFetchRows($query, [$device['device_id']]) as $entry) {
    $old_if      = $entry['mac_ifIndex'];
    $old_mac     = $entry['mac_address'];
    $old_address = $entry['ip_address'];
    $old_version = 'ipv' . $entry['ip_version'];
    $old_vrf     = !safe_empty($entry['virtual_name']) ? $entry['virtual_name'] : '';

    $old_table[$old_vrf][$old_version][$old_if][$old_address] = ['mac' => $old_mac, 'mac_id' => $entry['mac_id']];
}
print_debug_vars($old_table);

$table_rows = [];
foreach ($mac_table as $vrf_name => $entry1) {
    foreach ($entry1 as $ip_version => $entry2) {
        foreach ($entry2 as $ifIndex => $entry3) {
            $port_vrf_name = $vrf_name;
            if ($port = get_port_by_index_cache($device, $ifIndex)) {
                $port_id = $port['port_id'];
                if ($port['ifVrf']) {
                    // Set VRF name by port associated vrf
                    $port_vrf_name = dbFetchCell('SELECT `vrf_name` FROM `vrfs` WHERE `device_id` = ? AND `vrf_id` = ?', [$device['device_id'], $port['ifVrf']]);
                }
            } else {
                // That possible with VRF discovery
                print_debug("Not found port for ifIndex: $ifIndex");
                $port_id = ['NULL'];
            }
            foreach ($entry3 as $ip => $clean_mac) {

                if (isset($old_table[$port_vrf_name][$ip_version][$ifIndex][$ip])) {
                    // Already exist
                    $entry   = $old_table[$port_vrf_name][$ip_version][$ifIndex][$ip];
                    $old_mac = $entry['mac'];

                    if ($clean_mac !== $old_mac && $clean_mac !== '000000000000' && $old_mac !== '000000000000') {
                        print_debug("Changed MAC address for $ip from " . format_mac($old_mac) . " to " . format_mac($clean_mac));
                        log_event("MAC changed: $ip : " . format_mac($old_mac) . " -> " . format_mac($clean_mac), $device, "port", $port_id);
                        dbUpdate(['mac_address' => $clean_mac], 'ip_mac', 'mac_id = ?', [$entry['mac_id']]);
                        //echo("U");
                        $GLOBALS['module_stats'][$module]['updated']++;
                    } else {
                        //echo(".");
                        $GLOBALS['module_stats'][$module]['unchanged']++;
                    }
                    // remove entry for deletion
                    unset($old_table[$port_vrf_name][$ip_version][$ifIndex][$ip]);
                } else {
                    // new entry
                    $insert = [
                      'device_id'    => $device['device_id'],
                      'port_id'      => $port_id,
                      'mac_ifIndex'  => $ifIndex,
                      'mac_address'  => $clean_mac,
                      'ip_address'   => $ip,
                      'ip_version'   => str_replace('ipv', '', $ip_version),
                      'virtual_name' => safe_empty($port_vrf_name) ? ['NULL'] : $port_vrf_name
                    ];
                    dbInsert($insert, 'ip_mac');
                    print_debug("Added MAC address " . format_mac($clean_mac) . " for $ip");
                    //log_event("MAC added: $ip : " . format_mac($clean_mac), $device, "port", $port_id);
                    //echo("+");
                    $GLOBALS['module_stats'][$module]['added']++;
                }
            }
            $table_rows[] = [$ifIndex, truncate($port['ifDescr'], 30), $port_vrf_name, nicecase($ip_version), count($entry3)];
            // clean entry for deletion
            if (empty($old_table[$port_vrf_name][$ip_version][$ifIndex])) {
                unset($old_table[$port_vrf_name][$ip_version][$ifIndex]);
            }
            // clean entry for deletion
            if (empty($old_table[$port_vrf_name][$ip_version])) {
                unset($old_table[$port_vrf_name][$ip_version]);
            }
            // clean entry for deletion
            if (empty($old_table[$port_vrf_name])) {
                unset($old_table[$port_vrf_name]);
            }
        }
    }
}

// Remove expired ARP/NDP entries
$remove_mac_ids = [];
print_debug_vars($old_table);
foreach ($old_table as $vrf_name => $entry1) {
    foreach ($entry1 as $ip_version => $entry2) {
        foreach ($entry2 as $ifIndex => $entry3) {
            foreach ($entry3 as $ip => $entry) {
                $remove_mac_ids[] = $entry['mac_id'];

                print_debug("Removed MAC address " . format_mac($entry['mac']) . " for $ip");
                //log_event("MAC removed: $entry_ip : " . format_mac($entry_mac), $device, "port", $entry['port_id']);
                //echo("-");
                $GLOBALS['module_stats'][$module]['deleted']++;
            }
        }
    }
}
if (!safe_empty($remove_mac_ids)) {
    dbDelete('ip_mac', generate_query_values($remove_mac_ids, 'mac_id'));
}
echo(PHP_EOL);

$table_headers = ['%WifIndex%n', '%WifDescr%n', '%WVRF%n', '%WIP: Version%n', '%WAddresses Count%n'];
print_cli_table($table_rows, $table_headers);

unset($mac_table, $old_table, $remove_mac_ids, $table_rows, $table_headers);

// EOF
