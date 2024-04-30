<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage functions
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

/**
 * Process strings to give them a nicer capitalisation format
 *
 * This function does rewrites from the lowercase identifiers we use to the
 * standard capitalisation. UK English style plurals, please.
 * This uses $config['nicecase']
 *
 * @param string $item
 *
 * @return string
 */
function nicecase($item)
{
    if (is_numeric($item)) {
        return (string)$item;
    }
    if (!is_string($item)) {
        return '';
    }

    $mappings = $GLOBALS['config']['nicecase'];
    if (isset($mappings[$item])) {
        return $mappings[$item];
    }
    //$item = preg_replace('/([a-z])([A-Z]{2,})/', '$1 $2', $item); // turn "fixedAC" into "fixed AC"

    return ucfirst((string)$item);
}

/**
 * Trim string and remove paired and/or escaped quotes from string
 *
 * @param string $string Input string
 * @param int    $flags
 *
 * @return string Cleaned string
 */
function trim_quotes($string, $flags = OBS_QUOTES_TRIM)
{
    $string = trim($string); // basic string clean

    if (is_flag_set(OBS_QUOTES_STRIP, $flags) && str_contains($string, '"')) {
        // Just remove all (double) quotes from string
        return str_replace(['\"', '"'], '', $string);
    }

    if (is_flag_set(OBS_QUOTES_TRIM, $flags)) {
        if (str_contains($string, '\"')) {
            // replace escaped quotes
            $string = str_replace('\"', '"', $string);
        }

        if (str_starts($string, ['"', "'"])) {
            $quotes  = '["\']'; // remove double and single quotes
            $pattern = '/^(' . $quotes . ')(?<value>.*?)(\1)$/s';
            while (preg_match($pattern, $string, $matches)) {
                $string = $matches['value'];
            }
        }
    }

    return $string;
}

/**
 * Humanize User
 *
 *   Process an array containing user info to add/modify elements.
 *
 * @param array $user
 */
// TESTME needs unit testing
function humanize_user(&$user)
{
    $level_permissions = auth_user_level_permissions($user['level']);
    $level_real        = $level_permissions['level'];
    if (isset($GLOBALS['config']['user_level'][$level_real])) {
        $def                 = $GLOBALS['config']['user_level'][$level_real];
        $user['level_label'] = $def['name'];
        $user['level_name']  = $def['name'];
        $user['level_real']  = $level_real;
        unset($def['name'], $level_permissions['level']);
        $user = array_merge($user, $def, $level_permissions);
        // Add label class
        $user['label_class'] = $user['row_class'] === 'disabled' ? 'inverse' : $user['row_class'];
    }
    //r($user);
}

/**
 * Humanize Scheduled Maintanance
 *
 *   Process an array containing a row from `alert_maintenance` and in-place add/modify elements for use in the UI
 *
 *
 */
function humanize_maintenance(&$maint)
{

    $maint['duration'] = $maint['maint_end'] - $maint['maint_start'];

    if ($maint['maint_global'] == 1) {
        $maint['entities_text'] = '<span class="label label-info">Global Maintenance</span>';
    } else {
        $entities = dbFetchRows("SELECT * FROM `alerts_maint_assoc` WHERE `maint_id` = ?", [$maint['maint_id']]);
        if (is_array($entities) && count($entities)) {
            foreach ($entities as $entity) {
                // FIXME, what here should be?
            }
        } else {
            $maint['entities_text'] = '<span class="label label-error">Maintenance is not associated with any entities.</span>';
        }
    }

    $maint['row_class'] = '';

    if ($maint['maint_start'] > get_time()) {
        $maint['start_text'] = "+" . format_uptime($maint['maint_start'] - get_time());
    } else {
        $maint['start_text']  = "-" . format_uptime(get_time() - $maint['maint_start']);
        $maint['row_class']   = "warning";
        $maint['active_text'] = '<span class="label label-warning pull-right">active</span>';
    }

    if ($maint['maint_end'] > get_time()) {
        $maint['end_text'] = "+" . format_uptime($maint['maint_end'] - get_time());
    } else {
        $maint['end_text']    = "-" . format_uptime(get_time() - $maint['maint_end']);
        $maint['row_class']   = "disabled";
        $maint['active_text'] = '<span class="label label-disabled pull-right">ended</span>';
    }

}

/**
 * Humanize Alert Check
 *
 *   Process an array containing a row from `alert_checks` and in place to add/modify elements.
 *
 * @param array $check
 */
// TESTME needs unit testing
function humanize_alert_check(&$check)
{
    // Fetch the queries to build the alert table.
    [$query, $param, $query_count] = build_alert_table_query(['alert_test_id' => $check['alert_test_id']]);

    // Fetch a quick set of alert_status values to build the alert check status text
    $query             = str_replace(" * ", " `alert_status` ", $query);
    $check['entities'] = dbFetchRows($query, $param);

    $check['entity_status'] = ['up' => 0, 'down' => 0, 'unknown' => 0, 'delay' => 0, 'suppress' => 0];
    foreach ($check['entities'] as $alert_table_id => $alert_table_entry) {
        if ($alert_table_entry['alert_status'] == '1') {
            ++$check['entity_status']['up'];
        } elseif ($alert_table_entry['alert_status'] == '0') {
            ++$check['entity_status']['down'];
        } elseif ($alert_table_entry['alert_status'] == '2') {
            ++$check['entity_status']['delay'];
        } elseif ($alert_table_entry['alert_status'] == '3') {
            ++$check['entity_status']['suppress'];
        } else {
            ++$check['entity_status']['unknown'];
        }
    }

    $check['num_entities'] = count($check['entities']);

    if ($check['entity_status']['up'] == $check['num_entities']) {
        $check['class']          = "green";
        $check['html_row_class'] = "up";
    } elseif ($check['entity_status']['down'] > 0) {
        if ($check['severity'] === 'warn') {
            $check['class']          = "olive";
            $check['html_row_class'] = "warning";
        } else {
            $check['class']          = "red";
            $check['html_row_class'] = "error";
        }
    } elseif ($check['entity_status']['delay'] > 0) {
        $check['class']          = "orange";
        $check['html_row_class'] = "warning";
    } elseif ($check['entity_status']['suppress'] > 0) {
        $check['class']          = "purple";
        $check['html_row_class'] = "suppressed";
    } elseif ($check['entity_status']['up'] > 0) {
        $check['class']          = "green";
        $check['html_row_class'] = "success";
    } else {
        $check['entity_status']['class'] = "gray";
        $check['table_tab_colour']       = "#555555";
        $check['html_row_class']         = "disabled";
    }

    $check['status_numbers'] = '<span class="label label-success">' . $check['entity_status']['up'] . '</span><span class="label label-suppressed">' . $check['entity_status']['suppress'] .
                               '</span><span class="label label-error">' . $check['entity_status']['down'] . '</span><span class="label label-warning">' . $check['entity_status']['delay'] .
                               '</span><span class="label">' . $check['entity_status']['unknown'] . '</span>';

    // We return nothing, $check is modified in place.
}

/**
 * Humanize Alert
 *
 *   Process an array containing a row from `alert_entry` and `alert_entry-state` in place to add/modify elements.
 *
 * @param array $entry
 */
// TESTME needs unit testing
function humanize_alert_entry(&$entry)
{
    // Exit if already humanized
    if (isset($entry['humanized']) && $entry['humanized']) {
        return;
    }

    // Set colours and classes based on the status of the alert
    if ($entry['alert_status'] == '1') {
        // 1 means ok. Set blue text and disable row class
        $entry['class']          = "green";
        $entry['html_row_class'] = "up";
        $entry['status']         = "OK";
    } elseif ($entry['alert_status'] == '0') {
        // 0 means down. Set red text and error class
        //r($entry);
        if ($entry['severity'] === 'warn') {
            $entry['class']          = "olive";
            $entry['html_row_class'] = "warning";
            $entry['status']         = "WARNING";
        } else {
            $entry['class']          = "red";
            $entry['html_row_class'] = "error";
            $entry['status']         = "FAILED";
        }
    } elseif ($entry['alert_status'] == '2') {
        // 2 means the checks failed but we're waiting for x repetitions. set colour to orange and class to warning
        $entry['class']          = "orange";
        $entry['html_row_class'] = "warning";
        $entry['status']         = "DELAYED";
    } elseif ($entry['alert_status'] == '3') {
        // 3 means the checks failed but the alert is suppressed. set the colour to purple and the row class to suppressed
        $entry['class']          = "purple";
        $entry['html_row_class'] = "suppressed";
        $entry['status']         = "SUPPRESSED";
    } else {
        // Anything else set the colour to grey and the class to disabled.
        $entry['class']          = "gray";
        $entry['html_row_class'] = "disabled";
        $entry['status']         = "Unknown";
    }

    // Set the checked/changed/alerted entries to formatted date strings if they exist, else set them to never
    if (!isset($entry['last_checked']) || $entry['last_checked'] == '0') {
        $entry['checked'] = "<i>Never</i>";
    } else {
        $entry['checked'] = format_uptime(time() - $entry['last_checked'], 'short-3');
    }
    if (!isset($entry['last_changed']) || $entry['last_changed'] == '0') {
        $entry['changed'] = "<i>Never</i>";
    } else {
        $entry['changed'] = format_uptime(time() - $entry['last_changed'], 'short-3');
    }
    if (!isset($entry['last_alerted']) || $entry['last_alerted'] == '0') {
        $entry['alerted'] = "<i>Never</i>";
    } else {
        $entry['alerted'] = format_uptime(time() - $entry['last_alerted'], 'short-3');
    }
    if (!isset($entry['last_recovered']) || $entry['last_recovered'] == '0') {
        $entry['recovered'] = "<i>Never</i>";
    } else {
        $entry['recovered'] = format_uptime(time() - $entry['last_recovered'], 'short-3');
    }

    if (!isset($entry['ignore_until']) || $entry['ignore_until'] == '0') {
        $entry['ignore_until_text'] = "<i>Disabled</i>";
    } else {
        $entry['ignore_until_text'] = format_timestamp($entry['ignore_until']);
    }
    if (!isset($entry['ignore_until_ok']) || $entry['ignore_until_ok'] == '0') {
        $entry['ignore_until_ok_text'] = "<i>Disabled</i>";
    } else {
        $entry['ignore_until_ok_text'] = '<span class="purple">Yes</span>';
    }

    // Set humanized so we can check for it later.
    $entry['humanized'] = TRUE;

    // We return nothing as we're working on a reference.
}

/**
 * Humanize Device
 *
 *   Process an array containing a row from `devices` to add/modify elements.
 *
 * @param array $device
 *
 * @return none
 */
// TESTME needs unit testing
function humanize_device(&$device)
{
    global $config;

    // Exit if already humanized
    if (isset($device['humanized']) && $device['humanized']) {
        return;
    }

    // Expand the device state array from the php serialized string
    $device['state'] = safe_unserialize($device['device_state']);

    // Set the HTML class and Tab color for the device based on status
    if ($device['status'] == '0') {
        $device['row_class']      = "danger";
        $device['html_row_class'] = "error";
    } else {
        $device['row_class']      = "";
        $device['html_row_class'] = "up";
    }
    if ($device['ignore'] == '1' || (!is_null($device['ignore_until']) && strtotime($device['ignore_until']) > time())) {
        $device['html_row_class'] = "suppressed";
        if ($device['status'] == '1') {
            $device['html_row_class'] = "success";  // Why green for ignore? Confusing!
            // I chose this purely because using green for up and blue for up/ignore was uglier.
        } else {
            $device['row_class'] = "suppressed";
        }
    }
    if ($device['disabled'] == '1') {
        $device['row_class']      = "disabled";
        $device['html_row_class'] = "disabled";
    }

    // Set country code always lowercase
    if (isset($device['location_country'])) {
        $device['location_country'] = strtolower($device['location_country']);
    }

    // Set the name we print for the OS
    $device['os_text'] = $config['os'][$device['os']]['text'];

    // Format ASN as asdot if configured
    $device['human_local_as'] = $config['web_show_bgp_asdot'] ? bgp_asplain_to_asdot($device['bgpLocalAs']) : $device['bgpLocalAs'];

    // Mark this device as being humanized
    $device['humanized'] = TRUE;
}

/**
 * Humanize BGP Peer
 *
 * Returns a the $peer array with processed information:
 * row_class, table_tab_colour, state_class, admin_class
 *
 * @param array $peer
 *
 * @return array|void $peer
 *
 */
// TESTME needs unit testing
function humanize_bgp(&$peer)
{
    global $config;

    // Exit if already humanized
    if (isset($peer['humanized']) && $peer['humanized']) {
        return;
    }

    // Set colours and classes based on the status of the peer
    if ($peer['bgpPeerAdminStatus'] === 'stop' || $peer['bgpPeerAdminStatus'] === 'halted') {
        // Peer is disabled, set row to warning and text classes to muted.
        $peer['html_row_class'] = "disabled";
        $peer['state_class']    = "muted";
        $peer['admin_class']    = "muted";
        $peer['alert']          = 0;
        $peer['disabled']       = 1;
    } elseif ($peer['bgpPeerAdminStatus'] === "start" || $peer['bgpPeerAdminStatus'] === "running") {
        // Peer is enabled, set state green and check other things
        $peer['admin_class'] = "success";
        if ($peer['bgpPeerState'] === "established") {
            // Peer is up, set colour to blue and disable row class
            $peer['state_class']    = "success";
            $peer['html_row_class'] = "up";
        } else {
            // Peer is down, set colour to red and row class to error.
            $peer['state_class']    = "danger";
            $peer['html_row_class'] = "error";
        }
    }

    // Set text and colour if peer is same AS, private AS or external.
    if ($peer['bgpPeerRemoteAs'] == $peer['local_as']) {
        $peer['peer_type_class'] = "info";
        $peer['peer_type']       = "iBGP";
    } else {
        $peer['peer_type_class'] = "primary";
        $peer['peer_type']       = "eBGP";
    }

    // Private AS numbers, see: https://tools.ietf.org/html/rfc6996
    if (is_bgp_as_private($peer['bgpPeerRemoteAs'])) {
        $peer['peer_type_class'] = "warning";
        $peer['peer_type']       = "Priv " . $peer['peer_type'];
    }

    if (is_bgp_as_private($peer['local_as'])) {
        $peer['peer_local_class'] = "warning";
        $peer['peer_local_type']  = "private";
    } else {
        $peer['peer_local_class'] = "";
        $peer['peer_local_type']  = "public";
    }

    // Format (compress) the local/remote IPs if they're IPv6
    $peer['human_localip']  = ip_compress($peer['bgpPeerLocalAddr']);
    $peer['human_remoteip'] = ip_compress($peer['bgpPeerRemoteAddr']);

    // Format ASN as asdot if configured
    $peer['human_local_as']  = $config['web_show_bgp_asdot'] ? bgp_asplain_to_asdot($peer['local_as']) : $peer['local_as'];
    $peer['human_remote_as'] = $config['web_show_bgp_asdot'] ? bgp_asplain_to_asdot($peer['bgpPeerRemoteAs']) : $peer['bgpPeerRemoteAs'];

    // Set humanized entry in the array so we can tell later
    $peer['humanized'] = TRUE;
}

/**
 * Humanize port.
 *
 * Returns a $port array with processed information:
 * label, humans_speed, human_type, html_class and human_mac
 * row_class, table_tab_colour
 *
 * Escaping should not be done here, since these values are used in the API too.
 *
 * @param array $port
 *
 * @return void
 *
 */
// TESTME needs unit testing
function humanize_port(&$port) {
    global $config, $cache;

    // Exit if already humanized
    if (isset($port['humanized']) && $port['humanized']) {
        return;
    }

    // Pre-check if port attribs for device exist
    if (!isset($GLOBALS['cache']['devices_attribs'][$port['device_id']])) {
        cache_device_attribs_exist($port);
    }
    // if (!isset($GLOBALS['cache']['devices_attribs'][$port['device_id']]['port'])) {
    //   $GLOBALS['cache']['devices_attribs'][$port['device_id']]['port'] = dbExist('entity_attribs', '`entity_type` = ? AND `device_id` = ?', [ 'port', $port['device_id'] ]);
    // }
    // Speedup queries, when not exist attribs
    if (isset($GLOBALS['cache']['devices_attribs'][$port['device_id']]['port']) &&
        $GLOBALS['cache']['devices_attribs'][$port['device_id']]['port']) {
        $port['attribs'] = get_entity_attribs('port', $port['port_id']);
    } else {
        $port['attribs'] = [];
    }

    // If we can get the device data from the global cache, do it, else pull it from the db (mostly for external scripts)
    //$device = device_by_id_cache($port['device_id']);

    // Workaround for devices/ports who long time not updated and have empty port_label
    if (safe_empty($port['port_label']) || safe_empty($port['port_label_short']) || safe_empty($port['port_label_base'] . $port['port_label_num'])) {
        unset($port['port_label'], $port['port_label_short'], $port['port_label_base'], $port['port_label_num']);
        process_port_label($port, device_by_id_cache($port['device_id']));
    }

    // Set humanised values for use in UI
    $port['human_speed'] = humanspeed($port['ifSpeed']);
    $port['human_type']  = rewrite_iftype($port['ifType']);
    $port['html_class']  = port_html_class($port['ifOperStatus'], $port['ifAdminStatus'], $port['encrypted']);
    $port['human_mac']   = format_mac($port['ifPhysAddress']);

    // Set entity_* values for code which expects them.
    $port['entity_name']      = $port['port_label'];
    $port['entity_shortname'] = $port['port_label_short'];
    $port['entity_descr']     = $port['ifAlias'];

    $port['table_tab_colour'] = "#aaaaaa";
    $port['row_class']        = "";
    $port['icon']             = 'port-ignored'; // Default
    $port['admin_status']     = $port['ifAdminStatus'];
    if ($port['ifAdminStatus'] === "down") {
        $port['admin_status'] = 'disabled';
        $port['row_class']    = "disabled";
        $port['icon']         = 'port-disabled';
        $port['admin_class']  = "disabled";
    } elseif ($port['ifAdminStatus'] === "up") {
        $port['admin_status'] = 'enabled';
        $port['admin_class']  = 'primary';
        switch ($port['ifOperStatus']) {
            case 'up':
                $port['table_tab_colour'] = "#194B7F";
                $port['row_class']        = "ok";
                $port['icon']             = 'port-up';
                $port['oper_class']       = "primary";
                break;
            case 'monitoring':
                // This is monitoring ([e|r]span) ports
                $port['table_tab_colour'] = "#008C00";
                $port['row_class']        = "success";
                $port['icon']             = 'port-up';
                $port['oper_class']       = "success";
                break;
            case 'down':
                $port['table_tab_colour'] = "#cc0000";
                $port['row_class']        = "error";
                $port['icon']             = 'port-down';
                $port['oper_class']       = "error";
                break;
            case 'lowerLayerDown':
                $port['table_tab_colour'] = "#ff6600";
                $port['row_class']        = "warning";
                $port['icon']             = 'port-down';
                $port['oper_class']       = "warning";
                break;
            case 'testing':
            case 'unknown':
            case 'dormant':
            case 'notPresent':
                $port['table_tab_colour'] = "#85004b";
                $port['row_class']        = "info";
                $port['icon']             = 'port-ignored';
                $port['oper_class']       = "info";
                break;
        }
    }

    /* If the device is down, colour the row/tab as 'warning' meaning that the entity is down because of something below it.
    if ($device['status'] == '0') {
        $port['table_tab_colour'] = "#ff6600";
        //$port['row_class']        = "warning";
        $port['icon']             = 'port-ignored';
    } */
    if ($port['ignore'] == '1' && $port['row_class'] !== 'ok') {
        $port['row_class'] = "suppressed";
    }
    if ($port['disabled'] == '1') {
        $port['row_class'] = "disabled";
    }
    $port['in_rate']  = $port['ifInOctets_rate'] * 8;
    $port['out_rate'] = $port['ifOutOctets_rate'] * 8;

    // Colour in bps based on speed if > 50, else by UI convention.
    $in_perc  = float_div($port['in_rate'], $port['ifSpeed']) * 100;
    $out_perc = float_div($port['out_rate'], $port['ifSpeed']) * 100;

    if ($port['in_rate'] == 0) {
        $port['bps_in_style'] = '';
    } elseif ($in_perc < '50') {
        $port['bps_in_style'] = 'color: #008C00;';
    } else {
        $port['bps_in_style'] = 'color: ' . percent_colour($in_perc) . '; ';
    }

    // Colour out bps based on speed if > 50, else by UI convention.
    if ($port['out_rate'] == 0) {
        $port['bps_out_style'] = '';
    } elseif ($out_perc < '50') {
        $port['bps_out_style'] = 'color: #394182;';
    } else {
        $port['bps_out_style'] = 'color: ' . percent_colour($out_perc) . '; ';
    }

    // Colour in and out pps based on UI convention
    $port['pps_in_style']  = $port['ifInUcastPkts_rate'] == 0 ? '' : 'color: #740074;';
    $port['pps_out_style'] = $port['ifOutUcastPkts_rate'] == 0 ? '' : 'color: #FF7400;';

    $port['humanized'] = TRUE; /// Set this so we can check it later.

}

// Rewrite arrays
/// FIXME. Clean, rename GLOBAL $rewrite_* variables into $config['rewrite'] definition

$rewrite_liebert_hardware = [
    // UpsProducts - Liebert UPS Registrations
    'lgpSeries7200'                    => ['name' => 'Series 7200 UPS', 'type' => 'ups'],
    'lgpUPStationGXT'                  => ['name' => 'UPStationGXT UPS', 'type' => 'ups'],
    'lgpPowerSureInteractive'          => ['name' => 'PowerSure Interactive UPS', 'type' => 'ups'],
    'lgpNfinity'                       => ['name' => 'Nfinity UPS', 'type' => 'ups'],
    'lgpNpower'                        => ['name' => 'Npower UPS', 'type' => 'ups'],
    'lgpGXT2Dual'                      => ['name' => 'GXT2 Dual Inverter', 'type' => 'ups'],
    'lgpPowerSureInteractive2'         => ['name' => 'PowerSure Interactive 2 UPS', 'type' => 'ups'],
    'lgpNX'                            => ['name' => 'ENPC Nx UPS', 'type' => 'ups'],
    'lgpHiNet'                         => ['name' => 'Hiross HiNet UPS', 'type' => 'ups'],
    'lgpNXL'                           => ['name' => 'NXL UPS', 'type' => 'ups'],
    'lgpSuper400'                      => ['name' => 'Super 400 UPS', 'type' => 'ups'],
    'lgpSeries600or610'                => ['name' => 'Series 600/610 UPS', 'type' => 'ups'],
    'lgpSeries300'                     => ['name' => 'Series 300 UPS', 'type' => 'ups'],
    'lgpSeries610SMS'                  => ['name' => 'Series 610 Single Module System (SMS) UPS', 'type' => 'ups'],
    'lgpSeries610MMU'                  => ['name' => 'Series 610 Multi Module Unit (MMU) UPS', 'type' => 'ups'],
    'lgpSeries610SCC'                  => ['name' => 'Series 610 System Control Cabinet (SCC) UPS', 'type' => 'ups'],
    'lgpNXr'                           => ['name' => 'APM UPS', 'type' => 'ups'],
    // AcProducts - Liebert Environmental Air Conditioning Registrations
    'lgpAdvancedMicroprocessor'        => ['name' => 'Environmental Advanced Microprocessor control', 'type' => 'environment'],
    'lgpStandardMicroprocessor'        => ['name' => 'Environmental Standard Microprocessor control', 'type' => 'environment'],
    'lgpMiniMate2'                     => ['name' => 'Environmental Mini-Mate 2', 'type' => 'environment'],
    'lgpHimod'                         => ['name' => 'Environmental Himod', 'type' => 'environment'],
    'lgpCEMS100orLECS15'               => ['name' => 'Australia Environmental CEMS100 and LECS15 control', 'type' => 'environment'],
    'lgpIcom'                          => ['name' => 'Environmental iCOM control', 'type' => 'environment'],
    'lgpIcomPA'                        => ['name' => 'iCOM PA (Floor mount) Environmental', 'type' => 'environment'],
    'lgpIcomXD'                        => ['name' => 'iCOM XD (Rack cooling with compressor) Environmental', 'type' => 'environment'],
    'lgpIcomXP'                        => ['name' => 'iCOM XP (Pumped refrigerant) Environmental', 'type' => 'environment'],
    'lgpIcomSC'                        => ['name' => 'iCOM SC (Chiller) Environmental', 'type' => 'environment'],
    'lgpIcomCR'                        => ['name' => 'iCOM CR (Computer Row) Environmental', 'type' => 'environment'],
    // iCOM PA Family - Liebert PA (Floor mount) Environmental Registrations
    'lgpIcomPAtypeDS'                  => ['name' => 'DS Environmental', 'type' => 'environment'],
    'lgpIcomPAtypeHPM'                 => ['name' => 'HPM Environmental', 'type' => 'environment'],
    'lgpIcomPAtypeChallenger'          => ['name' => 'Challenger Environmental', 'type' => 'environment'],
    'lgpIcomPAtypePeX'                 => ['name' => 'PeX Environmental', 'type' => 'environment'],
    'lgpIcomPAtypeDeluxeSys3'          => ['name' => 'Deluxe System 3 Environmental', 'type' => 'environment'],
    'lgpIcomPAtypeJumboCW'             => ['name' => 'Jumbo CW Environmental', 'type' => 'environment'],
    'lgpIcomPAtypeDSE'                 => ['name' => 'DSE Environmental', 'type' => 'environment'],
    'lgpIcomPAtypePEXS'                => ['name' => 'PEX-S Environmental', 'type' => 'environment'],
    'lgpIcomPAtypePDX'                 => ['name' => 'PDX - PCW Environmental', 'type' => 'environment'],
    // iCOM XD Family - Liebert XD Environmental Registrations
    'lgpIcomXDtypeXDF'                 => ['name' => 'XDF Environmental', 'type' => 'environment'],
    'lgpIcomXDtypeXDFN'                => ['name' => 'XDFN Environmental', 'type' => 'environment'],
    'lgpIcomXPtypeXDP'                 => ['name' => 'XDP Environmental', 'type' => 'environment'],
    'lgpIcomXPtypeXDPCray'             => ['name' => 'XDP Environmental products for Cray', 'type' => 'environment'],
    'lgpIcomXPtypeXDC'                 => ['name' => 'XDC Environmental', 'type' => 'environment'],
    'lgpIcomXPtypeXDPW'                => ['name' => 'XDP-W Environmental', 'type' => 'environment'],
    // iCOM SC Family - Liebert SC (Chillers) Environmental Registrations
    'lgpIcomSCtypeHPC'                 => ['name' => 'HPC Environmental', 'type' => 'environment'],
    'lgpIcomSCtypeHPCSSmall'           => ['name' => 'HPC-S Small', 'type' => 'environment'],
    'lgpIcomSCtypeHPCSLarge'           => ['name' => 'HPC-S Large', 'type' => 'environment'],
    'lgpIcomSCtypeHPCR'                => ['name' => 'HPC-R', 'type' => 'environment'],
    'lgpIcomSCtypeHPCM'                => ['name' => 'HPC-M', 'type' => 'environment'],
    'lgpIcomSCtypeHPCL'                => ['name' => 'HPC-L', 'type' => 'environment'],
    'lgpIcomSCtypeHPCW'                => ['name' => 'HPC-W', 'type' => 'environment'],
    // iCOM CR Family - Liebert CR (Computer Row) Environmental Registrations
    'lgpIcomCRtypeCRV'                 => ['name' => 'CRV Environmental', 'type' => 'environment'],
    // PowerConditioningProducts - Liebert Power Conditioning Registrations
    'lgpPMP'                           => ['name' => 'PMP (Power Monitoring Panel)', 'type' => 'power'],
    'lgpEPMP'                          => ['name' => 'EPMP (Extended Power Monitoring Panel)', 'type' => 'power'],
    // Transfer Switch Products - Liebert Transfer Switch Registrations
    'lgpStaticTransferSwitchEDS'       => ['name' => 'EDS Static Transfer Switch', 'type' => 'network'],
    'lgpStaticTransferSwitch1'         => ['name' => 'Static Transfer Switch 1', 'type' => 'network'],
    'lgpStaticTransferSwitch2'         => ['name' => 'Static Transfer Switch 2', 'type' => 'network'],
    'lgpStaticTransferSwitch2FourPole' => ['name' => 'Static Transfer Switch 2 - 4Pole', 'type' => 'network'],
    // MultiLink Products - Liebert MultiLink Registrations
    'lgpMultiLinkBasicNotification'    => ['name' => 'MultiLink MLBN device proxy', 'type' => 'power'],
    // Power Distribution Products - Liebert Power Conditioning Distribution
    'lgpRackPDU'                       => ['name' => 'Rack Power Distribution Products (RPDU)', 'type' => 'pdu'],
    'lgpMPX'                           => ['name' => 'MPX product distribution (PDU)', 'type' => 'pdu'],
    'lgpMPH'                           => ['name' => 'MPH product distribution (PDU)', 'type' => 'pdu'],
    'lgpRackPDU2'                      => ['name' => 'Rack Power Distribution Products 2 (RPDU2)', 'type' => 'pdu'],
    'lgpRPC2kMPX'                      => ['name' => 'MPX product distribution 2 (PDU2)', 'type' => 'pdu'],
    'lgpRPC2kMPH'                      => ['name' => 'MPH product distribution 2 (PDU2)', 'type' => 'pdu'],
    // Combined System Product Registrations
    'lgpPMPandLDMF'                    => ['name' => 'PMP version 4/LDMF', 'type' => 'power'],
    'lgpPMPgeneric'                    => ['name' => 'PMP version 4', 'type' => 'power'],
    'lgpPMPonFPC'                      => ['name' => 'PMP version 4 for FPC', 'type' => 'power'],
    'lgpPMPonPPC'                      => ['name' => 'PMP version 4 for PPC', 'type' => 'power'],
    'lgpPMPonFDC'                      => ['name' => 'PMP version 4 for FDC', 'type' => 'power'],
    'lgpPMPonRDC'                      => ['name' => 'PMP version 4 for RDC', 'type' => 'power'],
    'lgpPMPonEXC'                      => ['name' => 'PMP version 4 for EXC', 'type' => 'power'],
    'lgpPMPonSTS2'                     => ['name' => 'PMP version 4 for STS2', 'type' => 'power'],
    'lgpPMPonSTS2PDU'                  => ['name' => 'PMP version 4 for STS2/PDU', 'type' => 'power'],
];

// Rewrite functions

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_extreme_hardware($hardware)
{

    $hardware = str_replace('EXTREME-BASE-MIB::', '', $hardware);

    // Common replaces
    $from     = [];
    $to       = [];
    $from[]   = '/^summit/';
    $to[]     = 'Summit ';               // summitX440G2-48t-10G4-DC
    $from[]   = '/^x/';
    $to[]     = 'Summit X';              // x690-48x-4q-2c
    $from[]   = '/^isw/';
    $to[]     = 'Industrial Switch isw'; // isw-8GP-G4
    $from[]   = '/^one/';
    $to[]     = 'One';                   // oneC-A-600
    $from[]   = '/^aviatCtr/';
    $to[]     = 'CTR';                   // aviatCtr-8440
    $from[]   = '/^e4g/';
    $to[]     = 'E4G';                   // e4g-200-12x
    $from[]   = '/^bdx8/';
    $to[]     = 'BlackDiamond X';        // bdx8
    $from[]   = '/^bd/';
    $to[]     = 'BlackDiamond ';         // bd20804
    $from[]   = '/^blackDiamond/';
    $to[]     = 'BlackDiamond ';         // blackDiamond6816
    $from[]   = '/^ags/';
    $to[]     = 'AGS';                   // ags150-24p
    $from[]   = '/^altitude/';
    $to[]     = 'Altitude ';             // altitude4700
    $from[]   = '/^sentriant/';
    $to[]     = 'Sentriant ';            // sentriantPS200v1
    $from[]   = '/^nwi/';
    $to[]     = 'NWI';                   // nwi-e450a
    $from[]   = '/^enetSwitch/';
    $to[]     = 'EnetSwitch ';           // enetSwitch24Port
    $hardware = preg_replace($from, $to, $hardware);

    return $hardware;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_cpqida_hardware($hardware)
{
    return array_str_replace($GLOBALS['config']['rewrites']['cpqida_hardware'], $hardware);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_liebert_hardware($hardware)
{
    global $rewrite_liebert_hardware;

    if (isset($rewrite_liebert_hardware[$hardware])) {
        $hardware = $rewrite_liebert_hardware[$hardware]['name'];
    }

    return ($hardware);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_breeze_type($type)
{
    $type = strtolower($type);

    return $GLOBALS['config']['rewrites']['breeze_type'][$type] ?? strtoupper($type);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_unix_hardware($descr, $hw = NULL)
{

    $hardware = !empty($hw) ? trim($hw) : 'Generic';

    if (preg_match('/i[3456]86/i', $descr)) {
        $hardware .= ' x86 [32bit]';
    } elseif (preg_match('/x86_64|amd64/i', $descr)) {
        $hardware .= ' x86 [64bit]';
    } elseif (stripos($descr, 'ia64') !== FALSE) {
        $hardware .= ' IA [64bit]';
    } elseif (stripos($descr, 'ppc') !== FALSE) {
        $hardware .= ' PPC [32bit]';
    } elseif (stripos($descr, 'sparc32') !== FALSE) {
        $hardware .= ' SPARC [32bit]';
    } elseif (stripos($descr, 'sparc64') !== FALSE) {
        $hardware .= ' SPARC [64bit]';
    } elseif (stripos($descr, 'mips64') !== FALSE) {
        $hardware .= ' MIPS [64bit]';
    } elseif (stripos($descr, 'mips') !== FALSE) {
        $hardware .= ' MIPS [32bit]';
    } elseif (stripos($descr, 'aarch64') !== FALSE) {
        $hardware .= ' AArch64';
    } elseif (preg_match('/armv(\d+)/i', $descr, $matches)) {
        $hardware .= ' ARMv' . $matches[1];
    } elseif (stripos($descr, 'armv') !== FALSE) {
        $hardware .= ' ARM';
    }

    return $hardware;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_ftos_vlanid($device, $ifindex) {
    // damn DELL use them one known indexes
    //dot1qVlanStaticName.1107787777 = Vlan 1
    //dot1qVlanStaticName.1107787998 = mgmt
    if ($ifindex > 4096 &&
        $ftos_vlan = dbFetchCell('SELECT `ifName` FROM `ports` WHERE `device_id` = ? AND `ifIndex` = ?', [ $device['device_id'], $ifindex ])) {
        return explode(' ', $ftos_vlan)[1];
    }

    return $ifindex;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_iftype($type)
{
    return array_key_replace($GLOBALS['config']['rewrites']['iftype'], $type);
}

// NOTE. For graphs use $escape = FALSE
// TESTME needs unit testing
function rewrite_ifname($ifname, $escape = TRUE)
{
    $inf = array_str_replace($GLOBALS['config']['rewrites']['ifname'], $ifname);
    //$inf = array_preg_replace($GLOBALS['rewrite_ifname_regexp'], $inf); // use os definitions instead
    $inf = preg_replace('/\ {2,}/', ' ', $inf); // Clean multiple spaces
    if ($escape) {
        // By default use htmlentities
        $inf = escape_html($inf);
    }
    if ($ifname !== $inf) {
        // Fixes some incorrect rewrites
        if (str_starts_with($inf, 'vEther')) {
            $inf = str_replace('vEther', 'vether', $inf);
        }
    }

    return trim($inf);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_adslLineType($adslLineType)
{
    $rewrite_adslLineType = $GLOBALS['config']['rewrites']['adslLineType'];

    if (isset($rewrite_adslLineType[$adslLineType])) {
        $adslLineType = $rewrite_adslLineType[$adslLineType];
    }

    return $adslLineType;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function short_hostname($hostname, $len = NULL, $escape = TRUE)
{
    $len = (is_numeric($len) ? (int)$len : (int)$GLOBALS['config']['short_hostname']['length']);

    if (function_exists('custom_shorthost')) {
        $short_hostname = custom_shorthost($hostname, $len);
    } elseif (function_exists('custom_short_hostname')) {
        $short_hostname = custom_short_hostname($hostname, $len);
    } else {

        if (get_ip_version($hostname)) {
            return $hostname;
        } // If hostname is IP address, always return full hostname

        $parts          = explode('.', $hostname);
        $short_hostname = $parts[0];
        $i              = 1;
        while ($i < count($parts) && strlen($short_hostname . '.' . $parts[$i]) < $len) {
            $short_hostname .= '.' . $parts[$i];
            $i++;
        }
    }
    if ($escape) {
        $short_hostname = escape_html($short_hostname);
    }

    return $short_hostname;
}

// DOCME needs phpdoc block
// TESTME needs unit testing
// NOTE, this is shorting for ifAlias! Can be rename to short_ifalias() ?
function short_port_descr($descr, $len = NULL, $escape = TRUE)
{
    $len = (is_numeric($len) ? (int)$len : (int)$GLOBALS['config']['short_port_descr']['length']);

    if (function_exists('custom_short_port_descr')) {
        $descr = custom_short_port_descr($descr, $len);
    } else {

        [$descr] = explode("(", $descr);
        [$descr] = explode("[", $descr);
        [$descr] = explode("{", $descr);
        [$descr] = explode("|", $descr);
        [$descr] = explode("<", $descr);
        $descr = truncate(trim($descr), $len, '');
    }
    if ($escape) {
        $descr = escape_html($descr);
    }

    return $descr;
}

// NOTE. For graphs use $escape = FALSE
// NOTE. short_ifname() differs from short_port_descr()
// short_ifname('FastEternet0/10') == 'Fa0/10'
// DOCME needs phpdoc block
// TESTME needs unit testing
function short_ifname($if, $len = NULL, $escape = TRUE)
{
    $len = (is_numeric($len) ? (int)$len : FALSE);

    $if = rewrite_ifname($if, $escape);

    $if = array_str_replace($GLOBALS['config']['rewrites']['shortif'], $if);
    $if = array_preg_replace($GLOBALS['config']['rewrites']['shortif_regexp'], $if);
    if ($len) {
        $if = truncate($if, $len, '');
    }

    return $if;
}

/**
 * Rewrite name or description of an entity.
 * Note, not same as entity_rewrite(), since this function impersonally of entity db entry.
 *
 * @param string      $string      Entity name or description
 * @param null|string $entity_type Entity type, by default use common rewrites
 * @param bool        $escape      Escape return string
 *
 * @return string
 */
function rewrite_entity_name($string, $entity_type = NULL, $escape = TRUE)
{
    $string = array_str_replace($GLOBALS['config']['rewrites']['entity_name'], $string, TRUE); // case-sensitive
    $string = array_preg_replace($GLOBALS['config']['rewrites']['entity_name_regexp'], $string);

    $string = preg_replace('/\s{2,}/', ' ', $string);                 // multiple spaces to single space
    $string = preg_replace('/([a-z])([A-Z]{2,})/', '$1 $2', $string); // turn "fixedAC" into "fixed AC"

    // Entity specific rewrites (additionally to common)
    switch ($entity_type) {
        case 'port':
            return rewrite_ifname($string, $escape);
            break;

        case 'processor':
            $string = str_replace(["Routing Processor", "Route Processor", "Switching Processor"], ["RP", "RP", "SP"], $string);
            break;

        case 'storage':
            $string = rewrite_storage($string);
            break;
    }

    if ($escape) {
        $string = escape_html($string);
    }

    return trim($string);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_storage($string)
{
    $string = preg_replace('/.*mounted on: (.*)/', "\\1", $string);                 // JunOS
    $string = preg_replace("/(.*), type: (.*), dev: (.*)/", "\\1", $string);        // FreeBSD: '/mnt/Media, type: zfs, dev: Media'
    $string = preg_replace("/(.*) Label:(.*) Serial Number (.*)/", "\\1", $string); // Windows: E:\ Label:Large Space Serial Number 26ad0d98

    return trim($string);
}

// DOCME needs phpdoc block
// TESTME needs unit testing
function rewrite_location($location)
{
    global $config, $attribs;

    // Allow override sysLocation from DB.
    if ($attribs['override_sysLocation_bool']) {
        $new_location = $attribs['override_sysLocation_string'];
        $by           = 'DB override';
    } else {
        $location = str_replace(['\"', '"'], '', $location);

        $new_location = array_preg_replace($config['location']['rewrite_regexp'], $location);
        //$new_location = array_preg_replace($config['rewrites']['location_regexp'], $location);
        if ($new_location !== $location) {
            $location = $new_location;
            print_debug("sysLocation rewritten from '$location' to '$new_location' by \$config['location']['rewrite_regexp'].");
        }
        unset($new_location); // Still allow location maps
    }

    // This will call a user-definable function to rewrite the location however the user wants.
    // FIXME. Hard for use by users, better just use regexp
    if (!isset($new_location) && function_exists('custom_rewrite_location')) {
        $new_location = custom_rewrite_location($location);
        $by           = 'function custom_rewrite_location()';
    }
    // This uses a statically defined array to map locations.
    if (!isset($new_location)) {
        if (isset($config['location']['map'][$location])) {
            $new_location = $config['location']['map'][$location];
            $by           = '$config[\'location\'][\'map\']';
        } elseif (isset($config['location']['map_regexp'])) {
            foreach ($config['location']['map_regexp'] as $pattern => $entry) {
                if (preg_match($pattern, $location)) {
                    $new_location = $entry;
                    $by           = '$config[\'location\'][\'map_regexp\']';
                    break; // stop foreach
                }
            }
        }
    }

    if (isset($new_location)) {
        print_debug("sysLocation rewritten from '$location' to '$new_location' by $by.");
        $location = $new_location;
    }
    return $location;
}

/**
 * This function cleanup vendor/manufacturer name and
 * unification multiple same names to single common vendor name.
 */
function rewrite_vendor($string)
{
    global $config;

    $clean_name = $string;

    // By first, clean all additional abbreviations in vendor name
    $clean_array = [
      '/(?:\s+|,\s*)(?:inc|corp|comm|co|elec|tech|llc)(?![a-z])/i'                                           => '', // abbreviations
      '/(?:\s+|,\s*)(?:Systems|Computer|Corporation|Company|Communications|Networks|Electronics)(?![a-z])/i' => '',
    ];
    foreach ($clean_array as $pattern => $replace) {
        if (preg_match_all($pattern, $string, $matches)) {
            foreach ($matches[0] as $match) {
                $clean_name = str_replace($match, $replace, $clean_name);
            }
        }
    }
    $clean_name = trim($clean_name, " \t\n\r\0\x0B.,;'\"()"); // Clean punctuations after rewrites

    // Remove string duplicates
    $clean_name_array = array_unique(explode(' ', $clean_name));
    $clean_name       = implode(' ', $clean_name_array);

    // Now try to find exist vendor definition
    $clean_key = safename(strtolower($clean_name));
    if (isset($config['vendors'][$clean_key])) {
        // Founded definition by original string
        return $config['vendors'][$clean_key]['name'];
    }
    $key = safename(strtolower($string));
    if (isset($config['vendors'][$key])) {
        // Founded definition by clean string
        return $config['vendors'][$key]['name'];
    }

    // Now try to find definition by full search in definitions
    foreach ($config['vendors'] as $vendor_key => $entry) {
        if (strlen($entry['name']) <= 3) {
            // In case, when vendor name too short, that seems as abbr, ie GE
            if (strcasecmp($clean_name, $entry['name']) == 0 || // Cleaned string
                strcasecmp($string, $entry['name']) == 0)       // Original string
            {
                // Founded in definitions
                return $entry['name'];
            }
            $search_array = [];
        } else {
            $search_array = [$entry['name']];
        }

        if (isset($entry['full_name'])) {
            $search_array[] = $entry['full_name'];
        } // Full name of vendor
        if (isset($entry['alternatives'])) {
            $search_array = array_merge($search_array, $entry['alternatives']);
        } // Alternative (possible) names of vendor

        if (str_istarts($clean_name, $search_array) || // Cleaned string
            str_istarts($string, $search_array))       // Original string
        {
            // Founded in definitions
            return $entry['name'];
        }
    }

    if (strlen($clean_name) < 5 ||
        preg_match('/^([A-Z0-9][a-z]+[\ \-]?[A-Z]+[a-z]*|[A-Z0-9]+[\ \-][A-Za-z]+|[A-Z]{2,}[a-z]+)/', $clean_name)) {
        // This is MultiCase name or small name, keeps as is
        //echo("\n"); print_error($clean_name . ': MULTICASE ');
        return $clean_name;
    } else {
        // Last, just return cleaned name
        //echo("\n"); print_error($clean_name . ': UCWORDS');
        return ucwords(strtolower($clean_name));
    }
}

function rewrite_vm_guestos($vm_guestos, $vm_type = '') {
    global $config;

    if (safe_empty($vm_guestos)) {
        return 'Unknown';
    }
    if (str_contains(strtolower($vm_guestos), 'tools not installed')) {
        return 'Unknown ('.nicecase($vm_type).' Tools not installed)';
    }
    if (str_contains(strtolower($vm_guestos), 'tools not running')) {
        return 'Unknown ('.nicecase($vm_type).' Tools not running)';
    }

    if (isset($config['vmware_guestid'][$vm_guestos]) && strtolower($vm_type) === 'vmware') {
        return $config['vmware_guestid'][$vm_guestos];
    }

    return $vm_guestos;
}

// Underlying rewrite functions

/**
 * Replace strings equals to key string with appropriate value from array: key -> replace
 *
 * @param array  $array  Array with string and replace string (key -> replace)
 * @param string $string String subject where replace
 *
 * @return string           Result string with replaced strings
 */
function array_key_replace($array, $string)
{
    if (array_key_exists($string, (array)$array)) {
        $string = $array[$string];
    }
    return $string;
}

/**
 * Replace strings matched by key string with appropriate value from array: string -> replace
 * Note, by default CASE INSENSITIVE
 *
 * @param array  $array          Array with string and replace string (string -> replace)
 * @param string $string         String subject where replace
 * @param bool   $case_sensitive Case sensitive (default FALSE)
 *
 * @return string           Result string with replaced strings
 */
function array_str_replace($array, $string, $case_sensitive = FALSE)
{
    $search  = [];
    $replace = [];

    foreach ((array)$array as $key => $entry) {
        $search[]  = $key;
        $replace[] = $entry;
    }

    if ($case_sensitive) {
        $string = str_replace($search, $replace, $string);
    } else {
        $string = str_ireplace($search, $replace, $string);
    }

    return $string;
}

/**
 * Replace strings matched by pattern key with appropriate value from array: pattern -> replace
 *
 * @param array  $array  Array with pattern and replace string (pattern -> replace)
 * @param string $string String subject where replace
 *
 * @return string           Result string with replaced patterns
 */
function array_preg_replace($array, $string)
{
    foreach ((array)$array as $search => $replace) {
        $string = preg_replace($search, $replace, $string);
    }

    return $string;
}

/**
 * Replace tag(s) inside string with an appropriate key from array: %tag% -> $array['tag']
 * Note, not exist tags in an array will clean from string!
 *
 * @param array        $array     Array with keys appropriate for tags, which used for replace
 * @param string|array $string    String with tag(s) for replace (between percent sign, ie: %key%)
 * @param string       $tag_scope Scope string for detect tag(s), default: %
 *
 * @return string|array           Result string with replaced tags
 */
function array_tag_replace($array, $string, $tag_scope = '%')
{
    // If passed array, do tag replace recursive (for values only)
    if (is_array($string)) {
        foreach ($string as $key => $value) {
            $string[$key] = array_tag_replace($array, $value, $tag_scope);
        }
        return $string;
    }

    // Keep non string values as is
    if (!is_string($string)) {
        return $string;
    }

    $new_array = [];

    if (str_contains($string, $tag_scope)) {
        // Generate a new array of tags including delimiter
        foreach ($array as $key => $value) {
            $new_array[$tag_scope . $key . $tag_scope] = $value;
        }

        // Replace tags
        $string = array_str_replace($new_array, $string, TRUE);
    }

    // Remove unused tags
    if ($tag_scope !== '/') {
        $pattern_clean = '/' . $tag_scope . '\S+?' . $tag_scope . '/';
    } else {
        $pattern_clean = '%/\S+?/%';
    }

    return preg_replace($pattern_clean, '', $string);
}

/**
 * Mostly same as array_tag_replace() but with extra rawurlencode() for %%tag%%.
 *
 * @param array $array
 * @param string $string
 *
 * @return string
 */
function array_tag_replace_encode($array, $string) {
    if (str_contains($string, '%%')) {
        // urlencode tags for url
        $encoded_tags = (array)$array;
        array_walk_recursive($encoded_tags, function (&$tag) {
            if (is_string($tag)) {
                $tag = rawurlencode($tag);
            }
        });
        $string = array_tag_replace($encoded_tags, $string, '%%');
        unset($encoded_tags);
    }

    return array_tag_replace($array, $string);
}

/**
 * @param string $code
 *
 * @return string
 */
function country_from_code($code)
{
    global $config;

    $countries = $config['rewrites']['countries'];
    $code      = trim($code);
    switch (strlen($code)) {
        case 0:
        case 1:
            return "Unknown";
        case 2: // ISO 2
        case 3: // ISO 3
            // Return country by code
            $code = strtoupper($code);
            if (array_key_exists($code, $countries)) {
                return $countries[$code];
            }
            return "Unknown";
        default:
            // Try to search by country name
            $names = array_unique(array_values($countries));
            foreach ($names as $country) {
                if (str_istarts($country, $code) || str_istarts($code, $country)) {
                    return $country;
                }
            }
            return $code;
    }
}

// EOF
