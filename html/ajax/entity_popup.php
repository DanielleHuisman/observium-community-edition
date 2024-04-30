<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

require_once("../../includes/observium.inc.php");

include($config['html_dir'] . "/includes/authenticate.inc.php");

if (!$_SESSION['authenticated']) {
    print_error('Session expired, please log in again!');
    exit;
}

ob_start();

$vars = get_vars([ 'JSON', 'POST', 'GET' ]);

$vars['page'] = "popup";

if (isset($vars['debug'])) {
    r($vars);
}

switch ($vars['entity_type']) {
    case "port":
        if (is_numeric($vars['entity_id']) && (port_permitted($vars['entity_id']))) {
            $port = get_port_by_id($vars['entity_id']);
            echo generate_port_popup($port);
        } else {
            print_warning("You are not permitted to view this port.");
        }
        break;

    case "link":
        if (is_numeric($vars['entity_id_a']) && (port_permitted($vars['entity_id_a']))) {
            $port = get_port_by_id($vars['entity_id_a']);
            echo generate_port_popup($port);
        } else {
            print_warning("You are not permitted to view this port.");
        }

        if (is_numeric($vars['entity_id_b']) && (port_permitted($vars['entity_id_b']))) {
            $port = get_port_by_id($vars['entity_id_b']);
            echo generate_port_popup($port, '', 'none'); // suppress graph for b side of link
        } else {
            print_warning("You are not permitted to view this port.");
        }
        break;


    case "device":
        if (is_numeric($vars['entity_id']) && device_permitted($vars['entity_id'])) {
            $device = device_by_id_cache($vars['entity_id']);
            echo generate_device_popup($device, $vars);
        } else {
            print_warning("You are not permitted to view this device.");
        }
        break;

    case "group":
        if (is_numeric($vars['entity_id']) && $_SESSION['userlevel'] >= 5) {
            $group = get_group_by_id($vars['entity_id']);
            echo generate_group_popup_header($group);
        } else {
            print_warning("You are not permitted to view this group.");
        }
        break;

    case "mac":
        if (preg_match('/^' . OBS_PATTERN_MAC . '$/i', $vars['entity_id'])) {
            // Other way by using Pear::Net_MAC, see here: http://pear.php.net/manual/en/package.networking.net-mac.importvendors.php
            if ($response = get_http_def('macvendors_mac', [ 'mac' => format_mac($vars['entity_id']) ])) {
                echo 'MAC vendor: ' . escape_html($response);
            } else {
                echo 'Not Found';
            }
        } else {
            echo 'Not correct MAC address';
        }
        break;

    case "ip":
        $ip = explode('/', $vars['entity_id'])[0];

        if ($ip_version = get_ip_version($ip)) {
            $cache_key   = 'response_' . $vars['entity_type'] . '_' . $ip;
            $cache_entry = get_cache_session($cache_key);
            //r($cache_entry);
            if (ishit_cache_session()) {
                //echo '<h2>CACHED!</h2>';
                echo $cache_entry;
                exit;
            }

            $response    = '';
            if ($reverse_dns = gethostbyaddr6($ip)) {
                $response .= '<h4>' . escape_html($reverse_dns) . '</h4><hr />' . PHP_EOL;
            }

            // WHOIS
            $response .= escape_html(ip_whois($ip));

            if ($response) {
                $cache_entry = '<pre class="small">' . $response . '</pre>';
                // @session_start();
                // $_SESSION['cache']['response_' . $vars['entity_type'] . '_' . $ip] = '<pre class="small">' . $response . '</pre>';
                // session_commit();
            } else {
                $cache_entry = 'Not Found';
                //echo 'Not Found';
            }
            set_cache_session($cache_key, $cache_entry);
            echo $cache_entry;
        } else {
            echo 'Not correct IP address';
        }
        break;

    case 'autodiscovery':
        // if (isset($vars['autodiscovery_id']))
        // {
        //   $vars['entity_id'] = $vars['autodiscovery_id'];
        // }
        //r($vars);
        if (is_numeric($vars['entity_id']) &&
            $_SESSION['userlevel'] > 7) {

            $cache_key   = 'response_' . $vars['entity_type'] . '_' . $vars['entity_id'];
            $cache_entry = get_cache_session($cache_key);
            //r($cache_entry);
            if (ishit_cache_session()) {
                //echo '<h2>CACHED!</h2>';
                echo $cache_entry;
                exit;
            }

            $entry    = dbFetchRow('SELECT `remote_hostname`, `remote_ip`, `last_reason`, UNIX_TIMESTAMP(`last_checked`) AS `last_checked_unixtime` FROM `autodiscovery` WHERE `autodiscovery_id` = ?', [$vars['entity_id']]);
            $hostname = $entry['remote_hostname'];
            $ip       = $entry['remote_ip'];

            //r($entry);
            // 'ok','no_xdp','no_fqdn','no_dns','no_ip_permit','no_ping','no_snmp','no_db','duplicated','unknown'
            switch ($entry['last_reason']) {
                case 'ok':
                    $last_reason = "Remote host $hostname ($ip) successfully added to db.";
                    break;

                case 'no_xdp':
                    $last_reason = 'Remote platform ignored by XDP autodiscovery configuration.';
                    break;

                case 'no_fqdn':
                    $last_reason = "Remote IP $ip does not seem to have FQDN.";
                    break;

                case 'no_dns':
                    $last_reason = "Remote host $hostname not resolved.";
                    break;

                case 'no_ip_permit':
                    $last_reason = "Remote IP $ip not permitted in autodiscovery configuration or invalid.";
                    break;

                case 'no_ping':
                    $last_reason = "Remote host $hostname not pingable.";
                    break;

                case 'no_snmp':
                    $last_reason = "Remote host $hostname not SNMPable by configured auth parameters.";
                    break;

                case 'duplicated':
                    $last_reason = "Remote host $hostname ($ip) already found in db.";
                    break;

                case 'no_db':
                    $last_reason = "Remote host $hostname ($ip) success, but not added by an DB error.";
                    break;

                default:
                    $last_reason = "Remote host $hostname ($ip) not added by unknown reason.";
                    break;
            }
            $cache_entry = '<div style="width: 280px;">';
            $cache_entry .= "<h4>" . escape_html($last_reason) . "</h4><hr />";
            $cache_entry .= '<strong style="margin-left: 10px;">Autodiscovery checked:</strong> ' .
                            format_uptime(get_time() - $entry['last_checked_unixtime'], 'shorter') . ' ago</span>';
            $cache_entry .= '</div>';
            //$cache_entry .= build_table_row($entry);
            set_cache_session($cache_key, $cache_entry);
            echo $cache_entry;
        } else {
            print_warning("You are not permitted to view this entry.");
        }
        break;

    case 'latlon':
        // Check if latitude and longitude are set
        if (!isset($vars['lat'], $vars['lon'])) {
            echo "ERROR: Latitude and Longitude required";
            break;
        }

        $location  = [];

        // Fetch devices and their locations
        $devices = dbFetchRows("SELECT * FROM `devices` LEFT JOIN `devices_locations` USING (`device_id`) " .
                               generate_where_clause(generate_query_permitted_ng(['devices']),
                                                     "location_lat = ? AND location_lon = ?"),
                               [$vars['lat'], $vars['lon']]);

        foreach ($devices as $device) {
            if (!$config['web_show_disabled'] && $device["disabled"]) {
                continue;
            }

            if ($device['location'] != '') {
                $location['location_name'] = $device['location'];
            }

            // Categorize devices as up or down
            if ($device["status"] == "0" && $device["ignore"] == "0") {
                $location["down_hosts"][] = $device;
            } else {
                $location["up_hosts"][] = $device;
            }
        }

        // Display location information
        if (!isset($location['location_name'])) {
            echo "Unknown Location";
        } else {
            $num_up      = safe_count($location["up_hosts"]);
            $num_down    = safe_count($location["down_hosts"]);
            $total_hosts = $num_up + $num_down;

            $state = 'unknown';
            if ($num_down > 0) {
                $state = 'down';
            } elseif ($num_up > 0) {
                $state = 'up';
            }

            // Generate tooltip content
            $tooltip = "<h3>" . escape_html($location['location_name']) . "</h3><hr />";
            $tooltip .= '<p><span class="label label-success">Up ' . $num_up . '</span>
                            <span class="label label-error">Down ' . $num_down . '</span></p>';

            if($num_up < 50) {
                foreach ($location["up_hosts"] as $host) {
                    $tooltip .= '<span class="label label-success">' . escape_html($host['hostname']) . '</span> ';
                }
            }
            foreach ($location["down_hosts"] as $host) {
                $tooltip .= '<span class="label label-error">' . escape_html($host['hostname']) . '</span> ';
            }
            //$tooltip .= "<p><small>Coordinates: ".$vars['lat'].",".$vars['lon']."</small></p>";

            echo $tooltip;
        }

        break;

    default:
        if (isset($config['entities'][$vars['entity_type']])) {
            $entity_ids = [];
            foreach (explode(',', $vars['entity_id']) as $id) {
                // Filter permitted IDs
                if (is_numeric($id) && (is_entity_permitted($id, $vars['entity_type']))) {
                    $entity_ids[] = $id;
                }
            }
            if (count($entity_ids)) {
                echo generate_entity_popup_multi($entity_ids, $vars);
            } else {
                print_warning("You are not permitted to view this entity.");
            }
        } else {
            print_error("Unknown entity type.");
        }
        break;
}
exit;

// EOF
