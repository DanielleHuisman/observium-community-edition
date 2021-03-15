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

// print_r($permissions);

// Global write permissions required.
if ($_SESSION['userlevel'] < 10)
{
  print_error_permission();
  return;
}

include($config['html_dir'].'/includes/user_menu.inc.php');

register_html_title("User Groups");

if(isset($vars['role_id']))
{

  // Load JS entity picker
  register_html_resource('js', 'tw-sack.js');
  register_html_resource('js', 'observium-entities.js');

  $role = dbFetchRow("SELECT * FROM `roles` WHERE `role_id` = ?", array($vars['role_id']));

  if(count($role)){

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
                    echo '<button class="btn btn-default pull-right" data-toggle="modal" data-target="#modal-group_edit"><i class="'.$config['icon']['user-edit'].'"></i>&nbsp;Edit&nbsp;Group</button>';
                  ?>
                </div>
-->
              </div>
            </div>

          </div> <!-- userinfo end -->

      <div class="col-md-12"> <!-- userinfo begin -->

                <?php

                echo generate_box_open(array('header-border' => TRUE, 'title' => 'Role Members'));

                $group_members = dbFetchRows("SELECT * FROM `roles_users` WHERE `role_id` = ? AND `auth_mechanism` = ?", [ $role['role_id'], $config['auth_mechanism'] ]);
                $user_list = auth_user_list();

                if (count($group_members))
                {
                  echo '<div class="box-body no-padding">';
                  echo('<table class="table table-hover table-condensed">');

                  $cols = array(
                    array('', 'class="state-marker"'),
                    'username'  => array('Name', 'style="width: 200px;"'),
                    'email' => array('Users', 'style="width: 80px;"'),
                    'level' => 'Description',
                  );
                  //echo(get_table_header($cols));

                  foreach ($group_members as $user)
                  {

                    $user = array_merge((array)$user, (array)$user_list[$user['user_id']]);

                    echo '<tr>';
                    echo '<td width="5"></td>';
                    echo '<td>' . $user['username'] . '</td>';
                    echo '<td width="100">' . $user['email'] . '</td>';
                    echo '<td width="100">Level ' . $user['level'] . '</td>';

                    echo '<td width="40">';

                    $form = array('type'  => 'simple',
                                  //'submit_by_key' => TRUE,
                                  //'url'   => generate_url($vars)
                    );
                    // Elements
                    $form['row'][0]['auth_secret'] = array(
                      'type'     => 'hidden',
                      'value'    => $_SESSION['auth_secret']);

                    $form['row'][0]['user_id']     = array('type'     => 'hidden',
                                                           'value'    => $user['user_id']);
                    $form['row'][0]['action']      = array('type'     => 'hidden',
                                                           'value'    => 'role_user_del');
                    $form['row'][0]['submit']      = array('type'     => 'submit',
                                                           'name'     => ' ',
                                                           'class'    => 'btn-danger btn-mini',
                                                           'icon'     => 'icon-trash',
                                                           'value'    => 'role_user_del');
                    print_form($form); unset($form);

                    echo '</td>';

                    echo '</tr>';
                    $memberlist[] = $user['username'];
                  }
                  echo('</table></div>');
                }
                else
                {
                  echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This role currently has no members</strong></p>');
                }


                $form = array('type'  => 'simple',
                              'style' => 'padding: 7px; margin: 0px;',
                              //'submit_by_key' => TRUE,
                              //'url'   => generate_url($vars)
                );
                // Elements
                $form['row'][0]['auth_secret'] = array('type'     => 'hidden', 'value'    => $_SESSION['auth_secret']);
                $form['row'][0]['role_id']     = array('type'     => 'hidden', 'value'    => $role['role_id']);
                $form['row'][0]['action']      = array('type'     => 'hidden', 'value'    => 'role_user_add');

                $form_items['users'] = array();

                foreach ($user_list as $user_id => $user)
                {
                  if (!in_array($user['username'], $memberlist))
                  {
                    $form_items['users'][$user['user_id']] = array('name'    => escape_html($user['username']),
                                                                   'descr'       => escape_html($user['email']));
                  }
                }

                $form['row'][0]['user_id']   = array('type'     => 'multiselect',
                                                        'name'     => 'Add Member',
                                                        'width'    => '250px',
                                                        'values'   => $form_items['users']);
                // add button
                $form['row'][0]['Submit']      = array('type'     => 'submit',
                                                       'name'     => 'Add',
                                                       'icon'     => $config['icon']['plus'],
                                                       'right'    => TRUE,
                                                       'value'    => 'Add');

                print_form($form); unset($form);



                echo generate_box_close();

                ?>

      </div></div>

      </div> <!-- right column end -->

      <div class="col-md-6"> <!-- left column begin -->

          <?php

          // Start platform permissions
          if (OBSERVIUM_EDITION != 'community')
          {
            echo generate_box_open(array('header-border' => TRUE, 'title' => 'Platform Permissions'));

            // Cache group permissions
            foreach (dbFetchRows("SELECT * FROM `roles_permissions` WHERE `role_id` = ?", array($vars['role_id'])) as $perm)
            {
              $role_perms['permission'][$perm['permission']] = TRUE;
            }

            if (count($role_perms['permission']))
            {
              echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

              foreach ($role_perms['permission'] as $perm => $status)
              {
                echo('<tr><td style="width: 1px;"></td>
                <td style="overflow: hidden;"><span class="label">'.$perm.'</span>
                <small>' . $config['permissions'][$perm]['descr'] . '</small></td>
                <td width="25">');

                $form = array('type'  => 'simple',
                              //'submit_by_key' => TRUE,
                              //'url'   => generate_url($vars)
                );
                // Elements
                $form['row'][0]['auth_secret'] = array(
                  'type'     => 'hidden',
                  'value'    => $_SESSION['auth_secret']);
                $form['row'][0]['role_id']     = array('type'     => 'hidden',
                                                       'value'    => $role['role_id']);
                $form['row'][0]['permission']  = array('type'     => 'hidden',
                                                       'value'    => $perm);
                $form['row'][0]['action']      = array('type'     => 'hidden',
                                                       'value'    => 'role_permission_del');
                $form['row'][0]['submit']      = array('type'     => 'submit',
                                                       'name'     => ' ',
                                                       'class'    => 'btn-danger btn-mini',
                                                       'icon'     => 'icon-trash',
                                                       'value'    => 'role_permission_del');
                print_form($form); unset($form);

                echo('</td>
              </tr>');
              }
              echo('</table>' . PHP_EOL);

            } else {
              echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This role currently has no permissions</strong></p>');
              //print_warning("This user currently has no permitted groups");
            }

            // Permissions Selector
            $permissions_list = array_keys($role_perms['permission']);

            $form = array('type'  => 'simple',
                          'style' => 'padding: 7px; margin: 0px;',
                          //'submit_by_key' => TRUE,
                          //'url'   => generate_url($vars)
            );
            // Elements
            $form['row'][0]['auth_secret'] = array(
              'type'     => 'hidden',
              'value'    => $_SESSION['auth_secret']);
            $form['row'][0]['role_id'] = array('type'     => 'hidden',
                                               'value'    => $role['role_id']);
            $form['row'][0]['action']       = array('type'     => 'hidden',
                                                    'value'    => 'role_permission_add');
            $form_items['perms'] = array();

            foreach ($config['permissions'] as $perm => $perm_data)
            {
              if (!in_array($perm, $permissions_list))
              {
                $form_items['perms'][$perm] = array('name'    => escape_html($perm),
                                                    'subtext' => escape_html($perm_data['descr']));
              }
            }

            $form['row'][0]['permission']   = array('type'     => 'multiselect',
                                                   'name'     => 'Add Permission',
                                                   'width'    => '250px',
                                                   //'value'    => $vars['entity_id'],
                                                   'values'   => $form_items['perms']);
            // add button
            $form['row'][0]['Submit']      = array('type'     => 'submit',
                                                   'name'     => 'Add',
                                                   'icon'     => $config['icon']['plus'],
                                                   'right'    => TRUE,
                                                   'value'    => 'Add');

            print_form($form); unset($form);

            echo generate_box_close();
          }
          // End platform permissions

          // Cache group permissions
          foreach (dbFetchRows("SELECT * FROM `roles_entity_permissions` WHERE `role_id` = ?", array($vars['role_id'])) as $entity)
          {
            $role_perms[$entity['entity_type']][$entity['entity_id']] = $entity['access'];
          }

          //print_vars($role_perms);

          // Start bill Permissions
          if (isset($config['enable_billing']) && $config['enable_billing'])
          {
            echo generate_box_open(array('header-border' => TRUE, 'title' => 'Bill Permissions'));
            if (count($role_perms['bill']))
            {
              echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

              foreach ($role_perms['bill'] as $bill_id => $status)
              {
                $bill = get_bill_by_id($bill_id);

                echo('<tr><td style="width: 1px;"></td>
                  <td style="overflow: hidden;"><i class="'.$config['entities']['bill']['icon'].'"></i> '.$bill['bill_name'].'
                  <small>' . $bill['bill_type'] . '</small></td>
                  <td width="25">');

                $form = array('type'  => 'simple',
                              //'submit_by_key' => TRUE,
                              //'url'   => generate_url($vars)
                );
                // Elements
                $form['row'][0]['auth_secret'] = array(
                  'type'     => 'hidden',
                  'value'    => $_SESSION['auth_secret']);
                $form['row'][0]['entity_id']   = array('type'     => 'hidden',
                                                       'value'    => $bill['bill_id']);
                $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                                       'value'    => 'bill');
                $form['row'][0]['submit']      = array('type'     => 'submit',
                                                       'name'     => ' ',
                                                       'class'    => 'btn-danger btn-mini',
                                                       'icon'     => 'icon-trash',
                                                       'value'    => 'role_entity_del');
                print_form($form); unset($form);

                echo('</td>
                </tr>');
              }
              echo('</table>' . PHP_EOL);

            } else {
              echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This role currently has no permitted bills</strong></p>');
              //print_warning("This user currently has no permitted bills");
            }

            // Bills
            $permissions_list = array_keys($role_perms['bill']);

            $form = array('type'  => 'simple',
                          'style' => 'padding: 7px; margin: 0px;',
                          //'submit_by_key' => TRUE,
                          //'url'   => generate_url($vars)
            );
            // Elements
            $form['row'][0]['auth_secret'] = array(
              'type'     => 'hidden',
              'value'    => $_SESSION['auth_secret']);
            $form['row'][0]['role_id']     = array('type'     => 'hidden',
                                                        'value'    => $role['role_id']);
            $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                                   'value'    => 'bill');
            $form['row'][0]['action']      = array('type'     => 'hidden',
                                                   'value'    => 'role_entity_add');

            $form_items['bills'] = array();
            foreach (dbFetchRows("SELECT * FROM `bills`") as $bill)
            {
              if (!in_array($bill['bill_id'], $permissions_list))
              {
                $form_items['bills'][$bill['bill_id']] = array('name'    => escape_html($bill['bill_name']),
                                                               'subtext' => escape_html($bill['bill_descr']),
                                                               'icon'    => $config['entities']['bill']['icon']);
              }
            }
            $form['row'][0]['entity_id']   = array('type'     => 'multiselect',
                                                   'name'     => 'Permit Bill',
                                                   'width'    => '250px',
                                                   //'value'    => $vars['entity_id'],
                                                   'values'   => $form_items['bills']);
            // add button
            $form['row'][0]['Submit']      = array('type'     => 'submit',
                                                   'name'     => 'Add',
                                                   'icon'     => $config['icon']['plus'],
                                                   'right'    => TRUE,
                                                   'value'    => 'Add');
            print_form($form); unset($form);

            echo generate_box_close();
          }
          // End bill permissions


          // Start entity group permissions
          if (OBSERVIUM_EDITION != 'community')
          {
            echo generate_box_open(array('header-border' => TRUE, 'title' => 'Entity Group Permissions'));

            if (count($role_perms['group']))
            {
              echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

              foreach ($role_perms['group'] as $group_id => $status)
              {
                $group = get_group_by_id($group_id);

                echo('<tr><td style="width: 1px;"></td>
                <td style="overflow: hidden;"><i class="'.$config['entities'][$group['entity_type']]['icon'].'"></i> '.generate_entity_link('group', $group).' '. ($status == 'rw' ? '<label class="label label-danger">RW</label>' : '') .'
                <small>' . $group['group_descr'] . '</small></td>
                <td width="25">');

                $form = array('type'  => 'simple',
                              //'submit_by_key' => TRUE,
                              //'url'   => generate_url($vars)
                );
                // Elements
                $form['row'][0]['auth_secret'] = array(
                  'type'     => 'hidden',
                  'value'    => $_SESSION['auth_secret']);
                $form['row'][0]['entity_id']   = array('type'     => 'hidden',
                                                       'value'    => $group['group_id']);
                $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                                       'value'    => 'group');
                $form['row'][0]['action']      = array('type'     => 'hidden',
                                                       'value'    => 'role_entity_del');
                $form['row'][0]['submit']      = array('type'     => 'submit',
                                                       'name'     => ' ',
                                                       'class'    => 'btn-danger btn-mini',
                                                       'icon'     => 'icon-trash',
                                                       'value'    => 'role_entity_del');
                print_form($form); unset($form);

                echo('</td>
              </tr>');
              }
              echo('</table>' . PHP_EOL);

            } else {
              echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This role currently has no permitted entity groups</strong></p>');
              //print_warning("This user currently has no permitted groups");
            }

            // Groups
            $permissions_list = array_keys($role_perms['group']);

            $form = array('type'  => 'simple',
                          'style' => 'padding: 7px; margin: 0px;',
                          //'submit_by_key' => TRUE,
                          //'url'   => generate_url($vars)
            );
            // Elements
            $form['row'][0]['auth_secret'] = array(
              'type'     => 'hidden',
              'value'    => $_SESSION['auth_secret']);
            $form['row'][0]['role_id'] = array('type'     => 'hidden',
                                                   'value'    => $role['role_id']);
            $form['row'][0]['entity_type']  = array('type'     => 'hidden',
                                                   'value'    => 'group');
            $form['row'][0]['action']       = array('type'     => 'hidden',
                                                   'value'    => 'role_entity_add');

            $form_items['groups'] = array();
            foreach (dbFetchRows("SELECT * FROM `groups`") as $group)
            {
              if (!in_array($group['group_id'], $permissions_list))
              {
                $form_items['groups'][$group['group_id']] = array('name'    => escape_html($group['group_name']),
                                                                  'subtext' => escape_html($group['group_descr']),
                                                                  'icon'    => $config['entities'][$group['entity_type']]['icon']);
              }
            }
            $form['row'][0]['entity_id']   = array('type'     => 'multiselect',
                                                   'name'     => 'Permit Group',
                                                   'width'    => '250px',
                                                   //'value'    => $vars['entity_id'],
                                                   'values'   => $form_items['groups']);

            $form['row'][0]['access']       = array('type'     => 'select',
                                                    'name'     => 'Access Level',
                                                    'width'    => '110px',
                                                    'value'    => 'ro',
                                                    'values'   => array('ro' => array('name' => 'Read Only'),
                                                                       'rw' => array('name' => 'Read Write')));

            // add button
            $form['row'][0]['Submit']      = array('type'     => 'submit',
                                                   'name'     => 'Add',
                                                   'icon'     => $config['icon']['plus'],
                                                   'right'    => TRUE,
                                                   'value'    => 'Add');
            print_form($form); unset($form);

            echo generate_box_close();
          }
          // End group permissions

          // Start device permissions
          echo generate_box_open(array('header-border' => TRUE, 'title' => 'Device Permissions'));

          if (count($role_perms['device']))
          {
            echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

            foreach ($role_perms['device'] as $device_id => $status)
            {
              $device = device_by_id_cache($device_id);

              echo('<tr><td style="width: 1px;"></td>
                <td style="overflow: hidden;"><i class="'.$config['entities']['device']['icon'].'"></i> '.generate_device_link($device).'
                <small>' . $device['location'] . '</small></td>
                <td width="25">');

              $form = array('type'  => 'simple',
                            //'submit_by_key' => TRUE,
                            //'url'   => generate_url($vars)
              );
              // Elements
              $form['row'][0]['auth_secret'] = array('type'     => 'hidden',
                                                     'value'    => $_SESSION['auth_secret']);
              $form['row'][0]['entity_id']   = array('type'     => 'hidden',
                                                     'value'    => $device['device_id']);
              $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                                     'value'    => 'device');
              $form['row'][0]['action']       = array('type'    => 'hidden',
                                                      'value'   => 'role_entity_del');
              $form['row'][0]['submit']      = array('type'     => 'submit',
                                                     'name'     => ' ',
                                                     'class'    => 'btn-danger btn-mini',
                                                     'icon'     => 'icon-trash',
                                                     'value'    => 'role_entity_del');
              print_form($form); unset($form);

              echo('</td>
              </tr>');
            }
            echo('</table>' . PHP_EOL);

          } else {
            echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This role currently has no permitted devices</strong></p>');
            //print_warning("This user currently has no permitted devices");
          }

          // Devices
          $permissions_list = array_keys($role_perms['device']);
          // Display devices this user doesn't have Permissions to
          $form = array('type'  => 'simple',
                        'style' => 'padding: 7px; margin: 0px;',
                        //'submit_by_key' => TRUE,
                        //'url'   => generate_url($vars)
          );
          // Elements
          $form['row'][0]['auth_secret']  = array('type'    => 'hidden',
                                                 'value'    => $_SESSION['auth_secret']);
          $form['row'][0]['role_id'] = array('type'    => 'hidden',
                                                 'value'    => $role['role_id']);
          $form['row'][0]['entity_type']  = array('type'    => 'hidden',
                                                 'value'    => 'device');
          $form['row'][0]['action']       = array('type'    => 'hidden',
                                                 'value'    => 'role_entity_add');

          $form_items['devices'] = array();
          foreach (dbFetchRows("SELECT * FROM `devices` ORDER BY `hostname`") as $device)
          {
            if (!in_array($device['device_id'], $permissions_list))
            {
              //humanize_device($device);
              $form_items['devices'][$device['device_id']] = array('name'    => escape_html($device['hostname']),
                                                                   'subtext' => escape_html($device['location']),
                                                                   //'class'   => $device['html_row_class'],
                                                                   'icon'    => $config['entities']['device']['icon']);
            }
          }
          $form['row'][0]['entity_id']   = array('type'     => 'multiselect',
                                                 'name'     => 'Permit Device',
                                                 'width'    => '250px',
                                                 //'value'    => $vars['entity_id'],
                                                 'values'   => $form_items['devices']);
          // add button
          $form['row'][0]['Submit']      = array('type'     => 'submit',
                                                 'name'     => 'Add',
                                                 'icon'     => $config['icon']['plus'],
                                                 'right'    => TRUE,
                                                 'value'    => 'Add');
          print_form($form); unset($form);

          echo generate_box_close();
          // End device permissions

          // Start port permissions
          echo generate_box_open(array('header-border' => TRUE, 'title' => 'Port Permissions'));
          if (count($role_perms['port']))
          {
            echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

            foreach (array_keys($role_perms['port']) as $entity_id)
            {
              $port   = get_port_by_id($entity_id);
              $device = device_by_id_cache($port['device_id']);

              echo('<tr><td style="width: 1px;"></td>
                <td style="width: 200px; overflow: hidden;"><i class="'.$config['entities']['device']['icon'].'"></i> '.generate_entity_link('device', $device).'</td>
                <td style="overflow: hidden;"><i class="'.$config['entities']['port']['icon'].'"></i> '.generate_entity_link('port', $port).'
                <small>' . $port['ifDescr'] . '</small></td>
                <td width="25">');

              $form = array('type'  => 'simple',
                            //'submit_by_key' => TRUE,
                            //'url'   => generate_url($vars)
              );
              // Elements
              $form['row'][0]['auth_secret'] = array(
                'type'     => 'hidden',
                'value'    => $_SESSION['auth_secret']);
              $form['row'][0]['entity_id']   = array('type'     => 'hidden',
                                                     'value'    => $port['port_id']);
              $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                                     'value'    => 'port');
              $form['row'][0]['action']      = array('type'     => 'hidden',
                                                     'value'    => 'role_entity_del');
              $form['row'][0]['submit']      = array('type'     => 'submit',
                                                     'name'     => '',
                                                     'class'    => 'btn-danger btn-mini',
                                                     'icon'     => 'icon-trash',
                                                     'value'    => 'role_entity_del');
              print_form($form); unset($form);

              echo('</td>
              </tr>');
            }
            echo('</table>' . PHP_EOL);

          } else {
            echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This role currently has no permitted ports</strong></p>');
            //print_warning('This user currently has no permitted ports');
          }

          // Ports
          $permissions_list = array_keys($role_perms['port']);

          // Display devices this user doesn't have Permissions to
          $form = array('type'  => 'simple',
                        'style' => 'padding: 7px; margin: 0px;',
                        //'submit_by_key' => TRUE,
                        //'url'   => generate_url($vars)
          );
          // Elements
          $form['row'][0]['auth_secret'] = array('type'     => 'hidden',
                                                 'value'    => $_SESSION['auth_secret']);
          $form['row'][0]['role_id'] = array('type'     => 'hidden',
                                                 'value'    => $role['role_id']);
          $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                                 'value'    => 'port');
          $form['row'][0]['action']      = array('type'     => 'hidden',
                                                 'value'    => 'role_entity_add');

          $form_items['devices'] = array();
          foreach ($cache['devices']['hostname'] as $hostname => $device_id)
          {
            if (!array_key_exists($device_id, $role_perms['device']))
            {
              $form_items['devices'][$device_id] = escape_html($hostname);
            }
          }
          $form['row'][0]['device_id']   = array('type'     => 'select',
                                                 'name'     => 'Select a device',
                                                 'width'    => '150px',
                                                 'onchange' => "getInterfaceList(this, 'port_entity_id')",
                                                 //'value'    => $vars['device_id'],
                                                 'values'   => $form_items['devices']);
          $form['row'][0]['port_entity_id'] = array('type'  => 'multiselect',
                                                    'name'     => 'Permit Port',
                                                    'width'    => '150px',
                                                    //'value'    => $vars['port_entity_id'],
                                                    'values'   => array());
          // add button
          $form['row'][0]['Submit']      = array('type'     => 'submit',
                                                 'name'     => 'Add',
                                                 'icon'     => $config['icon']['plus'],
                                                 'right'    => TRUE,
                                                 'value'    => 'Add');
          print_form($form); unset($form);

          echo generate_box_close();
          // End port permissions

          // Start sensor permissions
          echo generate_box_open(array('header-border' => TRUE, 'title' => 'Sensor Permissions'));

          if (count($role_perms['sensor']))
          {
            echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

            foreach (array_keys($role_perms['sensor']) as $entity_id)
            {
              $sensor   = get_entity_by_id_cache('sensor', $entity_id);
              $device   = device_by_id_cache($sensor['device_id']);

              echo('<tr><td style="width: 1px;"></td>
                <td style="width: 200px; overflow: hidden;"><i class="'.$config['entities']['device']['icon'].'"></i> '.generate_entity_link('device', $device).'</td>
                <td style="overflow: hidden;"><i class="'.$config['entities']['sensor']['icon'].'"></i> '.generate_entity_link('sensor', $sensor).'
                <td width="25">');

              $form = array('type'  => 'simple',
                            //'submit_by_key' => TRUE,
                            //'url'   => generate_url($vars)
              );
              // Elements
              $form['row'][0]['auth_secret'] = array('type'     => 'hidden',
                                                     'value'    => $_SESSION['auth_secret']);
              $form['row'][0]['entity_id']   = array('type'     => 'hidden',
                                                     'value'    => $sensor['sensor_id']);
              $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                                     'value'    => 'sensor');
              $form['row'][0]['action']      = array('type'     => 'hidden',
                                                     'value'    => 'role_entity_del');
              $form['row'][0]['submit']      = array('type'     => 'submit',
                                                     'name'     => ' ',
                                                     'class'    => 'btn-danger btn-mini',
                                                     'icon'     => 'icon-trash',
                                                     'value'    => 'role_entity_del');
              print_form($form); unset($form);

              echo('</td>
              </tr>');
            }
            echo('</table>' . PHP_EOL);

          } else {
            echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This role currently has no permitted sensors</strong></p>');
            //print_warning('This user currently has no permitted sensors');
          }

          $permissions_list = array_keys($role_perms['sensor']);
          // Display devices this user doesn't have Permissions to
          $form = array('type'  => 'simple',
                        'style' => 'padding: 7px; margin: 0px;',
                        //'submit_by_key' => TRUE,
                        //'url'   => generate_url($vars)
          );
          // Elements
          $form['row'][0]['auth_secret'] = array('type'     => 'hidden',
                                                 'value'    => $_SESSION['auth_secret']);
          $form['row'][0]['role_id']     = array('type'     => 'hidden',
                                                 'value'    => $role['role_id']);
          $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                                 'value'    => 'sensor');
          $form['row'][0]['action']      = array('type'     => 'hidden',
                                                 'value'    => 'role_entity_add');

          // FIXME, limit devices list only with sensors?
          $form_items['devices'] = array();
          foreach ($cache['devices']['hostname'] as $hostname => $device_id)
          {
            if (!in_array($device_id, $permissions_list))
            {
              $form_items['devices'][$device_id] = escape_html($hostname);
            }
          }
          $form['row'][0]['device_id']   = array('type'     => 'select',
                                                 'name'     => 'Select a device',
                                                 'width'    => '150px',
                                                 'onchange' => "getEntityList(this, 'sensor_entity_id', 'sensor')",
                                                 //'value'    => $vars['device_id'],
                                                 'values'   => $form_items['devices']);
          $form['row'][0]['sensor_entity_id'] = array('type' => 'multiselect',
                                                      'name'     => 'Permit Sensor',
                                                      'width'    => '150px',
                                                      //'value'    => $vars['sensor_entity_id'],
                                                      'values'   => array());
          // add button
          $form['row'][0]['Submit']      = array('type'     => 'submit',
                                                 'name'     => 'Add',
                                                 'icon'     => $config['icon']['plus'],
                                                 'right'    => TRUE,
                                                 'value'    => 'Add');
          print_form($form); unset($form);

          echo generate_box_close();
          // End sensor permissions



?>
</div>


    </div>

    <?php
} else
  { // Invalid role_id

    print_error("Invalid User Group");

  }

} else { // if no role_id

  $roles = dbFetchRows("SELECT * FROM `roles` ORDER BY `role_name`");

  if (count($roles))
  {

    echo(generate_box_open());
    echo('<table class="table table-hover table-condensed">');

    $cols = array(
      array('', 'class="state-marker"'),
      'role_id'    => array('Group ID', 'style="width: 80px;"'),
      'role_name'  => array('Name', 'style="width: 200px;"'),
      'role_count' => array('Users', 'style="width: 80px;"'),
      'role_descr' => 'Description',

    );
    echo(get_table_header($cols));

    foreach ($roles as $role)
    {
      humanize_user($role);

      $role['edit_url'] = generate_url(array('page' => 'roles', 'role_id' => $role['role_id']));

      $role['count'] = dbFetchCell("SELECT COUNT(*) FROM `roles_users` WHERE `role_id` = ? AND `auth_mechanism` = ?", [ $role['role_id'], $config['auth_mechanism'] ]);

      echo '<tr class="' . $role['row_class'] . '">';
      echo '<td class="state-marker"></td>';
      echo '<td>' . $role['role_id'] . '</td>';
      echo '<td><strong><a href="' . $role['edit_url'] . '">' . escape_html($role['role_name']) . '</a></strong></td>';
      echo '<td><label class="label">' . $role['count'] . '</label></td>';
      echo '<td>' . $role['role_descr'] . '</td>';
      echo '</tr>';
    }

    echo('</table>');
    echo(generate_box_close());

  }
  else
  {
    print_warning('There are no user groups in the database.');
  }
} // end if role_id