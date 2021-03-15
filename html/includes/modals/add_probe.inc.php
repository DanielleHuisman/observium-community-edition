<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

/* Begin Add Probe Modal */

$form = array('type'       => 'horizontal',
              'userlevel'  => 10,          // Minimum user level for display form
              'id'         => 'modal-add_probe',
              'title'      => 'Add Probe',
              'url'        => generate_url(array('page' => 'probes')),
);

$form_params = array();
$form_params['oid_type']['GAUGE']   = array('name' => 'GAUGE',   'subtext' => 'Values is simply stored as-is, is for things like temperatures.');
$form_params['oid_type']['COUNTER'] = array('name' => 'COUNTER', 'subtext' => 'Data source assumes that the counter never decreases.');

$form_items['devices'] = generate_form_values('device'); // Always all devices

if($vars['page'] == "device")
{ // On device page, hardcode hidden device id.
  $form['row'][1]['form_device_id'] = array(
    'type'     => 'hidden',
    'value'    => $device['device_id']);
} else {
  $form['row'][1]['form_device_id'] = array(
    'type'     => 'select',
    'fieldset' => 'body',
    'name'     => 'Device',
    'value'    => $vars['device_id'],
    'width'    => '100%',
    'values'   => $form_items['devices']);
}

foreach ($config['probes'] as $probe_type => $entry)
{
  if (isset($entry['enable']) && !$entry['enable']) { continue; } // Skip not enabled probes

  $form_items['probe_types'][$probe_type] = [ 'name' => $probe_type, 'subtext' => $entry['descr'] ];

  // Check if probe exist
  if (!get_probe_path($probe_type))
  {
    $form_items['probe_types'][$probe_type]['class'] = 'bg-warning';
    $form_items['probe_types'][$probe_type]['subtext'] .= ' (Not installed)';
  }
}

$form['row'][2]['form_probe_type'] = array(
  'type'        => 'select',
  'fieldset'    => 'body',
  'name'        => 'Probe Type',
  'width'       => '100%',
  'placeholder' => 'check_snmp_cisco_wlc',
  'values'      => $form_items['probe_types']);

$form['row'][3]['form_probe_descr'] = array(
  'type'     => 'text',
  'fieldset' => 'body',
  'name'     => 'Description',
  'class'    => 'input-xlarge',
  'value'    => '');

$form['row'][4]['form_probe_args'] = array(
  'type'        => 'text',
  'fieldset'    => 'body',
  'name'        => 'Extra Arguments',
  'class'       => 'input-xlarge',
  'placeholder' => 'ie: -H custom.hostname.domain',
  'value'       => '');

$form['row'][5]['form_probe_no_default'] = array(
  'type'        => 'checkbox',
  'fieldset'    => 'body',
  'name'        => 'No Default Arguments',
  'placeholder' => 'Disable default probe arguments',
  'class'       => 'input-xlarge');


$form['row'][99]['close'] = array(
  'type'        => 'submit',
  'fieldset'    => 'footer',
  'div_class'   => '', // Clean default form-action class!
  'name'        => 'Close',
  'icon'        => '',
  'attribs'     => array('data-dismiss' => 'modal',
                         'aria-hidden'  => 'true'));
$form['row'][99]['action'] = array(
  'type'        => 'submit',
  'fieldset'    => 'footer',
  'div_class'   => '', // Clean default form-action class!
  'name'        => 'Add Probe',
  'icon'        => 'icon-ok icon-white',
  //'right'       => TRUE,
  'class'       => 'btn-primary',
  'value'       => 'add_probe');

echo generate_form_modal($form);
unset($form, $form_params);
