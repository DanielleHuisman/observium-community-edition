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

register_html_title("Edit user");

if ($_SESSION['userlevel'] < 10)
{
  print_error_permission();
  return;
}

include($config['html_dir']."/pages/usermenu.inc.php");

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

  $user_list_sort = auth_user_list();
  $user_list_sort = array_sort_by($user_list_sort, 'level', SORT_DESC, SORT_NUMERIC, 'username', SORT_ASC, SORT_STRING);
  $user_list = array();
  foreach ($user_list_sort as $entry)
  {
    humanize_user($entry);
    /*
    if (isset($user_list[$entry['user_id']]))
    {
      r($user_list[$entry['user_id']]);
      r($entry);
      break;
    }
    */
    $user_list[$entry['user_id']]            = $entry;
    $user_list[$entry['user_id']]['name']    = escape_html($entry['username']);
    if ($entry['row_class'])
    {
      $user_list[$entry['user_id']]['class']   = 'bg-'.$entry['row_class'];
    }
    $user_list[$entry['user_id']]['group']   = $entry['level_label'];
    $user_list[$entry['user_id']]['subtext'] = $entry['realname'];
  }
  //r($user_list);
  unset($user_list_sort);

  echo('<li>');
  // FIXME, currently users list more than 1000 have troubles with memory use
  // Do not generate this unusable dropdown form, need to switch ajax input
  if (count($user_list) <= 512)
  {
    $item = array('id'       => 'page',
                  'value'    => 'edituser');
    echo(generate_form_element($item, 'hidden'));
    $item = array('id'       => 'user_id',
                  'title'    => 'Select User',
                  'width'    => '150px',
                  'onchange' => "location.href='edituser/user_id=' + this.options[this.selectedIndex].value + '/';",
                  'values'   => $user_list,
                  'value'    => $vars['user_id']);
    echo(generate_form_element($item, 'select'));
  }
  echo('
      </li>
    </ul>');

  if ($vars['user_id'])
  {
    // Load the user's information
    if (isset($user_list[$vars['user_id']]))
    {
      $user_data = $user_list[$vars['user_id']];
    } else {
      $user_data = dbFetchRow("SELECT * FROM `users` WHERE `user_id` = ?", array($vars['user_id']));
    }
    $user_data['username'] = auth_username_by_id($vars['user_id']);
    $user_data['level']    = auth_user_level($user_data['username']);
    humanize_user($user_data); // Get level_label, level_real, row_class, etc

    // Become the selected user. Dirty.
    // FIXME this functionality is currently BROKEN. Commented out the link until we handle this better.
    // echo("<li><a href='edituser/action=becomeuser/user_id=".$vars['user_id']."/'>Become User</a></li>");

    // Delete the selected user.
    if (auth_usermanagement() && $vars['user_id'] !== $_SESSION['user_id'])
    {
      echo('<ul class="nav pull-right">');
      echo('<li><a href="'.generate_url(array('page'         => 'edituser',
                                              'action'       => 'deleteuser',
                                              'user_id'      => $vars['user_id'],
                                              'confirm'      => 'yes',
                                              'requesttoken' => $_SESSION['requesttoken'])) . '"
                   data-toggle="confirmation"
                   data-confirm-content="You have requested deletion of the user <strong>'.$user_data['username'].'</strong>.<br />This action can not be reversed."
                   data-confirm-placement="bottom">
                <i class="'.$config['icon']['cancel'].'"></i> Delete User</a></li>');
      echo('</ul>');
      register_html_resource('js',     'jquery.popconfirm.js');
      register_html_resource('script', '$("[data-toggle=\'confirmation\']").popConfirm();');
    }
  }

?>

    </div>
  </div>
</div>
</form>

<?php
  if ($vars['user_id'])
  {
    // Check if correct auth secret passed
    $auth_secret_fail = empty($_SESSION['auth_secret']) || empty($vars['auth_secret']) || !hash_equals($_SESSION['auth_secret'], $vars['auth_secret']);
    //print_vars($auth_secret_fail);
    //$auth_secret_fail = TRUE;

    if ($vars['action'] == "deleteuser" && request_token_valid($vars))
    {
      include($config['html_dir']."/pages/edituser/deleteuser.inc.php");
    } else {

      // Perform actions if requested

    if (auth_usermanagement() && isset($vars['action']) && request_token_valid($vars)) // Admins always can change user info & password
    {
      switch($vars['action'])
      {
        case "changepass":
          if ($vars['new_pass'] == "" || $vars['new_pass2'] == "")
          {
            print_warning("Password cannot be blank.");
          }
          else if ($auth_secret_fail)
          {
            // Incorrect auth secret, seems as someone try to hack system ;)
            print_debug("Incorrect admin auth, get out from here nasty hacker.");
          }
          else if ($vars['new_pass'] == $vars['new_pass2'])
          {
            $status = auth_change_password($user_data['username'], $vars['new_pass']);
            if ($status)
            {
              print_success("Password Changed.");
            } else {
              print_error("Password not changed.");
            }
          } else {
            print_error("Passwords don't match!");
          }
          break;

        case "change_user":
          if ($auth_secret_fail)
          {
            // Incorrect auth secret, seems as someone try to hack system ;)
            print_debug("Incorrect admin auth, get out from here nasty hacker.");
          } else {
            $update_array = array();
            $vars['new_can_modify_passwd'] = (isset($vars['new_can_modify_passwd']) && $vars['new_can_modify_passwd'] ? 1 : 0);
            foreach (array('realname', 'level', 'email', 'descr', 'can_modify_passwd') as $param)
            {
              if ($vars['new_' . $param] != $user_data[$param]) { $update_array[$param] = $vars['new_' . $param]; }
            }
            if (count($update_array))
            {
              $status = dbUpdate($update_array, 'users', '`user_id` = ?', array($vars['user_id']));
            }
            if ($status)
            {
              print_success("User Info Changed.");
            } else {
              print_error("User Info not changed.");
            }
          }
          break;
      }

      if ($status)
      {
        // Reload user info
        //$user_data = dbFetchRow("SELECT * FROM `users` WHERE `user_id` = ?", array($vars['user_id']));
        $user_data['username'] = auth_username_by_id($vars['user_id']);
        $user_data             = auth_user_info($user_data['username']);
        $user_data['level']    = auth_user_level($user_data['username']);
        humanize_user($user_data); // Get level_label, level_real, label_class, row_class, etc
      }
    }

    // FIXME broken PoS code.
    /*
    if ($vars['action'] == "becomeuser")
    {
      $_SESSION['origusername'] = $_SESSION['username'];
      $_SESSION['username'] = $user_data['username'];
      redirect_to_url($config['base_url']);
      dbInsert(array('user' => $_SESSION['origusername'], 'address' => $_SERVER["REMOTE_ADDR"], 'result' => 'Became ' . $_SESSION['username']), 'authlog');

      include($config['html_dir']."/includes/authenticate.inc.php");
    }
    */

    // FIXME -- output messages!
    if (($vars['submit'] == "user_perm_del" || $vars['action'] == "user_perm_del") && request_token_valid($vars))
    {
      if ($auth_secret_fail)
      {
        // Incorrect auth secret, seems as someone try to hack system ;)
        print_debug("Incorrect admin auth, get out from here nasty hacker.");
      } else {
        if (isset($vars['entity_id'])) {} // use entity_id
        else if (isset($vars[$vars['entity_type'].'_entity_id'])) // use type_entity_id
        {
          $vars['entity_id'] = $vars[$vars['entity_type'].'_entity_id'];
        }

        $where = '`user_id` = ? AND `entity_type` = ?' . generate_query_values($vars['entity_id'], 'entity_id');
        //if (@dbFetchCell("SELECT COUNT(*) FROM `entity_permissions` WHERE " . $where, array($vars['user_id'], $vars['entity_type'])))
        if (dbExist('entity_permissions', $where, array($vars['user_id'], $vars['entity_type'])))
        {
          dbDelete('entity_permissions', $where, array($vars['user_id'], $vars['entity_type']));
        }
      }
    }
    else if (($vars['submit'] == "user_perm_add" || $vars['action'] == "user_perm_add") && request_token_valid($vars))
    {
      if ($auth_secret_fail)
      {
        // Incorrect auth secret, seems as someone try to hack system ;)
        print_debug("Incorrect admin auth, get out from here nasty hacker.");
      } else {
        if      (isset($vars['entity_id'])) {} // use entity_id
        else if (isset($vars[$vars['entity_type'].'_entity_id'])) // use type_entity_id
        {
          $vars['entity_id'] = $vars[$vars['entity_type'].'_entity_id'];
        }
        if (!is_array($vars['entity_id'])) { $vars['entity_id'] = array($vars['entity_id']); }

        foreach ($vars['entity_id'] as $entry)
        {
          if (get_entity_by_id_cache($vars['entity_type'], $entry)) // Skip not exist entities
          {
            //if (!dbFetchCell("SELECT COUNT(*) FROM `entity_permissions` WHERE `user_id` = ? AND `entity_type` = ? AND `entity_id` = ?", array($vars['user_id'], $vars['entity_type'], $entry)))
            if (!dbExist('entity_permissions', '`user_id` = ? AND `entity_type` = ? AND `entity_id` = ?', array($vars['user_id'], $vars['entity_type'], $entry)))
            {
              dbInsert(array('entity_id' => $entry, 'entity_type' => $vars['entity_type'], 'user_id' => $vars['user_id']), 'entity_permissions');
            }
          }
        }
      }
    }

    // Generate new auth secret
    session_set_var('auth_secret', md5(strgen()));

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
              <td><?php echo('<span class="label label-'.$user_data['label_class'].'">'.$user_data['level_label'].'</span>'); ?></td>
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

          <div class="form-actions" style="margin: 0;">
<?php
        if (auth_usermanagement())
        {
          echo '<button class="btn btn-default pull-right" data-toggle="modal" data-target="#modal-edituser"><i class="'.$config['icon']['user-edit'].'"></i>&nbsp;Edit&nbsp;User</button>';
        }
?>
          </div>
        </div>
      </div>

      </div> <!-- userinfo end -->

<?php
    if (auth_usermanagement())
    { // begin user edit modal

      $form = array('type'       => 'horizontal',
                    //'userlevel'  => 10,          // Minimum user level for display form
                    'id'         => 'edituser',
                    'title'      => 'Edit User: <strong>"'   . escape_html($user_data['realname']) . '" ('. escape_html($user_data['username']) . '</strong>)',
                    //'modal_args' => $modal_args, // modal specific options
                    //'help'      => 'This will delete the selected contact and any alert assocations.',
                    //'class'     => '', // Clean default box class (default for modals)
                    //'url'       => 'delhost/'
                    );
      //$form['fieldset']['body']   = array('class' => 'modal-body');   // Required this class for modal body!
      //$form['fieldset']['footer'] = array('class' => 'modal-footer'); // Required this class for modal footer!

      $form['row'][0]['user_id'] = array(
                                        'type'        => 'hidden',
                                        'fieldset'    => 'body',
                                        'value'       => $user_data['user_id']);
      $form['row'][0]['auth_secret'] = array(
                                        'type'        => 'hidden',
                                        'fieldset'    => 'body',
                                        'value'       => $_SESSION['auth_secret']);

      $form['row'][1]['new_realname'] = array(
                                        'type'        => 'text',
                                        'fieldset'    => 'body',
                                        'name'        => 'Real Name',
                                        'width'       => '80%',
                                        'placeholder' => TRUE,
                                        'value'       => $user_data['realname']);
      $form['row'][2]['new_level'] = array(
                                        'type'        => 'select',
                                        'fieldset'    => 'body',
                                        'name'        => 'User Level',
                                        'width'       => '80%',
                                        'subtext'     => TRUE,
                                        'values'      => $GLOBALS['config']['user_level'],
                                        'value'       => $user_data['level_real']);
      $form['row'][3]['new_email'] = array(
                                        'type'        => 'text',
                                        'fieldset'    => 'body',
                                        'name'        => 'E-mail',
                                        'width'       => '80%',
                                        'placeholder' => TRUE,
                                        'value'       => $user_data['email']);
      $form['row'][4]['new_descr'] = array(
                                        'type'        => 'text',
                                        'fieldset'    => 'body',
                                        'name'        => 'Description',
                                        'width'       => '80%',
                                        'placeholder' => TRUE,
                                        'value'       => $user_data['descr']);
      $form['row'][5]['new_can_modify_passwd'] = array(
                                        'type'        => 'toggle',
                                        'view'        => 'toggle',
                                        'fieldset'    => 'body',
                                        'placeholder' => 'Allow the user to change his password',
                                        'value'       => $user_data['can_modify_passwd']);

      $form['row'][8]['close'] = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'footer',
                                      'div_class'   => '', // Clean default form-action class!
                                      'name'        => 'Close',
                                      'icon'        => '',
                                      'attribs'     => array('data-dismiss' => 'modal',  // dismiss modal
                                                             'aria-hidden'  => 'true')); // do not sent any value
      $form['row'][9]['action'] = array(
                                      'type'        => 'submit',
                                      'fieldset'    => 'footer',
                                      'div_class'   => '', // Clean default form-action class!
                                      'name'        => 'Save Changes',
                                      'icon'        => 'icon-ok icon-white',
                                      //'right'       => TRUE,
                                      'class'       => 'btn-primary',
                                      //'disabled'    => TRUE,
                                      'value'       => 'change_user');

      echo generate_form_modal($form);
      unset($form);

    } // end edit user modal

    if (auth_usermanagement())
    { // begin change password
      $form = array('type'    => 'horizontal',
                    //'space'   => '10px',
                    'title'   => 'Change Password',
                    'icon'    => $config['icon']['lock'],
                    //'class'   => 'box box-solid',
                    'fieldset' => array('change_password' => ''));
                    //'fieldset'  => array('change_password' => 'Change Password'));
      $form['row'][0]['action']   = array(
                                      'type'     => 'hidden',
                                      'value'    => 'changepass');
      $form['row'][1]['auth_secret'] = array(
                                      'type'     => 'hidden',
                                      'value'    => $_SESSION['auth_secret']);
      $form['row'][2]['new_pass'] = array(
                                      'type'        => 'password',
                                      'fieldset'    => 'change_password', // Group by fieldset
                                      'name'        => 'New Password',
                                      'width'       => '95%',
                                      'value'       => '');
      $form['row'][3]['new_pass2']  = array(
                                      'type'        => 'password',
                                      'fieldset'    => 'change_password', // Group by fieldset
                                      'name'        => 'Retype Password',
                                      'width'       => '95%',
                                      'value'       => '');
      $form['row'][10]['submit']    = array(
                                      'type'        => 'submit',
                                      'name'        => 'Update&nbsp;Password',
                                      'icon'        => $config['icon']['lock'],
                                      'right'       => TRUE,
                                      'value'       => 'save');
      echo('  <div class="col-md-6">' . PHP_EOL);
      print_form($form);
      unset($form, $i);
      echo('  </div>' . PHP_EOL);
    } // end change password
?>
    </div> <!-- left up row end -->
    <!--<div class="col-md-12">-->

<?php print_authlog(array_merge($vars, array('short' => TRUE, 'pagination' => FALSE))); ?>

  </div> <!-- left column end -->

  <div class="col-md-5"> <!-- right column begin -->

<?php

// Begin main permissions block
//if ($user_data['permission_access'] === FALSE || $user_data['permission_read'] === FALSE || $user_data['permission_admin'])
//{
  echo generate_box_open(array('header-border' => TRUE, 'title' => 'Permission level'));
  echo('<p class="text-center text-uppercase text-'.$user_data['row_class'].' bg-'.$user_data['row_class'].'" style="padding: 10px; margin: 0px;"><strong>'.$user_data['subtext'].'</strong></p>');
  echo generate_box_close();
  //print_error($user_data['subtext']);
//} else {
  // if user has access and not has read/secure read/edit use individual permissions
  //echo generate_box_open();
//}

// Always display (and edit permissions) also if user disabled or has global read or admin permissions

  // Cache user permissions
  foreach (dbFetchRows("SELECT * FROM `entity_permissions` WHERE `user_id` = ?", array($vars['user_id'])) as $entity)
  {
    $user_permissions[$entity['entity_type']][$entity['entity_id']] = TRUE;
  }

  // Start bill Permissions
  if (isset($config['enable_billing']) && $config['enable_billing'])
  {
    echo generate_box_open(array('header-border' => TRUE, 'title' => 'Bill Permissions'));
    if (count($user_permissions['bill']))
    {
      echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

      foreach ($user_permissions['bill'] as $bill_id => $status)
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
                                               'value'    => 'user_perm_del');
        print_form($form); unset($form);

        echo('</td>
                </tr>');
      }
      echo('</table>' . PHP_EOL);

    } else {
      echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted bills</strong></p>');
      //print_warning("This user currently has no permitted bills");
    }

    // Bills
    $permissions_list = array_keys($user_permissions['bill']);

    $form = array('type'  => 'simple',
                  'style' => 'padding: 7px; margin: 0px;',
                  //'submit_by_key' => TRUE,
                  //'url'   => generate_url($vars)
                  );
    // Elements
    $form['row'][0]['auth_secret'] = array(
                                           'type'     => 'hidden',
                                           'value'    => $_SESSION['auth_secret']);
    $form['row'][0]['user_id']     = array('type'     => 'hidden',
                                           'value'    => $vars['user_id']);
    $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                           'value'    => 'bill');
    $form['row'][0]['action']      = array('type'     => 'hidden',
                                           'value'    => 'user_perm_add');

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

  // Start group permissions
  if (OBSERVIUM_EDITION != 'community')
  {
    echo generate_box_open(array('header-border' => TRUE, 'title' => 'Group Permissions'));

    if (count($user_permissions['group']))
    {
      echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

      foreach ($user_permissions['group'] as $group_id => $status)
      {
        $group = get_group_by_id($group_id);

        echo('<tr><td style="width: 1px;"></td>
                <td style="overflow: hidden;"><i class="'.$config['entities'][$group['entity_type']]['icon'].'"></i> '.generate_entity_link('group', $group).'
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
        $form['row'][0]['submit']      = array('type'     => 'submit',
                                               'name'     => ' ',
                                               'class'    => 'btn-danger btn-mini',
                                               'icon'     => 'icon-trash',
                                               'value'    => 'user_perm_del');
        print_form($form); unset($form);

        echo('</td>
              </tr>');
      }
      echo('</table>' . PHP_EOL);

    } else {
      echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted groups</strong></p>');
      //print_warning("This user currently has no permitted groups");
    }

    // Groups
    $permissions_list = array_keys($user_permissions['group']);

    $form = array('type'  => 'simple',
                  'style' => 'padding: 7px; margin: 0px;',
                  //'submit_by_key' => TRUE,
                  //'url'   => generate_url($vars)
                  );
    // Elements
    $form['row'][0]['auth_secret'] = array(
                                           'type'     => 'hidden',
                                           'value'    => $_SESSION['auth_secret']);
    $form['row'][0]['user_id']     = array('type'     => 'hidden',
                                           'value'    => $vars['user_id']);
    $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                           'value'    => 'group');
    $form['row'][0]['action']      = array('type'     => 'hidden',
                                           'value'    => 'user_perm_add');

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

  if (count($user_permissions['device']))
  {
    echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

    foreach ($user_permissions['device'] as $device_id => $status)
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
      $form['row'][0]['auth_secret'] = array(
                                             'type'     => 'hidden',
                                             'value'    => $_SESSION['auth_secret']);
      $form['row'][0]['entity_id']   = array('type'     => 'hidden',
                                             'value'    => $device['device_id']);
      $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                             'value'    => 'device');
      $form['row'][0]['submit']      = array('type'     => 'submit',
                                             'name'     => ' ',
                                             'class'    => 'btn-danger btn-mini',
                                             'icon'     => 'icon-trash',
                                             'value'    => 'user_perm_del');
      print_form($form); unset($form);

      echo('</td>
              </tr>');
    }
    echo('</table>' . PHP_EOL);

  } else {
    echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted devices</strong></p>');
    //print_warning("This user currently has no permitted devices");
  }

  // Devices
  $permissions_list = array_keys($user_permissions['device']);
  // Display devices this user doesn't have Permissions to
  $form = array('type'  => 'simple',
                'style' => 'padding: 7px; margin: 0px;',
                //'submit_by_key' => TRUE,
                //'url'   => generate_url($vars)
                );
  // Elements
  $form['row'][0]['auth_secret'] = array(
                                         'type'     => 'hidden',
                                         'value'    => $_SESSION['auth_secret']);
  $form['row'][0]['user_id']     = array('type'     => 'hidden',
                                         'value'    => $vars['user_id']);
  $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                         'value'    => 'device');
  $form['row'][0]['action']      = array('type'     => 'hidden',
                                         'value'    => 'user_perm_add');

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
  if (count($user_permissions['port']))
  {
    echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

    foreach (array_keys($user_permissions['port']) as $entity_id)
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
      $form['row'][0]['submit']      = array('type'     => 'submit',
                                             'name'     => '',
                                             'class'    => 'btn-danger btn-mini',
                                             'icon'     => 'icon-trash',
                                             'value'    => 'user_perm_del');
      print_form($form); unset($form);

      echo('</td>
              </tr>');
    }
    echo('</table>' . PHP_EOL);

  } else {
    echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted ports</strong></p>');
    //print_warning('This user currently has no permitted ports');
  }

  // Ports
  $permissions_list = array_keys($user_permissions['port']);

  // Display devices this user doesn't have Permissions to
  $form = array('type'  => 'simple',
                'style' => 'padding: 7px; margin: 0px;',
                //'submit_by_key' => TRUE,
                //'url'   => generate_url($vars)
                );
  // Elements
  $form['row'][0]['auth_secret'] = array(
                                         'type'     => 'hidden',
                                         'value'    => $_SESSION['auth_secret']);
  $form['row'][0]['user_id']     = array('type'     => 'hidden',
                                         'value'    => $vars['user_id']);
  $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                         'value'    => 'port');
  $form['row'][0]['action']      = array('type'     => 'hidden',
                                         'value'    => 'user_perm_add');

  $form_items['devices'] = array();
  foreach ($cache['devices']['hostname'] as $hostname => $device_id)
  {
    if (!array_key_exists($device_id, $user_permissions['device']))
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

  if (count($user_permissions['sensor']))
  {
    echo('<table class="'.OBS_CLASS_TABLE.'">' . PHP_EOL);

    foreach (array_keys($user_permissions['sensor']) as $entity_id)
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
      $form['row'][0]['auth_secret'] = array(
                                             'type'     => 'hidden',
                                             'value'    => $_SESSION['auth_secret']);
      $form['row'][0]['entity_id']   = array('type'     => 'hidden',
                                             'value'    => $sensor['sensor_id']);
      $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                             'value'    => 'sensor');
      $form['row'][0]['submit']      = array('type'     => 'submit',
                                             'name'     => ' ',
                                             'class'    => 'btn-danger btn-mini',
                                             'icon'     => 'icon-trash',
                                             'value'    => 'user_perm_del');
      print_form($form); unset($form);

      echo('</td>
              </tr>');
    }
    echo('</table>' . PHP_EOL);

    } else {
      echo('<p class="text-center text-warning bg-warning" style="padding: 10px; margin: 0px;"><strong>This user currently has no permitted sensors</strong></p>');
      //print_warning('This user currently has no permitted sensors');
    }

    $permissions_list = array_keys($user_permissions['sensor']);
    // Display devices this user doesn't have Permissions to
    $form = array('type'  => 'simple',
                  'style' => 'padding: 7px; margin: 0px;',
                  //'submit_by_key' => TRUE,
                  //'url'   => generate_url($vars)
                  );
    // Elements
    $form['row'][0]['auth_secret'] = array(
                                           'type'     => 'hidden',
                                           'value'    => $_SESSION['auth_secret']);
    $form['row'][0]['user_id']     = array('type'     => 'hidden',
                                           'value'    => $vars['user_id']);
    $form['row'][0]['entity_type'] = array('type'     => 'hidden',
                                           'value'    => 'sensor');
    $form['row'][0]['action']      = array('type'     => 'hidden',
                                           'value'    => 'user_perm_add');

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

// End main permissions block
//echo generate_box_close();

?>

  </div> <!-- right column end -->

</div> <!-- main row end -->

<?php

    }

  } else {

    //$users = dbFetchRows("SELECT * FROM `users` ORDER BY `username`");

    $count = count($user_list);
    if ($count)
    {
      pagination($vars, 0, TRUE); // Get default pagesize/pageno
      $pageno   = $vars['pageno'];
      $pagesize = $vars['pagesize'];
      $start    = $pagesize * $pageno - $pagesize;
      $pagination = $count >= $pagesize;
      if ($pagination)
      {
        $users = array_slice($user_list, $start, $pagesize);
        echo(pagination($vars, $count));
      } else {
        $users = $user_list;
      }

      echo(generate_box_open());
      echo('<table class="table table-hover table-condensed">');

      $cols = array(
                      array('', 'class="state-marker"'),
        'user_id'  => array('User ID', 'style="width: 80px;"'),
        'user'     => 'Username',
        'access'   => 'Access',
        'realname' => 'Real Name',
        'email'    => 'Email',
      );
      echo(get_table_header($cols));

      foreach ($users as $user)
      {
        humanize_user($user);

        $user['edit_url'] = generate_url(array('page' => 'edituser', 'user_id' => $user['user_id']));

        echo('<tr class="'.$user['row_class'].'">');
        echo('<td class="state-marker"></td>');
        echo('<td>'.$user['user_id'].'</td>');
        echo('<td><strong><a href="'.$user['edit_url'].'">'.escape_html($user['username']).'</a></strong></td>');
        //echo('<td><strong>'.$user['level'].'</strong></td>');
        echo('<td><i class="'.$user['icon'].'"></i> <span class="label label-'.$user['label_class'].'">'.$user['level_label'].'</span></td>');
        echo('<td><strong>'.escape_html($user['realname']).'</strong></td>');
        echo('<td><strong>'.escape_html($user['email']).'</strong></td>');

        echo('</tr>');
      }

      echo('</table>');
      echo(generate_box_close());

      if ($pagination)
      {
        echo(pagination($vars, $count));
      }
    } else {
      print_warning('There are no users in the database.');
    }

  }

// EOF
