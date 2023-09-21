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
            $mac = format_mac($vars['entity_id']);
            // Other way by using Pear::Net_MAC, see here: http://pear.php.net/manual/en/package.networking.net-mac.importvendors.php
            $url      = 'https://api.macvendors.com/' . urlencode($mac);
            $response = get_http_request($url);
            if ($response) {
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
            $reverse_dns = gethostbyaddr6($ip);
            if ($reverse_dns) {
                $response .= '<h4>' . $reverse_dns . '</h4><hr />' . PHP_EOL;
            }

            // WHOIS
            if (!isset($config['http_proxy']) && is_executable($config['whois'])) {
                // Use direct whois cmd query (preferred)
                // NOTE, for now not tested and not supported for KRNIC, ie: 202.30.50.0, 2001:02B8:00A2::
                $cmd   = $config['whois'] . ' ' . $ip;
                $whois = external_exec($cmd);

                $multi_whois = explode('# start', $whois); // Some time whois return multiple (ie: whois 8.8.8.8), than use last
                if (safe_count($multi_whois) > 1) {
                    $whois = array_pop($multi_whois);
                }

                $org = 0;
                foreach (explode("\n", $whois) as $line) {
                    if (preg_match('/^(\w[\w\s\-\/]+):.*$/', $line, $matches)) {
                        if (in_array($matches[1], ['Ref', 'source', 'nic-hdl-br'])) {
                            if ($org === 1) {
                                $response .= PHP_EOL;
                                $org++;
                                continue;
                            }
                            break;
                        }
                        if (in_array($matches[1], ['Organization', 'org', 'mnt-irt'])) {
                            $org++; // has org info
                        } elseif ($matches[1] === 'Comment') {
                            continue; // skip comments
                        }
                        $response .= $line . PHP_EOL;
                    }
                }
            } else {
                // Use RIPE whois API query
                $whois_url = 'https://stat.ripe.net/data/whois/data.json?';
                $whois_url .= 'sourceapp=' . urlencode(OBSERVIUM_PRODUCT . '-' . get_unique_id());
                $whois_url .= '&resource=' . urlencode($ip);

                if ($request = get_http_request($whois_url)) {
                    $request = safe_json_decode($request); // Convert to array
                    if ($request['status'] === 'ok' && safe_count($request['data']['records'])) {
                        $whois_parts = [];
                        foreach ($request['data']['records'] as $i => $parts) {
                            $key = $parts[0]['key'];

                            if (in_array($key, ['NetRange', 'inetnum', 'inet6num'])) {
                                $org = 0;

                                $whois_parts[0] = '';
                                foreach ($parts as $part) {
                                    if (in_array($part['key'], ['Ref', 'source', 'nic-hdl-br'])) {
                                        break;
                                    }
                                    if (in_array($part['key'], ['Organization', 'org', 'mnt-irt'])) {
                                        $org      = 1; // has org info
                                        $org_name = $part['value'];
                                    } elseif ($part['key'] === 'Comment') {
                                        continue; // skip comments
                                    }
                                    $whois_parts[0] .= sprintf('%-16s %s' . PHP_EOL, $part['key'] . ':', $part['value']);
                                }

                            } elseif ($org === 1 && $key === 'OrgName' && strpos($org_name, $parts[0]['value']) === 0) {

                                $whois_parts[1] = '';
                                foreach ($parts as $part) {
                                    if (in_array($part['key'], ['Ref', 'source', 'nic-hdl-br'])) {
                                        break;
                                    }
                                    if ($part['key'] === 'Comment') {
                                        continue; // skip comments
                                    }
                                    $whois_parts[1] .= sprintf('%-16s %s' . PHP_EOL, $part['key'] . ':', $part['value']);
                                }

                                break;
                            }
                        }
                        $response .= implode(PHP_EOL, $whois_parts);

                        //print_vars($request['data']['records']);
                    }
                }
            }

            if ($response) {
                $cache_entry = '<pre class="small">' . escape_html($response) . '</pre>';
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
