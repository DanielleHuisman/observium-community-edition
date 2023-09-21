<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     authentication
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/**
 * Check username and password against MySQL authentication backend.
 * Cut short if remote_user setting is on, as we assume the user has already authed against Apache.
 *
 * @param string $username User name to check
 * @param string $password User password to check
 *
 * @return int Authentication success (0 = fail, 1 = success) FIXME bool
 */
function mysql_authenticate($username, $password)
{
    global $config;

    $row = dbFetchRow("SELECT `username`, `password` FROM `users` WHERE `username` = ? AND `type` = ?", [$username, 'mysql']);
    if ($row['username'] && $row['username'] == $username) {
        if ($config['auth']['remote_user']) {
            return 1;
        }

        if (str_starts($row['password'], '$1$')) {
            // Old MD5 hashes, need rehash/change passwords
            if ($row['password'] == crypt($password, $row['password'])) {
                // Rehash password
                mysql_auth_change_password($username, $password);
                return 1;
            }
        } elseif (password_verify($password, $row['password'])) {
            // New password hash verified
            if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                // Required password rehash
                //$hash = password_hash($password, PASSWORD_DEFAULT);
                mysql_auth_change_password($username, $password);
            }
            return 1;
        }
    }

    //session_logout();
    return 0;
}

/**
 * Check if the backend allows users to log out.
 * We don't check for Apache authentication (remote_user) as this is done already before calling into this function.
 *
 * @return bool TRUE if logout is possible, FALSE if it is not
 */
function mysql_auth_can_logout()
{
    return TRUE;
}

/**
 * Check if the backend allows a specific user to change their password.
 * Default is yes, unless the existing user is explicitly prohibited to do so.
 * Also, if user authed to Apache, we can't change his password.
 *
 * @param string $username Username to check
 *
 * @return bool TRUE if password change is possible, FALSE if it is not
 */
function mysql_auth_can_change_password($username = "")
{
    global $config;

    if ((empty($username) || !mysql_auth_user_exists($username)) && !$config['auth']['remote_user']) {
        return TRUE;
    }

    return dbFetchCell("SELECT `can_modify_passwd` FROM `users` WHERE `username` = ? AND `type` = ?", [$username, 'mysql']); // FIXME should return BOOL
}

/**
 * Changes a user's password.
 *
 * @param string $username Username to modify the password for
 * @param string $password New password
 *
 * @return bool TRUE if password change is successful, FALSE if it is not
 */
function mysql_auth_change_password($username, $password)
{
    if (get_db_version() < 414) {
        return 0;
    } // Do not update if DB schema old, new hashes require longer field

    // $hash = crypt($password, '$1$' . generate_random_string(8).'$'); // This is old hash, do not used anymore (keep for history)
    $hash = password_hash($password, PASSWORD_DEFAULT);
    return dbUpdate(['password' => $hash], 'users', '`username` = ? AND `type` = ?', [$username, 'mysql']); // FIXME should return BOOL
}

/**
 * Check if the backend allows user management at all (create/delete/modify users).
 *
 * @return bool TRUE if user management is possible, FALSE if it is not
 */
function mysql_auth_usermanagement()
{
    return TRUE;
}

/**
 * Adds a new user to the user backend.
 *
 * @param string $username          User's username
 * @param string $password          User's password (plain text)
 * @param int    $level             User's auth level
 * @param string $email             User's e-mail address
 * @param string $realname          User's real name
 * @param bool   $can_modify_passwd TRUE if user can modify their own password, FALSE if not
 * @param string $description       User's description
 *
 * @return bool TRUE if user addition is successful, FALSE if it is not
 */
function mysql_adduser($username, $password, $level, $email = "", $realname = "", $can_modify_passwd = '1', $description = "")
{
    if (!mysql_auth_user_exists($username)) {
        // $hash = crypt($password, '$1$' . generate_random_string(8).'$'); // This is old hash, do not used anymore (keep for history)
        $hash = password_hash($password, PASSWORD_DEFAULT);
        return dbInsert(['username'          => $username,
                         'password'          => $hash,
                         'level'             => $level,
                         'email'             => $email,
                         'realname'          => $realname,
                         'can_modify_passwd' => $can_modify_passwd,
                         'descr'             => $description], 'users');
    }

    return FALSE;
}

/**
 * Check if a user, specified by username, exists in the user backend.
 *
 * @param string $username Username to check
 *
 * @return bool TRUE if the user exists, FALSE if they do not
 */
function mysql_auth_user_exists($username)
{
    //return @dbFetchCell("SELECT COUNT(*) FROM `users` WHERE `username` = ?", array($username)); // FIXME should return BOOL
    return dbExist('users', '`username` = ? AND `type` = ?', [$username, 'mysql']);
}

/**
 * Find the user's username by specifying their user ID.
 *
 * @param int $user_id The user's ID to look up the username for
 *
 * @return string The user's user name, or FALSE if the user ID is not found
 */
function mysql_auth_username_by_id($user_id)
{
    return dbFetchCell("SELECT `username` FROM `users` WHERE `user_id` = ? AND `type` = ?", [$user_id, 'mysql']); // FIXME should return FALSE if not found
}

/**
 * Retrieve user auth level for specified user.
 *
 * @param string $username Username to retrieve the auth level for
 *
 * @return int User's auth level
 */
function mysql_auth_user_level($username)
{
    return dbFetchCell("SELECT `level` FROM `users` WHERE `username` = ? AND `type` = ?", [$username, 'mysql']);
}

/**
 * Retrieve user id for specified user.
 *
 * @param string $username Username to retrieve the ID for
 *
 * @return int User's ID
 */
function mysql_auth_user_id($username)
{
    return dbFetchCell("SELECT `user_id` FROM `users` WHERE `username` = ? AND `type` = ?", [$username, 'mysql']);
}

/**
 * Deletes a user from the user database.
 *
 * @param string $username Username to delete
 *
 * @return bool TRUE if user deletion is successful, FALSE if it is not
 */
function mysql_deluser($username, $type = 'mysql')
{
    $user_id = mysql_auth_user_id($username);

    dbDelete('entity_permissions', "`user_id` = ? AND `auth_mechanism` = ?", [$user_id, $GLOBALS['config']['auth_mechanism']]);
    dbDelete('roles_users', "`user_id` = ? AND `auth_mechanism` = ?", [$user_id, $GLOBALS['config']['auth_mechanism']]);
    dbDelete('users_prefs', "`user_id` = ?", [$user_id]);
    dbDelete('users_ckeys', "`username` = ?", [$username]);

    return dbDelete('users', "`username` = ? AND `type` = ?", [$username, $type]); // FIXME should return BOOL
}

/**
 * Retrieve list of users with all details.
 *
 * @return array Rows of user data
 */
function mysql_auth_user_list()
{
    return dbFetchRows("SELECT * FROM `users` WHERE `type` = ?", ['mysql']); // FIXME hardcode list of returned fields as in all other backends; array content should not depend on db changes/column names.
}

/**
 * Get the user information by username
 *
 * @param string $username Username
 *
 * @return string The user's user name, or FALSE if the user ID is not found
 */
function mysql_auth_user_info($username)
{
    return dbFetchRow("SELECT * FROM `users` WHERE `username` = ? AND `type` = ?", [$username, 'mysql']);
}

// EOF
