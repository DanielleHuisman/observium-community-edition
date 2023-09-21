<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Global write permissions required.
if ($_SESSION['userlevel'] < 10) {
    print_error_permission();
    return;
}

include($config['html_dir'] . '/includes/user_menu.inc.php');

register_html_title("Add User");

if (!auth_usermanagement()) {
    print_error('Auth module does not allow user management!');

    return;
}

if ($vars['submit'] === 'add_user' && request_token_valid($vars)) {
    if ($vars['new_username']) {
        $vars['new_username'] = strip_tags($vars['new_username']);
        if (!is_valid_param($vars['new_username'], 'username')) {
            print_error('User name contain not allowed chars or longer than 256.');
        } elseif (!auth_user_exists($vars['new_username'])) {
            if (isset($vars['can_modify_passwd'])) {
                $vars['can_modify_passwd'] = 1;
            } else {
                $vars['can_modify_passwd'] = 0;
            }

            if (!$vars['new_password']) {
                print_warning("Please enter a password!");
            } elseif (!is_valid_param($vars['new_password'], 'password')) {
                print_error('Password contain non printable chars.');
            } elseif (adduser($vars['new_username'],
                              $vars['new_password'],
                              $vars['new_level'],
                              $vars['new_email'],
                              $vars['new_realname'],
                              $vars['can_modify_passwd'],
                              $vars['new_description'])) {
                print_success('User ' . escape_html($vars['new_username']) . ' added!');
            }
        } else {
            print_error('User with this name already exists!');
        }
    } else {
        print_warning("Please enter a username!");
    }
}

$form = [
  'type' => 'horizontal',
  'id'   => 'add_user',
  //'space'     => '20px',
  //'title'     => 'Add User',
];
// top row div
$form['fieldset']['user'] = [
  'div'   => 'top',
  'title' => 'User Properties',
  'icon'  => $config['icon']['user-edit'],
  'class' => 'col-md-6'
];
$form['fieldset']['info'] = [
  'div'   => 'top',
  'title' => 'Optional Information',
  'icon'  => $config['icon']['info'],
  //'right' => TRUE,
  'class' => 'col-md-6 col-md-pull-0'
];
// bottom row div
$form['fieldset']['submit'] = [
  'div'   => 'bottom',
  'style' => 'padding: 0px;',
  'class' => 'col-md-12'
];

//$form['row'][0]['editing']   = array(
//                                'type'        => 'hidden',
//                                'value'       => 'yes');
// left fieldset
$form['row'][1]['new_username']      = [
  'type'     => 'text',
  'fieldset' => 'user',
  'name'     => 'Username',
  'width'    => '250px',
  'value'    => $vars['new_username']
];
$form['row'][2]['new_password']      = [
  'type'          => 'password',
  'fieldset'      => 'user',
  'name'          => 'Password',
  'width'         => '250px',
  'show_password' => TRUE,
  'value'         => $vars['new_password']
];
$form['row'][3]['can_modify_passwd'] = [
  'type'        => 'toggle',
  'view'        => 'toggle',
  'fieldset'    => 'user',
  'name'        => '',
  'placeholder' => 'Allow the user to change his password',
  'value'       => 1
];
$form['row'][4]['new_realname']      = [
  'type'     => 'text',
  'fieldset' => 'user',
  'name'     => 'Real Name',
  'width'    => '250px',
  'value'    => $vars['new_realname']
];
$form['row'][5]['new_level']         = [
  'type'     => 'select',
  'fieldset' => 'user',
  'name'     => 'User Level',
  'width'    => '250px',
  'subtext'  => TRUE,
  'values'   => $GLOBALS['config']['user_level'],
  'value'    => $vars['new_level'] ?? 1
];

// right fieldset
$form['row'][15]['new_email']       = [
  'type'     => 'text',
  'fieldset' => 'info',
  'name'     => 'E-mail',
  'width'    => '250px',
  'value'    => $vars['new_email']
];
$form['row'][16]['new_description'] = [
  'type'     => 'text',
  'fieldset' => 'info',
  'name'     => 'Description',
  'width'    => '250px',
  'value'    => $vars['new_description']
];

$form['row'][30]['submit'] = [
  'type'     => 'submit',
  'fieldset' => 'submit',
  'name'     => 'Add User',
  'icon'     => 'icon-ok icon-white',
  //'right'       => TRUE,
  'class'    => 'btn-primary',
  'value'    => 'add_user'
];

print_form_box($form);
unset($form);

// EOF
