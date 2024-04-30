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

register_html_title("Edit user");

if ($_SESSION['userlevel'] < 10) {
    print_error_permission();
    return;
}

include($config['html_dir'] . '/includes/user_menu.inc.php');

// Load JS entity picker
register_html_resource('js', 'tw-sack.js');
register_html_resource('js', 'observium-entities.js');

?>

    <form method="post" action="" class="form form-inline">
        <div class="navbar navbar-narrow">
            <div class="navbar-inner">
                <div class="container">
                    <a class="brand">Edit User</a>
                    <ul class="nav">

                        <?php


                        // FIXME -- this is used in two places, maybe function it.
                        $user_list = auth_user_list();

                        echo('<li>');
                        // FIXME, currently users list more than 1000 have troubles with memory use
                        // Do not generate this unusable dropdown form, need to switch ajax input
                        if (safe_count($user_list) <= 512) {
                            $item = [
                              'id'    => 'page',
                              'value' => 'user_edit'
                            ];
                            echo(generate_form_element($item, 'hidden'));
                            $item = [
                              'id'       => 'user_id',
                              'title'    => 'Select User',
                              'width'    => '150px',
                              'onchange' => "location.href='user_edit/user_id=' + this.options[this.selectedIndex].value + '/';",
                              'values'   => $user_list,
                              'value'    => $vars['user_id']
                            ];
                            echo(generate_form_element($item, 'select'));
                        }
                        echo('
      </li>
    </ul>');

                        if ($vars['user_id']) {
                            // Load the user's information
                            if (isset($user_list[$vars['user_id']])) {
                                $user_data = $user_list[$vars['user_id']];
                            } else {
                                $user_data = dbFetchRow("SELECT * FROM `users` WHERE `user_id` = ?", [$vars['user_id']]);
                            }
                            if (!isset($user_data['username'])) {
                                $user_data['username'] = auth_username_by_id($vars['user_id']);
                            }
                            if (!isset($user_data['level']) && !is_numeric($user_data['level'])) {
                                $user_data['level'] = auth_user_level($user_data['username']);
                            }

                            humanize_user($user_data); // Get level_label, level_real, row_class, etc

                            // Delete the selected user.
                            if (auth_usermanagement() && $vars['user_id'] !== $_SESSION['user_id']) {
                                echo '<ul class="nav pull-right">';
                                echo '<li><a href="' . generate_url(['page'         => 'user_edit',
                                                                     'action'       => 'deleteuser',
                                                                     'user_id'      => $vars['user_id'],
                                                                     'confirm'      => 'yes',
                                                                     'requesttoken' => $_SESSION['requesttoken']]) . '"
                   data-toggle="confirm"
                   data-content="You have requested deletion of the user <strong>' . escape_html($user_data['username']) . '</strong>.<br /><span class=\'text-nowrap\'>This action can not be reversed.</span>"
                   data-placement="bottom" data-btn-ok-class="btn-sm btn-danger" data-btn-cancel-class="btn-sm btn-primary" data-popout="true" data-html="true">' . get_icon('cancel') . ' Delete User</a></li>';
                                echo '</ul>';

                                register_html_resource('js', 'bootstrap-confirmation.min.js');
                            }
                        }

                        ?>

                </div>
            </div>
        </div>
    </form>

<?php
if ($vars['user_id']) {
    // Check if correct auth secret passed
    $auth_secret_fail = empty($_SESSION['auth_secret']) || empty($vars['auth_secret']) || !hash_equals($_SESSION['auth_secret'], $vars['auth_secret']);
    //print_vars($auth_secret_fail);
    //$auth_secret_fail = TRUE;

    if ($vars['action'] == "deleteuser" && request_token_valid($vars)) {
        include($config['html_dir'] . "/pages/edituser/deleteuser.inc.php");
    } else {

        // Perform actions if requested

        if (auth_usermanagement() && isset($vars['action']) && request_token_valid($vars)) { // Admins always can change user info & password
            switch ($vars['action']) {
                case "changepass":
                    if ($vars['new_pass'] == "" || $vars['new_pass2'] == "") {
                        print_warning("Password cannot be blank.");
                    } elseif ($auth_secret_fail) {
                        // Incorrect auth secret, seems as someone try to hack system ;)
                        print_debug("Incorrect admin auth, get out from here nasty hacker.");
                    } elseif ($vars['new_pass'] === $vars['new_pass2'] &&
                              is_valid_param($vars['new_pass'], 'password')) {
                        $status = auth_change_password($user_data['username'], $vars['new_pass']);
                        if ($status) {
                            print_success("Password Changed.");
                        } else {
                            print_error("Password not changed.");
                        }
                    } else {
                        print_error("Passwords don't match or contain non printable chars.");
                    }
                    break;

                case "change_user":
                    if ($auth_secret_fail) {
                        // Incorrect auth secret, seems as someone try to hack system ;)
                        print_debug("Incorrect admin auth, get out from here nasty hacker.");
                    } else {
                        $update_array                  = [];
                        $vars['new_can_modify_passwd'] = (isset($vars['new_can_modify_passwd']) && $vars['new_can_modify_passwd'] ? 1 : 0);
                        foreach (['realname', 'level', 'email', 'descr', 'can_modify_passwd'] as $param) {
                            if ($vars['new_' . $param] != $user_data[$param]) {
                                $update_array[$param] = $vars['new_' . $param];
                            }
                        }
                        $status = FALSE;
                        if (count($update_array)) {
                            $status = dbUpdate($update_array, 'users', '`user_id` = ?', [$vars['user_id']]);
                        }
                        if ($status) {
                            print_success("User Info Changed.");
                        } else {
                            print_error("User Info not changed.");
                        }
                    }
                    break;
            }

            if ($status) {
                // Reload user info
                //$user_data = dbFetchRow("SELECT * FROM `users` WHERE `user_id` = ?", array($vars['user_id']));
                $user_data['username'] = auth_username_by_id($vars['user_id']);
                $user_data             = auth_user_info($user_data['username']);
                $user_data['level']    = auth_user_level($user_data['username']);
                humanize_user($user_data); // Get level_label, level_real, label_class, row_class, etc
            }
        }

        // FIXME -- output messages!
        if (($vars['submit'] === "user_perm_del" || $vars['action'] === "user_perm_del") && request_token_valid($vars)) {
            if ($auth_secret_fail) {
                // Incorrect auth secret, seems as someone try to hack system ;)
                print_debug("Incorrect admin auth, get out from here nasty hacker.");
            } else {
                if (isset($vars['entity_id'])) {
                } // use entity_id
                elseif (isset($vars[$vars['entity_type'] . '_entity_id'])) // use type_entity_id
                {
                    $vars['entity_id'] = $vars[$vars['entity_type'] . '_entity_id'];
                }

                $where  = '`user_id` = ? AND `entity_type` = ? AND `auth_mechanism` = ?' . generate_query_values_and($vars['entity_id'], 'entity_id');
                $params = [$vars['user_id'], $vars['entity_type'], $config['auth_mechanism']];
                //if (@dbFetchCell("SELECT COUNT(*) FROM `entity_permissions` WHERE " . $where, array($vars['user_id'], $vars['entity_type'])))
                if (dbExist('entity_permissions', $where, $params)) {
                    dbDelete('entity_permissions', $where, $params);
                }
            }
        } elseif (($vars['submit'] == "user_perm_add" || $vars['action'] == "user_perm_add") &&
                  request_token_valid($vars)) {
            if ($auth_secret_fail) {
                // Incorrect auth secret, seems as someone try to hack system ;)
                print_debug("Incorrect admin auth, get out from here nasty hacker.");
            } else {
                if (isset($vars['entity_id'])) { // use entity_id
                } elseif (isset($vars[$vars['entity_type'] . '_entity_id'])) { // use type_entity_id
                    $vars['entity_id'] = $vars[$vars['entity_type'] . '_entity_id'];
                }
                if (!is_array($vars['entity_id'])) {
                    $vars['entity_id'] = [$vars['entity_id']];
                }

                foreach ($vars['entity_id'] as $entry) {
                    $where  = '`user_id` = ? AND `entity_type` = ? AND `entity_id` = ? AND `auth_mechanism` = ?';
                    $params = [$vars['user_id'], $vars['entity_type'], $entry, $config['auth_mechanism']];
                    if (get_entity_by_id_cache($vars['entity_type'], $entry) && // Skip not exist entities
                        !dbExist('entity_permissions', $where, $params)) {
                        dbInsert(['entity_id' => $entry, 'entity_type' => $vars['entity_type'], 'user_id' => $vars['user_id'], 'auth_mechanism' => $config['auth_mechanism']], 'entity_permissions');
                    }
                }
            }
        }

        // Generate new auth secret
        session_set_var('auth_secret', md5(random_string()));

        ?>
        <div class="row"> <!-- main row begin -->

            <div class="col-md-7"> <!-- left column begin -->
                <div class="row"> <!-- left up row begin -->

                    <div class="col-md-<?php echo(auth_usermanagement() ? '6' : '12'); ?>"> <!-- userinfo begin -->

                        <div class="box box-solid">
                            <div class="box-header">
                                <h3 class="box-title">User Information</h3>
                            </div>
                            <div class="box-body no-padding">

                                <table class="table table-striped table-condensed">
                                    <tr>
                                        <th style="width: 100px;">User ID</th>
                                        <td><?php echo(escape_html($user_data['user_id'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th style="width: 100px;">Username</th>
                                        <td><?php echo(escape_html($user_data['username'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Real Name</th>
                                        <td><?php echo(escape_html($user_data['realname'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>User Level</th>
                                        <td><?php echo('<span class="label label-' . $user_data['label_class'] . '">' . $user_data['level_label'] . '</span>'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td><?php echo(escape_html($user_data['email'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Description</th>
                                        <td><?php echo(escape_html($user_data['descr'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>User Source</th>
                                        <td><?php echo(get_type_class_label($user_data['type'], 'user_type')); ?></td>
                                    </tr>
                                </table>

                                <div class="form-actions" style="margin: 0;">
                                    <?php
                                    if (auth_usermanagement()) {
                                        echo '<button class="btn btn-default pull-right" data-toggle="modal" data-target="#modal-user_edit"><i class="' . $config['icon']['user-edit'] . '"></i>&nbsp;Edit&nbsp;User</button>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>

                    </div> <!-- userinfo end -->

                    <?php
                    if (auth_usermanagement()) { // begin user edit modal

                        $form = ['type'  => 'horizontal',
                                 //'userlevel'  => 10,          // Minimum user level for display form
                                 'id'    => 'user_edit',
                                 'title' => 'Edit User: ' . escape_html($user_data['username']),
                                 //'modal_args' => $modal_args, // modal specific options
                                 //'help'      => 'This will delete the selected contact and any alert assocations.',
                                 //'class'     => '', // Clean default box class (default for modals)
                                 //'url'       => 'delhost/'
                        ];
                        //$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
                        //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!


                        $form['row'][] = ['user_id'     => [
                          'type'     => 'hidden',
                          'fieldset' => 'body',
                          'value'    => $user_data['user_id']],
                                          'auth_secret' => [
                                            'type'     => 'hidden',
                                            'fieldset' => 'body',
                                            'value'    => $_SESSION['auth_secret']]
                        ];

                        $form['row'][]['old_username'] = [
                          'type'        => 'text',
                          'fieldset'    => 'body',
                          'name'        => 'User Name',
                          'width'       => '80%',
                          'placeholder' => TRUE,
                          'disabled'    => TRUE,
                          'value'       => $user_data['username']];

                        $form['row'][]['new_realname']          = [
                          'type'        => 'text',
                          'fieldset'    => 'body',
                          'name'        => 'Real Name',
                          'width'       => '80%',
                          'placeholder' => TRUE,
                          'value'       => $user_data['realname']];
                        $form['row'][]['new_level']             = [
                          'type'     => 'select',
                          'fieldset' => 'body',
                          'name'     => 'User Level',
                          'width'    => '80%',
                          'subtext'  => TRUE,
                          'values'   => $GLOBALS['config']['user_level'],
                          'value'    => $user_data['level_real']];
                        $form['row'][]['new_email']             = [
                          'type'        => 'text',
                          'fieldset'    => 'body',
                          'name'        => 'E-mail',
                          'width'       => '80%',
                          'placeholder' => TRUE,
                          'value'       => $user_data['email']];
                        $form['row'][]['new_descr']             = [
                          'type'        => 'text',
                          'fieldset'    => 'body',
                          'name'        => 'Description',
                          'width'       => '80%',
                          'placeholder' => TRUE,
                          'value'       => $user_data['descr']];
                        $form['row'][]['new_can_modify_passwd'] = [
                          'type'        => 'toggle',
                          'view'        => 'toggle',
                          'fieldset'    => 'body',
                          'placeholder' => 'Allow the user to change his password',
                          'value'       => $user_data['can_modify_passwd']];

                        $form['row'][] = [
                          'close'  => [
                            'type'      => 'submit',
                            'fieldset'  => 'footer',
                            'div_class' => '', // Clean default form-action class!
                            'name'      => 'Close',
                            'icon'      => '',
                            'attribs'   => ['data-dismiss' => 'modal',  // dismiss modal
                                            'aria-hidden'  => 'true']], // do not sent any value
                          'action' => [
                            'type'      => 'submit',
                            'fieldset'  => 'footer',
                            'div_class' => '', // Clean default form-action class!
                            'name'      => 'Save Changes',
                            'icon'      => 'icon-ok icon-white',
                            //'right'       => TRUE,
                            'class'     => 'btn-primary',
                            //'disabled'    => TRUE,
                            'value'     => 'change_user']
                        ];

                        echo generate_form_modal($form);
                        unset($form);

                    } // end edit user modal

                    if (auth_usermanagement()) { // begin change password
                        $form = ['type'     => 'horizontal',
                                 //'space'   => '10px',
                                 'title'    => 'Change Password',
                                 'icon'     => $config['icon']['lock'],
                                 //'class'   => 'box box-solid',
                                 'fieldset' => ['change_password' => '']];
                        //'fieldset'  => array('change_password' => 'Change Password'));
                        $form['row'][0]['action']      = [
                          'type'  => 'hidden',
                          'value' => 'changepass'];
                        $form['row'][1]['auth_secret'] = [
                          'type'  => 'hidden',
                          'value' => $_SESSION['auth_secret']];
                        $form['row'][2]['new_pass']    = [
                          'type'     => 'password',
                          'fieldset' => 'change_password', // Group by fieldset
                          'name'     => 'New Password',
                          'width'    => '95%',
                          'value'    => ''];
                        $form['row'][3]['new_pass2']   = [
                          'type'     => 'password',
                          'fieldset' => 'change_password', // Group by fieldset
                          'name'     => 'Retype Password',
                          'width'    => '95%',
                          'value'    => ''];
                        $form['row'][10]['submit']     = [
                          'type'  => 'submit',
                          'name'  => 'Update&nbsp;Password',
                          'icon'  => $config['icon']['lock'],
                          'right' => TRUE,
                          'value' => 'save'];
                        echo('  <div class="col-md-6">' . PHP_EOL);
                        print_form($form);
                        unset($form, $i);
                        echo('  </div>' . PHP_EOL);
                    } // end change password
                    ?>
                </div> <!-- left up row end -->
                <!--<div class="col-md-12">-->

                <?php

                echo generate_box_open(['header-border' => TRUE, 'title' => 'Role Membership']);

                $role_membership = dbFetchRows("SELECT * FROM `roles_users` LEFT JOIN `roles` USING (`role_id`) WHERE `user_id` = ? AND `auth_mechanism` = ? ORDER BY `role_name`", [$user_data['user_id'], $config['auth_mechanism']]);

                $users = dbFetchRows("SELECT * FROM `users`");

                $role_list = [];
                if (!safe_empty($role_membership)) {
                    echo '<div class="box-body no-padding">';
                    echo('<table class="table table-hover table-condensed">');

                    $cols = [
                      ['', 'class="state-marker"'],
                      'username' => ['Name', 'style="width: 200px;"'],
                      'email'    => ['Users', 'style="width: 80px;"'],
                      'level'    => 'Description',
                    ];
                    //echo(get_table_header($cols));

                    foreach ($role_membership as $role) {

                        echo '<tr>';
                        echo '<td width="5"></td>';
                        echo '<td width="200" class="entity">' . escape_html($role['role_name']) . '</td>';
                        echo '<td>' . escape_html($role['role_descr']) . '</td>';
                        echo '<td width="40">';
                        $form = ['type' => 'simple'];

                        // Elements
                        $form['row'][0]['auth_secret'] = [
                          'type'  => 'hidden',
                          'value' => $_SESSION['auth_secret']];

                        $form['row'][0]['role_id'] = ['type'  => 'hidden',
                                                      'value' => $role['role_id']];
                        $form['row'][0]['action']  = ['type'  => 'hidden',
                                                      'value' => 'role_user_del'];
                        $form['row'][0]['submit']  = ['type'  => 'submit',
                                                      'name'  => ' ',
                                                      'class' => 'btn-danger btn-mini',
                                                      'icon'  => 'icon-trash',
                                                      'value' => 'role_user_del'];
                        print_form($form);
                        unset($form);

                        echo '</td>';

                        echo '</tr>';
                        $role_list[] = $role['role_id'];
                    }
                    echo('</table></div>');
                } else {
                    echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no role memberships</strong></p>');
                }


                $form = ['type'  => 'simple',
                         'style' => 'padding: 7px; margin: 0px;',
                         //'submit_by_key' => TRUE,
                         //'url'   => generate_url($vars)
                ];
                // Elements
                $form['row'][0]['auth_secret'] = ['type' => 'hidden', 'value' => $_SESSION['auth_secret']];
                $form['row'][0]['user_id']     = ['type' => 'hidden', 'value' => $user_data['user_id']];
                $form['row'][0]['action']      = ['type' => 'hidden', 'value' => 'role_user_add'];

                $form_items['users'] = [];

                $roles = dbFetchRows("SELECT * FROM `roles`");


                foreach ($roles as $role) {
                    if (!in_array($role['role_id'], $role_list)) {
                        $form_items['roles'][$role['role_id']] = ['name'  => escape_html($role['role_name']),
                                                                  'descr' => escape_html($role['role_descr'])];
                    }
                }

                $form['row'][0]['role_id'] = ['type'   => 'multiselect',
                                              'name'   => 'Add Role',
                                              'width'  => '250px',
                                              'values' => $form_items['roles']];
                // add button
                $form['row'][0]['Submit'] = ['type'  => 'submit',
                                             'name'  => 'Add',
                                             'icon'  => $config['icon']['plus'],
                                             'right' => TRUE,
                                             'value' => 'Add'];

                print_form($form);
                unset($form);

                echo generate_box_close();

                ?>

                <?php print_authlog(array_merge($vars, ['short' => TRUE, 'pagination' => FALSE])); ?>

            </div> <!-- left column end -->

            <div class="col-md-5"> <!-- right column begin -->

                <?php

                // Begin main permissions block
                //if ($user_data['permission_access'] === FALSE || $user_data['permission_read'] === FALSE || $user_data['permission_admin'])
                //{
                echo generate_box_open(['header-border' => TRUE, 'title' => 'Global Permissions']);
                echo('<p class="text-center text-uppercase text-' . $user_data['row_class'] . ' bg-' . $user_data['row_class'] . '" style="padding: 10px; margin: 0px;"><strong>' . $user_data['subtext'] . '</strong></p>');
                echo generate_box_close();
                //print_error($user_data['subtext']);
                //} else {
                // if user has access and not has read/secure read/edit use individual permissions
                //echo generate_box_open();
                //}

                // Always display (and edit permissions) also if user disabled or has global read or admin permissions

                // Cache user permissions
                foreach (dbFetchRows("SELECT * FROM `entity_permissions` WHERE `user_id` = ? AND `auth_mechanism` = ?", [$vars['user_id'], $config['auth_mechanism']]) as $entity) {
                    $user_permissions[$entity['entity_type']][$entity['entity_id']] = TRUE;
                }

                if (OBSERVIUM_EDITION !== 'community') {
                    // Bill Permissions
                    print_billing_permission_box('user', $user_permissions, $vars);

                    // Entity group permissions
                    print_group_permission_box('user', $user_permissions, $vars);
                }

                // Device permissions
                print_device_permission_box('user', $user_permissions, $vars);

                // Port permissions
                print_port_permission_box('user', $user_permissions, $vars);

                // Sensor permissions
                print_sensor_permission_box('user', $user_permissions, $vars);

                ?>

            </div> <!-- right column end -->

        </div> <!-- main row end -->

        <?php

    }

} else {

    //$users = dbFetchRows("SELECT * FROM `users` ORDER BY `username`");

    if ($count = safe_count($user_list)) {
        pagination($vars, 0, TRUE); // Get default pagesize/pageno
        $pageno     = $vars['pageno'];
        $pagesize   = $vars['pagesize'];
        $start      = $pagesize * $pageno - $pagesize;
        $pagination = $count >= $pagesize;
        if ($pagination) {
            $users = array_slice($user_list, $start, $pagesize);
            echo pagination($vars, $count);
        } else {
            $users = $user_list;
        }

        echo generate_box_open();
        echo '<table class="table table-hover table-condensed">';

        $cols = [
          ['', 'class="state-marker"'],
          'user_id'  => ['User ID', 'style="width: 80px;"'],
          'user'     => 'Username',
          'access'   => 'Access',
          'realname' => 'Real Name',
          'email'    => 'Email'];
        echo get_table_header($cols);

        foreach ($users as $user) {
            humanize_user($user);

            $user['edit_url'] = generate_url(['page' => 'user_edit', 'user_id' => $user['user_id']]);

            echo '<tr class="' . $user['row_class'] . '">
      <td class="state-marker"></td>
        <td>' . $user['user_id'] . '</td>
        <td><strong><a href="' . $user['edit_url'] . '">' . escape_html($user['username']) . '</a></strong></td>
        <!-- <td><strong>' . $user['level'] . '</strong></td> -->
        <td><i class="' . $user['icon'] . '"></i> <span class="label label-' . $user['label_class'] . '">' . $user['level_label'] . '</span></td>
        <td><strong>' . escape_html($user['realname']) . '</strong></td>
        <td><strong>' . escape_html($user['email']) . '</strong></td>
        <td>' . get_type_class_label($user['type'], 'user_type') . '</td>
      </tr>';
        }

        echo '</table>';
        echo generate_box_close();

        if ($pagination) {
            echo pagination($vars, $count);
        }
    } else {
        print_warning('There are no users in the database.');
    }

}

// EOF
