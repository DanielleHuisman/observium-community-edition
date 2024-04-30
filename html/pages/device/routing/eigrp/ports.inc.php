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
echo '<tr><th class="state-marker"></th><th></th><th>Port</th><th>AS</th><th>Peers</th><th>Mean SRTT</th><th>Auth</th></tr>';
echo '</thead>';
echo '<tbody>';

foreach (dbFetchRows("SELECT * FROM `eigrp_ports` WHERE `device_id` = ? AND `eigrp_vpn` = ? AND `eigrp_as` = ?", [$device['device_id'], $vars['vpn'], $vars['asn']]) as $eigrp_port) {

    $port = get_port_by_id($eigrp_port['port_id']);
    echo '<tr class="' . $port['row_class'] . '" onclick="openLink(\'' . generate_port_url($port) . '/\')" style="cursor: pointer;">

          <td class="state-marker"></td>
          <td style="width: 1px;"></td>
          <td><span class="entity-title">' . generate_port_link($port) . '</span><br />
                                 ' . $port['ifAlias'] . '</td>
          <td>' . $eigrp_port['eigrp_as'] . '</td>
          <td>' . $eigrp_port['eigrp_peer_count'] . '</td>
          <td>' . $eigrp_port['eigrp_MeanSrtt'] . '</td>
          <td>' . $eigrp_port['eigrp_authmode'] . '</td>
        </tr>';

    if (get_var_true($vars['graphs'])) {
        echo '
        <tr>
          <td class="state-marker"></td>
          <td colspan=6>';

        $graph_array['to']   = get_time();
        $graph_array['id']   = $eigrp_port['eigrp_port_id'];
        $graph_array['type'] = 'eigrpport_packets';

        print_graph_row($graph_array);

        echo '</td></tr>';
    }
}

echo '</tbody>';
echo '</table>';

echo generate_box_close();

// EOF

