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

if (!isset($vars['view'])) {
    $vars['view'] = 'details';
}
unset($navbar);

$link_array = ['page'   => 'device',
               'device' => $device['device_id'],
               'tab'    => 'routing',
               'proto'  => 'ipsec_tunnels'];

$navbar['options_right']['details']['text'] = 'No Graphs';
if ($vars['view'] == 'details') {
    $navbar['options_right']['details']['class'] .= ' active';
}
$navbar['options_right']['details']['url'] = generate_url($vars, ['view' => 'details', 'graph' => 'NULL']);

$navbar['options_right']['bits']['text'] = 'Bits';
if ($vars['graph'] == 'bits') {
    $navbar['options_right']['bits']['class'] .= ' active';
}
$navbar['options_right']['bits']['url'] = generate_url($vars, ['view' => 'graphs', 'graph' => 'bits']);

$navbar['options_right']['pkts']['text'] = 'Packets';
if ($vars['graph'] == 'pkts') {
    $navbar['options_right']['pkts']['class'] .= ' active';
}
$navbar['options_right']['pkts']['url'] = generate_url($vars, ['view' => 'graphs', 'graph' => 'pkts']);

$navbar['class'] = 'navbar-narrow';
$navbar['brand'] = 'IPSec Tunnels';
print_navbar($navbar);

switch ($vars['view']) {
    case 'bits':
    case 'pkts':
        $table_class = 'table-striped-two';
        break;
    default:
        $table_class = 'table-striped';
}

echo generate_box_open();
echo '<table class="table table-hover  ' . $table_class . ' table-condensed ">';

// <thead><tr><th class="state-marker"></th><th>Local address</th><th></th><th>Peer address</th><th>Tunnel name</th><th>State</th></tr></thead>');


$cols = [
  [NULL, 'class="state-marker"'],
  'local_addr'   => ['Local Address', 'style="width: 10%;"'],
  ['', 'style="width: 30px;"'],
  'peer_addr'    => ['Peer Address', 'style="width: 10%;"'],
  'tun_name'     => ['Tunnel Name'],
  'tun_end'      => ['End points', 'style="width: 20%;"'],
  'tun_lifetime' => ['Lifetime', 'style="width: 100px;"'],
  'ike_alive'    => ['IKE Alive', 'style="width: 60px;"'],
  'ike_lifetime' => ['IKE Lifetime', 'style="width: 100px;"'],
  'added'        => ['Added', 'style="width: 120px;"'],
];

echo get_table_header($cols, $vars);

$where = 'WHERE 1';
$order = '';
if ($vars['deleted'] != 1) {
    $where .= ' AND `tunnel_deleted` = 0';
} else {
    $order .= '`tunnel_deleted`,';
}
$where .= ' AND `device_id` = ? AND `peer_addr` != ?';
$query = "SELECT * FROM `ipsec_tunnels` $where ORDER BY $order `tunnel_added` DESC, `peer_addr`";

foreach (dbFetchRows($query, [$device['device_id'], '']) as $tunnel) {

    if ($tunnel['tunnel_status'] == 'active') {
        $tunnel['label_class'] = 'label-success';
        if ($tunnel['tunnel_ike_alive'] != 'true') {
            $tunnel['row_class'] = 'warning';
        } else {
            $tunnel['row_class'] = '';
        }
    } else {
        $tunnel['label_class'] = 'label-error';
        $tunnel['row_class']   = 'error';
    }
    if ($tunnel['tunnel_ike_alive'] == 'true') {
        $tunnel['ike_label_class'] = 'label-success';
    } else {
        $tunnel['ike_label_class'] = 'label-error';
    }
    if ($tunnel['deleted']) {
        $tunnel['row_class'] = 'disabled';
    }

    // FIXME - Solves leading zeros in IPs - This should maybe be in ipsec polling instead
    //$local_addr = preg_replace('/\b0+\B/','',$tunnel['local_addr']);
    //$peer_addr  = preg_replace('/\b0+\B/','',$tunnel['peer_addr']);
    $tunnel_endpoint = [];
    foreach (json_decode($tunnel['tunnel_endpoint'], TRUE) as $entry) {
        $tunnel_endpoint[] = 'Local: ' . generate_popup_link('ip', $entry['local']) .
                             ', Remote: ' . generate_popup_link('ip', $entry['remote']);
    }
    $tunnel_endpoint = implode('<br />', $tunnel_endpoint);

    $timediff = get_time() - $tunnel['tunnel_added'];
    echo('<tr class="' . $tunnel['row_class'] . '">
  <td class="state-marker"></td>
  <td>' . generate_popup_link('ip', $tunnel['local_addr']) . '</td>
  <td><b>&#187;</b></td>
  <td>' . generate_popup_link('ip', $tunnel['peer_addr']) . '</td>
  <td>' . $tunnel['tunnel_name'] . '</td>
  <td><span class="text-nowrap">' . $tunnel_endpoint . '</span></td>
  <td><span>' . generate_tooltip_link(NULL, format_uptime($tunnel['tunnel_lifetime'], 'short-3'), $tunnel['tunnel_lifetime'] . ' sec') . '</span></td>
  <td><span class="label ' . $tunnel['ike_label_class'] . '">' . $tunnel['tunnel_ike_alive'] . '</span></td>
  <td><span>' . generate_tooltip_link(NULL, format_uptime($tunnel['tunnel_ike_lifetime'], 'short-3'), $tunnel['tunnel_ike_lifetime'] . ' sec') . '</span></td>
  <td><span>' . generate_tooltip_link(NULL, format_uptime($timediff, "short-3") . ' ago', format_unixtime($tunnel['tunnel_added'])) . '</span></td>
  </tr>');

    switch ($vars['graph']) {
        case 'bits':
        case 'pkts':
            $graph_array['type'] = 'ipsectunnel_' . $vars['graph'];
            $graph_array['id']   = $tunnel['tunnel_id'];
    }

    if ($vars['graph'] == 'bits' || $vars['graph'] == 'pkts') {
        $tunnel['graph'] = 1;
    }

    if ($tunnel['graph']) {
        $graph_array['to'] = get_time();

        echo('<tr class="' . $tunnel['row_class'] . '">');
        echo('<td colspan="10">');

        print_graph_row($graph_array);

        echo('</td></tr>');
    }
}

echo('</table>');
echo generate_box_close();

// EOF
