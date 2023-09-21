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

$neighbours_ports = dbFetchColumn('SELECT DISTINCT `port_id` FROM `neighbours` WHERE ' . $cache['where']['ports_permitted']);

$where = ' WHERE 1 ';
$where .= generate_query_values_and($neighbours_ports, 'port_id');
//r($where);

$form_items = [];

$form_items['devices'] = generate_form_values('device', dbFetchColumn('SELECT DISTINCT `device_id` FROM `neighbours`'));

// If device IDs passed, limit ports to specified devices
if ($vars['device']) {
    $neighbours_ports = dbFetchColumn('SELECT DISTINCT `port_id` FROM `ports`' . $where . generate_query_values_and($vars['device'], 'device_id'));
    $where            = ' WHERE 1 ';
    $where            .= generate_query_values_and($neighbours_ports, 'port_id');
    //r($where);
}

$form_params = ['platforms' => 'remote_platform',
                'versions'  => 'remote_version',
                'protocols' => 'protocol',
];
foreach ($form_params as $param => $column) {
    foreach (dbFetchColumn('SELECT DISTINCT `' . $column . '` FROM `neighbours`' . $where) as $entry) {
        if (!empty($entry)) {
            $form_items[$param][$entry] = ($param == 'protocols' ? nicecase($entry) : escape_html($entry));
        }
    }
}

$form                             = ['type'          => 'rows',
                                     'space'         => '5px',
                                     'submit_by_key' => TRUE,
                                     'url'           => generate_url($vars)];
$form['row'][0]['device']         = [
  'type'   => 'multiselect',
  'name'   => 'Device',
  'width'  => '100%',
  'value'  => $vars['device'],
  'groups' => ['', 'UP', 'DOWN', 'DISABLED'], // This is optgroup order for values (if required)
  'values' => $form_items['devices']];
$form['row'][0]['protocol']       = [
  'type'   => 'multiselect',
  'name'   => 'Protocol',
  'width'  => '100%',
  'value'  => $vars['protocol'],
  'values' => $form_items['protocols']];
$form['row'][0]['platform']       = [
  'type'   => 'multiselect',
  'name'   => 'Platform',
  'width'  => '100%',
  'value'  => $vars['platform'],
  'values' => $form_items['platforms']];
$form['row'][0]['version']        = [
  'type'   => 'multiselect',
  'name'   => 'Version',
  'width'  => '100%',
  'value'  => $vars['version'],
  'values' => $form_items['versions']];
$form['row'][0]['remote_port_id'] = [
  'type'   => 'select',
  'name'   => 'Version',
  'width'  => '100%',
  'value'  => escape_html($vars['remote_port_id']),
  'values' => ['' => 'All Devices', '1' => 'Known Devices', '0' => 'Unknown Devices']];
// search button
$form['row'][0]['search'] = [
  'type'  => 'submit',
  //'name'        => 'Search',
  //'icon'        => 'icon-search',
  'right' => TRUE];

$panel_form = ['type'          => 'rows',
               'title'         => 'Search Neighbours',
               'space'         => '10px',
               'submit_by_key' => TRUE,
               'url'           => generate_url($vars)];

$panel_form['row'][0]['device']   = $form['row'][0]['device'];
$panel_form['row'][0]['protocol'] = $form['row'][0]['protocol'];

$panel_form['row'][1]['platform'] = $form['row'][0]['platform'];
$panel_form['row'][1]['version']  = $form['row'][0]['version'];

$panel_form['row'][5]['remote_port_id'] = $form['row'][0]['remote_port_id'];
$panel_form['row'][5]['search']         = $form['row'][0]['search'];

// Register custom panel
register_html_panel(generate_form($panel_form));

echo '<div class="hidden-xl">';
print_form($form);
echo '</div>';

unset($form, $panel_form, $form_items);

$vars['pagination'] = 1;
print_neighbours($vars);

// EOF
