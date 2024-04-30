<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage authentication
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Load common LDAP functions
include_once($config['html_dir'] . '/includes/ldap-functions.inc.php');

// Warn if authentication will be impossible.
check_extension_exists('ldap', 'LDAP selected as authentication module, but PHP does not have LDAP support! Please load the PHP LDAP module.', TRUE);

// Set LDAP debugging level to 7 (dumped to Apache daemon error log) (not virtualhost error log!)
if (defined('OBS_DEBUG') && OBS_DEBUG > 1) { // Currently, OBS_DEBUG > 1 for WUI is not supported ;)
    // Disabled by default, VERY chatty.
    ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
}

// If a single server is specified, convert it to array anyway for use in the functions below
if (!is_array($config['auth_ldap_server'])) {
    // If no server set and domain is specified, get domain controllers from SRV records
    if (empty($config['auth_ldap_server']) && !safe_empty($config['auth_ldap_ad_domain'])) {
        $config['auth_ldap_server'] = ldap_domain_servers_from_dns($config['auth_ldap_ad_domain']);
    } else {
        $config['auth_ldap_server'] = [ $config['auth_ldap_server'] ];
    }
}

/**
 * Finds if user belongs to group, recursively if requested
 * Private function for this LDAP module only.
 *
 * @param string $ldap_group LDAP group to check
 * @param string $userdn     User Distinguished Name
 * @param int    $depth      Recursion depth (used in recursion, stops at configured maximum depth)
 *
 * @return boolean
 */
function ldap_search_user($ldap_group, $userdn, $depth = -1)
{
    global $ds, $config;

    if ($config['auth_ldap_groupreverse']) {
        $compare = ldap_internal_compare($ds, $userdn, $config['auth_ldap_attr']['memberOf'], $ldap_group);
    } else {
        $compare = ldap_internal_compare($ds, $ldap_group, $config['auth_ldap_groupmemberattr'], $userdn);
    }

    if ($compare === TRUE) {
        return TRUE; // Member found, return TRUE
    }

    if (!$config['auth_ldap_groupreverse'] && $config['auth_ldap_recursive'] && ($depth < $config['auth_ldap_recursive_maxdepth'])) {
        $depth++;

        //$filter = "(&(objectClass=group)(memberOf=". $ldap_group ."))";
        $filter_params   = [];
        $filter_params[] = ldap_filter_create('objectClass', $config['auth_ldap_attr']['group']);
        $filter_params[] = ldap_filter_create($config['auth_ldap_attr']['memberOf'], $ldap_group);
        $filter          = ldap_filter_combine($filter_params);

        print_debug("LDAP[UserSearch][$depth][Comparing: " . $ldap_group . "][" . $config['auth_ldap_groupmemberattr'] . "=$userdn][Filter: $filter]");

        $ldap_search = ldap_search($ds, trim($config['auth_ldap_groupbase'], ', '), $filter, [$config['auth_ldap_attr']['dn']]);
        //r($filter);
        if (ldap_internal_is_valid($ldap_search)) {
            $ldap_results = ldap_get_entries($ds, $ldap_search);

            //r($ldap_results);
            array_shift($ldap_results); // Chop off "count" array entry

            foreach ($ldap_results as $element) {
                if (!isset($element[$config['auth_ldap_attr']['dn']])) {
                    continue;
                }

                // Not sure, seems as different results in LDAP vs AD
                // See: https://jira.observium.org/browse/OBS-3240 and https://jira.observium.org/browse/OBS-3310
                $element_dn = is_array($element[$config['auth_ldap_attr']['dn']]) ? $element[$config['auth_ldap_attr']['dn']][0] : $element[$config['auth_ldap_attr']['dn']];

                print_debug("LDAP[UserSearch][$depth][Comparing: " . $element_dn . "][" . $config['auth_ldap_groupmemberattr'] . "=$userdn]");

                $result = ldap_search_user($element_dn, $userdn, $depth);
                if ($result === TRUE) {
                    return TRUE; // Member found, return TRUE
                }
            }
        }

        return FALSE; // Not found, return FALSE.
    }

    return FALSE; // Recursion disabled or reached maximum depth, return FALSE.
}

/**
 * Initializes the LDAP connection to the specified server(s). Cycles through all servers, throws error when no server can be reached.
 * Private function for this LDAP module only.
 */
function ldap_init() {
    global $ds, $config;

    if ($ldap_valid = ldap_internal_is_valid($ds)) {
        // Already initiated
        return TRUE;
    }

    print_debug('LDAP[Connecting to ' . implode(' ', $config['auth_ldap_server']) . ']');
    foreach ((array)$config['auth_ldap_server'] as $ldap_server) {
        if ($config['auth_ldap_port'] === 636) {
            print_debug('LDAP[Port 636. Prepending ldaps:// to server URI]');
            $ds = @ldap_connect(preg_replace('/^(ldaps?:\/\/)?/', 'ldaps://', $ldap_server), $config['auth_ldap_port']);
        } else {
            $ds = @ldap_connect($ldap_server, $config['auth_ldap_port']);
        }
        if ($ldap_valid = ldap_internal_is_valid($ds)) { break; }
    }

    if ($ldap_valid) {
        print_debug("LDAP[Connected]");

        ldap_options();

        // how validate starttls:
        // openssl s_client -connect <server>:389 -starttls ldap -showcerts
        if ($config['auth_ldap_starttls'] &&
            (in_array($config['auth_ldap_starttls'], [ 'optional', 'require', '1', 1, TRUE ], TRUE))) {

            $tls = ldap_start_tls($ds);
            //bdump($tls);
            if ($config['auth_ldap_starttls'] === 'require' && !$tls) {
                session_logout();
                print_error("Fatal error: LDAP TLS required but not successfully negotiated. " . ldap_internal_error($ds));
                exit;
            }
            if (!$tls) {
                print_debug("LDAP[StartTLS][TLS not successfully negotiated]");
            }
        }
    } else {
        // FIXME. I not sure reasons with multiple ldap servers..
        //session_logout();
        print_error("Error: LDAP not connected. " . ldap_internal_error($ds));
        //exit;
    }

    return $ldap_valid;
}

function ldap_options() {
    global $ds, $config;

    // for debugging
    if ($config['debug']) {
        ldap_set_option($ds, LDAP_OPT_DEBUG_LEVEL, 9);
        print_debug("LDAP[Debug][Enabled]");
    }

    if ($config['auth_ldap_referrals']) {
        ldap_set_option($ds, LDAP_OPT_REFERRALS, $config['auth_ldap_referrals']);
        print_debug("LDAP[Referrals][Set to " . $config['auth_ldap_referrals'] . "]");
    } else {
        ldap_set_option($ds, LDAP_OPT_REFERRALS, FALSE);
        print_debug("LDAP[Referrals][Disabled]");
    }

    if ($config['auth_ldap_version']) {
        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $config['auth_ldap_version']);
        print_debug("LDAP[Version][Set to " . $config['auth_ldap_version'] . "]");
    }

    //bdump($ds);
    // how validate starttls:
    // openssl s_client -connect <server>:389 -starttls ldap -showcerts
    if ($config['auth_ldap_starttls'] &&
        (in_array($config['auth_ldap_starttls'], [ 'optional', 'require', '1', 1, TRUE ], TRUE))) {

        /*
        Possible values:
        LDAP_OPT_X_TLS_NEVER
          This is the default. slapd will not ask the client for a certificate.
        LDAP_OPT_X_TLS_ALLOW
          The  client certificate is requested. If no certificate is provided,
          the session proceeds normally. If a bad certificate is provided, it
          will be ignored and the session proceeds normally.
        LDAP_OPT_X_TLS_TRY
          The client certificate is requested. If no certificate is provided, the
          session proceeds normally. If a bad certificate is provided, the session
          is immediately terminated.
        LDAP_OPT_X_TLS_DEMAND
        LDAP_OPT_X_TLS_HARD
          These keywords are all equivalent, for compatibility reasons. The client
          certificate is requested. If no certificate is provided, or a bad
          certificate is provided, the session is immediately terminated.

          Note that a valid client certificate is required in order to use the SASL
          EXTERNAL authentication mechanism with a TLS session. As such, a non-default
          TLSVerifyClient setting must be chosen to enable SASL EXTERNAL authentication.
        More on
         * https://linux.die.net/man/3/ldap_set_option
         * http://www.openldap.org/lists/openldap-software/200202/msg00456.html
         */
        switch ($config['auth_ldap_tls_require_cert']) {
            case 'never':
                ldap_set_option($ds, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                break;
            case 'allow':
                ldap_set_option($ds, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_ALLOW);
                break;
            case 'try':
                ldap_set_option($ds, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_TRY);
                break;
            case 'demand':
                ldap_set_option($ds, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND);
                break;
            case 'hard':
                ldap_set_option($ds, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_HARD);
                break;
        }
        if (ldap_get_option($ds, LDAP_OPT_X_TLS_REQUIRE_CERT, $tls_require_cert)) {
            switch ($tls_require_cert) {
                case LDAP_OPT_X_TLS_NEVER:
                    $tls_require_cert = 'never';
                    break;
                case LDAP_OPT_X_TLS_ALLOW:
                    $tls_require_cert = 'allow';
                    break;
                case LDAP_OPT_X_TLS_TRY:
                    $tls_require_cert = 'try';
                    break;
                case LDAP_OPT_X_TLS_DEMAND:
                    $tls_require_cert = 'demand';
                    break;
                case LDAP_OPT_X_TLS_HARD:
                    $tls_require_cert = 'hard';
                    break;
            }
            print_debug("LDAP[StartTLS][Certificate checking strategy: $tls_require_cert]");
        }

    }
}

/**
 * Check username and password against LDAP authentication backend.
 * Cut short if remote_user setting is on, as we assume the user has already authed against Apache.
 * We still need to check for certain group memberships however, so we can not simply bail out with TRUE in such case.
 *
 * @param string $username User name to check
 * @param string $password User password to check
 *
 * @return int Authentication success (0 = fail, 1 = success) FIXME bool
 */
function ldap_authenticate($username, $password)
{
    global $config, $ds;

    ldap_init();
    if ($username && ldap_internal_is_valid($ds)) {
        if (!ldap_bind_dn($username, $password)) {
            return 0;
        }

        $binduser = ldap_internal_dn_from_username($username);

        if ($binduser) {
            print_debug("LDAP[Authenticate][User: $username][Bind user: $binduser]");

            // Auth via Apache + LDAP fallback -> automatically authenticated, fall through to group permission check
            if ($config['auth']['remote_user'] || ldap_bind($ds, $binduser, $password)) {
                if (!$config['auth_ldap_group']) {
                    // No groups defined, auth is sufficient
                    return 1;
                }
                $userdn = ($config['auth_ldap_groupmembertype'] === 'fulldn' ? $binduser : $username);

                foreach ($config['auth_ldap_group'] as $ldap_group) {
                    if ($config['auth_ldap_groupreverse']) {
                        print_debug("LDAP[Authenticate][Comparing: " . $userdn . "][" . $config['auth_ldap_attr']['memberOf'] . "=$ldap_group]");
                    } else {
                        print_debug("LDAP[Authenticate][Comparing: " . $ldap_group . "][" . $config['auth_ldap_groupmemberattr'] . "=$userdn]");
                    }
                    $compare = ldap_search_user($ldap_group, $userdn);

                    if ($compare === -1) {
                        print_debug("LDAP[Authenticate][Compare LDAP error: " . ldap_error($ds) . "]");
                        continue;
                    }
                    if ($compare === FALSE) {
                        print_debug("LDAP[Authenticate][Processing group: $ldap_group][Not matched]");
                    } else {
                        // $compare === TRUE
                        print_debug("LDAP[Authenticate][Processing group: $ldap_group][Matched]");
                        return 1;
                    }
                }

                // Restore bind dn when used binddn or bindanonymous
                // https://jira.observium.org/browse/OBS-1976
                if (!$config['auth']['remote_user'] &&
                    ($config['auth_ldap_binddn'] || $config['auth_ldap_bindanonymous'])) {

                    unset($GLOBALS['cache']['ldap']['bind_result']);
                    ldap_bind_dn();
                }
            } else {
                print_debug(ldap_internal_error($ds));
            }
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
function ldap_auth_can_logout()
{
    return TRUE;
}

/**
 * Check if the backend allows a specific user to change their password.
 * This is not currently possible using the LDAP backend.
 *
 * @param string $username Username to check
 *
 * @return bool TRUE if password change is possible, FALSE if it is not
 */
function ldap_auth_can_change_password($username = "")
{
    return 0;
}

/**
 * Changes a user's password.
 * This is not currently possible using the LDAP backend.
 *
 * @param string $username Username to modify the password for
 * @param string $password New password
 *
 * @return bool TRUE if password change is successful, FALSE if it is not
 */
function ldap_auth_change_password($username, $newpassword)
{
    // Not supported
    return FALSE;
}

/**
 * Check if the backend allows user management at all (create/delete/modify users).
 * This is not currently possible using the LDAP backend.
 *
 * @return bool TRUE if user management is possible, FALSE if it is not
 */
function ldap_auth_usermanagement()
{
    return 0;
}

/**
 * Adds a new user to the user backend.
 * This is not currently possible using the LDAP backend.
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
function ldap_adduser($username, $password, $level, $email = "", $realname = "", $can_modify_passwd = '1')
{
    // Not supported
    return FALSE;
}

/**
 * Check if a user, specified by username, exists in the user backend.
 *
 * @param string $username Username to check
 *
 * @return bool TRUE if the user exists, FALSE if they do not
 */
function ldap_auth_user_exists($username)
{
    global $config, $ds;

    ldap_init();
    if (!ldap_bind_dn()) {
        // Will not work without bind user or anon bind
        return 0;
    }

    if (ldap_internal_dn_from_username($username)) {
        return 1;
    }

    return 0;
}

/**
 * Retrieve user auth level for specified user.
 *
 * @param string $username Username to retrieve the auth level for
 *
 * @return int User's auth level
 */
function ldap_auth_user_level($username)
{
    global $config, $ds, $cache;

    if (!isset($cache['ldap']['level'][$username])) {
        $userlevel = 0;

        ldap_init();
        ldap_bind_dn();

        // Find all defined groups $username is in
        $userdn = strtolower($config['auth_ldap_groupmembertype']) === 'fulldn' ? ldap_internal_dn_from_username($username) : $username;
        print_debug("LDAP[UserLevel][UserDN: $userdn]");

        // This used to be done with a filter, but AD seems to be really retarded with regards to escaping.
        //
        // Particularly:
        //   CN=Name\, User,OU=Team,OU=Region,OU=Employees,DC=corp,DC=example,DC=com
        // Has 2 methods of escaping, we automatically do the first:
        //   CN=Name\2C, User,OU=Team,OU=Region,OU=Employees,DC=corp,DC=example,DC=com
        // Yet the filter used here before only worked doing this:
        //   CN=Name\\, User,OU=Team,OU=Region,OU=Employees,DC=corp,DC=example,DC=com
        //
        // Yay for arbitrary escapes. Don't know how to handle; this is most likely (hopefully) AD specific.
        // So, we foreach our locally known groups instead.
        foreach ($config['auth_ldap_groups'] as $ldap_group => $ldap_group_info) {
            if (!str_contains($ldap_group, '=')) {
                print_debug("WARNING: You specified LDAP group '$ldap_group' without full DN syntax. Appending group base, this becomes 'CN=" . $ldap_group . ',' . $config['auth_ldap_groupbase'] . "'. If this is correct, you're in luck! If it's not, please check your configuration.");
                $ldap_group = 'CN=' . $ldap_group . ',' . $config['auth_ldap_groupbase'];
            }
            $compare = ldap_search_user($ldap_group, $userdn);

            if ($compare === -1) {
                print_debug("LDAP[UserLevel][Compare LDAP error][" . ldap_internal_error($ds) . "]");
                continue;
            }
            if ($compare === FALSE) {
                print_debug("LDAP[UserLevel][Processing group: $ldap_group][Not matched]");
            } else {
                // $compare === TRUE
                print_debug("LDAP[UserLevel][Processing group: $ldap_group][Level: " . $ldap_group_info['level'] . "]");
                if ($ldap_group_info['level'] > $userlevel) {
                    $userlevel = $ldap_group_info['level'];
                    print_debug("LDAP[UserLevel][Accepted group level as new highest level]");
                } else {
                    print_debug("LDAP[UserLevel][Ignoring group level as it's lower than what we have already]");
                }
            }
        }

        print_debug("LDAP[Userlevel][Final level: $userlevel]");

        $cache['ldap']['level'][$username] = $userlevel;
    }

    return $cache['ldap']['level'][$username];
}

/**
 * Retrieve user id for specified user.
 *
 * @param string $username Username to retrieve the ID for
 *
 * @return int User's ID
 */
function ldap_auth_user_id($username)
{
    global $config, $ds;

    $userid = -1;

    ldap_init();
    ldap_bind_dn();

    //$userdn = $config['auth_ldap_groupmembertype'] === 'fulldn' ? ldap_internal_dn_from_username($username) : $config['auth_ldap_prefix'] . $username . $config['auth_ldap_suffix'];

    //$filter = "(" . str_ireplace($config['auth_ldap_suffix'], '', $userdn) . ")";
    //$filter = "(&(objectClass=".$config['auth_ldap_objectclass'].")(".$config['auth_ldap_attr']['uid']."=" . $username . "))";
    $filter_params   = [];
    $filter_params[] = ldap_filter_create('objectClass', $config['auth_ldap_objectclass']);
    $filter_params[] = ldap_filter_create($config['auth_ldap_attr']['uid'], $username);
    $filter          = ldap_filter_combine($filter_params);

    print_debug("LDAP[Filter][$filter][" . trim($config['auth_ldap_suffix'], ', ') . "]");
    $search = ldap_search($ds, trim($config['auth_ldap_suffix'], ', '), $filter);
    //r($search);
    $entries = ldap_internal_is_valid($search) ? ldap_get_entries($ds, $search) : [];
    //r($entries);

    if ($entries['count']) {
        $userid = ldap_internal_auth_user_id($entries[0]);
        print_debug("LDAP[UserID][$userid]");
    } else {
        print_debug("LDAP[UserID][User not found through filter]");
    }

    return $userid;
}

/**
 * Deletes a user from the user database.
 * This is not currently possible using the LDAP backend.
 *
 * @param string $username Username to delete
 *
 * @return bool TRUE if user deletion is successful, FALSE if it is not
 */
function ldap_deluser($username)
{
    // Call into mysql database functions to make sure user is gone from the database for legacy setups
    mysql_deluser($username, 'ldap');

    // Not supported
    return FALSE;
}

/**
 * Find the user's username by specifying their user ID.
 *
 * @param int $user_id The user's ID to look up the username for
 *
 * @return string The user's user name, or FALSE if the user ID is not found
 */
function ldap_auth_username_by_id($user_id) {

    foreach (ldap_auth_user_list() as $user) {
        if ($user['user_id'] == $user_id) {
            return $user['username'];
        }
    }

    return ""; // FIXME FALSE!
}

/**
 * Get the user information by username
 *
 * @param string $username Username
 *
 * @return array|false The user's username, or FALSE if the user ID is not found
 */
function ldap_auth_user_info($username) {

    if (empty($username)) {
        return [];
    }

    foreach (ldap_auth_user_list($username) as $user) {
        if ($user['username'] == $username) {
            return $user;
        }
    }

    return [];
}

/**
 * Retrieve list of users with all details.
 *
 * @return array Rows of user data
 */
function ldap_auth_user_list($username = NULL) {
    global $config, $ds;

    // Use caching for reduce queries to LDAP
    if (isset($GLOBALS['cache']['ldap']['userlist'])) {
        if ((get_time() - $GLOBALS['cache']['ldap']['userlist']['unixtime']) <= 300) { // Cache valid for 5 min
            //print_message('cached');
            return $GLOBALS['cache']['ldap']['userlist']['entries'];
        }
        unset($GLOBALS['cache']['ldap']['userlist']);
    }

    ldap_init();
    ldap_bind_dn();

    //$filter = '(objectClass=' . $config['auth_ldap_objectclass'] . ')';
    $filter_params   = [];
    $filter_params[] = ldap_filter_create('objectClass', $config['auth_ldap_objectclass']);
    if (!empty($username)) {
        // Filter users by username
        $filter_params[] = ldap_filter_create($config['auth_ldap_attr']['uid'], $username);
    }

    $ldap_group_count = safe_count($config['auth_ldap_group']);
    if ($ldap_group_count === 1) {
        //$filter = '(&'.$filter.'(memberof='.$config['auth_ldap_group'][0].'))';
        $filter_params[] = ldap_filter_create($config['auth_ldap_attr']['memberOf'], $config['auth_ldap_group'][0]);
    } elseif ($ldap_group_count > 1) {
        $group_params = [];
        foreach ($config['auth_ldap_group'] as $group) {
            //$group_filter .= '(memberof='.$group.')';
            $group_params[] = ldap_filter_create($config['auth_ldap_attr']['memberOf'], $group);
        }

        $filter_params[] = ldap_filter_combine($group_params, '|');

        //$filter = '(&'.$filter.'(|'.$group_filter.'))';
    }
    $filter = ldap_filter_combine($filter_params);
    // Limit fetched attributes, for reduce network transfer size
    $attributes = [
      strtolower($config['auth_ldap_attr']['uid']),
      strtolower($config['auth_ldap_attr']['cn']),
      strtolower($config['auth_ldap_attr']['uidNumber']),
      'description',
      'mail',
      'dn',
    ];

    print_debug("LDAP[UserList][Filter][$filter][" . trim($config['auth_ldap_suffix'], ', ') . "]");

    $entries = ldap_paged_entries($filter, $attributes, trim($config['auth_ldap_suffix'], ', '));
    //print_vars($entries);
    ldap_internal_user_entries($entries, $userlist);
    unset($entries);

    $GLOBALS['cache']['ldap']['userlist'] = [ 'unixtime' => get_time(),
                                              'entries'  => $userlist ];
    return $userlist;
}

/**
 * Parse user entries in ldap_auth_user_list()
 *
 * @param array $entries  LDAP entries by ldap_get_entries()
 * @param array $userlist Users list
 */
function ldap_internal_user_entries($entries, &$userlist)
{
    global $config, $ds;

    if (!is_array($userlist)) {
        $userlist = [];
    }

    if ($entries['count']) {
        unset($entries['count']);
        //print_vars($entries);

        foreach ($entries as $i => $entry) {
            $username    = $entry[strtolower($config['auth_ldap_attr']['uid'])][0];
            $realname    = $entry[strtolower($config['auth_ldap_attr']['cn'])][0];
            $user_id     = ldap_internal_auth_user_id($entry);
            $email       = $entry['mail'][0];
            $description = $entry['description'][0];

            $userdn = (strtolower($config['auth_ldap_groupmembertype']) === 'fulldn' ? $entry['dn'] : $username);
            if ($config['auth_ldap_groupreverse']) {
                print_debug("LDAP[UserList][Compare: $userdn][" . $config['auth_ldap_attr']['memberOf'] . "][" . implode('|', (array)$config['auth_ldap_group']) . "]");
            } else {
                print_debug("LDAP[UserList][Compare: " . implode('|', (array)$config['auth_ldap_group']) . "][" . $config['auth_ldap_groupmemberattr'] . "][$userdn]");
            }

            //if (!is_numeric($user_id)) { print_vars($entry); continue; }

            $authorized = FALSE;
            foreach ($config['auth_ldap_group'] as $ldap_group) {

                $compare = ldap_search_user($ldap_group, $userdn);
                //print_warning("$username, $realname, ");
                //r($compare);

                if ($compare === -1) {
                    print_debug("LDAP[UserList][Compare LDAP error][" . ldap_internal_error($ds) . "]");
                    continue;
                }
                if ($compare === FALSE) {
                    print_debug("LDAP[UserList][Processing group: $ldap_group][Not matched]");
                } else {
                    // $compare === TRUE
                    print_debug("LDAP[UserList][Authorized: $userdn for group $ldap_group]");
                    $authorized = TRUE;
                    break;
                }
            }

            if (!isset($config['auth_ldap_group']) || $authorized) {
                $user_level = ldap_auth_user_level($username);
                $userlist[] = [ 'username' => $username, 'realname' => $realname, 'user_id' => $user_id, 'level' => $user_level, 'email' => $email, 'descr' => $description ];
            }
        }
        //print_vars($userlist);
    }
}

/**
 * Bind with either the configured bind DN, the user's configured DN, or anonymously, depending on config.
 * Private function for this LDAP module only.
 *
 * @param string $username Bind username (optional)
 * @param string $password Bind password (optional)
 *
 * @return bool TRUE if bind succeeded, FALSE if not
 */
function ldap_bind_dn($username = "", $password = "") {
    global $config, $ds, $cache;

    print_debug("LDAP[Bind DN called]");

    // Avoid binding multiple times on one resource, this upsets some LDAP servers.
    if (isset($cache['ldap']['bind_result'])) {
        return $cache['ldap']['bind_result'];
    }

    if ($config['auth_ldap_binddn']) {
        // Bind user/password
        print_debug("LDAP[Bind][" . $config['auth_ldap_binddn'] . "]");
        $bind = ldap_bind($ds, $config['auth_ldap_binddn'], $config['auth_ldap_bindpw']);
    } elseif ($config['auth_ldap_bindanonymous']) {
        // Try anonymous bind if configured to do so
        print_debug("LDAP[Bind][anonymous]");
        $bind = ldap_bind($ds);
    } else {
        // Session bind
        if (($username == '' || $password == '') && isset($_SESSION['user_encpass'])) {
            // Use session credentials
            print_debug("LDAP[Bind][session]");
            $username = $_SESSION['username'];
            $password = session_decrypt_password();
        }

        print_debug("LDAP[Bind][" . $config['auth_ldap_prefix'] . $username . $config['auth_ldap_suffix'] . "]");
        $bind = ldap_bind($ds, $config['auth_ldap_prefix'] . $username . $config['auth_ldap_suffix'], $password);
    }

    if ($bind) {
        $cache['ldap']['bind_result'] = 1;
        return TRUE;
    }

    $cache['ldap']['bind_result'] = 0;
    print_debug("LDAP[Bind error][LDAP server: " . implode(' ', $config['auth_ldap_server']) . '][' . ldap_internal_error($ds) . ']');
    session_logout();
    return FALSE;
}

/**
 * Find user's Distinguished Name based on their username.
 *
 * Private function for this LDAP module only.
 *
 * @param string $username Username to retrieve DN for
 *
 * @return string User's Distinguished Name
 */
function ldap_internal_dn_from_username($username) {
    global $config, $ds, $cache;

    //r(debug_backtrace());

    if (!isset($cache['ldap']['dn'][$username])) {
        ldap_init();
        //ldap_bind_dn();
        //$filter = "(" . $config['auth_ldap_attr']['uid'] . '=' . $username . ")";
        $filter_params[] = ldap_filter_create('objectClass', $config['auth_ldap_objectclass']);
        $filter_params[] = ldap_filter_create($config['auth_ldap_attr']['uid'], $username);
        $filter          = ldap_filter_combine($filter_params);
        print_debug("LDAP[Filter][$filter][" . trim($config['auth_ldap_suffix'], ', ') . "]");

        $search = ldap_search($ds, trim($config['auth_ldap_suffix'], ', '), $filter);

        //r($search);
        //r(ldap_get_entries($ds, $search));

        if (ldap_internal_is_valid($search)) {
            $entries = ldap_get_entries($ds, $search);

            if ($entries['count']) {
                [ $cache['ldap']['dn'][$username], ] = ldap_escape_filter_value($entries[0]['dn']);
            }
        } else {
            return '';
        }
    }

    return $cache['ldap']['dn'][$username];
}

/**
 * Calculate User's numeric ID from LDAP.
 * Fetches UID (through configured attribute) from the LDAP search result, with one caveat:
 * There is some special handling if uid attribute is objectSID; we grab the last numeric part
 * and hope it's unique. There is no other way to have a numeric ID from Active Directory - it is
 * highly recommended to use RFC2307 (unix attributes) in your AD forest, specifying a specific
 * POSIX-style "uid" for your users, so we can treat that as numeric user ID.
 *
 * Private function for this LDAP module only.
 *
 * @param object $result LDAP search result for the user
 *
 * @return int User ID.
 */
function ldap_internal_auth_user_id($result)
{
    global $config;

    // For AD, convert SID S-1-5-21-4113566099-323201010-15454308-1104 to 1104 as our numeric unique ID
    if ($config['auth_ldap_attr']['uidNumber'] === "objectSid") {
        $sid    = explode('-', ldap_bin_to_str_sid($result['objectsid'][0]));
        $userid = $sid[count($sid) - 1];
        print_debug("LDAP[UserID][Converted objectSid " . ldap_bin_to_str_sid($result['objectsid'][0]) . " to user ID " . $userid . "]");
    } else {
        $userid = $result[strtolower($config['auth_ldap_attr']['uidNumber'])][0];
        print_debug("LDAP[UserID][Attribute " . $config['auth_ldap_attr']['uidNumber'] . " yields user ID " . $userid . "]");
    }
    if (!is_numeric($userid)) // FIXME, do this configurable? $config['auth_ldap_uid_number_generate'] = TRUE|FALSE;
    {
        $userid = string_to_id('ldap|' . $result[strtolower($config['auth_ldap_attr']['uid'])][0]);
    }

    return $userid;
}

/**
 * Compare value of attribute found in entry specified with DN
 * Internal implementation with workaround for dumb services.
 *
 * @param $ds
 * @param $dn
 * @param $attribute
 * @param $value
 *
 * @return bool
 */
function ldap_internal_compare($ds, $dn, $attribute, $value)
{
    global $cache;

    // Return cached
    $cache_key = $dn . ',' . $attribute . '=' . $value;
    if (isset($cache['ldap']['compare'][$cache_key])) {
        return $cache['ldap']['compare'][$cache_key];
    }

    $compare = ldap_compare($ds, $dn, $attribute, $value);
    //$compare = -1;

    // On error, try compare by get entries for some dumb services
    // https://jira.observium.org/browse/OBS-3611
    if ($compare === -1) {
        $filter_params = [ldap_filter_create($attribute, $value)];
        $filter        = ldap_filter_combine($filter_params);

        if ($read = ldap_read($ds, $dn, $filter, ['dn', 'count'], 1)) {
            $entry = ldap_get_entries($ds, $read);
            //print_vars($filter);
            //print_vars($dn);
            //print_vars($entry);
            $compare = (int)$entry['count'] === 1;
        }
    }

    // Cache
    $cache['ldap']['compare'][$cache_key] = $compare;

    return $compare;
}

// EOF
