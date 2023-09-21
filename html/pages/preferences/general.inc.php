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

$user_data = ['user_id'  => $_SESSION['user_id'],
              'username' => $_SESSION['username'],
              'level'    => $_SESSION['userlevel']];

// Additional info
$user_data2 = auth_user_info($_SESSION['username']);
if (is_array($user_data2)) {
    $user_data = array_merge($user_data, $user_data2);
    unset($user_data2);
}
humanize_user($user_data); // Get level_label, level_real, row_class, etc

//r($user_data);

?>

    <div class="row">

        <div class="col-md-6"> <!-- userinfo begin -->

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
                            <td><?php echo('<span class="label label-' . $user_data['text_class'] . '">' . $user_data['level_label'] . '</span>'); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo(escape_html($user_data['email'])); ?></td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td><?php echo(escape_html($user_data['descr'])); ?></td>
                        </tr>
                    </table>

                </div>
            </div>

            <?php

            $roles = dbFetchRows("SELECT * FROM `roles_users` LEFT JOIN `roles` USING (`role_id`) WHERE `user_id` = ? AND `auth_mechanism` = ?", [$user_data['user_id'], $config['auth_mechanism']]);
            if (!safe_empty($roles)) {
                ?>

                <div class="box box-solid">
                    <div class="box-header">
                        <h3 class="box-title">User Roles</h3>
                    </div>
                    <div class="box-body no-padding">
                        <table class="table table-striped table-condensed">
                            <?php

                            foreach ($roles as $role) {
                                echo '<tr><td><b>' . escape_html($role['role_name']) . '</td><td>' . escape_html($role['role_descr']) . '</td></tr>';

                                //print_vars($role);
                            }

                            ?>
                        </table>
                    </div>
                </div>

                <?php
            }


            ?>


        </div> <!-- userinfo end -->


        <div class="col-md-6">
            <?php

            echo generate_box_open(['header-border' => TRUE, 'title' => 'Access Keys']);

            ?>

            <table class="table table-striped table-condensed">
                <tr>
                    <td>RSS/Atom access key</td>
                    <?php
                    // Warn about lack of encrypt modules unless told not to.
                    if (!OBS_ENCRYPT) {
                        echo('<td colspan="2"><span class="text text-danger">To use RSS/Atom feeds the PHP mcrypt or sodium (php >= 7.2) extension is required.</span></td>');
                    } elseif (!check_extension_exists('SimpleXML')) {
                        echo('<td colspan="2"><span class="text text-danger">To use RSS/Atom feeds the PHP SimpleXML module is required.</span></td>');
                    } else {
                        echo("      <td>RSS/Atom access key created $atom_key_updated.</td>");
                        echo('      <td>');

                        $form = ['type' => 'simple'];
                        // Elements
                        $form['row'][0]['key_type'] = ['type'  => 'hidden',
                                                       'value' => 'atom'];
                        $form['row'][0]['atom_key'] = ['type'  => 'submit',
                                                       'name'  => 'Reset',
                                                       'icon'  => '',
                                                       'class' => 'btn-mini btn-success',
                                                       'value' => 'toggle'];
                        print_form($form);
                        unset($form);

                        echo('</td>');
                    }
                    ?>
                </tr>
                <tr>
                    <td colspan=3 style="padding: 0px; border: 0px none;"></td> <!-- hidden row -->
                </tr>
                <tr>
                    <td>API access key</td>
                    <?php
                    echo("      <td>API access key created $api_key_updated.</td>");
                    echo('      <td>');

                    $form = ['type' => 'simple'];
                    // Elements
                    $form['row'][0]['key_type'] = ['type'  => 'hidden',
                                                   'value' => 'api'];
                    $form['row'][0]['api_key']  = ['type'     => 'submit',
                                                   'name'     => 'Reset',
                                                   'icon'     => '',
                                                   'class'    => 'btn-mini btn-success',
                                                   'disabled' => TRUE, // Not supported for now
                                                   'value'    => 'toggle'];
                    print_form($form);
                    unset($form);

                    echo('</td>');
                    ?>
                </tr>
            </table>

            <?php
            echo generate_box_close();
            ?>

        </div>

        <div class="col-md-6 col-sm-12 col-xs-12 pull-right">

            <?php

            echo generate_box_open(['header-border' => TRUE, 'title' => 'Permission level']);
            echo('<p class="text-center text-uppercase text-' . $user_data['text_class'] . ' bg-' . $user_data['text_class'] . '" style="padding: 10px; margin: 0px;"><strong>' . $user_data['subtext'] . '</strong></p>');
            echo generate_box_close();

            // Show entity permissions only for Normal users
            if ($user_data['permission_access'] && !$user_data['permission_read']) {
                // Cache user permissions
                foreach (dbFetchRows("SELECT * FROM `entity_permissions` WHERE `user_id` = ? AND `auth_mechanism` = ?", [$user_data['user_id'], $config['auth_mechanism']]) as $entity) {
                    $user_permissions[$entity['entity_type']][$entity['entity_id']] = TRUE;
                }

                // Start bill Permissions
                if (isset($config['enable_billing']) && $config['enable_billing'] && !safe_empty($user_permissions['bill'])) {
                    // Display info about user bill permissions, only if user has is
                    echo generate_box_open(['header-border' => TRUE, 'title' => 'Bill Permissions']);
                    //if (count($user_permissions['bill']))
                    //{
                    echo('<table class="' . OBS_CLASS_TABLE . '">' . PHP_EOL);

                    foreach ($user_permissions['bill'] as $bill_id => $status) {
                        $bill = get_bill_by_id($bill_id);

                        echo('<tr><td style="width: 1px;"></td>
                  <td style="overflow: hidden;"><i class="' . $config['entities']['bill']['icon'] . '"></i> ' . $bill['bill_name'] . '
                  <small>' . $bill['bill_type'] . '</small></td>
                </tr>');
                    }
                    echo('</table>' . PHP_EOL);

                    //} else {
                    //  echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted bills</strong></p>');
                    //  //print_warning("This user currently has no permitted bills");
                    //}

                    echo generate_box_close();
                }
                // End bill permissions

                // Start group permissions
                if (OBSERVIUM_EDITION !== 'community') {
                    echo generate_box_open(['header-border' => TRUE, 'title' => 'Group Permissions']);

                    if (!safe_empty($user_permissions['group'])) {
                        echo('<table class="' . OBS_CLASS_TABLE . '">' . PHP_EOL);

                        foreach ($user_permissions['group'] as $group_id => $status) {
                            $group = get_group_by_id($group_id);

                            echo('<tr><td style="width: 1px;"></td>
                  <td style="overflow: hidden;"><i class="' . $config['entities'][$group['entity_type']]['icon'] . '"></i> ' . generate_entity_link('group', $group) . '
                  <small>' . $group['group_descr'] . '</small></td>
              </tr>' . PHP_EOL);
                        }
                        echo('</table>' . PHP_EOL);
                    } else {
                        echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted groups</strong></p>');
                        //print_warning("This user currently has no permitted groups");
                    }

                    echo generate_box_close();
                }
                // End group permissions

                // Start device permissions
                echo generate_box_open(['header-border' => TRUE, 'title' => 'Device Permissions']);

                if (!safe_empty($user_permissions['device'])) {
                    echo('<table class="' . OBS_CLASS_TABLE . '">' . PHP_EOL);

                    foreach ($user_permissions['device'] as $device_id => $status) {
                        $device = device_by_id_cache($device_id);

                        echo('<tr><td style="width: 1px;"></td>
                <td style="overflow: hidden;"><i class="' . $config['entities']['device']['icon'] . '"></i> ' . generate_device_link($device) . '
                <small>' . $device['location'] . '</small></td>
              </tr>');
                    }
                    echo('</table>' . PHP_EOL);

                } else {
                    echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted devices</strong></p>');
                    //print_warning("This user currently has no permitted devices");
                }

                echo generate_box_close();
                // End devices permissions

                // Start port permissions
                echo generate_box_open(['header-border' => TRUE, 'title' => 'Port Permissions']);
                if (!safe_empty($user_permissions['port'])) {
                    echo('<table class="' . OBS_CLASS_TABLE . '">' . PHP_EOL);

                    foreach (array_keys($user_permissions['port']) as $entity_id) {
                        $port   = get_port_by_id($entity_id);
                        $device = device_by_id_cache($port['device_id']);

                        echo('<tr><td style="width: 1px;"></td>
                <td style="width: 200px; overflow: hidden;"><i class="' . $config['entities']['device']['icon'] . '"></i> ' . generate_entity_link('device', $device) . '</td>
                <td style="overflow: hidden;"><i class="' . $config['entities']['port']['icon'] . '"></i> ' . generate_entity_link('port', $port) . '
                <small>' . $port['ifDescr'] . '</small></td>
              </tr>');
                    }
                    echo('</table>' . PHP_EOL);

                } else {
                    echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted ports</strong></p>');
                    //print_warning('This user currently has no permitted ports');
                }

                echo generate_box_close();
                // End port permissions

                // Start sensor permissions
                echo generate_box_open(['header-border' => TRUE, 'title' => 'Sensor Permissions']);
                if (!safe_empty($user_permissions['sensor'])) {
                    echo('<table class="' . OBS_CLASS_TABLE . '">' . PHP_EOL);

                    foreach (array_keys($user_permissions['sensor']) as $entity_id) {
                        $sensor = get_entity_by_id_cache('sensor', $entity_id);
                        $device = device_by_id_cache($sensor['device_id']);

                        echo('<tr><td style="width: 1px;"></td>
                  <td style="width: 200px; overflow: hidden;"><i class="' . $config['entities']['device']['icon'] . '"></i> ' . generate_entity_link('device', $device) . '</td>
                  <td style="overflow: hidden;"><i class="' . $config['entities']['sensor']['icon'] . '"></i> ' . generate_entity_link('sensor', $sensor) . '
                  <td width="25">
                </tr>');
                    }
                    echo('</table>' . PHP_EOL);

                } else {
                    echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted sensors</strong></p>');
                    //print_warning('This user currently has no permitted sensors');
                }

                echo generate_box_close();
                // End sensor permissions

            }

            ?>

        </div>

    </div> <!-- end row -->

<?php

if (isset($config['debug_user_perms']) && $config['debug_user_perms']) {
    r($_SESSION);
    r($permissions);
}

// EOF
