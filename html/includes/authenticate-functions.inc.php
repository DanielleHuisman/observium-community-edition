<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage authentication
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

// DOCME needs phpdoc block
function authenticate($username, $password)
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_authenticate'))
  {
    // Can't consider remote_user setting here, as for example the LDAP plugin still needs to check
    // group membership before logging in. So remote_user currently needs to be considered in 
    // mech_authenticate() by the module itself until we split this up, maybe...
    return call_user_func($config['auth_mechanism'] . '_authenticate', $username, $password);
  } else {
    return call_user_func('mysql_authenticate', $username, $password);
  }
}

// DOCME needs phpdoc block
function auth_can_logout()
{
  global $config;

  // If logged in through Apache REMOTE_USER, logout is not possible
  if ($config['auth']['remote_user'])
  {
    return FALSE;
  } else if (function_exists($config['auth_mechanism'] . '_auth_can_logout'))
  {
    return call_user_func($config['auth_mechanism'] . '_auth_can_logout');
  } else {
    return call_user_func('mysql_auth_can_logout');
  }
}

/**
 * Returns the URL redirection required for logout, or null if internal logout is sufficient.
 * @return string logout url
 */
function auth_logout_url()
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_auth_logout_url'))
  {
    return call_user_func($config['auth_mechanism'] . '_auth_logout_url');
  } else {
    return null;
  }
}

// DOCME needs phpdoc block
function auth_can_change_password($username = "")
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_auth_can_change_password'))
  {
    return call_user_func($config['auth_mechanism'] . '_auth_can_change_password', $username);
  } else {
    return call_user_func('mysql_auth_can_change_password', $username);
  }
}

// DOCME needs phpdoc block
function auth_change_password($username, $password)
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_auth_change_password'))
  {
    return call_user_func($config['auth_mechanism'] . '_auth_change_password', $username, $password);
  } else {
    return call_user_func('mysql_auth_change_password', $username, $password);
  }
}

// DOCME needs phpdoc block
function auth_usermanagement()
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_auth_usermanagement'))
  {
    return call_user_func($config['auth_mechanism'] . '_auth_usermanagement');
  } else {
    return call_user_func('mysql_auth_usermanagement');
  }
}

// DOCME needs phpdoc block
function adduser($username, $password, $level, $email = "", $realname = "", $can_modify_passwd = '1', $description = "")
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_adduser'))
  {
    return call_user_func($config['auth_mechanism'] . '_adduser', $username, $password, $level, $email, $realname, $can_modify_passwd, $description);
  } else {
    return call_user_func('mysql_adduser', $username, $password, $level, $email, $realname, $can_modify_passwd, $description);
  }
}

// DOCME needs phpdoc block
function auth_user_exists($username)
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_auth_user_exists'))
  {
    return call_user_func($config['auth_mechanism'] . '_auth_user_exists', $username);
  } else {
    return call_user_func('mysql_auth_user_exists', $username);
  }
}

// DOCME needs phpdoc block
function auth_user_level($username)
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_auth_user_level'))
  {
    return call_user_func($config['auth_mechanism'] . '_auth_user_level', $username);
  } else {
    return call_user_func('mysql_auth_user_level', $username);
  }
}

// DOCME needs phpdoc block
function auth_user_level_permissions($user_level)
{

  $user = array('level' => -1, 'permission' => 0); // level -1 equals "not exist" user

  if (is_numeric($user_level))
  {
    krsort($GLOBALS['config']['user_level']); // Order levels from max to low
    foreach ($GLOBALS['config']['user_level'] as $level => $entry)
    {
      if ($user_level >= $level)
      {
        $user['level']      = $level; // Real (normalized) user level
        $user['permission'] = $entry['permission'];
        break;
      }
    }
  }
  // Convert permission flags to Boolean permissions
  $user['permission_admin']  = is_flag_set(OBS_PERMIT_ALL,    $user['permission'], TRUE); // Administrator
  $user['permission_edit']   = is_flag_set(OBS_PERMIT_EDIT,   $user['permission']); // Limited Edit
  $user['permission_secure'] = is_flag_set(OBS_PERMIT_SECURE, $user['permission']); // Secure Read
  $user['permission_read']   = is_flag_set(OBS_PERMIT_READ,   $user['permission']); // Global Read
  $user['permission_access'] = is_flag_set(OBS_PERMIT_ACCESS, $user['permission']); // Access (logon) allowed
  // Set quick boolen flag that user limited
  $user['limited']           = !$user['permission_read'] && !$user['permission_secure'] && !$user['permission_edit'] && !$user['permission_admin'];

  return $user;
}

// DOCME needs phpdoc block
function auth_user_id($username)
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_auth_user_id'))
  {
    return call_user_func($config['auth_mechanism'] . '_auth_user_id', $username);
  } else {
    return call_user_func('mysql_auth_user_id', $username);
  }
}

// DOCME needs phpdoc block
function auth_username_by_id($user_id)
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_auth_username_by_id'))
  {
    return call_user_func($config['auth_mechanism'] . '_auth_username_by_id', $user_id);
  } else {
    return call_user_func('mysql_auth_username_by_id', $user_id);
  }
}

// DOCME needs phpdoc block
function deluser($username)
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_deluser'))
  {
    return call_user_func($config['auth_mechanism'] . '_deluser', $username);
  } else {
    return call_user_func('mysql_deluser', $username);
  }
}

// DOCME needs phpdoc block
function auth_user_list()
{
  global $config;

  if (function_exists($config['auth_mechanism'] . '_auth_user_list'))
  {
    $user_list_sort = call_user_func($config['auth_mechanism'] . '_auth_user_list');
  } else {
    $user_list_sort = call_user_func('mysql_auth_user_list');
  }

    // Process the user list here to provide all of the additional data used elsewhere in the UI
    // This prepares user_ids for LDAP to be used in AJAX and other places

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
    unset($user_list_sort);

    return $user_list;

}

// DOCME needs phpdoc block
function auth_user_info($username)
{
  if (function_exists($GLOBALS['config']['auth_mechanism'] . '_auth_user_info'))
  {
    return call_user_func($GLOBALS['config']['auth_mechanism'] . '_auth_user_info', $username);
  } else {
    return call_user_func('mysql_auth_user_info', $username);
  }
}

// EOF
