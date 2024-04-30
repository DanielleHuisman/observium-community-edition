<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Ports
// Print table of EIGRP interfaces from eigrp_ports.

echo generate_box_open();

echo '<table class="table table-hover table-striped-two  table-condensed ">';
echo '<thead>';
echo '<tr><th class="state-marker"></th><th></th><th>Local Port</th><th>Peer Device</th><th>Peer Port</th><th>Uptime</th><th>SRTT</th><th>RTO</th><th>Version</th></th></tr>';
echo '</thead>';
echo '<tbody>';

foreach (dbFetchRows("SELECT * FROM `eigrp_peers` WHERE `device_id` = ? AND `eigrp_vpn` = ? AND `eigrp_as` = ?", [$device['device_id'], $vars['vpn'], $vars['asn']]) as $peer) {

    //print_vars($peer);

    $peer['local_port'] = get_port_by_ifIndex($device['device_id'], $peer['peer_ifindex']);

    $ip = dbFetchRow("SELECT * FROM `ipv4_addresses` WHERE `ipv4_address` = ?", [$peer['peer_addr']]);
    if (is_array($ip)) {
        $peer['port'] = get_port_by_id_cache($ip['port_id']);

        $peer['device'] = device_by_id_cache($peer['port']['device_id']);
    }

    echo '<tr class="' . $port['row_class'] . '" onclick="openLink(\'' . generate_port_url($port) . '/\')" style="cursor: pointer;">';

    echo '
          <td class="state-marker"></td>
          <td style="width: 1px;"></td>
          <td><span class="entity-title">' . (is_array($peer['local_port']) ? generate_port_link($peer['local_port']) : 'Unknown') . '</span><br />' . $peer['local_port']['ifAlias'] . '</td>
          <td>' . $peer['peer_addr'] . '<br /><span class="entity-title">' . (is_array($peer['device']) ? generate_device_link($peer['device']) : '') . '</span></td>
          <td><span class="entity-title">' . (is_array($peer['port']) ? generate_port_link($peer['port']) : '') . '</span><br />
                                 ' . $peer['port']['ifAlias'] . '</td>
          <td>' . format_uptime($peer['peer_uptime']) . '</td>
          <td>' . $peer['peer_srtt'] . 'ms</td>
          <td>' . $peer['peer_rto'] . 'ms</td>
          <td>' . $peer['peer_version'] . '</td>
';

    echo '</tr>';

    if (get_var_true($vars['graphs'])) {
        echo '<tr>
          <td class="state-marker"></td>
          <td colspan=8>';

        $graph_array['to']   = get_time();
        $graph_array['id']   = $peer['eigrp_peer_id'];
        $graph_array['type'] = 'eigrppeer_graph';

        print_graph_row($graph_array);

        echo('</td></tr>');
    }

}

echo '</tbody>';
echo '</table>';

echo generate_box_close();

// EOF

