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

$ping_skip            = get_entity_attrib('device', $device, 'ping_skip');
$override_type_string = get_entity_attrib('device', $device, 'override_type');
$override_type_bool   = !empty($override_type_string);

$default_type = $override_type_bool ? $config['os'][$device['os']]['type'] : $device['type'];
$device_types = [];
foreach ($config['device_types'] as $type) {
    $device_types[$type['type']] = ['name' => nicecase($type['type']), 'icon' => $type['icon']];
    if ($type['type'] == $default_type) {
        $device_types[$type['type']]['subtext'] = 'Default';
        //$device_types[$type['type']]['class']   = 'error';
    }
}
if (!array_key_exists($device['type'], $device_types)) {
    $device_types[$device['type']] = ['name' => 'Other', 'icon' => $config['icon']['question']];
}

if ($vars['editing']) {
    if ($readonly) {
        print_error_permission('You have insufficient permissions to edit settings.');
    } else {
        print_debug_vars($vars);
        $updated = 0;

        // Changed sysLocation
        $override_sysLocation_bool = $vars['override_sysLocation'];
        if (isset($vars['sysLocation'])) {
            $override_sysLocation_string = $vars['sysLocation'];
        }

        if (get_entity_attrib('device', $device, 'override_sysLocation_bool') != $override_sysLocation_bool ||
            get_entity_attrib('device', $device, 'override_sysLocation_string') != $override_sysLocation_string) {
            $updated = 2;
        }

        if ($override_sysLocation_bool) {
            set_entity_attrib('device', $device, 'override_sysLocation_bool', '1', $device['device_id']);
        } else {
            del_entity_attrib('device', $device, 'override_sysLocation_bool');
        }
        if (isset($override_sysLocation_string)) {
            set_entity_attrib('device', $device, 'override_sysLocation_string', $override_sysLocation_string);
        }

        // Changed Skip ping
        $ping_skip_set = isset($vars['ping_skip']) && get_var_true($vars['ping_skip']);
        if ($ping_skip != $ping_skip_set) {
            if ($ping_skip_set) {
                set_entity_attrib('device', $device, 'ping_skip', '1');
            } else {
                del_entity_attrib('device', $device, 'ping_skip');
            }
            $ping_skip = get_entity_attrib('device', $device, 'ping_skip');
            $updated++;
        }
        # FIXME needs more sanity checking! and better feedback
        # FIXME -- update location too? Need to trigger geolocation!

        $update_array = [];

        // Changed Type
        if ($vars['type'] != $device['type'] && isset($device_types[$vars['type']])) {
            $update_array['type'] = $vars['type'];
            if (!$override_type_bool || $override_type_string != $vars['type']) {
                // Type overridden by user..
                if ($vars['type'] == $default_type) {
                    del_entity_attrib('device', $device, 'override_type');
                    $override_type_string = NULL;
                } else {
                    set_entity_attrib('device', $device, 'override_type', $vars['type']);
                    $override_type_string = $vars['type'];
                }
                $override_type_bool = !empty($override_type_string);
            }
            $updated++;
        }

        foreach (['purpose', 'ignore', 'disabled', 'poller_id'] as $param) {
            if (!in_array($param, ['purpose', 'poller_id'])) {
                // Boolean params
                $vars[$param] = get_var_true($vars[$param]) ? '1' : '0';
            }
            if ($vars[$param] != $device[$param]) {
                $update_array[$param] = $vars[$param];
                $updated++;
            }
        }

        if (count($update_array)) {
            $rows_updated = dbUpdate($update_array, 'devices', '`device_id` = ?', [$device['device_id']]);
        }
        //r($updated);
        //r($update_array);
        //r($rows_updated);

        if ($updated) {
            if ((bool)$vars['ignore'] != (bool)$device['ignore']) {
                log_event('Device ' . ((bool)$vars['ignore'] ? 'ignored' : 'attended') . ': ' . $device['hostname'], $device['device_id'], 'device', $device['device_id'], 5);
            }
            if ((bool)$vars['disabled'] != (bool)$device['disabled']) {
                log_event('Device ' . ((bool)$vars['disabled'] ? 'disabled' : 'enabled') . ': ' . $device['hostname'], $device['device_id'], 'device', $device['device_id'], 5);
            }
            $update_message = "Device record updated.";
            if ($override_sysLocation_bool) {
                $update_message .= " Please note that the updated sysLocation string will only be visible after the next poll.";
            }
            $updated = 1;

            // Request for clear WUI cache
            set_cache_clear('wui');

            $device = dbFetchRow("SELECT * FROM `devices` WHERE `device_id` = ?", [$device['device_id']]);
        } elseif ($rows_updated = '-1') {
            $update_message = "Device record unchanged. No update necessary.";
            $updated        = -1;
        } else {
            $update_message = "Device record update error.";
        }
    }
}

$override_sysLocation_bool   = get_entity_attrib('device', $device, 'override_sysLocation_bool');
$override_sysLocation_string = get_entity_attrib('device', $device, 'override_sysLocation_string');

if ($updated && $update_message) {
    print_message($update_message);
} elseif ($update_message) {
    print_error($update_message);
}

$form = ['type'     => 'horizontal',
         'id'       => 'edit',
         //'space'     => '20px',
         'title'    => 'General Device Settings',
         'icon'     => $config['icon']['tools'],
         //'class'     => 'box box-solid',
         'fieldset' => ['edit' => ''],
];

$form['row'][0]['editing'] = [
  'type'  => 'hidden',
  'value' => 'yes'];
$form['row'][1]['purpose'] = [
  'type'     => 'text',
  //'fieldset'    => 'edit',
  'name'     => 'Description',
  //'class'       => 'input-xlarge',
  'width'    => '500px',
  'readonly' => $readonly,
  'value'    => $device['purpose']];
$form['row'][2]['type']    = [
  'type'     => 'select',
  //'fieldset'    => 'edit',
  'name'     => 'Type',
  'width'    => '250px',
  'readonly' => $readonly,
  'values'   => $device_types,
  'value'    => $device['type']];
/*
$form['row'][2]['reset_type'] = array(
                                'type'        => 'switch',
                                //'fieldset'    => 'edit',
                                //'onchange'    => "toggleAttrib('disabled', 'sysLocation')",
                                'readonly'    => $readonly,
                                'on-color'    => 'danger',
                                'off-color'   => 'primary',
                                'on-text'     => 'Reset',
                                'off-text'    => 'Keep',
                                'value'       => 0);
*/
$form['row'][3]['sysLocation']          = [
  'type'        => 'text',
  //'fieldset'    => 'edit',
  'name'        => 'Custom location',
  'placeholder' => '',
  'width'       => '250px',
  'readonly'    => $readonly,
  'disabled'    => !$override_sysLocation_bool,
  'value'       => $override_sysLocation_string];
$form['row'][3]['override_sysLocation'] = [
  'type'     => 'toggle',
  'size'     => 'large',
  //'fieldset'    => 'edit',
  //'placeholder' => 'Use custom location below.',
  'onchange' => "toggleAttrib('disabled', 'sysLocation')",
  'readonly' => $readonly,
  'value'    => $override_sysLocation_bool];

$poller_list                 = get_pollers();
$form['row'][4]['poller_id'] = [
  'type'      => 'select',
  'community' => FALSE, // not available on community edition
  'name'      => 'Poller',
  'width'     => '250px',
  'readonly'  => $readonly,
  'disabled'  => !(count($poller_list) > 1),
  'values'    => $poller_list,
  'value'     => $device['poller_id']];

$form['row'][5]['ping_skip'] = [
  'type'        => 'toggle',
  'view'        => 'toggle',
  'palette'     => 'yellow',
  'name'        => 'Skip ping',
  //'fieldset'    => 'edit',
  'placeholder' => 'Skip ICMP echo checks, only SNMP availability.',
  'readonly'    => $readonly,
  'value'       => $ping_skip];
// FIXME (Mike): $device['ignore'] and get_dev_attrib($device,'disable_notify') it is same/redundant options?
$form['row'][6]['ignore']   = [
  'type'        => 'toggle',
  'view'        => 'toggle',
  'palette'     => 'yellow',
  'name'        => 'Device ignore',
  //'fieldset'    => 'edit',
  'placeholder' => 'Suppress alerts and notifications and hide in some UI elements.',
  'readonly'    => $readonly,
  'value'       => $device['ignore']];
$form['row'][7]['disabled'] = [
  'type'        => 'toggle',
  'view'        => 'toggle',
  'palette'     => 'red',
  'name'        => 'Disable',
  //'fieldset'    => 'edit',
  'placeholder' => 'Disables polling and discovery.',
  'readonly'    => $readonly,
  'value'       => $device['disabled']];
$form['row'][8]['submit']   = [
  'type'     => 'submit',
  'name'     => 'Save Changes',
  'icon'     => 'icon-ok icon-white',
  //'right'       => TRUE,
  'class'    => 'btn-primary',
  'readonly' => $readonly,
  'value'    => 'save'];

print_form($form);
unset($form);

// EOF
