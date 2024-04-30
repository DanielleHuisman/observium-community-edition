<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

echo(generate_link($descr, $link_array, ['view' => 'macaccounting']));
$graphs = ['bits' => 'Bits', 'pkts' => 'Packets'];

$navbar = [];

$navbar['class'] = "navbar-narrow";
$navbar['brand'] = 'Mac Accounting';

$subviews = ['details', 'graphs', 'minigraphs', 'top10'];
foreach ($subviews as $type) {
    $navbar['options'][$type]['text'] = nicecase($type);
    $navbar['options'][$type]['url']  = generate_url($vars, ['subview' => $type]);
    if ($vars['subview'] == $type) {
        $navbar['options'][$type]['class'] = "active";
    }
}

foreach ($graphs as $type => $text) {
    $navbar['options_right'][$type]['text'] = $text;
    $navbar['options_right'][$type]['url']  = generate_url($link_array, ['view' => 'macaccounting', 'subview' => 'graphs', 'graph' => $type]);
    if ($vars['graph'] == $type) {
        $navbar['options_right'][$type]['class'] = "active";
    }
}

print_navbar($navbar);

// FIXME - REWRITE!

$hostname = $device['hostname'];
$hostid   = $device['port_id'];
$ifname   = $port['port_label'];
$ifIndex  = $port['ifIndex'];
$speed    = humanspeed($port['ifSpeed']);

$ifalias = $port['name'];

if ($port['ifPhysAddress']) {
    $mac = $port['ifPhysAddress'];
}

$color = "black";
if ($port['ifAdminStatus'] == "down") {
    $status = "<span class='grey'>Disabled</span>";
} elseif ($port['ifAdminStatus'] == "up") {
    if ($port['ifOperStatus'] == "down" || $port['ifOperStatus'] == "lowerLayerDown") {
        $status = "<span class='red'>Enabled / Disconnected</span>";
    } else {
        $status = "<span class='green'>Enabled / Connected</span>";
    }
}

$i = 1;

echo("<div style='clear: both;'>");

if ($vars['subview'] == "top10") {

    include("macaccounting_top10.inc.php");

} else {

    $query = "SELECT *, `mac_accounting`.`ma_id` as `ma_id` FROM `mac_accounting` WHERE port_id = ?";
    $param = [$port['port_id']];

    if ($vars['subview'] != 'minigraphs') {

        if ($vars['subview'] == "graphs") {
            $table_class = "table-striped-two";
        } else {
            $table_class = "table-striped";
        }

        echo generate_box_open();
        echo '<table class="table table-hover table-condensed ' . $table_class . '">';
        echo '  <thead>';

        echo '<tr>';
        $cols = [
          'BLANK'    => NULL,
          'mac'      => 'MAC Address',
          'BLANK'    => NULL,
          'ip'       => 'IP Address',
          'graphs'   => NULL,
          'bps_in'   => 'Traffic In',
          'bps_out'  => 'Traffic Out',
          'pkts_in'  => 'Packets In',
          'pkts_out' => 'Packets Out',
          'BLANK'    => NULL];

        foreach ($cols as $sort => $col) {
            if ($col == NULL) {
                echo('<th></th>');
            } elseif ($vars['sort'] == $sort) {
                echo('<th>' . $col . ' *</th>');
            } else {
                echo('<th><a href="' . generate_url($vars, ['sort' => $sort]) . '">' . $col . '</a></th>');
            }
        }

        echo("      </tr>");
        echo('  </thead>');

    } else {

        echo '<div class="row">';

    }

    $ma_array = dbFetchRows($query, $param);

    switch ($vars['sort']) {
        case 'bps_in':
            $ma_array = array_sort($ma_array, 'bytes_input_rate', 'SORT_DESC');
            break;
        case 'bps_out':
            $ma_array = array_sort($ma_array, 'bytes_output_rate', 'SORT_DESC');
            break;
        case 'pkts_in':
            $ma_array = array_sort($ma_array, 'bytes_input_rate', 'SORT_DESC');
            break;
        case 'pkts_out':
            $ma_array = array_sort($ma_array, 'bytes_output_rate', 'SORT_DESC');
            break;
    }

    foreach ($ma_array as $acc) {

        $ips = [];
        foreach (dbFetchRows("SELECT `ip_address` FROM `ip_mac` WHERE `mac_address` = ? AND `port_id` = ?", [$acc['mac'], $acc['port_id']]) as $ip) {
            $ips[] = $ip['ip_address'];
        }

        unset($name);
        ///FIXME. Need rewrite, because $addy is array with multiple items.
        #$name = gethostbyaddr($addy['ipv4_address']); FIXME - Maybe some caching for this?

        $arp_host = dbFetchRow("SELECT * FROM ipv4_addresses AS A, ports AS I, devices AS D WHERE A.ipv4_address = ? AND I.port_id = A.port_id AND D.device_id = I.device_id", [$addy['ip_address']]);
        if ($arp_host) {
            $arp_name = generate_device_link($arp_host);
            $arp_name .= " " . generate_port_link($arp_host);
        } else {
            unset($arp_if);
        }

        if ($name == $addy['ip_address']) {
            unset ($name);
        }
        //if (dbFetchCell("SELECT COUNT(*) FROM bgpPeers WHERE device_id = ? AND bgpPeerIdentifier = ?", array($acc['device_id'], $addy['ip_address'])))
        if (dbExist('bgpPeers', '`device_id` = ? AND `bgpPeerIdentifier` = ?', [$device['device_id'], $addy['ip_address']])) {
            $peer_info = dbFetchRow("SELECT * FROM `bgpPeers` WHERE `device_id` = ? AND `bgpPeerIdentifier` = ?", [$acc['device_id'], $addy['ip_address']]);
        } else {
            unset ($peer_info);
        }

        if ($peer_info) {
            $asn    = "AS" . $peer_info['bgpPeerRemoteAs'];
            $astext = $peer_info['astext'];
        } else {
            unset ($as);
            unset ($astext);
            unset($asn);
        }

        $name = format_mac($acc['mac']);

        if (!isset($vars['graph'])) {
            $vars['graph'] = "bits";
        }
        $graph_type = "macaccounting_" . $vars['graph'];

        if ($vars['subview'] == "minigraphs") {
            if (!$asn) {
                $asn = "No Session";
            }

            $graph_array = ['id' => $acc['ma_id'], 'type' => $graph_type, 'from' => get_time('twoday'), 'to' => get_time('now'), 'width' => '215', 'height' => '100'];


            echo '<div class="col-md-3">';
            echo generate_box_open(['title'         => $name,
                                    /** 'url' => generate_device_url($device), */
                                    'header-border' => TRUE,
                                   ]);

            print_graph_popup($graph_array);

            echo generate_box_close([]);
            echo '</div>';

        } else {
            echo("
        <tr>
          <td width=20></td>
          <td width=200><bold>" . format_mac($acc['mac']) . "</bold></td>
          <td width=200>" . implode("<br />", $ips) . "</td>
          <td width=500>" . $name . " " . $arp_name . "</td>
          <td width=100>" . format_bps($acc['bytes_input_rate'] / 8) . "</td>
          <td width=100>" . format_bps($acc['bytes_output_rate'] / 8) . "</td>
          <td width=100>" . format_number($acc['pkts_input_rate'] / 8) . "pps</td>
          <td width=100>" . format_number($acc['pkts_output_rate'] / 8) . "pps</td>
        </tr>
    ");

            $peer_info['astext'];

            if ($vars['subview'] == "graphs") {
                $graph_array['type']   = $graph_type;
                $graph_array['id']     = $acc['ma_id'];
                $graph_array['height'] = "100";
                $graph_array['to']     = get_time();
                echo('<tr><td colspan="8">');

                print_graph_row($graph_array);

                echo("</td></tr>");
                $i++;
            }
        }
    }
    echo '</table>';
    echo generate_box_close();
}

// EOF
