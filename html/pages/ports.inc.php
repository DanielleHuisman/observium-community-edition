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

register_html_title('Ports');

// Set Defaults here

if (!isset($vars['format']) || !is_alpha($vars['format']) || !is_file($config['html_dir'] . '/pages/ports/' . $vars['format'] . '.inc.php')) {
    $vars['format'] = 'list';
}

print_debug_vars($vars, 1);

$param = [];

if (!isset($vars['sort'])) {
    $vars['sort'] = 'device';
}
//if (!isset($vars['ignore']))   { $vars['ignore'] = "0"; }
if (!isset($vars['disabled'])) {
    $vars['disabled'] = "0";
}
if (!isset($vars['deleted'])) {
    $vars['deleted'] = "0";
}

$select = "`ports`.`port_id` AS `port_id`, `devices`.`device_id` AS `device_id`";

$where_array = build_ports_where_array_ng($vars);

/*
if (!$config['web_show_disabled'] && safe_count($cache['devices']['disabled']) > 0) {
    $where_array[] = generate_query_values($cache['devices']['disabled'], 'ports.device_id', '!=');
}
$where .= implode('', $where_array);
*/
//r($where_array);

$form_items = [];

foreach (get_locations() as $entry) {
    if ($entry === '') {
        $entry = OBS_VAR_UNSET;
    }
    $form_items['location'][$entry] = $entry;
}

foreach (get_type_groups('port') as $entry) {
    $form_items['group'][$entry['group_id']] = $entry['group_name'];
}

foreach (['ifType', 'ifSpeed', 'port_descr_type'] as $entry) {
    $query = "SELECT `$entry` FROM `ports`";
    $query .= " LEFT JOIN `devices` USING (`device_id`)";

    $form_where = [];
    foreach ($where_array as $where_entry) {
        if (!str_contains($where_entry, "`$entry`")) {
            $form_where[] .= $where_entry;
        }
    }
    $form_where[] = "`$entry` != ''";

    //r($form_where);
    $query .= generate_where_clause($form_where, $cache['where']['ports_permitted']);
    $query .= " GROUP BY `$entry`";

    $sort_order = $entry === 'ifSpeed' ? 'DESC' : '';
    $query .= generate_query_sort($entry, $sort_order);

    $form_items[$entry] = [];
    foreach (dbFetchRows($query) as $data) {
        if ($entry === "ifType") {
            $form_items[$entry][$data['ifType']] = rewrite_iftype($data['ifType']) . ' (' . $data['ifType'] . ')';
        } elseif ($entry === "ifSpeed") {
            $form_items[$entry][$data[$entry]] = format_bps($data[$entry]);
        } else {
            $form_items[$entry][$data[$entry]] = nicecase($data[$entry]);
        }
    }
}

if (isset($form_items['ifType'])) {
    asort($form_items['ifType']);
}

$form_items['devices'] = generate_form_values('device'); // Always all devices

$form_items['sort'] = [
  'device'           => 'Device',
  'port'             => 'Port',
  'speed'            => 'Speed',
  'traffic'          => 'Traffic In+Out',
  'traffic_in'       => 'Traffic In',
  'traffic_out'      => 'Traffic Out',
  'traffic_perc'     => 'Traffic Percentage In+Out',
  'traffic_perc_in'  => 'Traffic Percentage In',
  'traffic_perc_out' => 'Traffic Percentage Out',
  'packets'          => 'Packets In+Out',
  'packets_in'       => 'Packets In',
  'packets_out'      => 'Packets Out',
  'errors'           => 'Errors',
  'mac'              => 'MAC Address',
  'media'            => 'Media',
  'descr'            => 'Description'
];

$form = [
  'type'          => 'rows',
  'space'         => '10px',
  //'brand' => NULL,
  //'class' => 'well',
  'submit_by_key' => TRUE,
  'url'           => generate_url($vars)
];

// First row
$form['row'][0]['device_id'] = [
  'type'   => 'multiselect',
  'name'   => 'Device',
  'value'  => $vars['device_id'],
  'width'  => '100%', //'180px',
  'values' => $form_items['devices']
];

foreach (get_locations() as $entry) {
    if ($entry === '') {
        $entry = OBS_VAR_UNSET;
    }
    $form_items['location'][$entry] = $entry;
}

$form['row'][0]['location'] = [
  'type'   => 'multiselect',
  'name'   => 'Device Location',
  'width'  => '100%', //'180px',
  //'encode'      => TRUE,
  'value'  => $vars['location'],
  'values' => $form_items['location']
];

$form['row'][0]['mac'] = [
  'type'        => 'text',
  'name'        => 'Port Mac Address',
  'value'       => $vars['mac'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE
];

$form['row'][0]['state'] = [
  'type'   => 'multiselect',
  'name'   => 'Port State',
  'width'  => '100%', //'180px',
  'value'  => $vars['state'],
  'values' => [ 'up' => 'Up', 'down' => ' Down', 'admindown' => 'Shutdown']
];

$form['row'][0]['ifType'] = [
  'type'   => 'multiselect',
  'name'   => 'Port Media',
  'width'  => '100%', //'180px',
  'value'  => $vars['ifType'],
  'values' => $form_items['ifType']
];

$form['row'][0]['group'] = [
  'type'   => 'multiselect',
  'name'   => 'Select Groups',
  'width'  => '100%', //'180px',
  'value'  => $vars['group'],
  'values' => $form_items['group']
];

$form['row'][1]['hostname'] = [
  'type'        => 'text',
  'name'        => 'Device Hostname',
  'value'       => $vars['hostname'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE
];

$form['row'][1]['label'] = [
  'type'        => 'text',
  'name'        => 'Port Name',
  'value'       => $vars['label'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE
];

$form['row'][1]['ifAlias'] = [
  'type'        => 'text',
  'name'        => 'Port Description (ifAlias)',
  'value'       => $vars['ifAlias'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE
];

$form['row'][1]['ifSpeed'] = [
  'type'   => 'multiselect',
  'name'   => 'Port Speed',
  'width'  => '100%', //'180px',
  'value'  => $vars['ifSpeed'],
  'values' => $form_items['ifSpeed']
];

$form['row'][1]['port_descr_type'] = [
  'type'   => 'multiselect',
  'name'   => 'Port Parsed Type',
  'width'  => '100%', //'180px',
  'value'  => $vars['port_descr_type'],
  'values' => $form_items['port_descr_type']
];

// Select sort pull-right
$form['row'][1]['sort'] = [
  'type'   => 'select',
  'icon'   => $config['icon']['sort'],
  'grid'   => 1,
  //'right'       => TRUE,
  'width'  => '100%', //'150px',
  'value'  => $vars['sort'],
  'values' => $form_items['sort']
];

$form['row'][1]['search'] = [
  'type'  => 'submit',
  'grid'  => 1,
  //'name'        => 'Search',
  //'icon'        => 'icon-search',
  'right' => TRUE,
];

$panel_form = [
  'type'          => 'rows',
  'title'         => 'Search Ports',
  'space'         => '10px',
  //'brand' => NULL,
  //'class' => '',
  'submit_by_key' => TRUE,
  'url'           => generate_url($vars)
];

$panel_form['row'][0]['device_id'] = $form['row'][0]['device_id'];
$panel_form['row'][0]['hostname']  = $form['row'][1]['hostname'];
//$panel_form['row'][0]['location']  = $form['row'][0]['location'];

$panel_form['row'][1]['label']   = $form['row'][1]['label'];
$panel_form['row'][1]['ifAlias'] = $form['row'][1]['ifAlias'];
$panel_form['row'][1]['mac']     = $form['row'][0]['mac'];

$panel_form['row'][2]['state']   = $form['row'][0]['state'];
$panel_form['row'][2]['ifSpeed'] = $form['row'][1]['ifSpeed'];

$panel_form['row'][3]['ifType']          = $form['row'][0]['ifType'];
$panel_form['row'][3]['port_descr_type'] = $form['row'][1]['port_descr_type'];

$panel_form['row'][4]['group']            = $form['row'][0]['group'];
$panel_form['row'][4]['group']['grid']    = 4;
$panel_form['row'][4]['location']         = $form['row'][0]['location'];
$panel_form['row'][4]['location']['grid'] = 4;

$panel_form['row'][4]['sort']           = $form['row'][1]['sort'];
$panel_form['row'][4]['sort']['grid']   = 2;
$panel_form['row'][4]['search']         = $form['row'][1]['search'];
$panel_form['row'][4]['search']['grid'] = 2;

// Register custom panel
register_html_panel(generate_form($panel_form));

if ($vars['searchbar'] !== "hide") {
    echo '<div class="hidden-xl">';
    print_form($form);
    echo '</div>';
}
unset($form, $panel_form, $form_items);

$navbar = ['brand' => "Ports", 'class' => "navbar-narrow"];

$navbar['options']['basic']['text'] = 'Basic';
// There is no detailed view for this yet.
//$navbar['options']['detail']['text']  = 'Details';

$navbar['options']['graphs'] = ['text' => 'Graphs'];

foreach ($navbar['options'] as $option => $array) {
    if ($vars['format'] === 'list' && !isset($vars['view'])) {
        $vars['view'] = 'basic';
    }
    if ($vars['format'] === 'list' && $vars['view'] == $option) {
        $navbar['options'][$option]['class'] .= " active";
    }
    $navbar['options'][$option]['url'] = generate_url(['page' => 'ports', 'format' => 'list', 'view' => $option]);
}

foreach (['graphs'] as $type) {
    foreach ($config['graph_types']['port'] as $option => $data) {
        if ($vars['view'] == $type && $vars['graph'] == $option) {
            $navbar['options'][$type]['suboptions'][$option]['class'] = 'active';
            $navbar['options'][$type]['text']                         .= " (" . $data['name'] . ')';
        }
        $navbar['options'][$type]['suboptions'][$option]['text'] = $data['name'];
        $navbar['options'][$type]['suboptions'][$option]['url']  = generate_url($vars, ['view' => $type, 'graph' => $option]);
    }
}

if (is_array($config['entities']['port']['agg_graphs'])) {
    $navbar['options']['agg_graphs'] = ['text' => 'Aggregate Graphs'];
    foreach ($config['entities']['port']['agg_graphs'] as $option => $data) {
        if ($vars['agg_graph'] == $option) {
            $navbar['options']['agg_graphs']['class']                        = 'active';
            $navbar['options']['agg_graphs']['suboptions'][$option]['class'] = 'active';
            $navbar['options']['agg_graphs']['text']                         .= " (" . $data['name'] . ')';
        }
        $navbar['options']['agg_graphs']['suboptions'][$option]['text'] = $data['name'];
        $navbar['options']['agg_graphs']['suboptions'][$option]['url']  = generate_url($vars, ['agg_graph' => $option]);
    }
}


if ($vars['searchbar'] === "hide") {
    $navbar['options_right']['searchbar'] = ['text' => 'Show Search', 'url' => generate_url($vars, ['searchbar' => NULL])];
} else {
    $navbar['options_right']['searchbar'] = ['text' => 'Hide Search', 'url' => generate_url($vars, ['searchbar' => 'hide'])];
}

if (get_var_true($vars['bare'])) {
    $navbar['options_right']['header'] = ['text' => 'Show Header', 'url' => generate_url($vars, ['bare' => NULL])];
} else {
    $navbar['options_right']['header'] = ['text' => 'Hide Header', 'url' => generate_url($vars, ['bare' => 'yes'])];
}

$navbar['options_right']['reset'] = ['text' => 'Reset', 'url' => generate_url(['page' => 'ports', 'section' => $vars['section']])];

print_navbar($navbar);
unset($navbar);

/* not required array, because permissions array already in select query
include($config['html_dir'] . '/includes/port-sort-select.inc.php');

$sql = "SELECT " . $select;
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
$sql .= generate_where_clause($where_array);

//r($sql);

$ports = dbFetchRows($sql, $param);
port_permitted_array($ports);
$ports_count = count($ports);

$port_ids = array_value_recursive('port_id', $ports);
*/

$sql = "SELECT `port_id`";
$sql .= " FROM `ports`";
$sql .= " INNER JOIN `devices` USING (`device_id`)";
$sql .= generate_where_clause($where_array, $cache['where']['ports_permitted']);

$ports_ids = dbFetchColumn($sql, $param);
$ports_count = safe_count($ports_ids);
//r($port_ids);
if ($vars['agg_graph']) {

    $graph_vars = [
      'type'   => 'multi-port_' . $vars['agg_graph'],
      'legend' => 'no',
      'id'     => implode(',', $ports_ids)
    ];

    if ($ports_count < 350) {
        print generate_box_open();
        print_graph_row($graph_vars);
        print generate_box_close();
    } else {
        print_error("Search matches too many ports. Aggregate graphs are limited to 349 ports.");
    }
}

$row = 1; // FIXME. not know for what..

//include($config['html_dir'] . '/includes/port-sort.inc.php');
include($config['html_dir'] . '/pages/ports/' . $vars['format'] . '.inc.php');

// EOF
