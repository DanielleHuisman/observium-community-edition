<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage authentication
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Warn if authentication will be impossible.
check_extension_exists('ldap', 'LDAP selected as authentication module, but PHP does not have LDAP support! Please load the PHP LDAP module.', TRUE);

// Set LDAP debugging level to 7 (dumped to Apache daemon error log) (not virtualhost error log!)
if (defined('OBS_DEBUG') && OBS_DEBUG > 1) { // Currently, OBS_DEBUG > 1 for WUI is not supported ;)
    // Disabled by default, VERY chatty.
    ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
}

// If a single server is specified, convert it to array anyway for use in functions below
if (!is_array($config['auth_ldap_server'])) {
    // If no server set and domain is specified, get domain controllers from SRV records
    if (empty($config['auth_ldap_server']) && !safe_empty($config['auth_ldap_ad_domain'])) {
        $config['auth_ldap_server'] = ldap_domain_servers_from_dns($config['auth_ldap_ad_domain']);
    } else {
        $config['auth_ldap_server'] = [$config['auth_ldap_server']];
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
function ldap_init()
{
    global $ds, $config;

    if (!ldap_internal_is_valid($ds)) {
        print_debug('LDAP[Connecting to ' . implode(' ', $config['auth_ldap_server']) . ']');
        if ($config['auth_ldap_port'] === 636) {
            print_debug('LDAP[Port 636. Prepending ldaps:// to server URI]');
            $ds = @ldap_connect(implode(' ', preg_filter('/^(ldaps:\/\/)?/', 'ldaps://', $config['auth_ldap_server'])), $config['auth_ldap_port']);
        } else {
            $ds = @ldap_connect(implode(' ', $config['auth_ldap_server']), $config['auth_ldap_port']);
        }
        print_debug("LDAP[Connected]");

        if ($config['auth_ldap_starttls'] &&
            (in_array($config['auth_ldap_starttls'], ['optional', 'require', '1', 1, TRUE], TRUE))) {
            $tls = ldap_start_tls($ds);
            if ($config['auth_ldap_starttls'] === 'require' && !$tls) {
                session_logout();
                print_error("Fatal error: LDAP TLS required but not successfully negotiated. " . ldap_internal_error($ds));
                exit;
            }
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
    if ($username && $ds) {
        if (ldap_bind_dn($username, $password)) {
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
    // Not supported (for now?)
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
    if (ldap_bind_dn()) {
        return 0;
    } // Will not work without bind user or anon bind

    $binduser = ldap_internal_dn_from_username($username);

    if ($binduser) {
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
function ldap_auth_username_by_id($user_id)
{
    $userlist = ldap_auth_user_list();
    foreach ($userlist as $user) {
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
 * @return string The user's user name, or FALSE if the user ID is not found
 */
function ldap_auth_user_info($username)
{
    $userinfo = [];
    if (empty($username)) {
        return $userinfo;
    }

    $userlist = ldap_auth_user_list($username);
    foreach ($userlist as $user) {
        if ($user['username'] == $username) {
            $userinfo = $user;
            break;
        }
    }

    return $userinfo;
}

/**
 * Retrieve list of users with all details.
 *
 * @return array Rows of user data
 */
function ldap_auth_user_list($username = NULL)
{
    global $config, $ds;

    // Use caching for reduce queries to LDAP
    if (isset($GLOBALS['cache']['ldap']['userlist'])) {
        if (($config['time']['now'] - $GLOBALS['cache']['ldap']['userlist']['unixtime']) <= 300) { // Cache valid for 5 min
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

    $entries = ldap_internal_paged_entries($filter, $attributes);
    //print_vars($entries);
    ldap_internal_user_entries($entries, $userlist);
    unset($entries);

    $GLOBALS['cache']['ldap']['userlist'] = ['unixtime' => $config['time']['now'],
                                             'entries'  => $userlist];
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
                $userlist[] = ['username' => $username, 'realname' => $realname, 'user_id' => $user_id, 'level' => $user_level, 'email' => $email, 'descr' => $description];
            }
        }
        //print_vars($userlist);
    }
}

function ldap_internal_paged_entries($filter, $attributes)
{
    global $config, $ds;

    $entries = [];

    if ($config['auth_ldap_version'] >= 3 && PHP_VERSION_ID >= 70300) {
        // Use pagination for speedup fetch huge lists, there is new style, see:
        // https://www.php.net/manual/en/ldap.examples-controls.php (Example #5)
        $page_size = 200;
        $cookie    = '';

        do {
            $search = ldap_search(
              $ds, trim($config['auth_ldap_suffix'], ', '), $filter, $attributes, 0, 0, 0, LDAP_DEREF_NEVER,
              [['oid' => LDAP_CONTROL_PAGEDRESULTS, 'value' => ['size' => $page_size, 'cookie' => $cookie]]]
            );
            if (ldap_internal_is_valid($search)) {
                ldap_parse_result($ds, $search, $errcode, $matcheddn, $errmsg, $referrals, $controls);
                print_debug(ldap_internal_error($ds));
                $entries[] = ldap_get_entries($ds, $search);

                if (isset($controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
                    // You need to pass the cookie from the last call to the next one
                    $cookie = $controls[LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'];
                } else {
                    $cookie = '';
                }
            } else {
                $cookie = '';
            }
            // Empty cookie means last page
        } while (!empty($cookie));
        $entries = array_merge([], ...$entries);

    } elseif ($config['auth_ldap_version'] >= 3 && function_exists('ldap_control_paged_result')) {
        // Use pagination for speedup fetch huge lists, pre 7.3 style
        $page_size = 200;
        $cookie    = '';
        do {
            // WARNING, do not make any ldap queries between ldap_control_paged_result() and ldap_control_paged_result_response()!!
            //          this produce loop and errors in queries
            $page_test = ldap_control_paged_result($ds, $page_size, TRUE, $cookie);
            //print_vars($page_test);
            print_debug(ldap_internal_error($ds));

            $search = ldap_search($ds, trim($config['auth_ldap_suffix'], ', '), $filter, $attributes);
            print_debug(ldap_internal_error($ds));
            if (ldap_internal_is_valid($search)) {
                $entries[] = ldap_get_entries($ds, $search);
                //print_vars($filter);
                //print_vars($search);

                //ldap_internal_user_entries($entries, $userlist);

                ldap_control_paged_result_response($ds, $search, $cookie);
            } else {
                $cookie = '';
            }

        } while ($page_test && $cookie !== NULL && $cookie != '');
        $entries = array_merge([], ...$entries);
        // Reset LDAP paged result
        ldap_control_paged_result($ds, 1000);

    } else {
        // Old php < 5.4, trouble with limit 1000 entries, see:
        // http://stackoverflow.com/questions/24990243/ldap-search-not-returning-more-than-1000-user

        $search = ldap_search($ds, trim($config['auth_ldap_suffix'], ', '), $filter, $attributes);
        print_debug(ldap_internal_error($ds));

        if (ldap_internal_is_valid($search)) {
            $entries = ldap_get_entries($ds, $search);
            //print_vars($filter);
            //print_vars($search);

            //ldap_internal_user_entries($entries, $userlist);
        }
    }

    return $entries;
}

/**
 * Returns the textual SID for Active Directory
 * Private function for this LDAP module only
 *
 * Source: http://stackoverflow.com/questions/13130291/how-to-query-ldap-adfs-by-objectsid-in-php-or-any-language-really
 *
 * @param string Binary SID
 *
 * @return string Textual SID
 */
function ldap_bin_to_str_sid($binsid)
{
    $hex_sid  = bin2hex($binsid);
    $rev      = hexdec(substr($hex_sid, 0, 2));
    $subcount = hexdec(substr($hex_sid, 2, 2));
    $auth     = hexdec(substr($hex_sid, 4, 12));
    $result   = "$rev-$auth";

    for ($x = 0; $x < $subcount; $x++) {
        $subauth[$x] = hexdec(ldap_little_endian(substr($hex_sid, 16 + ($x * 8), 8)));
        $result      .= "-" . $subauth[$x];
    }

    // Cheat by tacking on the S-
    return 'S-' . $result;
}

/**
 * Convert a little-endian hex-number to one that 'hexdec' can convert.
 * Private function for this LDAP module only.
 *
 * Source: http://stackoverflow.com/questions/13130291/how-to-query-ldap-adfs-by-objectsid-in-php-or-any-language-really
 *
 * @param string $hex Hexadecimal number
 *
 * @return string Converted hexadecimal number
 */
function ldap_little_endian($hex)
{
    $result = '';
    for ($x = strlen($hex) - 2; $x >= 0; $x = $x - 2) {
        $result .= substr($hex, $x, 2);
    }

    return $result;
}

/**
 * Bind with either the configured bind DN, the user's configured DN, or anonymously, depending on config.
 * Private function for this LDAP module only.
 *
 * @param string $username Bind username (optional)
 * @param string $password Bind password (optional)
 *
 * @return bool FALSE if bind succeeded, TRUE if not
 */
function ldap_bind_dn($username = "", $password = "")
{
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
            if (!isset($_SESSION['encrypt_required'])) {
                $key = session_unique_id();
                if (OBS_ENCRYPT_MODULE === 'mcrypt') {
                    $key .= get_unique_id();
                }
                $password = decrypt($_SESSION['user_encpass'], $key);
            } else {
                // WARNING, requires mcrypt or sodium
                $password = base64_decode($_SESSION['user_encpass'], TRUE);
            }
        }

        print_debug("LDAP[Bind][" . $config['auth_ldap_prefix'] . $username . $config['auth_ldap_suffix'] . "]");
        $bind = ldap_bind($ds, $config['auth_ldap_prefix'] . $username . $config['auth_ldap_suffix'], $password);
    }

    if ($bind) {
        $cache['ldap']['bind_result'] = 0;
        return FALSE;
    }

    $cache['ldap']['bind_result'] = 1;
    print_debug("LDAP[Bind error][LDAP server: " . implode(' ', $config['auth_ldap_server']) . '][' . ldap_internal_error($ds) . ']');
    session_logout();
    return TRUE;
}

/**
 * Find user's Distinguished Name based on their username.
 *
 * Private function for this LDAP module only.
 *
 * @param string Username to retrieve DN for
 *
 * @return string User's Distinguished Name
 */
function ldap_internal_dn_from_username($username)
{

    //r(debug_backtrace());

    global $config, $ds, $cache;

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
                [$cache['ldap']['dn'][$username],] = ldap_escape_filter_value($entries[0]['dn']);
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
 * @param object LDAP search result for the user
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

function ldap_internal_error($ds) {
    $error_no = ldap_errno($ds);
    return ldap_error($ds) . ' (' . $error_no . ': ' . ldap_err2str($error_no) . ")";
}

/**
 * Retrieves list of domain controllers from DNS through SRV records.
 * Private function for this LDAP module only.
 *
 * @param string Domain name (fqdn-style) for the AD domain.
 *
 * @return array Array of server names to be used for LDAP.
 */
function ldap_domain_servers_from_dns($domain)
{
    global $config;

    $servers = [];

    $resolver = new Net_DNS2_Resolver();

    $response = $resolver -> query("_ldap._tcp.dc._msdcs.$domain", 'SRV', 'IN');
    if ($response) {
        foreach ($response -> answer as $answer) {
            $servers[] = $answer -> target;
        }
    }

    return $servers;
}

/**
 * Constructor of a new part of a LDAP filter.
 *
 * Example:
 *  ldap_filter_create('memberOf', 'name', '=') >>> '(memberOf=name)'
 *
 * @param string  $param     Name of the attribute the filter should apply to
 * @param string  $value     Filter value
 * @param string  $condition Matching rule
 * @param boolean $escape    Should $value be escaped? (default: yes)
 *
 * @return string Generated filter
 */
function ldap_filter_create($param, $value, $condition = '=', $escape = TRUE)
{
    if ($escape) {
        $value = ldap_escape_filter_value($value);
        $value = array_shift($value);
    }

    // Convert common rule name to ldap rule
    // Default rule is equals
    $condition = strtolower(trim($condition));
    switch ($condition) {
        case 'ge':
        case '>=':
            $filter = '(' . $param . '>=' . $value . ')';
            break;
        case 'le':
        case '<=':
            $filter = '(' . $param . '<=' . $value . ')';
            break;
        case 'gt':
        case 'greater':
        case '>':
            $filter = '(' . $param . '>' . $value . ')';
            break;
        case 'lt':
        case 'less':
        case '<':
            $filter = '(' . $param . '<' . $value . ')';
            break;
        case 'match':
        case 'matches':
        case '~=':
            $filter = '(' . $param . '~=' . $value . ')';
            break;
        case 'notmatches':
        case 'notmatch':
        case '!match':
        case '!~=':
            $filter = '(!(' . $param . '~=' . $value . '))';
            break;
        case 'notequals':
        case 'isnot':
        case 'ne':
        case '!=':
        case '!':
            $filter = '(!(' . $param . '=' . $value . '))';
            break;
        case 'equals':
        case 'eq':
        case 'is':
        case '==':
        case '=':
        default:
            $filter = '(' . $param . '=' . $value . ')';
    }

    return $filter;
}

/**
 * Combine two or more filter objects using a logical operator
 *
 * @param array  $values    Array with Filter entries generated by ldap_filter_create()
 * @param string $condition The logical operator. May be "and", "or", "not" or the subsequent logical equivalents "&", "|", "!"
 *
 * @return string Generated filter
 */
function ldap_filter_combine($values = [], $condition = '&')
{
    $count = safe_count($values);
    if (!$count) {
        return '';
    }

    $condition = strtolower(trim($condition));
    switch ($condition) {
        case '!':
        case 'not':
            $filter = '(!' . implode('', $values) . ')';
            break;
        case '|':
        case 'or':
            if ($count === 1) {
                $filter = array_shift($values);
            } else {
                $filter = '(|' . implode('', $values) . ')';
            }
            break;
        case '&':
        case 'and':
        default:
            if ($count === 1) {
                $filter = array_shift($values);
            } else {
                $filter = '(&' . implode('', $values) . ')';
            }
    }

    return $filter;
}

/**
 * Escapes the given VALUES according to RFC 2254 so that they can be safely used in LDAP filters.
 *
 * Any control characters with an ACII code < 32 as well as the characters with special meaning in
 * LDAP filters "*", "(", ")", and "\" (the backslash) are converted into the representation of a
 * backslash followed by two hex digits representing the hexadecimal value of the character.
 *
 * @param array|string $values Array of values to escape
 *
 * @return array Array $values, but escaped
 */
function ldap_escape_filter_value($values = [])
{
    // Parameter validation
    if (!is_array($values)) {
        $values = [$values];
    }

    foreach ($values as $key => $val) {
        // Escaping of filter meta characters
        $val = str_replace(['\\', '\5c,', '*', '(', ')'],
                           ['\5c', '\2c', '\2a', '\28', '\29'], $val);

        // ASCII < 32 escaping
        $val = asc2hex32($val);

        if (NULL === $val) {
            $val = '\0';
        }  // apply escaped "null" if string is empty

        $values[$key] = $val;
    }

    return $values;
}

/**
 * Undoes the conversion done by {@link ldap_escape_filter_value()}.
 *
 * Converts any sequences of a backslash followed by two hex digits into the corresponding character.
 *
 * @param array $values Array of values to escape
 *
 * @return array Array $values, but unescaped
 */
function ldap_unescape_filter_value($values = [])
{
    // Parameter validation
    if (!is_array($values)) {
        $values = [$values];
    }

    foreach ($values as $key => $value) {
        // Translate hex code into ascii
        $values[$key] = hex2asc($value);
    }

    return $values;
}

function ldap_internal_is_valid($obj)
{
    if (PHP_VERSION_ID >= 80100) {
        // ldap_bind() returns an LDAP\Connection instance in 8.1; previously, a resource was returned
        // ldap_search() returns an LDAP\Result instance in 8.1; previously, a resource was returned.
        return is_object($obj);
    }

    return is_resource($obj);
}

/**
 * Converts all ASCII chars < 32 to "\HEX"
 *
 * @param string $string String to convert
 *
 * @return string
 */
function asc2hex32($string)
{
    for ($i = 0, $max = strlen($string); $i < $max; $i++) {
        $char = $string[$i];
        if (ord($char) < 32) {
            $hex = dechex(ord($char));
            if (strlen($hex) === 1) {
                $hex = '0' . $hex;
            }
            $string = str_replace($char, '\\' . $hex, $string);
        }
    }
    return $string;
}

/**
 * Converts all Hex expressions ("\HEX") to their original ASCII characters
 *
 * @param string $string String to convert
 *
 * @return string
 * @author beni@php.net, heavily based on work from DavidSmith@byu.net
 */
function hex2asc($string)
{
    return preg_replace_callback("/\\\([0-9A-Fa-f]{2})/", function ($matches) {
        foreach ($matches as $match) {
            return chr(hexdec($match));
        }
    },                           $string);
}

// EOF
