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

// DOCME needs phpdoc block
function authenticate($username, $password)
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_authenticate')) {
        // Can't consider remote_user setting here, as for example the LDAP plugin still needs to check
        // group membership before logging in. So remote_user currently needs to be considered in
        // mech_authenticate() by the module itself until we split this up, maybe...
        return call_user_func($config['auth_mechanism'] . '_authenticate', $username, $password);
    }
    return mysql_authenticate($username, $password);
}

// DOCME needs phpdoc block
function auth_can_logout()
{
    global $config;

    // If logged in through Apache REMOTE_USER, logout is not possible
    if ($config['auth']['remote_user']) {
        return FALSE;
    }
    if (function_exists($config['auth_mechanism'] . '_auth_can_logout')) {
        return call_user_func($config['auth_mechanism'] . '_auth_can_logout');
    }
    return mysql_auth_can_logout();
}

/**
 * Returns the URL redirection required for logout, or null if internal logout is sufficient.
 *
 * @return string logout url
 */
function auth_logout_url()
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_auth_logout_url')) {
        return call_user_func($config['auth_mechanism'] . '_auth_logout_url');
    }
    return NULL;
}

// DOCME needs phpdoc block
function auth_can_change_password($username = "")
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_auth_can_change_password')) {
        return call_user_func($config['auth_mechanism'] . '_auth_can_change_password', $username);
    }
    return mysql_auth_can_change_password($username);
}

// DOCME needs phpdoc block
function auth_change_password($username, $password)
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_auth_change_password')) {
        return call_user_func($config['auth_mechanism'] . '_auth_change_password', $username, $password);
    }
    return mysql_auth_change_password($username, $password);
}

// DOCME needs phpdoc block
function auth_usermanagement()
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_auth_usermanagement')) {
        return call_user_func($config['auth_mechanism'] . '_auth_usermanagement');
    }
    return mysql_auth_usermanagement();
}

// DOCME needs phpdoc block
function adduser($username, $password, $level, $email = "", $realname = "", $can_modify_passwd = '1', $description = "")
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_adduser')) {
        return call_user_func($config['auth_mechanism'] . '_adduser', $username, $password, $level, $email, $realname, $can_modify_passwd, $description);
    }
    return mysql_adduser($username, $password, $level, $email, $realname, $can_modify_passwd, $description);
}

// DOCME needs phpdoc block
function auth_user_exists($username)
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_auth_user_exists')) {
        return call_user_func($config['auth_mechanism'] . '_auth_user_exists', $username);
    }
    return mysql_auth_user_exists($username);
}

// DOCME needs phpdoc block
function auth_user_level($username)
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_auth_user_level')) {
        return call_user_func($config['auth_mechanism'] . '_auth_user_level', $username);
    }
    return mysql_auth_user_level($username);
}

function auth_user_level_name($user_level)
{
    if (!is_numeric($user_level)) {
        return 'Invalid';
    }

    $def = $GLOBALS['config']['user_level'];
    if (isset($def[$user_level])) {
        return $def['name']; // Simple
    }

    krsort($def); // Order levels from max to low
    foreach ($def as $level => $entry) {
        if ($user_level >= $level) {
            return $entry['name']; // Real (normalized) user level
        }
    }
}

// DOCME needs phpdoc block
function auth_user_level_permissions($user_level)
{
    $user = ['level' => -1, 'permission' => 0]; // level -1 equals "not exist" user

    if (is_numeric($user_level)) {
        $def = $GLOBALS['config']['user_level'];
        if (isset($def[$user_level])) {
            $user['level'] = (int)$user_level; // Simple
        } else {
            krsort($def); // Order levels from max to low
            foreach ($def as $level => $entry) {
                if ($user_level >= $level) {
                    $user['level'] = $level; // Real (normalized) user level
                    break;
                }
            }
        }
    }
    // Convert permission flags to Boolean permissions
    $user['permission_admin']  = $user['level'] >= 10; // Administrator
    $user['permission_edit']   = $user['level'] >= 8;  // Limited Edit
    $user['permission_secure'] = $user['level'] >= 7;  // Secure Read
    $user['permission_read']   = $user['level'] >= 5;  // Global Read
    $user['permission_access'] = $user['level'] >= 1;  // Access (logon) allowed
    // Set quick boolean flag that user limited
    $user['limited'] = !$user['permission_read'] && !$user['permission_secure'] && !$user['permission_edit'] && !$user['permission_admin'];

    return $user;
}

// DOCME needs phpdoc block
function auth_user_id($username)
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_auth_user_id')) {
        return call_user_func($config['auth_mechanism'] . '_auth_user_id', $username);
    }
    return mysql_auth_user_id($username);
}

// DOCME needs phpdoc block
function auth_username_by_id($user_id)
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_auth_username_by_id')) {
        return call_user_func($config['auth_mechanism'] . '_auth_username_by_id', $user_id);
    }
    return mysql_auth_username_by_id($user_id);
}

// DOCME needs phpdoc block
function deluser($username)
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_deluser')) {
        return call_user_func($config['auth_mechanism'] . '_deluser', $username);
    }
    return mysql_deluser($username);
}

// DOCME needs phpdoc block
function auth_user_list()
{
    global $config;

    if (function_exists($config['auth_mechanism'] . '_auth_user_list')) {
        $user_list_sort = call_user_func($config['auth_mechanism'] . '_auth_user_list');
    } else {
        $user_list_sort = mysql_auth_user_list();
    }

    // Process the user list here to provide all the additional data used elsewhere in the UI
    // This prepares user_ids for LDAP to be used in AJAX and other places

    $user_list_sort = array_sort_by($user_list_sort, 'level', SORT_DESC, SORT_NUMERIC, 'username', SORT_ASC, SORT_STRING);
    $user_list      = [];
    foreach ($user_list_sort as $entry) {
        humanize_user($entry);
        /*
        if (isset($user_list[$entry['user_id']]))
        {
          r($user_list[$entry['user_id']]);
          r($entry);
          break;
        }
        */
        $user_list[$entry['user_id']]         = $entry;
        $user_list[$entry['user_id']]['name'] = escape_html($entry['username']);
        if ($entry['row_class']) {
            $user_list[$entry['user_id']]['class'] = 'bg-' . $entry['row_class'];
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
    if (function_exists($GLOBALS['config']['auth_mechanism'] . '_auth_user_info')) {
        return call_user_func($GLOBALS['config']['auth_mechanism'] . '_auth_user_info', $username);
    }
    return mysql_auth_user_info($username);
}

// Create placeholder user for users logged in via non-MySQL mechanisms to enable user list
function create_mysql_user($username, $userid, $level = '1', $type = 'mysql')
{
    if (isset($username, $userid) && is_numeric($userid)) {
        dbInsert([ 'username' => $username, 'user_id' => $userid, 'level' => $level, 'type' => $type ], 'users');
    }
}

// EOF
