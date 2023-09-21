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

$link_array = ['page' => 'slas'];

$form_items = [];
$form_limit = 250; // Limit count for multiselect (use input instead)

$form_devices          = dbFetchColumn('SELECT DISTINCT `device_id` FROM `slas`;');
$form_items['devices'] = generate_form_values('device', $form_devices);

$form_params = ['rtt_sense' => 'rtt_sense',
                'rtt_type'  => 'rtt_type'];

foreach ($form_params as $param => $column) {
    $query = 'SELECT COUNT(DISTINCT `' . $column . '`) FROM `slas`' . generate_where_clause($cache['where']['devices_permitted']);
    $count = dbFetchCell($query);
    if ($count < $form_limit) {
        $query = 'SELECT DISTINCT `' . $column . '` FROM `slas`' . generate_where_clause($cache['where']['devices_permitted']) . ' ORDER BY `' . $column . '`';
        foreach (dbFetchColumn($query) as $entry) {
            if (empty($entry)) {
                continue;
            }
            $form_items[$param][$entry]['name'] = $entry;
        }
    }
}

$form                     = ['type'          => 'rows',
                             'space'         => '5px',
                             'submit_by_key' => TRUE,
                             'url'           => generate_url($vars)];
$form['row'][0]['device'] = [
  'type'   => 'multiselect',
  'name'   => 'Local Device',
  'width'  => '100%',
  'value'  => $vars['device'],
  'values' => $form_items['devices']];

$form['row'][0]['sla_target'] = [
  'type'        => 'text',
  'name'        => 'SLA Target',
  'value'       => $vars['sla_target'],
  'width'       => '100%', //'180px',
  'placeholder' => TRUE];

$form['row'][0]['rtt_type'] = [
  'type'   => 'multiselect',
  'name'   => 'Select RTT Type',
  'width'  => '100%',
  'value'  => $vars['rtt_type'],
  'values' => $form_items['rtt_type']];

$form['row'][0]['event'] = [
  'type'   => 'multiselect',
  'name'   => 'Select Status',
  'width'  => '100%',
  'value'  => $vars['event'],
  'values' => ['ok' => 'Ok', 'warning' => 'Warning', 'alert' => 'Alert', 'ignore' => 'Ignore']];

$form['row'][0]['rtt_sense'] = [
  'type'   => 'multiselect',
  'name'   => 'Select RTT Sense',
  'width'  => '100%',
  'value'  => $vars['rtt_sense'],
  'values' => $form_items['rtt_sense']];


// search button
$form['row'][0]['search'] = [
  'type'  => 'submit',
  //'name'        => 'Search',
  //'icon'        => 'icon-search',
  'right' => TRUE];

echo '<div class="hidden-xl">';
print_form($form);
echo '</div>';

unset($form, $panel_form, $form_items);

$navbar['brand'] = 'SLAs';
$navbar['class'] = 'navbar-narrow';

if (!isset($vars['rtt_type'])) {
    $navbar['options']['all']['class'] = 'active';
}
$navbar['options']['all']['url']  = generate_url(['page' => 'slas', 'rtt_type' => NULL]);
$navbar['options']['all']['text'] = 'All SLAs';

$vars_filter = $vars;
unset($vars_filter['rtt_type'], $vars_filter['owner']); // Do not filter rtt_type and owner for navbar

$sql = generate_sla_query($vars_filter);

$rtt_types  = [];
$sla_owners = [];
foreach (dbFetchRows($sql) as $sla) {
    $owner = ($sla['sla_owner'] == '' ? OBS_VAR_UNSET : $sla['sla_owner']);
    if (!isset($vars['rtt_type']) || $vars['rtt_type'] === $sla['rtt_type']) {
        if (!isset($sla_owners[$owner])) {
            $sla_owners[$owner] = nicecase($owner);
        }
    }

    if (!isset($vars['owner']) || $vars['owner'] === $owner) {
        $rtt_type = $sla['rtt_type'];

        if (isset($config['sla_type_labels'][$rtt_type])) {
            $rtt_label = $config['sla_type_labels'][$rtt_type];
        } else {
            $rtt_label = nicecase($rtt_type);
        }

        // Combine different types with same label
        if (!isset($rtt_types[$rtt_label]) || !in_array($rtt_type, $rtt_types[$rtt_label])) {
            $rtt_types[$rtt_label][] = $rtt_type;
        }
    }
}
ksort($rtt_types);
ksort($sla_owners);

foreach ($rtt_types as $text => $type) {
    $type = implode(',', $type);

    if ($vars['rtt_type'] === $type) {
        $navbar['options'][$type]['class'] = 'active';
    }
    $navbar['options'][$type]['url']  = generate_url(['page' => 'slas', 'rtt_type' => $type]);
    $navbar['options'][$type]['text'] = $text;
}

// Owners
$navbar['options']['owner'] = ['text' => 'Owners', 'right' => 'true'];

foreach ($sla_owners as $owner => $name) {
    if (!safe_empty($vars['owner']) && in_array($owner, (array)$vars['owner'])) {
        $navbar['options']['owner']['class']                       = 'active';
        $navbar['options']['owner']['url']                         = generate_url($vars, ['owner' => NULL]);
        $navbar['options']['owner']['text']                        .= ' (' . $name . ')';
        $navbar['options']['owner']['suboptions'][$owner]['url']   = generate_url($vars, ['owner' => NULL]);
        $navbar['options']['owner']['suboptions'][$owner]['class'] = 'active';
    } else {
        $navbar['options']['owner']['suboptions'][$owner]['url'] = generate_url($vars, ['owner' => $owner]);
    }
    $navbar['options']['owner']['suboptions'][$owner]['text'] = $name;
}

// Groups
$groups = get_type_groups('sla');

$navbar['options']['group'] = ['text' => 'Groups', 'right' => TRUE, 'community' => FALSE];
foreach ($groups as $group) {
    if (!safe_empty($vars['group']) && in_array($group['group_id'], (array)$vars['group'])) {
        $navbar['options']['group']['class']                                   = 'active';
        $navbar['options']['group']['url']                                     = generate_url($vars, ['group' => NULL]);
        $navbar['options']['group']['text']                                    .= ' (' . $group['group_name'] . ')';
        $navbar['options']['group']['suboptions'][$group['group_id']]['url']   = generate_url($vars, ['group' => NULL]);
        $navbar['options']['group']['suboptions'][$group['group_id']]['class'] = 'active';
    } else {
        $navbar['options']['group']['suboptions'][$group['group_id']]['url'] = generate_url($vars, ['group' => $group['group_id']]);
    }
    $navbar['options']['group']['suboptions'][$group['group_id']]['text'] = $group['group_name'];
}

$navbar['options']['graphs']['text']  = 'Graphs';
$navbar['options']['graphs']['icon']  = $config['icon']['graphs'];
$navbar['options']['graphs']['right'] = TRUE;

if ($vars['view'] === 'graphs') {
    $navbar['options']['graphs']['class'] = 'active';
    $navbar['options']['graphs']['url']   = generate_url($vars, ['view' => NULL]);
} else {
    $navbar['options']['graphs']['url'] = generate_url($vars, ['view' => 'graphs']);
}

register_html_title('SLAs');

print_navbar($navbar);

print_sla_table($vars);

// EOF
