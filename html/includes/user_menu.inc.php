<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package        observium
 * @subpackage     webui
 * @author         Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$isUserlist = (isset($vars['user_id']) ? TRUE : FALSE);

$navbar['class'] = 'navbar-narrow';
$navbar['brand'] = 'Users';

$navbar['options']['user_edit']['url']  = generate_url(['page' => 'user_edit']);
$navbar['options']['user_edit']['text'] = 'Users';
$navbar['options']['user_edit']['icon'] = $config['icon']['user-edit'];

$navbar['options']['roles']['url']  = generate_url(['page' => 'roles']);
$navbar['options']['roles']['text'] = 'Roles';
$navbar['options']['roles']['icon'] = $config['icon']['users'];

$navbar['options_right']['authlog']['url']  = generate_url(['page' => 'authlog']);
$navbar['options_right']['authlog']['text'] = 'Authentication Log';
$navbar['options_right']['authlog']['icon'] = $config['icon']['user-log'];

if (auth_usermanagement()) {
    $navbar['options_right']['user_add']['url']  = generate_url(['page' => 'user_add']);
    $navbar['options_right']['user_add']['text'] = 'Add User';
    $navbar['options_right']['user_add']['icon'] = $config['icon']['user-add'];
}

$navbar['options_right']['role_add']['url']       = '#modal-role_add';
$navbar['options_right']['role_add']['link_opts'] = 'data-toggle="modal"';
$navbar['options_right']['role_add']['text']      = 'Add Role';
$navbar['options_right']['role_add']['icon']      = $config['icon']['plus'];

if (isset($navbar['options'][$vars['page']])) {
    $navbar['options'][$vars['page']]['class'] = 'active';
}

if ($isUserlist) {
    $navbar['options_right']['edit']['url']  = generate_url(['page' => 'edituser']);
    $navbar['options_right']['edit']['text'] = 'Back to userlist';
    $navbar['options_right']['edit']['icon'] = 'icon-chevron-left';
}

print_navbar($navbar);
unset($navbar);

$form = ['type'      => 'horizontal',
         'userlevel' => 10,          // Minimum user level for display form
         'id'        => 'modal-role_add',
         'title'     => 'Add Role',
         'url'       => generate_url(['page' => 'roles']),
];
//$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
//$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

$form_params = [];

$form['row'][1]['role_name']  = [
  'type'        => 'text',
  'fieldset'    => 'body',
  'name'        => 'Role Name',
  'class'       => 'input-xlarge',
  'placeholder' => 'Network Technician',
  'value'       => ''];
$form['row'][2]['role_descr'] = [
  'type'        => 'text',
  'fieldset'    => 'body',
  'name'        => 'Description',
  'class'       => 'input-xlarge',
  'placeholder' => 'Permits read access to all devices',
  'value'       => ''];

$form['row'][99]['close']  = [
  'type'      => 'submit',
  'fieldset'  => 'footer',
  'div_class' => '', // Clean default form-action class!
  'name'      => 'Close',
  'icon'      => '',
  'attribs'   => ['data-dismiss' => 'modal',
                  'aria-hidden'  => 'true']];
$form['row'][99]['action'] = [
  'type'      => 'submit',
  'fieldset'  => 'footer',
  'div_class' => '', // Clean default form-action class!
  'name'      => 'Add Role',
  'icon'      => 'icon-ok icon-white',
  //'right'       => TRUE,
  'class'     => 'btn-primary',
  'value'     => 'role_add'];

echo generate_form_modal($form);
unset($form, $form_params);


// EOF
