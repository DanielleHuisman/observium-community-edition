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

print_cli_data_field("MIBs", 2);

// Init db
foreach (dbFetchRows("SELECT * FROM `wifi_radios` WHERE `device_id` = ?", [$device['device_id']]) as $radio) {
    $GLOBALS['cache']['wifi_radios'][$radio['radio_ap']][$radio['radio_number']] = $radio;
}

foreach (dbFetchRows("SELECT * FROM `wifi_wlans` WHERE `device_id` = ?", [$device['device_id']]) as $wlan) {
    $GLOBALS['cache']['wifi_wlans'][$wlan['wlan_index']] = $wlan;
}

$aps_db = [];
foreach (dbFetchRows("SELECT * FROM `wifi_aps` WHERE `device_id` = ?", [$device['device_id']]) as $aps) {
    if (isset($aps_db[$aps['ap_index']])) {
        // Duplicate entry
        print_debug_vars($aps);
        dbDelete('wifi_aps', '`wifi_ap_id` =  ?', [$aps['wifi_ap_id']]);
        dbDelete('wifi_aps_members', '`wifi_ap_id` =  ?', [$aps['wifi_ap_id']]);
        continue;
    }
    $aps_db[$aps['ap_index']] = $aps;
}

$aps_member_db = [];
foreach (dbFetchRows('SELECT * FROM `wifi_aps_members` WHERE `device_id` = ?', [$device['device_id']]) as $member) {
    if (isset($aps_member_db[$member['ap_index_member']])) {
        // Duplicate entry
        print_debug_vars($member);
        dbDelete('wifi_aps_members', '`wifi_ap_member_id` =  ?', [$member['wifi_ap_member_id']]);
        continue;
    }
    $aps_member_db[$member['ap_index_member']] = $member;
}

// Init poll vars
$aps_poll        = [];
$aps_member_poll = [];
$wifi_controller = [];

$include_dir = "includes/polling/wifi";
include("includes/include-dir-mib.inc.php");

// Process APs entries
if (count($aps_poll) || count($aps_db)) {
    // Check WiFi APs
    $table_rows = [];

    // Add/update
    foreach ($aps_poll as $index => $entry) {
        if (safe_empty($entry['device_id'])) {
            $entry['device_id'] = $device['device_id'];
        }
        if (is_array($aps_db[$index])) {
            //echo("AP Index exists: $index\n");
            $ap_db      = $aps_db[$index];
            $wifi_ap_id = $ap_db['wifi_ap_id'];
            $ap_update  = [];
            foreach ($entry as $param => $value) {
                if ($entry[$param] !== $ap_db[$param]) {
                    $ap_update[$param] = safe_empty($entry[$param]) ? ['NULL'] : $entry[$param];
                }
            }
            if (count($ap_update)) {
                dbUpdate($ap_update, 'wifi_aps', '`wifi_ap_id` = ?', [$wifi_ap_id]);
            }
        } else {
            //echo("New AP Index: $index\n");
            $wifi_ap_id = dbInsert($entry, 'wifi_aps');
            // update db cache, need for members
            $entry['wifi_ap_id'] = $wifi_ap_id;
            $aps_db[$index]      = $entry;
        }

        $table_row    = [];
        $table_row[]  = $index;
        $table_row[]  = $entry['ap_name'];
        $table_row[]  = $entry['ap_address'];
        $table_row[]  = $entry['ap_serial'];
        $table_row[]  = $entry['ap_model'];
        $table_row[]  = $entry['ap_location'];
        $table_row[]  = $entry['ap_status'];
        $table_row[]  = $entry['ap_admin_status'];
        $table_row[]  = format_uptime($entry['ap_uptime']);
        $table_row[]  = format_uptime($entry['ap_control_uptime']);
        $table_row[]  = format_uptime($entry['ap_control_latency']);
        $table_rows[] = $table_row;
    }
    // Append AP count graph
    if (!isset($wifi_ap_count)) {
        $wifi_ap_count = count($aps_poll);
    }

    // Delete removed
    foreach ($aps_db as $index => $entry) {
        if (!isset($aps_poll[$index])) {
            $wifi_ap_id = $entry['wifi_ap_id'];
            if ($entry['deleted'] || safe_empty($index)) {
                print_warning("AP will delete AP:$index with id: $wifi_ap_id");
                dbDelete('wifi_aps', '`wifi_ap_id` =  ?', [$wifi_ap_id]);
                dbDelete('wifi_aps_members', '`wifi_ap_id` =  ?', [$wifi_ap_id]);
                $status = '--';
            } else {
                print_warning("AP don't exists anymore, but it's not marked to be deleted (considering Down): $index with id: $wifi_ap_id");
                dbUpdate(['ap_status' => "down"], 'wifi_aps', '`device_id` = ? AND `wifi_ap_id` = ?', [$device['device_id'], $wifi_ap_id]);
                $status = 'down';
            }

            $table_row    = [];
            $table_row[]  = $index;
            $table_row[]  = $entry['ap_name'];
            $table_row[]  = $entry['ap_address'];
            $table_row[]  = $entry['ap_serial'];
            $table_row[]  = $entry['ap_model'];
            $table_row[]  = $entry['ap_location'];
            $table_row[]  = $status;
            $table_row[]  = '%rdeleted%n';
            $table_row[]  = '--';
            $table_row[]  = '--';
            $table_row[]  = '--';
            $table_rows[] = $table_row;
            unset($table_row);
        }
    }

    $table_headers = ['%WAP MacAddress%n', '%WName%n', '%WAddress%n', '%WSerial%n', '%WModel%n', '%WLocation%n', '%WOperStatus%n', '%WAdminStatus', '%WUptime%n', '%WController Uptime%n', '%WController Latency%n'];
    print_cli_table($table_rows, $table_headers);
    unset($table_rows, $table_headers, $table_row);
}

// Process APs members entries
if (count($aps_member_poll) || count($aps_member_db)) {
    // Check WiFi APs members
    $table_rows = [];

    // Known params
    $aps_member_params = ['wifi_ap_id', 'device_id', 'ap_index_member', 'ap_name', 'ap_member_state', 'ap_member_admin_state',
                          'ap_member_conns', 'ap_member_channel', 'ap_member_radiotype'];

    // Add/update
    foreach ($aps_member_poll as $member_index => $entry) {
        if (safe_empty($entry['device_id'])) {
            $entry['device_id'] = $device['device_id'];
        }
        $ap_entry = $aps_db[$entry['ap_index']];
        unset($entry['ap_index']); // not exist in db
        if (safe_empty($entry['ap_name'])) {
            $entry['ap_name'] = $ap_entry['ap_name'];
        }
        if (safe_empty($entry['wifi_ap_id'])) {
            $entry['wifi_ap_id'] = $ap_entry['wifi_ap_id'];
        }

        if (is_array($aps_member_db[$member_index])) {
            //echo("AP member exists: $member_index\n");
            $ap_member_db      = $aps_member_db[$member_index];
            $wifi_ap_member_id = $ap_member_db['wifi_ap_member_id'];
            $ap_member_update  = [];
            foreach ($entry as $param => $value) {
                if ($entry[$param] !== $ap_member_db[$param]) {
                    $ap_member_update[$param] = safe_empty($entry[$param]) ? ['NULL'] : $entry[$param];
                }
            }
            if (count($ap_member_update)) {
                dbUpdate($ap_member_update, 'wifi_aps_members', '`wifi_ap_member_id` = ?', [$wifi_ap_member_id]);
            }
        } else {
            //echo("New AP member: $member_index\n");
            $wifi_ap_member_id = dbInsert($entry, 'wifi_aps_members');
        }

        $table_row    = [];
        $table_row[]  = $member_index;
        $table_row[]  = $entry['ap_name'];
        $table_row[]  = $entry['ap_member_radiotype'];
        $table_row[]  = $entry['ap_member_channel'];
        $table_row[]  = $entry['ap_member_conns'];
        $table_row[]  = $entry['ap_member_state'];
        $table_row[]  = $entry['ap_member_admin_state'];
        $table_rows[] = $table_row;
        unset($table_row);

        // Update graph
        rrdtool_update_ng($device, 'wifi_ap_member', ['bsnApIfNoOfUsers' => $entry['ap_member_conns']], $member_index);
    }

    // Delete removed members
    foreach ($aps_member_db as $member_index => $entry) {
        if (!isset($aps_member_poll[$member_index])) {
            $wifi_ap_member_id = $entry['wifi_ap_member_id'];
            dbDelete('wifi_aps_members', '`wifi_ap_member_id` =  ?', [$wifi_ap_member_id]);

            $table_row    = [];
            $table_row[]  = $member_index;
            $table_row[]  = $entry['ap_name'];
            $table_row[]  = $entry['ap_member_radiotype'];
            $table_row[]  = $entry['ap_member_channel'];
            $table_row[]  = '--';
            $table_row[]  = '--';
            $table_row[]  = '%rdeleted%n';
            $table_rows[] = $table_row;
            unset($table_row);
        }
    }

    $table_headers = ['%WAP MacAddress%n', '%WAP Name%n', '%WMember Radio Type%n', '%WChannel Number%n', '%WConnected Users%n', '%WOper Status%n', '%WAdmin Status%n'];
    print_cli_table($table_rows, $table_headers);
    unset($table_rows, $table_headers);
}

// WiFi Controller stats
if (count($wifi_controller)) {
    rrdtool_update_ng($device, 'wifi-controller', [
      'NUMAPS'     => $wifi_controller['aps'],
      'NUMCLIENTS' => $wifi_controller['clients'],
    ]);
}
unset($GLOBALS['cache']['wifi_radios'], $GLOBALS['cache']['wifi_wlans'], $aps_db, $aps_poll, $aps_member_db, $aps_member_poll, $member_index);

/// FIXME : everything below this point is horrible shit that needs to be moved elsewhere.
/// These aren't wireless entities, they're just graphs. They go in graphs.

// Convert old variables to array
$poll_wifi = [];
if (isset($wificlients1) && is_numeric($wificlients1)) {
    $poll_wifi['wifi_clients1'] = $wificlients1;
}
if (isset($wificlients2) && is_numeric($wificlients2)) {
    $poll_wifi['wifi_clients2'] = $wificlients2;
}
if (isset($wifi_ap_count) && is_numeric($wifi_ap_count)) {
    $poll_wifi['wifi_ap_count'] = $wifi_ap_count;
}

// Find MIB-specific SNMP data via OID fetch: wifi_clients (or wifi_clients1, wifi_clients2), wifi_ap_count
$wifi_metatypes = ['wifi_clients', 'wifi_clients1', 'wifi_clients2', 'wifi_ap_count'];
foreach (poll_device_mib_metatypes($device, $wifi_metatypes, $poll_wifi) as $metatype => $value) {
    if (!is_numeric($value)) {
        continue;
    } // Skip not numeric entries

    // RRD Filling Code
    switch ($metatype) {
        case 'wifi_clients':
        case 'wifi_clients1':
            rrdtool_update_ng($device, 'wificlients', ['wificlients' => $value], 'radio1');
            $graphs['wifi_clients'] = TRUE;
            break;

        case 'wifi_clients2':
            rrdtool_update_ng($device, 'wificlients', ['wificlients' => $value], 'radio2');
            $graphs['wifi_clients'] = TRUE;
            break;

        case 'wifi_ap_count':
            rrdtool_update_ng($device, 'wifi_ap_count', ['value' => $value]);
            $graphs['wifi_ap_count'] = TRUE;
            break;
    }
}

unset($wificlients2, $wificlients1, $wifi_ap_count);

// EOF
