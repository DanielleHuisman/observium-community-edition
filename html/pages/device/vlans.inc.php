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

$link_array = [
    'page'   => 'device',
    'device' => $device['device_id'],
    'tab'    => 'vlans'
];

if (isset($vars['graph'])) {
    $graph_type = "port_" . $vars['graph'];
} else {
    $graph_type = "port_bits";
}
if (!$vars['view']) {
    $vars['view'] = "basic";
}

$navbar['brand'] = 'VLANs';
$navbar['class'] = 'navbar-narrow';

if ($vars['view'] == 'basic') {
    $navbar['options']['basic']['class'] = 'active';
}
$navbar['options']['basic']['url']  = generate_url($link_array, ['view' => 'basic', 'graph' => NULL]);
$navbar['options']['basic']['text'] = 'No Graphs';

foreach ($config['graph_types']['port'] as $type => $data) {

    if ($type === 'fdb_count' && !is_module_enabled($device, 'ports_fdbcount', 'poller')) {
        continue;
    }

    if ($vars['graph'] == $type && $vars['view'] == "graphs") {
        $navbar['options'][$type]['class'] = "active";
    }
    $navbar['options'][$type]['url']  = generate_url($link_array, ['view' => 'graphs', 'graph' => $type]);
    $navbar['options'][$type]['text'] = $data['name'];

}

// Quick filters

if (isset($vars['graph']) && in_array($vars['graph'], [ 'fdb_count' ])) {

    $vars['filters'] = $vars['filters'] ?? [ 'deleted' => TRUE, 'virtual' => TRUE ];

    $vars['filters'] = navbar_ports_filter($navbar, $vars, [ 'virtual' ]);
}

print_navbar($navbar);

echo generate_box_open();

echo('<table class="table table-striped table-hover table-condensed">');
$cols = [
    //[NULL, 'class="state-marker"'],
    'VLAN',
    'Description',
    //'Other Ports'
    ['Other Ports', 'style="width: 77%;"'],
];

echo get_table_header($cols, $vars);
//echo("<thead><tr><th>VLAN</th><th>Description</th><th>Other Ports</th></tr></thead>");

foreach (dbFetchRows("SELECT * FROM `vlans` WHERE `device_id` = ? ORDER BY 'vlan_vlan'", [$device['device_id']]) as $vlan) {
    print_vlan_ports_row($device, $vlan, $vars);
}

echo "</table>";

echo generate_box_close();

register_html_title("VLANs");

// EOF
