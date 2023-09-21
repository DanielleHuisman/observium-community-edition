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

include($config['html_dir'] . '/includes/user_menu.inc.php');

if ($_SESSION['userlevel'] < 10) {
    print_error_permission();
    return;
}

$userlist = [];
foreach (dbFetchColumn('SELECT DISTINCT `user` FROM `authlog` WHERE `result` NOT LIKE ? AND `user` != ?;', ['%Fail%', '']) as $user) {
    $user            = escape_html($user);
    $userlist[$user] = ($user === '' ? '<Anonymous>' : $user);
}

ksort($userlist);
$form = ['type'          => 'rows',
         //'space' => '5px',
         //'brand' => NULL,
         //'class' => 'box box-solid',
         'submit_by_key' => TRUE];
//'url'   => generate_url($vars)); // Use POST in authlog search
// Row
$form['row'][0]['user']    = [
  'type'   => 'multiselect',
  'name'   => 'Select Users',
  'width'  => '100%',
  'value'  => $vars['user'],
  'values' => $userlist];
$form['row'][0]['result']  = [
  'type'   => 'multiselect',
  'name'   => 'Action',
  'width'  => '100%',
  'value'  => $vars['result'],
  'values' => ['Logged In'              => ['name' => 'Logon', 'class' => ''],
               'Logged Out'             => ['name' => 'Logout', 'class' => ''],
               'Authentication Failure' => ['name' => 'Failed', 'class' => '']]];
$form['row'][0]['address'] = [
  'type'        => 'text',
  'name'        => 'Address',
  'placeholder' => TRUE,
  'width'       => '100%',
  'value'       => $vars['address']];
//$form['row'][0]['date'] = array(
//                                'type'        => 'datetime',
//                                'name'        => 'Date',
//                                //'min'     => dbFetchCell('SELECT `datetime` FROM `authlog`' . $where . ' ORDER BY `datetime` LIMIT 0,1;'),
//                                //'max'     => dbFetchCell('SELECT `datetime` FROM `authlog`' . $where . ' ORDER BY `datetime` DESC LIMIT 0,1;'),
//                                'to'          => $vars['date_to'],
//                                'from'        => $vars['date_from']);
// Search button pull-rigth
$form['row'][0]['search'] = [
  'type'  => 'submit',
  //'name'        => 'Search',
  //'icon'        => 'icon-search',
  'right' => TRUE,
];

print_form($form);
unset($form, $userlist);

// Pagination
$vars['pagination'] = TRUE;

print_authlog($vars);

register_html_title('Authlog');

// EOF
