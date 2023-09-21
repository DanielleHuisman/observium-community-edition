<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * This authentication method assumes that the user has already been authenticated by the web server,
 * using the trusted specified variable (REMOTE_USER by default). It is important to configure the server
 * such that this variable cannot be overridden by users.
 *
 * There is no local user storage and all users will be assumed to have the privilege level specified
 * (which defaults to 1).
 *
 * A possible future improvement to this auth method would be to have the remote user automatically
 * created in the mysql auth method upon login with a basic user level, but could later be edited and
 * assigned higher privileges.
 *
 * Configuration variables:
 *
 * $config['auth_mechanism'] = "remote";
 *   - Enables this authentication method
 *
 * $config['auth_remote_userlevel'] = 10;
 *   - What userlevel to assign to users, defaults to 1. https://docs.observium.org/user_levels/
 *
 * $config['auth_remote_variable'] = 'REMOTE_USER';
 *   - What server variable to to use, if unspecified then REMOTE_USER is assumed.
 *
 * $config['auth_remote_logout_url'] = 'http://blah';
 *   - URL to redirect users when they click the logout button. If this is not specified, no logout button
 *     will be available.
 *
 * @package        observium
 * @subpackage     authentication
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

if (!$_SESSION['authenticated'] && !is_cli()) {
    $var = isset($config['auth_remote_variable']) ? $config['auth_remote_variable'] : 'REMOTE_USER';

    if (isset($_SERVER[$var]) && !empty($_SERVER[$var])) {
        $username = $_SERVER[$var];
        session_set_var('username', $username);
        session_set_var('authenticated', TRUE);
    } else {
        header('HTTP/1.1 401 Unauthorized');

        print_error_permission();
        die();
    }
}

/**
 * Check if the backend allows users to log out.
 *
 * @return bool TRUE if logout is possible, FALSE if it is not
 */
function remote_auth_can_logout()
{
    global $config;
    return isset($config['auth_remote_logout_url']);
}

/**
 * Returns the URL to lgoout.
 *
 * @return string logout url
 */
function remote_auth_logout_url()
{
    global $config;
    return isset($config['auth_remote_logout_url']) ? $config['auth_remote_logout_url'] : NULL;
}

/**
 * Check if the backend allows a specific user to change their password.
 * This is not possible using the remote backend.
 *
 * @param string $username Username to check
 *
 * @return bool TRUE if password change is possible, FALSE if it is not
 */
function remote_auth_can_change_password($username = "")
{
    return 0;
}

/**
 * Changes a user's password.
 * This is not possible using the remote backend.
 *
 * @param string $username Username to modify the password for
 * @param string $password New password
 *
 * @return bool TRUE if password change is successful, FALSE if it is not
 */
function remote_auth_change_password($username, $newpassword)
{
    # Not supported
    return FALSE;
}

/**
 * Check if the backend allows user management at all (create/delete/modify users).
 * This is not possible using the remote backend.
 *
 * @return bool TRUE if user management is possible, FALSE if it is not
 */
function remote_auth_usermanagement()
{
    return 0;
}

/**
 * Check if a user, specified by username, exists in the user backend.
 * This is not possible using the remote backend.
 *
 * @param string $username Username to check
 *
 * @return bool TRUE if the user exists, FALSE if they do not
 */
function remote_auth_user_exists($username)
{
    return FALSE;
}

/**
 * Retrieve user auth level for specified user.
 *
 * @param string $username Username to retrieve the auth level for
 *
 * @return int User's auth level
 */
function remote_auth_user_level($username)
{
    global $config;

    return isset($config['auth_remote_userlevel']) ? $config['auth_remote_userlevel'] : 1;
}

/**
 * Retrieve user id for specified user.
 * Returns a hash of the username.
 *
 * @param string $username Username to retrieve the ID for
 *
 * @return int User's ID
 */
function remote_auth_user_id($username)
{
    //return -1;
    return string_to_id('remote|' . $username);
}

/**
 * Deletes a user from the user database.
 * This is not possible using the remote backend.
 *
 * @param string $username Username to delete
 *
 * @return bool TRUE if user deletion is successful, FALSE if it is not
 */
function remote_deluser($username)
{
    // Not supported
    return FALSE;
}

/**
 * Retrieve list of users with all details.
 * This is not possible using the remote backend.
 *
 * @return array Rows of user data
 */
function remote_auth_user_list()
{
    return [];
}

// EOF
