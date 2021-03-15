<?php

if ($_SESSION['userlevel'] == 10 && request_token_valid($vars)) // Only valid forms from level 10 users
{

  foreach ($vars['permission'] as $permission)
  {
    if(isset($config['permissions'][$permission]))
    {
      if (!dbExist('roles_permissions', '`role_id` = ? AND `permission` = ?',
                   array($vars['role_id'], $permission)
      ))
      {
        dbInsert(array('permission' => $permission, 'role_id' => $vars['role_id']), 'roles_permissions');
      }
    }
  }
}
