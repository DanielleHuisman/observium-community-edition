<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

function generate_alert_entries($vars) {

    global $alert_rules;
    global $config;

    // This should be set outside, but do it here if it isn't
    if (!is_array($alert_rules)) {
        $alert_rules = cache_alert_rules();
    }
    /// WARN HERE

    $vars['sort'] = 'alert_last_changed';

    [ $query, $param, $query_count ] = build_alert_table_query($vars);

    // Fetch alerts
    //$count  = dbFetchCell($query_count, $param);
    $alerts = dbFetchRows($query, $param);

    $array = [];
    foreach ($alerts as $alert) {
        $alert_rule        = &$alert_rules[$alert['alert_test_id']];
        $alert['severity'] = $alert_rule['severity'];
        humanize_alert_entry($alert);

        $device = device_by_id_cache($alert['device_id']);

        $array[] = [
            'sev'           => 100,
            'icon'          => $config['entities'][$alert['entity_type']]['icon'],
            'alert_test_id' => $alert['alert_test_id'],
            'event'         => '<a href="' . generate_url(['page' => 'alert_check', 'alert_test_id' => $alert_rule['alert_test_id']]) . '">' . escape_html($alert_rule['alert_name']) . '</a>',
            'entity_type'   => $alert['entity_type'],
            'entity_id'     => $alert['entity_id'],
            'entity_link'   => $alert['entity_type'] !== "device" ? generate_entity_link($alert['entity_type'], $alert['entity_id'], NULL, NULL, TRUE, TRUE) : NULL,
            'device_id'     => $device['device_id'],
            'device_link'   => generate_device_link_short($device),
            'time'          => $alert['changed']
        ];
    }

    return $array;

}

/**
 * Display status alerts.
 *
 * Display pages with alerts about device troubles.
 * Examples:
 * print_status(array('devices' => TRUE)) - display for devices down
 *
 * Another statuses:
 * devices, uptime, ports, errors, services, bgp
 *
 * @param array $options
 *
 * @return void|true
 *
 */
function print_status_boxes($options, $limit = NULL)
{
    if (isset($options['widget_type']) && $options['widget_type'] === 'alert_boxes') {
        $status_array = generate_alert_entries(['status' => 'failed']); //, 'entity_id' => '1'));
    } elseif ($status_array = get_status_array($options)) {
        $status_array = array_sort($status_array, 'sev', 'SORT_DESC');
    }

    $count = safe_count($status_array);

    if ($count === 0) {
        echo '<div class="alert statusbox alert-info" style="border-left: 1px; width: 80%; height: 75px; margin-left:10%; float:none; display: block;">';
        echo '<div style="margin: auto; line-height: 75px; text-align: center;">There are currently no ongoing alert events.</div>';
        echo '</div>';

        return;
    }

    $i = 1;
    foreach ($status_array as $entry) {
        if ($i >= $limit) {
            echo('<div class="alert statusbox alert-danger" style="border-left: 1px;">');
            echo '<div style="margin: auto; line-height: 75px; text-align: center;"><b>' . $count . ' more...</b></div>';
            echo('</div>');

            return;
        }

        if ($entry['entity_link']) {
            $entry['entity_link'] = preg_replace('/(<a.*?>)(.{0,20}).*(<\/a>)/', '\\1\\2\\3', $entry['entity_link']);
        }

        if ($entry['sev'] > 51) {
            $class = "alert-danger";
        } elseif ($entry['sev'] > 20) {
            $class = "alert-warning";
        } else {
            $class = "alert-info";
        }

        //if ($entry['wide']) { $class .= ' statusbox-wide'; }

        echo('<div class="alert statusbox ' . $class . '" style="text-align: center;position: relative;">
            <p style="margin: 0; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">');

        echo '<h4>' . $entry['device_link'] . '</h4>';
        echo '' . $entry['event'] . '<br />';
        echo '<h4 style="margin-bottom: 2px;">' . ($entry['entity_link'] ? get_icon($entry['icon']) . $entry['entity_link'] : 'Device') . ' </h4>';
        echo '<small>' . $entry['time'] . '</small>';
        echo('</p></div>');

        $count--;
        $i++;
    }

    return TRUE;

}

function generate_status_table($options, $print = FALSE) {

    $status_array = get_status_array($options);
    $status_array = array_sort($status_array, 'sev', 'SORT_DESC');
    $string       = '<table style="" class="table table-striped table-hover table-condensed">' . PHP_EOL;

    foreach ($status_array as $entry) {
        if ($entry['sev'] > 51) {
            $class     = "danger";
            $row_class = 'error';
        } elseif ($entry['sev'] > 20) {
            $class     = "warning";
            $row_class = 'warning';
        } else {
            $class = "info";
            $row_class = '';
        }

        $string .= '  <tr class="' . $row_class . '">' . PHP_EOL;
        $string .= '    <td class="state-marker"></td>';
        $string .= '    <td class="entity">' . $entry['device_link'] . '</td>' . PHP_EOL;
        $string .= '    <td><span class="label label-' . $class . '" title="' . $entry['event'] . '">' . $entry['class'] . ' ' . $entry['event'] . '</span></td>' . PHP_EOL;
        $string .= '    <td class="entity" style="white-space: nowrap">' . get_icon($entry['icon']) . ($entry['entity_link'] ?: $entry['device_link']) . '</td>' . PHP_EOL;
        $string .= '    <td>' . $entry['time'] . '</td>' . PHP_EOL;
        $string .= '  </tr>' . PHP_EOL;
    }
    $string .= '</table>';
    if ($print) {
        echo $string;
        return TRUE;
    }

    return $string;
}


// DOCME needs phpdoc block
function get_status_array($options) {
    global $config;

    $boxes                  = [];
    $max_interval           = filter_var($options['max']['interval'], FILTER_VALIDATE_INT, ['options' => ['default' => 24, 'min_range' => 1]]);
    $max_count              = filter_var($options['max']['count'], FILTER_VALIDATE_INT, ['options' => ['default' => 200, 'min_range' => 1]]);
    $query_device_permitted = generate_query_permitted_ng([ 'device' ], [ 'hide_ignored' => TRUE, 'hide_disabled' => TRUE ]);

    // Show Device Status
    if ($options['devices']) {
        $query   = 'SELECT * FROM `devices`';
        $query   .= generate_where_clause('`status` = 0', $query_device_permitted);
        $query   .= 'ORDER BY `hostname` ASC';

        foreach (dbFetchRows($query) as $device) {
            $boxes[] = [
                'sev'         => 100,
                'class'       => 'Device',
                'event'       => 'Down',
                'device_link' => generate_device_link_short($device),
                'time'        => device_uptime($device, 'short-3'),
                'icon'        => $config['entities']['device']['icon']
            ];
        }
    }

    // Uptime
    if ($options['uptime'] && $config['uptime_warning'] > 0 &&
        filter_var($config['uptime_warning'], FILTER_VALIDATE_FLOAT) !== FALSE) {
        $where_array = [ '`status` = 1', '`uptime` > 0' ];
        $where_array[] = generate_query_values(get_time() - $config['uptime_warning'] - 10, 'last_rebooted', '>');
        $query = 'SELECT * FROM `devices`';
        $query   .= generate_where_clause($where_array, $query_device_permitted);
        $query   .= 'ORDER BY `hostname` ASC';

        foreach (dbFetchRows($query) as $device) {
            $boxes[] = [
                'sev'         => 10,
                'class'       => 'Device',
                'event'       => 'Rebooted',
                'device_link' => generate_device_link_short($device),
                'time'        => device_uptime($device, 'short-3'),
                'location'    => $device['location'],
                'icon'        => $config['entities']['device']['icon']
            ];
        }
    }

    // Ports Down
    if ($options['ports'] || $options['neighbours']) {

        $options['neighbours'] = $options['neighbours'] && !$options['ports']; // Disable 'neighbours' if 'ports' already enabled
        $query_port_permitted  = generate_query_permitted([ 'port' ], [ 'port_table' => 'I', 'hide_ignored' => TRUE ]);

        $query = 'SELECT * FROM `ports` AS I ';
        if ($options['neighbours']) {
            $query .= 'INNER JOIN `neighbours` as L ON I.`port_id` = L.`port_id` ';
        }
        $query .= 'LEFT JOIN `devices` AS D ON I.`device_id` = D.`device_id` ';

        $where_array = [ 'D.`status` = 1', 'D.`ignore` = 0', 'I.`ignore` = 0', 'I.`deleted` = 0' ];
        $where_array[] = generate_query_values('up', 'I.ifAdminStatus');
        $where_array[] = generate_query_values([ 'down', 'lowerLayerDown' ], 'I.ifOperStatus');

        if ($options['neighbours']) {
            $where_array[] = 'L.`active` = 1';
        }
        $where_array[] = generate_query_values('DATE_SUB(NOW(), INTERVAL ' . $max_interval . ' HOUR)', 'I.ifLastChange', '>=');

        $query .= generate_where_clause($where_array, $query_port_permitted);
        if ($options['neighbours']) {
            $query .= ' GROUP BY L.`port_id`'; // hrm.. this works?
        }
        $query   .= ' ORDER BY I.`ifLastChange` DESC, D.`hostname` ASC, I.`ifDescr` * 1 ASC';

        $i       = 1;
        foreach (dbFetchRows($query) as $port) {
            if ($i > $max_count) {
                // Limit to 200 ports on overview page
                break;
            }
            //humanize_port($port);
            $boxes[] = [
                'sev'         => 50,
                'class'       => 'Port',
                'event'       => 'Down',
                'device_link' => generate_device_link_short($port),
                'entity_link' => generate_port_link_short($port),
                'time'        => format_uptime(get_time() - strtotime($port['ifLastChange'])),
                'location'    => $device['location'],
                'icon'        => $config['entities']['port']['icon']
            ];
        }
    }


    // Ports Errors (only deltas)
    if ($options['errors']) {

        foreach ($GLOBALS['cache']['ports']['errored'] as $port_id) {
            if (in_array($port_id, $GLOBALS['cache']['ports']['ignored'])) {
                continue;
            } // Skip ignored ports
            if (in_array($port['ifType'], $config['ports']['ignore_errors_iftype'])) {
                continue;
            } // Skip iftypes we ignore

            $port   = get_port_by_id($port_id);
            $device = device_by_id_cache($port['device_id']);
            humanize_port($port);

            $port['text'] = [];
            if ($port['ifInErrors_delta']) {
                $port['text'][] = 'Rx: ' . format_number($port['ifInErrors_delta']) . ' (' . format_number($port['ifInErrors_rate']) . '/s)';
            }
            if ($port['ifOutErrors_delta']) {
                $port['text'][] = 'Tx: ' . format_number($port['ifOutErrors_delta']) . ' (' . format_number($port['ifOutErrors_rate']) . '/s)';
            }

            $port['string'] = implode(', ', $port['text']);

            if ($port['ifInErrors_rate'] > 1 || $port['ifOutErrors_rate'] > 1) {
                $sev = 70;
            } else {
                $sev = 45;
            }

            $boxes[] = [
                'sev'         => $sev,
                'class'       => 'Port',
                'event'       => 'Errors',
                'device_link' => generate_device_link_short($device),
                'entity_link' => generate_port_link_short($port),
                'time'        => $port['string'],
                'location'    => $device['location'],
                'icon'        => $config['entities']['port']['icon']
            ];
        }
    }

    // BGP
    if ($options['bgp'] && isset($config['enable_bgp']) && $config['enable_bgp']) {
        $where_array = [ '`status` = 1' ];
        $where_array[] = generate_query_values([ 'start', 'running' ], 'bgpPeerAdminStatus');
        $where_array[] = generate_query_values('established', 'bgpPeerState', '!=');

        $query = 'SELECT * FROM `bgpPeers`';
        $query .= ' LEFT JOIN `devices` USING(`device_id`)';
        $query .= generate_where_clause($where_array, $query_device_permitted);
        $query .= ' ORDER BY D.`hostname` ASC';

        foreach (dbFetchRows($query) as $peer) {
            humanize_bgp($peer);

            if (isset($options['bgp_peer_name']) && $options['bgp_peer_name'] === "peer_dns" &&
                !safe_empty($peer['reverse_dns'])) {
                $peer_link = generate_entity_link("bgp_peer", $peer, short_hostname($peer['reverse_dns']));
            } else {
                $peer_link = generate_entity_link("bgp_peer", $peer, $peer['human_remoteip']);
            }

            $peer['wide'] = str_contains($peer['bgpPeerRemoteAddr'], ':');
            $boxes[]      = [
                'sev'         => 75,
                'class'       => 'BGP Session',
                'event'       => 'Down',
                'device_link' => generate_device_link_short($peer),
                'entity_link' => $peer_link,
                'wide'        => $peer['wide'],
                'time'        => format_uptime($peer['bgpPeerFsmEstablishedTime'], 'short-3'),
                'location'    => $device['location'],
                'icon'        => $config['entities']['bgp_peer']['icon']
            ];
        }
    }

    // Return boxes array
    return $boxes;
}

// EOF
