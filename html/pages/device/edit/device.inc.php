<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$ping_skip            = get_entity_attrib('device', $device, 'ping_skip');
$override_type_string = get_entity_attrib('device', $device, 'override_type');
$override_type_bool   = !empty($override_type_string);

$default_type = $override_type_bool ? $config['os'][$device['os']]['type'] : $device['type'];
$device_types = array();
foreach ($config['device_types'] as $type)
{
  $device_types[$type['type']] = array('name' => nicecase($type['type']), 'icon' => $type['icon']);
  if ($type['type'] == $default_type)
  {
    $device_types[$type['type']]['subtext'] = 'Default';
    //$device_types[$type['type']]['class']   = 'error';
  }
}
if (!in_array($device['type'], array_keys($device_types)))
{
  $device_types[$device['type']] = array('name' => 'Other', 'icon' => $config['icon']['question']);
}

if ($vars['editing'])
{
  if ($readonly)
  {
    print_error_permission('You have insufficient permissions to edit settings.');
  } else {
    if (OBS_DEBUG) { print_vars($vars); }
    $updated = 0;

    // Changed sysLocation
    $override_sysLocation_bool = $vars['override_sysLocation'];
    if (isset($vars['sysLocation'])) { $override_sysLocation_string = $vars['sysLocation']; }

    if (get_entity_attrib('device', $device, 'override_sysLocation_bool')   != $override_sysLocation_bool ||
        get_entity_attrib('device', $device, 'override_sysLocation_string') != $override_sysLocation_string)
    {
      $updated = 2;
    }

    if ($override_sysLocation_bool) { set_entity_attrib( 'device', $device, 'override_sysLocation_bool', '1', $device['device_id']); }
    else                            { del_entity_attrib('device', $device, 'override_sysLocation_bool'); }
    if (isset($override_sysLocation_string)) { set_entity_attrib('device', $device, 'override_sysLocation_string', $override_sysLocation_string); }

    // Changed Skip ping
    $ping_skip_set = isset($vars['ping_skip']) && ($vars['ping_skip'] == 'on' || $vars['ping_skip'] == '1');
    if ($ping_skip != $ping_skip_set)
    {
      if ($ping_skip_set) { set_entity_attrib('device', $device, 'ping_skip', '1'); }
      else                { del_entity_attrib('device', $device, 'ping_skip'); }
      $ping_skip = get_entity_attrib('device', $device, 'ping_skip');
      $updated++;
    }
    # FIXME needs more sanity checking! and better feedback
    # FIXME -- update location too? Need to trigger geolocation!

    $update_array = array();

    // Changed Type
    if ($vars['type'] != $device['type'] && isset($device_types[$vars['type']]))
    {
      $update_array['type'] = $vars['type'];
      if (!$override_type_bool || $override_type_string != $vars['type'])
      {
        // Type overridden by user..
        if ($vars['type'] == $default_type)
        {
          del_entity_attrib('device', $device, 'override_type');
          $override_type_string = NULL;
        } else {
          set_entity_attrib('device', $device, 'override_type', $vars['type']);
          $override_type_string = $vars['type'];
        }
        $override_type_bool   = !empty($override_type_string);
      }
      $updated++;
    }

    foreach (array('purpose', 'ignore', 'disabled', 'poller_id') as $param)
    {
      if (!in_array($param, array('purpose', 'poller_id')))
      {
        // Boolead params
        $vars[$param] = in_array($vars[$param], array('on', '1')) ? '1' : '0';
      }
      if ($vars[$param] != $device[$param])
      {
        $update_array[$param] = $vars[$param];
        $updated++;
      }
    }

    if (count($update_array))
    {
      $rows_updated = dbUpdate($update_array, 'devices', '`device_id` = ?', array($device['device_id']));
    }
    //r($updated);
    //r($update_array);
    //r($rows_updated);
    
    if ($updated)
    {
      if ((bool)$vars['ignore'] != (bool)$device['ignore'])
      {
        log_event('Device '.((bool)$vars['ignore'] ? 'ignored' : 'attended').': '.$device['hostname'], $device['device_id'], 'device', $device['device_id'], 5);
      }
      if ((bool)$vars['disabled'] != (bool)$device['disabled'])
      {
        log_event('Device '.((bool)$vars['disabled'] ? 'disabled' : 'enabled').': '.$device['hostname'], $device['device_id'], 'device', $device['device_id'], 5);
      }
      $update_message = "Device record updated.";
      if ($override_sysLocation_bool) { $update_message.= " Please note that the updated sysLocation string will only be visible after the next poll."; }
      $updated = 1;

      // Request for clear WUI cache
      set_cache_clear('wui');

      $device = dbFetchRow("SELECT * FROM `devices` WHERE `device_id` = ?", array($device['device_id']));
    }
    else if ($rows_updated = '-1')
    {
      $update_message = "Device record unchanged. No update necessary.";
      $updated = -1;
    } else {
      $update_message = "Device record update error.";
    }
  }
}

$override_sysLocation_bool   = get_entity_attrib('device', $device, 'override_sysLocation_bool');
$override_sysLocation_string = get_entity_attrib('device', $device, 'override_sysLocation_string');

if ($updated && $update_message)
{
  print_message($update_message);
}
else if ($update_message)
{
  print_error($update_message);
}

      $form = array('type'      => 'horizontal',
                    'id'        => 'edit',
                    //'space'     => '20px',
                    'title'     => 'General Device Settings',
                    'icon'      => $config['icon']['tools'],
                    //'class'     => 'box box-solid',
                    'fieldset'  => array('edit' => ''),
                    );

      $form['row'][0]['editing']   = array(
                                      'type'        => 'hidden',
                                      'value'       => 'yes');
      $form['row'][1]['purpose']   = array(
                                      'type'        => 'text',
                                      //'fieldset'    => 'edit',
                                      'name'        => 'Description',
                                      //'class'       => 'input-xlarge',
                                      'width'       => '500px',
                                      'readonly'    => $readonly,
                                      'value'       => $device['purpose']);
      $form['row'][2]['type']      = array(
                                      'type'        => 'select',
                                      //'fieldset'    => 'edit',
                                      'name'        => 'Type',
                                      'width'       => '250px',
                                      'readonly'    => $readonly,
                                      'values'      => $device_types,
                                      'value'       => $device['type']);
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
      $form['row'][3]['sysLocation'] = array(
                                      'type'        => 'text',
                                      //'fieldset'    => 'edit',
                                      'name'        => 'Custom location',
                                      'placeholder' => '',
                                      'width'       => '250px',
                                      'readonly'    => $readonly,
                                      'disabled'    => !$override_sysLocation_bool,
                                      'value'       => $override_sysLocation_string);
      $form['row'][3]['override_sysLocation'] = array(
                                      'type'        => 'toggle',
                                      'size'        => 'large',
                                      //'fieldset'    => 'edit',
                                      //'placeholder' => 'Use custom location below.',
                                      'onchange'    => "toggleAttrib('disabled', 'sysLocation')",
                                      'readonly'    => $readonly,
                                      'value'       => $override_sysLocation_bool);

      $poller_list = array();
      $poller_list[0] = 'Default Poller';
      foreach(dbFetchRows("SELECT * FROM `pollers`") AS $poller)
      {
        $poller_list[$poller['poller_id']] = $poller['poller_name'];
      }

      $form['row'][4]['poller_id']      = array(
                                      'type'        => 'select',
                                      'name'        => 'Poller',
                                      'width'       => '250px',
                                      'readonly'    => $readonly,
                                      'disabled'    => !(count($poller_list) > 1),
                                      'values'      => $poller_list,
                                      'value'       => $device['poller_id']);

      $form['row'][5]['ping_skip'] = array(
                                      'type'        => 'toggle',
                                      'view'        => 'toggle',
                                      'palette'     => 'yellow',
                                      'name'        => 'Skip ping',
                                      //'fieldset'    => 'edit',
                                      'placeholder' => 'Skip ICMP echo checks, only SNMP availability.',
                                      'readonly'    => $readonly,
                                      'value'       => $ping_skip);
      // FIXME (Mike): $device['ignore'] and get_dev_attrib($device,'disable_notify') it is same/redundant options?
      $form['row'][6]['ignore'] = array(
                                      'type'        => 'toggle',
                                      'view'        => 'toggle',
                                      'palette'     => 'yellow',
                                      'name'        => 'Device ignore',
                                      //'fieldset'    => 'edit',
                                      'placeholder' => 'Ignore device for alerting and notifications.',
                                      'readonly'    => $readonly,
                                      'value'       => $device['ignore']);
      $form['row'][7]['disabled'] = array(
                                      'type'        => 'toggle',
                                      'view'        => 'toggle',
                                      'palette'     => 'red',
                                      'name'        => 'Disable',
                                      //'fieldset'    => 'edit',
                                      'placeholder' => 'Disables polling and discovery.',
                                      'readonly'    => $readonly,
                                      'value'       => $device['disabled']);
      $form['row'][8]['submit']    = array(
                                      'type'        => 'submit',
                                      'name'        => 'Save Changes',
                                      'icon'        => 'icon-ok icon-white',
                                      //'right'       => TRUE,
                                      'class'       => 'btn-primary',
                                      'readonly'    => $readonly,
                                      'value'       => 'save');

      print_form($form);
      unset($form);

// EOF
