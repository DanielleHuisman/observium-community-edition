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

// Active by default
if (!isset($vars['active'])) {
    $vars['active'] = '1';
}

$form_items = [];

$form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `neighbours`' .
                                                                      generate_where_clause($cache['where']['devices_permitted'])));

// If device IDs passed, limit ports to specified devices
if ($vars['device']) {
    $neighbours_ports = dbFetchColumn('SELECT `port_id` FROM `ports`' .
                                      generate_where_clause(generate_query_values($vars['device'], 'device_id'), $cache['where']['ports_permitted']));
} else {
    $neighbours_ports = dbFetchColumn('SELECT DISTINCT `port_id` FROM `neighbours` ' . generate_where_clause($cache['where']['ports_permitted']));
}
$where = generate_where_clause(generate_query_values($neighbours_ports, 'port_id'));
//r($where);

$form_params = [
    'platforms' => 'remote_platform',
    'versions'  => 'remote_version',
    'protocols' => 'protocol',
    'remote'    => 'remote_hostname'
];
foreach ($form_params as $param => $column) {
    foreach (dbFetchColumn('SELECT DISTINCT `' . $column . '` FROM `neighbours`' . $where) as $entry) {
        if (!empty($entry)) {
            $form_items[$param][$entry] = $param === 'protocols' ? nicecase($entry) : $entry;
        }
    }
    if (!safe_empty($form_items[$param])) {
        asort($form_items[$param]);
    }
}
//r($form_items);

$form = [
    'type'          => 'rows',
    'space'         => '5px',
    'submit_by_key' => TRUE,
    'url'           => generate_url($vars)
];

$form['row'][0]['device'] = [
    'type'   => 'multiselect',
    'name'   => 'Device',
    'width'  => '100%',
    'value'  => $vars['device'],
    'groups' => [ '', 'UP', 'DOWN', 'DISABLED' ], // This is optgroup order for values (if required)
    'values' => $form_items['devices']
];
$form['row'][0]['protocol'] = [
    'type'   => 'multiselect',
    'name'   => 'Protocol',
    'width'  => '100%',
    'grid'   => 1,
    'value'  => $vars['protocol'],
    'values' => $form_items['protocols']
];
$form['row'][0]['platform'] = [
    'type'   => 'multiselect',
    'name'   => 'Platform',
    'width'  => '100%',
    'value'  => $vars['platform'],
    'values' => $form_items['platforms']
];
$form['row'][0]['version'] = [
    'type'   => 'multiselect',
    'name'   => 'Version',
    'width'  => '100%',
    'value'  => $vars['version'],
    'values' => $form_items['versions']
];
$form['row'][0]['remote'] = [
    'type'   => 'multiselect',
    'name'   => 'Remote Host',
    'width'  => '100%',
    'value'  => $vars['remote'],
    //'groups' => [ '', 'UP', 'DOWN', 'DISABLED' ], // This is optgroup order for values (if required)
    'values' => $form_items['remote']
];
$form['row'][0]['known'] = [
    'type'   => 'select',
    'name'   => 'Version',
    'width'  => '100%',
    'grid'   => 1,
    'value'  => $vars['known'],
    'values' => [ '' => 'Any', '1' => 'Known Remote', '0' => 'Unknown Remote' ]
];
$form['row'][0]['active'] = [
    'type'     => 'switch',
    'on-text'  => 'Active',
    'on-color' => 'primary',
    'on-icon'  => 'icon-eye-open',
    'on-value' => '1',
    'off-text' => 'Inactive',
    'off-icon' => 'icon-eye-close',
    //'off-color' => 'default',
    'off-value' => '0',
    'grid'     => 1,
    //'size'          => 'large',
    //'height'        => '15px',
    'title'    => 'Show Active/Inactive',
    //'placeholder'   => 'Disabled',
    //'readonly'      => TRUE,
    //'disabled'      => TRUE,
    //'submit_by_key' => TRUE,
    'value'    => $vars['active']
];

// search button
$form['row'][0]['search'] = [
    'type'   => 'submit',
    //'name'        => 'Search',
    //'icon'        => 'icon-search',
    'grid'   => 1,
    'right'  => TRUE
];

echo '<div class="hidden-xl">';
print_form($form);
echo '</div>';

$panel_form = [
    'type'          => 'rows',
    'title'         => 'Search Neighbours',
    'space'         => '10px',
    'submit_by_key' => TRUE,
    'url'           => generate_url($vars)
];

$panel_form['row'][0]['device']   = $form['row'][0]['device'];
$panel_form['row'][0]['protocol'] = $form['row'][0]['protocol'];
unset($panel_form['row'][0]['protocol']['grid']);

$panel_form['row'][1]['platform'] = $form['row'][0]['platform'];
$panel_form['row'][1]['version']  = $form['row'][0]['version'];

$panel_form['row'][5]['remote']   = $form['row'][0]['remote'];
$panel_form['row'][5]['remote']['grid'] = 4;
$panel_form['row'][5]['known']    = $form['row'][0]['known'];
$panel_form['row'][5]['known']['grid'] = 4;
$panel_form['row'][5]['active']   = $form['row'][0]['active'];
$panel_form['row'][5]['active']['grid'] = 2;
$panel_form['row'][5]['search']   = $form['row'][0]['search'];
$panel_form['row'][5]['search']['grid'] = 2;

// Register custom panel
register_html_panel(generate_form($panel_form));

unset($form, $panel_form, $form_items);

$vars['pagination'] = 1;
print_neighbours($vars);

// EOF
