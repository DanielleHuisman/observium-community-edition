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

// Edit Rule Modal

$modal_args = array(
  'id'    => 'modal-edit_probe_' . $probe['probe_id'],
  'title' => 'Edit Probe',
  //'hide'  => TRUE,
  //'fade'  => TRUE,
  //'role'  => 'dialog',
  'class' => 'modal-lg'
);

$form = array('type'       => 'horizontal',
              'id'         => 'edit_probe_' . $probe['probe_id'],
              'userlevel'  => 10,          // Minimum user level for display form
              'modal_args' => $modal_args, // !!! This generate modal specific form
              //'help'     => 'This will completely delete the rule and all associations and history.',
              'class'      => '' // Clean default box class!
              //'url'        => generate_url(array('page' => 'syslog_rules'))
);

$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

$form['row'][0]['probe_id'] = array(
  'type'     => 'hidden',
  'fieldset' => 'body',
  'value'    => $probe['probe_id']);

$form['row'][1]['probe_type']    = array(
  'type'     => 'text',
  'fieldset' => 'body',
  'name'     => 'Type',
  'disabled' => TRUE,
  'class'    => 'input-xlarge',
  'value'    => $probe['probe_type']);

$form['row'][3]['probe_descr']    = array(
  'type'     => 'text',
  'fieldset' => 'body',
  'name'     => 'Description',
  'class'    => 'input-xxlarge',
  'value'    => $probe['probe_descr']);

$form['row'][4]['probe_args']   = array(
  'type'     => 'text',
  'fieldset' => 'body',
  'name'     => 'CLI Arguments',
  'class'    => 'input-xxlarge',
  //'style'       => 'margin-bottom: 10px;',
  'placeholder' => 'ie: -H custom.hostname.domain',
  'value'    => $probe['probe_args']);

$form['row'][8]['close']  = array(
  'type'      => 'submit',
  'fieldset'  => 'footer',
  'div_class' => '', // Clean default form-action class!
  'name'      => 'Close',
  'icon'      => '',
  'attribs'   => array('data-dismiss' => 'modal',
                       'aria-hidden'  => 'true'));
$form['row'][9]['action'] = array(
  'type'      => 'submit',
  'fieldset'  => 'footer',
  'div_class' => '', // Clean default form-action class!
  'name'      => 'Save Changes',
  'icon'      => 'icon-ok icon-white',
  //'right'       => TRUE,
  'class'     => 'btn-primary',
  'value'     => 'edit_probe');

$modals .= generate_form_modal($form);
unset($form);