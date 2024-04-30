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

// print_vars($permissions);

// Global write permissions required.
if ($_SESSION['userlevel'] < 10) {
    print_error_permission();
    return;
}

include($config['html_dir'] . '/includes/user_menu.inc.php');

register_html_title("Roles");

if (isset($vars['role_id'])) {

    // Load JS entity picker
    register_html_resource('js', 'tw-sack.js');
    register_html_resource('js', 'observium-entities.js');

    $role = dbFetchRow("SELECT * FROM `roles` WHERE `role_id` = ?", [$vars['role_id']]);

    if (!safe_empty($role)) {

        ?>

        <div class="row"> <!-- main row begin -->

            <div class="col-md-6"> <!-- left column begin -->
                <div class="row"> <!-- left up row begin -->

                    <div class="col-md-12"> <!-- userinfo begin -->

                        <div class="box box-solid">
                            <div class="box-header">
                                <h3 class="box-title">Role Information</h3>
                            </div>
                            <div class="box-body no-padding">

                                <table class="table table-striped table-condensed">
                                    <tr>
                                        <th style="width: 100px;">Role ID</th>
                                        <td><?php echo(escape_html($role['role_id'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th style="width: 100px;">Name</th>
                                        <td><?php echo(escape_html($role['role_name'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th style="width: 100px;">Description</th>
                                        <td><?php echo(escape_html($role['role_descr'])); ?></td>
                                    </tr>
                                </table>
                                <!--
                <div class="form-actions" style="margin: 0;">
                  <?php
                                echo '<button class="btn btn-default pull-right" data-toggle="modal" data-target="#modal-group_edit"><i class="' . $config['icon']['user-edit'] . '"></i>&nbsp;Edit&nbsp;Group</button>';
                                ?>
                </div>
-->
                            </div>
                        </div>

                    </div> <!-- userinfo end -->

                    <div class="col-md-12"> <!-- userinfo begin -->

                        <?php

                        echo generate_box_open(['header-border' => TRUE, 'title' => 'Role Members']);

                        $group_members = dbFetchRows("SELECT * FROM `roles_users` WHERE `role_id` = ? AND `auth_mechanism` = ?", [$role['role_id'], $config['auth_mechanism']]);
                        $user_list     = auth_user_list();
                        $memberlist    = [];
                        //bdump($user_list);

                        if (!safe_empty($group_members)) {
                            echo '<div class="box-body no-padding">';
                            echo('<table class="table table-hover table-condensed">');

                            $cols = [
                                              [ '', 'class="state-marker"' ],
                                'username' => [ 'Name', 'style="width: 200px;"' ],
                                'email'    => [ 'Users', 'style="width: 80px;"' ],
                                'level'    => 'Description',
                            ];
                            //echo(get_table_header($cols));

                            foreach ($group_members as $user) {

                                $user = array_merge((array)$user, (array)$user_list[$user['user_id']]);

                                echo '<tr>';
                                echo '<td width="5"></td>';
                                echo '<td>' . $user['username'] . '</td>';
                                echo '<td width="100">' . $user['email'] . '</td>';
                                echo '<td width="100">Level ' . $user['level'] . '</td>';

                                echo '<td width="40">';

                                $form = [
                                    'type' => 'simple',
                                    //'submit_by_key' => TRUE,
                                    //'url'   => generate_url($vars)
                                ];

                                // Elements
                                $form['row'][0]['auth_secret'] = [
                                  'type'  => 'hidden',
                                  'value' => $_SESSION['auth_secret']
                                ];

                                $form['row'][0]['user_id'] = [
                                    'type'  => 'hidden',
                                    'value' => $user['user_id']
                                ];
                                $form['row'][0]['action'] = [
                                    'type'  => 'hidden',
                                    'value' => 'role_user_del'
                                ];
                                $form['row'][0]['submit'] = [
                                    'type'  => 'submit',
                                    'name'  => ' ',
                                    'class' => 'btn-danger btn-mini',
                                    'icon'  => 'icon-trash',
                                    'value' => 'role_user_del'
                                ];
                                print_form($form);
                                unset($form);

                                echo '</td>';

                                echo '</tr>';
                                $memberlist[] = $user['user_id'];
                            }
                            echo('</table></div>');
                        } else {
                            echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This role currently has no members</strong></p>');
                        }


                        $form = [
                            'type'  => 'simple',
                            'style' => 'padding: 7px; margin: 0px;',
                            //'submit_by_key' => TRUE,
                            //'url'   => generate_url($vars)
                        ];
                        // Elements
                        $form['row'][0]['auth_secret'] = [ 'type' => 'hidden', 'value' => $_SESSION['auth_secret'] ];
                        $form['row'][0]['role_id']     = [ 'type' => 'hidden', 'value' => $role['role_id'] ];
                        $form['row'][0]['action']      = [ 'type' => 'hidden', 'value' => 'role_user_add' ];

                        $form_items['users'] = array_filter_key($user_list, $memberlist, '!=');
                        //bdump($form_items['users']);

                        $form['row'][0]['user_id'] = [
                            'type'   => 'multiselect',
                            'name'   => 'Add Member',
                            'width'  => '250px',
                            'values' => $form_items['users']
                        ];
                        // add button
                        $form['row'][0]['Submit'] = [
                          'type'  => 'submit',
                          'name'  => 'Add',
                          'icon'  => $config['icon']['plus'],
                          'right' => TRUE,
                          'value' => 'Add'
                        ];
                        print_form($form);
                        unset($form);

                        echo generate_box_close();

                        ?>

                    </div>
                </div>

            </div> <!-- right column end -->

            <div class="col-md-6"> <!-- left column begin -->

                <?php

                // Start platform permissions
                if (OBSERVIUM_EDITION !== 'community') {
                    echo generate_box_open(['header-border' => TRUE, 'title' => 'Platform Permissions']);

                    // Cache group permissions
                    $role_perms['permission'] = [];
                    foreach (dbFetchRows("SELECT * FROM `roles_permissions` WHERE `role_id` = ?", [ $vars['role_id'] ]) as $perm) {
                        $role_perms['permission'][$perm['permission']] = TRUE;
                    }

                    if (!safe_empty($role_perms['permission'])) {
                        echo('<table class="' . OBS_CLASS_TABLE . '">' . PHP_EOL);

                        foreach ($role_perms['permission'] as $perm => $status) {
                            echo('<tr><td style="width: 1px;"></td>
                <td style="overflow: hidden;"><span class="label">' . $perm . '</span>
                <small>' . $config['permissions'][$perm]['descr'] . '</small></td>
                <td width="25">');

                            $form = [
                              'type' => 'simple',
                              //'submit_by_key' => TRUE,
                              //'url'   => generate_url($vars)
                            ];
                            // Elements
                            $form['row'][0]['auth_secret'] = [
                              'type'  => 'hidden',
                              'value' => $_SESSION['auth_secret']
                            ];
                            $form['row'][0]['role_id']     = ['type' => 'hidden', 'value' => $role['role_id']];
                            $form['row'][0]['permission']  = ['type' => 'hidden', 'value' => $perm];
                            $form['row'][0]['action']      = ['type' => 'hidden', 'value' => 'role_permission_del'];
                            $form['row'][0]['submit']      = ['type'  => 'submit',
                                                              'name'  => ' ',
                                                              'class' => 'btn-danger btn-mini',
                                                              'icon'  => 'icon-trash',
                                                              'value' => 'role_permission_del'];
                            print_form($form);
                            unset($form);

                            echo('</td>
              </tr>');
                        }
                        echo('</table>' . PHP_EOL);

                    } else {
                        echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This role currently has no permissions</strong></p>');
                        //print_warning("This user currently has no permitted groups");
                    }

                    // Permissions Selector
                    $permissions_list = array_keys((array)$role_perms['permission']);

                    $form = [
                      'type'  => 'simple',
                      'style' => 'padding: 7px; margin: 0px;',
                      //'submit_by_key' => TRUE,
                      //'url'   => generate_url($vars)
                    ];
                    // Elements
                    $form['row'][0]['auth_secret'] = ['type' => 'hidden', 'value' => $_SESSION['auth_secret']];
                    $form['row'][0]['role_id']     = ['type' => 'hidden', 'value' => $role['role_id']];
                    $form['row'][0]['action']      = ['type' => 'hidden', 'value' => 'role_permission_add'];

                    $form_items['perms'] = [];
                    foreach ($config['permissions'] as $perm => $perm_data) {
                        if (!in_array($perm, $permissions_list, TRUE)) {
                            $form_items['perms'][$perm] = [
                              'name'    => $perm,
                              'subtext' => $perm_data['descr']
                            ];
                        }
                    }

                    $form['row'][0]['permission'] = [
                      'type'   => 'multiselect',
                      'name'   => 'Add Permission',
                      'width'  => '250px',
                      //'value'    => $vars['entity_id'],
                      'values' => $form_items['perms']
                    ];
                    // add button
                    $form['row'][0]['Submit'] = [
                      'type'  => 'submit',
                      'name'  => 'Add',
                      'icon'  => $config['icon']['plus'],
                      'right' => TRUE,
                      'value' => 'Add'
                    ];

                    print_form($form);
                    unset($form);

                    echo generate_box_close();
                }
                // End platform permissions

                // Cache entity permissions
                foreach (dbFetchRows("SELECT * FROM `roles_entity_permissions` WHERE `role_id` = ?", [$vars['role_id']]) as $entity) {
                    $role_perms[$entity['entity_type']][$entity['entity_id']] = $entity['access'];
                }

                //print_vars($role_perms);

                if (OBSERVIUM_EDITION !== 'community') {
                    // Bill Permissions
                    print_billing_permission_box('role', $role_perms, $role);

                    // Entity group permissions
                    print_group_permission_box('role', $role_perms, $role);
                }

                // Device permissions
                print_device_permission_box('role', $role_perms, $role);

                // Port permissions
                print_port_permission_box('role', $role_perms, $role);

                // Sensor permissions
                print_sensor_permission_box('role', $role_perms, $role);


                ?>
            </div>


        </div>

        <?php
    } else {
        // Invalid role_id

        print_error("Invalid User Group");
    }

} else { // if no role_id

    $roles = dbFetchRows("SELECT * FROM `roles` ORDER BY `role_name`");

    if (!safe_empty($roles)) {

        echo '<div id="ajax-form-message"></div>'; // placeholder for ajax form message

        echo(generate_box_open());
        echo('<table class="table table-hover table-condensed">');

        $cols = [
          ['', 'class="state-marker"'],
          'role_id'    => ['Role ID', 'style="width: 80px;"'],
          'role_name'  => ['Name', 'style="width: 200px;"'],
          'role_count' => ['Users', 'style="width: 80px;"'],
          'role_descr' => 'Description',
          'role_del'   => ['', 'style= "width: 40px;"']
        ];
        echo(get_table_header($cols));

        foreach ($roles as $role) {
            humanize_user($role);

            $role['edit_url'] = generate_url(['page' => 'roles', 'role_id' => $role['role_id']]);

            $role['count'] = dbFetchCell("SELECT COUNT(*) FROM `roles_users` WHERE `role_id` = ? AND `auth_mechanism` = ?", [$role['role_id'], $config['auth_mechanism']]);

            echo '<tr class="' . $role['row_class'] . '">';
            echo '<td class="state-marker"></td>';
            echo '<td>' . $role['role_id'] . '</td>';
            echo '<td><strong><a href="' . $role['edit_url'] . '">' . escape_html($role['role_name']) . '</a></strong></td>';
            echo '<td><label class="label">' . $role['count'] . '</label></td>';
            echo '<td>' . escape_html($role['role_descr']) . '</td>';

            // Delete form
            $form_id                   = 'delete_role_' . $role['role_id'];
            $form                      = [
              'type'  => 'simple',
              //'userlevel'  => 10,          // Minimum user level for display form
              'id'    => $form_id,
              'style' => 'display:inline;',
            ];
            $form['row'][0]['role_id'] = ['type' => 'hidden', 'value' => $role['role_id']];
            $form['row'][0]['action']  = ['type' => 'hidden', 'value' => 'role_del'];

            $form['row'][99]['submit'] = [
              'type'    => 'submit',
              //'icon_only'   => TRUE, // hide button styles
              'name'    => 'Delete',
              //'icon'        => $config['icon']['cancel'],
              //'right'       => TRUE,
              'class'   => 'btn-danger btn-xs',
              // confirmation dialog
              'attribs' => ['data-toggle'            => 'confirm', // Enable confirmation dialog
                            'data-confirm-placement' => 'left',
                            'data-confirm-content'   => 'Are you sure you want to delete this role "' . escape_html($role['role_name']) . '"?'],
              'value'   => 'role_del'
            ];

            echo '<td>';
            print_form($form);
            unset($form);
            echo '</td>';

            echo '</tr>';

            register_html_resource('script', '$("#' . $form_id . '").submit(processAjaxForm);');
        }
        register_html_resource('js', 'js/jquery.serializejson.min.js');

        echo('</table>');
        echo(generate_box_close());

    } else {
        print_warning('There are no roles in the database.');
    }
} // end if role_id

// EOF
