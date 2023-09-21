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

if ($vars['editing']) {
    if ($readonly) {
        print_error_permission('You have insufficient permissions to edit settings.');
    } else {
        $override_sysContact_bool = $vars['override_sysContact'];
        if (isset($vars['sysContact'])) {
            $override_sysContact_string = $vars['sysContact'];
        }
        $disable_notify = $vars['disable_notify'];

        if ($override_sysContact_bool) {
            set_dev_attrib($device, 'override_sysContact_bool', '1');
        } else {
            del_dev_attrib($device, 'override_sysContact_bool');
        }
        if (isset($override_sysContact_string)) {
            set_dev_attrib($device, 'override_sysContact_string', $override_sysContact_string);
        }
        if ($disable_notify) {
            set_dev_attrib($device, 'disable_notify', '1');
        } else {
            del_dev_attrib($device, 'disable_notify');
        }

        // 2019-12-05 23:30:00

        if (isset($vars['ignore_until']) && $vars['ignore_until_enable']) {
            $update['ignore_until'] = $vars['ignore_until'];
            $device['ignore_until'] = $vars['ignore_until'];
        } else {
            $update['ignore_until'] = ['NULL'];
            $device['ignore_until'] = '';
        }

        foreach (['ignore'] as $param) {

            if (!in_array($param, ['purpose', 'poller_id'])) {
                // Boolean params
                $vars[$param] = get_var_true($vars[$param]) ? '1' : '0';
            }
            if ($vars[$param] != $device[$param]) {
                $update[$param] = $vars[$param];
            }
        }

        dbUpdate($update, 'devices', '`device_id` = ?', [$device['device_id']]);

        $update_message = "Device alert settings updated.";
        $updated        = 1;

        // Request for clear WUI cache
        set_cache_clear('wui');

        $device = dbFetchRow("SELECT * FROM `devices` WHERE `device_id` = ?", [$device['device_id']]);

    }

    if ($updated && $update_message) {
        print_message($update_message);
    } elseif ($update_message) {
        print_error($update_message);
    }
}

$override_sysContact_bool   = get_dev_attrib($device, 'override_sysContact_bool');
$override_sysContact_string = get_dev_attrib($device, 'override_sysContact_string');
$disable_notify             = get_dev_attrib($device, 'disable_notify');

$form = ['type'     => 'horizontal',
         'id'       => 'edit',
         //'space'     => '20px',
         'title'    => 'Alert Settings',
         //'class'     => 'box box-solid',
         'fieldset' => ['edit' => ''],
];

$form['row'][0]['editing'] = [
  'type'  => 'hidden',
  'value' => 'yes'];

$form['row'][1]['ignore'] = [
  'type'        => 'toggle',
  'view'        => 'toggle',
  'palette'     => 'yellow',
  'name'        => 'Ignore Device',
  //'fieldset'    => 'edit',
  'placeholder' => 'Suppresses alerts and notifications. Hides device from some UI elements.',
  'readonly'    => $readonly,
  'value'       => $device['ignore']];

$form['row'][2]['ignore_until']        = [
  'type'        => 'datetime',
  //'fieldset'    => 'edit',
  'name'        => 'Ignore Until',
  'placeholder' => '',
  //'width'       => '250px',
  'readonly'    => $readonly,
  'disabled'    => empty($device['ignore_until']),
  'min'         => 'current',
  'value'       => $device['ignore_until'] ?: ''];
$form['row'][2]['ignore_until_enable'] = [
  'type'     => 'toggle',
  'size'     => 'large',
  'readonly' => $readonly,
  'onchange' => "toggleAttrib('disabled', 'ignore_until')",
  'value'    => !empty($device['ignore_until'])];

$form['row'][3]['override_sysContact'] = [
  'type'        => 'toggle',
  'view'        => 'toggle',
  'palette'     => 'yellow',
  'name'        => 'Override sysContact',
  //'fieldset'    => 'edit',
  'placeholder' => 'Use custom contact below',
  'readonly'    => $readonly,
  'onchange'    => "toggleAttrib('disabled', 'sysContact')",
  'value'       => $override_sysContact_bool];
$form['row'][4]['sysContact']          = [
  'type'        => 'text',
  //'fieldset'    => 'edit',
  'name'        => 'Custom contact',
  'placeholder' => '',
  'width'       => '250px',
  'readonly'    => $readonly,
  'disabled'    => !$override_sysContact_bool,
  'value'       => $override_sysContact_string];
$form['row'][5]['disable_notify']      = [
  'type'        => 'toggle',
  'view'        => 'toggle',
  'palette'     => 'red',
  'name'        => 'Disable notifications',
  //'fieldset'    => 'edit',
  'placeholder' => 'Don\'t send alert notifications (but write to eventlog)',
  'readonly'    => $readonly,
  'value'       => $disable_notify];
$form['row'][7]['submit']              = [
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
