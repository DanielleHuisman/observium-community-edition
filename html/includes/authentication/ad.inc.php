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
check_extension_exists('ldap', 'AD selected as authentication module, but PHP does not have LDAP support! Please load the PHP LDAP module.', TRUE);

// Set LDAP debugging level to 7 (dumped to Apache daemon error log) (not virtualhost error log!)
if (OBS_DEBUG > 1) { // FIXME Currently OBS_DEBUG > 1 for WUI is not supported ;)
    // Disabled by default, VERY chatty.
    ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
}

// If a single server is specified, convert it to array anyway for use in functions below
if (!is_array($config['auth_ad_server'])) {
    // If no server set and AD domain is specified, get domain controllers from SRV records
    if ($config['auth_ad_server'] == '' && $config['auth_ad_domain'] != '') {
        $config['auth_ad_server'] = ldap_domain_servers_from_dns($config['auth_ad_domain']);
    } else {
        $config['auth_ad_server'] = array($config['auth_ad_server']);
    }
}

// If no access control groups are specified, use the defined level groups as access control
if (!is_array($config['auth_ad_group'])) {
    $config['auth_ad_group'] = array_keys($config['auth_ad_groups']);
}

// Synthesize Base DN from configured domain name, if it is not set
if (!isset($config['auth_ad_basedn'])) {
    if (isset($config['auth_ad_domain'])) {
        $config['auth_ad_basedn'] = ad_internal_basedn_from_domain($config['auth_ad_domain']);
        print_debug("Synthesized base DN " . $config['auth_ad_basedn'] . " from " . $config['auth_ad_domain']);
    } else {
        print_error("AD authentication selected, but AD domain AND BaseDN are not set. Authentication will fail.");
  }
}

// If we have "hard" TLS, don't also set STARTTLS, and mangle the server names for ldap_connect
if ($config['auth_ad_tls']) {
    unset($config['auth_ad_starttls']);

    // Add ldaps:// in front of every hostname
    $config['auth_ad_server'] = array_map(function($value) { return "ldaps://$value"; }, $config['auth_ad_server']);
}

// We need a bind DN, as we only get the user's password on initial login and not on successive page loads, meaning that
// we are not able to connect to AD anymore without a dedicated bind user.
if (!isset($config['auth_ad_binddn']) || !isset($config['auth_ad_bindpw'])) {
    print_error("AD authentication selected, but AD bind user is not correctly set (auth_ad_binddn and auth_ad_bindpwd). Authentication will fail.");
}

// TESTME
/**
 * Synthesize base DN from AD domain ('fqdn') name.
 * Private function for this auth module only.
 *
 * @param string $ad_domain AD domain fqdn
 *
 * @return string Base DN
 */
function ad_internal_basedn_from_domain($ad_domain) {
    // ad.observium.org -> DC=ad,DC=observium,DC=org
    // contoso.com -> DC=contoso,DC=com
    return 'DC=' . implode(',DC=', explode('.', $ad_domain));
}

/**
 * Finds if user belongs to group, recursively.
 * Private function for this auth module only.
 *
 * @param string $ldap_group LDAP group to check
 * @param string $userdn User Distinguished Name
 * @return bool TRUE when user is member of the group, FALSE if not
 */
function ad_internal_search_user($ldap_group, $userdn) {
    global $ds, $config;

    $filter_params   = [];
    $filter_params[] = ldap_filter_create('objectCategory', 'person');
    $filter_params[] = ldap_filter_create('objectClass', 'user');
    $filter_params[] = ldap_filter_create('distinguishedName', $userdn);
    $filter_params[] = ldap_filter_create('memberOf:1.2.840.113556.1.4.1941:', ad_internal_dn_from_groupname($ldap_group));
    $filter          = ldap_filter_combine($filter_params);

    print_debug("ad_internal_search_user: Searching for user [$userdn] Group membership: [$ldap_group] using filter: [$filter] on base " . $config['auth_ad_basedn']);

    $ldap_search = ldap_search($ds, $config['auth_ad_basedn'], $filter, [ 'distinguishedName' ]);
    $entries     = ldap_get_entries($ds, $ldap_search);

    return ($entries['count'] != 0);
}

/**
 * Initializes the LDAP connection to the specified server(s). Cycles through all servers, throws error when no server can be reached.
 * Private function for this auth module only.
 */
function ad_internal_init() {
    global $ds, $config;

    if ($ad_valid = ldap_internal_is_valid($ds)) {
        // Already initiated
        return TRUE;
    }

    print_debug('ad_internal_init: connecting to ' . implode(' ', $config['auth_ad_server']));

    if ($config['auth_ad_validatecert'] === FALSE) {
        // FIXME. Sync names with auth_ldap_tls_require_cert
        //ldap_set_option($ds, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
        putenv('LDAPTLS_REQCERT=never');
    }

    foreach ((array)$config['auth_ad_server'] as $ad_server) {
        $ds = @ldap_connect($ad_server, $config['auth_ad_port']);
        if ($ad_valid = ldap_internal_is_valid($ds)) { break; }
    }

    if ($ad_valid) {
        print_debug('ad_internal_init: Connected');

        ldap_set_option($ds, LDAP_OPT_REFERRALS, FALSE);
        print_debug('ad_internal_init: Referrals disabled');

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        print_debug('ad_internal_init: Version set to 3');

        if ($config['auth_ad_starttls'] &&
            ($config['auth_ad_starttls'] === 'optional' || $config['auth_ad_starttls'] === 'require')) {
            $tls = ldap_start_tls($ds);
            if ($config['auth_ad_starttls'] === 'require' && $tls === FALSE) {
                session_logout();
                print_error('Fatal error: AD TLS required but not successfully negotiated. ' . ldap_internal_error($ds));
                exit;
            }
        }
    }

    return $ad_valid;
}

/**
 * Check username and password against LDAP authentication backend.
 * Cut short if remote_user setting is on, as we assume the user has already authed against Apache.
 * We still need to check for certain group memberships however, so we can not simply bail out with TRUE in such case.
 *
 * @param string $username User name to check
 * @param string $password User password to check
 * @return int Authentication success (0 = fail, 1 = success) FIXME bool
 */
function ad_authenticate($username, $password) {
    global $config, $ds;

    ad_internal_init();
    if ($username && ldap_internal_is_valid($ds)) {
        // Bind using bindDN, fail early if this fails
        if (!ad_bind_dn()) {
            return 0;
        }

        // Find DN for username trying to log in
        $binduser = ad_internal_dn_from_username($username);

        // If user is found, try authenticating using their password
        if ($binduser) {
            print_debug("ad_authenticate: User: $username - LDAP bind user: $binduser");

            // Auth via Apache + LDAP fallback -> automatically authenticated, fall through to group permission check
            if ($config['auth']['remote_user'] || ldap_bind($ds, $binduser, $password)) {
                if (!$config['auth_ad_group']) {
                    // No groups defined, auth is sufficient
                    return 1;
                }

                foreach ($config['auth_ad_group'] as $ldap_group) {
                    print_debug("ad_authenticate: Searching $ldap_group for $binduser");
                    $compare = ad_internal_search_user($ldap_group, $binduser);

                    if ($compare === -1) {
                        print_debug("ad_authenticate: Compare LDAP error: " . ldap_internal_error($ds));
                        continue;
                    } elseif ($compare === FALSE) {
                        print_debug("ad_authenticate: Processing group: $ldap_group - Not matched");
                    } else {
                        // $compare === TRUE
                        print_debug("ad_authenticate: Processing group: $ldap_group - Matched");
                        return 1;
                    }
                }
            } else {
                print_debug(ldap_internal_error($ds));
            }
        }
    }

    return 0;
}

/**
 * Check if the backend allows users to log out.
 * We don't check for Apache authentication (remote_user) as this is done already before calling into this function.
 *
 * @return bool TRUE if logout is possible, FALSE if it is not
 */
function ad_auth_can_logout() {
    return TRUE;
}

/**
 * Check if the backend allows a specific user to change their password.
 * This is not possible using the AD backend.
 *
 * @param string $username Username to check
 * @return bool TRUE if password change is possible, FALSE if it is not
 */
function ad_auth_can_change_password($username = "") {
    // Not supported
    return FALSE;
}

/**
 * Changes a user's password.
 * This is not possible using the AD backend.
 *
 * @param string $username Username to modify the password for
 * @param string $password New password
 * @return bool TRUE if password change is successful, FALSE if it is not
 */
function ad_auth_change_password($username, $newpassword) {
    // Not supported
    return FALSE;
}

/**
 * Check if the backend allows user management at all (create/delete/modify users).
 * This is not possible using the AD backend.
 *
 * @return bool TRUE if user management is possible, FALSE if it is not
 */
function ad_auth_usermanagement() {
    // Not supported
    return FALSE;
}

/**
 * Adds a new user to the user backend.
 * This is not possible using the AD backend.
 *
 * @param string $username User's username
 * @param string $password User's password (plain text)
 * @param int $level User's auth level
 * @param string $email User's e-mail address
 * @param string $realname User's real name
 * @param bool $can_modify_passwd TRUE if user can modify their own password, FALSE if not
 * @param string $description User's description
 * @return bool TRUE if user addition is successful, FALSE if it is not
 */
function ad_adduser($username, $password, $level, $email = "", $realname = "", $can_modify_passwd = '1') {
    // Not supported
    return FALSE;
}

/**
 * Check if a user, specified by username, exists in the user backend.
 *
 * @param string $username Username to check
 * @return bool TRUE if the user exists, FALSE if they do not
 */
function ad_auth_user_exists($username) {
    global $config, $ds;

    ad_internal_init();
    if (!ad_bind_dn()) {
        // Will not work without bind user
        return 0;
    }

    // Find user's DN which will reveal if it exists or not
    if (ad_internal_dn_from_username($username)) {
        return TRUE;
    }

    return FALSE;
}

/**
 * Retrieve user auth level for specified user.
 *
 * @param string $username Username to retrieve the auth level for
 * @return int User's auth level
 */
function ad_auth_user_level($username) {
    global $config, $ds, $cache;

    if (!isset($cache['ldap']['level'][$username])) {
        $userlevel = 0;

        ad_internal_init();
        ad_bind_dn();

        // Find all defined groups $username is in
        $userdn = ad_internal_dn_from_username($username);
        print_debug("ad_auth_user_level: UserDN: $userdn");

        // Check membership of each of our groups for the requested user
        foreach ($config['auth_ad_groups'] as $ldap_group => $ldap_group_info) {
            $compare = ad_internal_search_user(ad_internal_dn_from_groupname($ldap_group), $userdn);

            if ($compare === -1) {
                print_debug("ad_user_level: Compare LDAP error: " . ldap_internal_error($ds));
                continue;
            } elseif ($compare === FALSE) {
                print_debug("ad_user_level: Processing group: $ldap_group - Not matched");
            } else { // $compare === TRUE
                print_debug("ad_user_level: Processing group: $ldap_group - level: " . $ldap_group_info['level']);
                if ($ldap_group_info['level'] > $userlevel) {
                    $userlevel = $ldap_group_info['level'];
                    print_debug('ad_user_level: Accepted group level as new highest level');
                } else {
                    print_debug("ad_user_level: Ignoring group level as it's lower than what we have already");
                }
            }
        }

        print_debug('ad_user_level: Final level: '.$userlevel);

        $cache['ldap']['level'][$username] = $userlevel;
    }

    return $cache['ldap']['level'][$username];
}

/**
 * Retrieve user id for specified user.
 *
 * @param string $username Username to retrieve the ID for
 * @return int User's ID
 */
function ad_auth_user_id($username) {
    global $config, $ds;

    $userid = -1;

    ad_internal_init();
    ad_bind_dn();

    //$userdn = ad_internal_dn_from_username($username);

    $filter_params = [];
    $filter_params[] = ldap_filter_create('objectClass', 'person');
    $filter_params[] = ldap_filter_create('sAMAccountName', $username);
    $filter          = ldap_filter_combine($filter_params);
  
    print_debug("ad_auth_user_id: Filter $filter");
    $search = ldap_search($ds, $config['auth_ad_basedn'], $filter);
    $entries = ldap_internal_is_valid($search) ? ldap_get_entries($ds, $search) : [];

    if ($entries['count']) {
        $userid = ad_internal_auth_user_id($entries[0]);
        print_debug('ad_auth_user_id: UserID '.$userid);
    } else {
        print_debug('ad_auth_user_id: User not found through filter');
    }

    return $userid;
}

/**
 * Deletes a user from the user database.
 * This is not possible using the AD backend.
 *
 * @param string $username Username to delete
 * @return bool TRUE if user deletion is successful, FALSE if it is not
 */
function ad_deluser($username) {
    // Call into mysql database functions to make sure user is gone from the database for legacy setups
    mysql_deluser($username, 'ad');

    // Not supported
    return FALSE;
}

/**
 * Find the user's username by specifying their user ID.
 * Can't search objectSid object for the RID using LDAP filters, so fetch all users and compare.
 *
 * @param int $user_id The user's ID to look up the username for
 * @return string The user's user name, or FALSE if the user ID is not found
 */
function ad_auth_username_by_id($user_id) {

    foreach(ad_auth_user_list() as $user) {
        if ($user['user_id'] == $user_id) {
            return $user['username'];
        }
    }

    return FALSE;
}

/**
 * Get the user information by username
 *
 * @param string $username Username
 * @return array The user's user name, or FALSE if the user ID is not found
 */
function ad_auth_user_info($username) {

    if (empty($username)) {
        return [];
    }

    // Delegate fetching of user details to user list function
    foreach(ad_auth_user_list($username) as $user) {
        if ($user['username'] == $username) {
            return $user;
        }
    }

    return [];
}

/**
 * Retrieve list of users with all details.
 * If we specify a username, only fetch details for that specific use.
 *
 * @param string $username Username (optional)
 * @return array Rows of user data
 */
function ad_auth_user_list($username = NULL) {
    global $config, $ds;

    // Use caching for reduce queries to LDAP
    if (isset($GLOBALS['cache']['ldap']['userlist'])) {
        if ((get_time() - $GLOBALS['cache']['ldap']['userlist']['unixtime']) <= 300) { // Cache valid for 5 min
            return $GLOBALS['cache']['ldap']['userlist']['entries'];
        }
        unset($GLOBALS['cache']['ldap']['userlist']);
    }

    ad_internal_init();
    ad_bind_dn();

    $filter_params   = [];
    $filter_params[] = ldap_filter_create('objectClass', 'person');

    if (!empty($username)) {
        // Filter users by username, if passed to the function
        $filter_params[] = ldap_filter_create('sAMAccountName', $username);
    }

    // Filter user(s) by group(s) as configured in auth_ad_group, if any
    if (count($config['auth_ad_group']) > 0) {
        $group_params = [];

        // Add all configured LDAP groups as an OR filter to build the user list
        foreach($config['auth_ad_group'] as $group) {
            $group_params[] = ldap_filter_create('memberOf:1.2.840.113556.1.4.1941:', ad_internal_dn_from_groupname($group));
        }
      
        $filter_params[] = ldap_filter_combine($group_params, '|');
    }

    $filter = ldap_filter_combine($filter_params);

    // Limit fetched attributes, to reduce network transfer size
    $attributes = [ 'samaccountname', 'name', 'objectsid', 'description', 'mail', 'dn' ];

    $entries = ldap_paged_entries($filter, $attributes, $config['auth_ad_basedn']);

    // Process array in separate function
    ad_internal_user_entries($entries, $userlist);
    unset($entries);

    // Store userlist in cache
    $GLOBALS['cache']['ldap']['userlist'] = [ 'unixtime' => get_time(),
                                              'entries'  => $userlist ];
    return $userlist;
}

/**
 * Parse user entries in ad_auth_user_list()
 *
 * @param	array	$entries LDAP entries by ldap_get_entries()
 * @param	array	$userlist	Users list
 */
function ad_internal_user_entries($entries, &$userlist) {
    global $config, $ds;

    if (!is_array($userlist)) {
        $userlist = [];
    }

    if ($entries['count']) {
        unset($entries['count']);

        foreach ($entries as $i => $entry) {
            $username    = $entry['samaccountname'][0];
            $realname    = $entry['name'][0];
            $user_id     = ad_internal_auth_user_id($entry);
            $email       = $entry['mail'][0];
            $description = $entry['description'][0];
            $userdn      = $entry['dn'];

            print_debug("ad_internal_user_entries: Compare: " . implode('|',$config['auth_ad_group']) . " to fulldn ($userdn)");

            foreach ($config['auth_ad_group'] as $ldap_group) {
                $authorized = 0;

                $compare = ad_internal_search_user($ldap_group, $userdn);

                if ($compare === -1) {
                    print_debug("ad_internal_user_entries: Compare LDAP error: " . ldap_internal_error($ds));
                    continue;
                } elseif ($compare === FALSE) {
                    print_debug("ad_internal_user_entries: Processing group: $ldap_group - Not matched");
                } else {
                    // $$compare === TRUE
                    print_debug("ad_internal_user_entries: Authorized: $userdn for group $ldap_group");
                    $authorized = 1;
                    break;
                }
            }

            if (!isset($config['auth_ad_group']) || $authorized) {
                $user_level = ad_auth_user_level($username);
                $userlist[] = [ 'username' => $username, 'realname' => $realname, 'user_id' => $user_id,
                                'level' => $user_level, 'email' => $email, 'descr' => $description ];
            }
        }
    }
}

/**
 * Bind with the configured bind DN
 * Private function for this auth module only.
 *
 * @return bool TRUE if bind succeeded, FALSE if not
*/
function ad_bind_dn() {
    global $config, $ds, $cache;

    // Avoid binding multiple times on one resource, this upsets some LDAP servers.
    if (isset($cache['ldap_bind_result'])) {
        return $cache['ldap_bind_result'];
    }

    print_debug("ad_bind_dn: Binding to server with DN [" . $config['auth_ad_binddn'] . "]");
    $bind = ldap_bind($ds, $config['auth_ad_binddn'], $config['auth_ad_bindpw']);

    if ($bind) {
        $cache['ldap_bind_result'] = 1;
        print_debug("ad_bind_dn: Bound to AD server.");
        return TRUE;
    }

    $cache['ldap_bind_result'] = 0;
    ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $err);
    print_debug("ad_bind_dn: Error binding to AD server: " . implode(' ', $config['auth_ad_server']) . ': ' . ldap_error($ds) . " ($err)");
    session_logout();
    return FALSE;
}

/**
 * Find user's Distinguished Name based on their username.
 * Private function for this auth module only.
 *
 * @param string $username Username to retrieve DN for
 *
 * @return string User's Distinguished Name
 */
function ad_internal_dn_from_username($username) {
    global $config, $ds, $cache;

    // If not cached, retrieve DN from AD by searching for the sAMAccountName
    if (!isset($cache['ldap']['dn'][$username])) {
        ad_internal_init();
        $filter_params[] = ldap_filter_create('objectClass', 'person');
        $filter_params[] = ldap_filter_create('sAMAccountName', $username);
        $filter          = ldap_filter_combine($filter_params);
        print_debug("ad_internal_dn_from_username: Searching for user $username using filter: $filter on base " . $config['auth_ad_basedn']);

        $search  = ldap_search($ds, $config['auth_ad_basedn'], $filter);
        $entries = ldap_get_entries($ds, $search);

        if ($entries['count']) {
            [ $cache['ldap']['dn'][$username], ] = ldap_escape_filter_value($entries[0]['dn']);
            print_debug("ad_internal_dn_from_username: retrieved DN for $username from AD: " . $cache['ldap']['dn'][$username]);
        } else {
            $cache['ldap']['dn'][$username] = FALSE;
            print_debug("ad_internal_dn_from_username: unable to retrieve DN for $username from AD");
        }
    } else {
        print_debug("ad_internal_dn_from_username: retrieved DN for $username from cache: " . $cache['ldap']['dn'][$username]);
    }

    return $cache['ldap']['dn'][$username];
}

/**
 * Calculate User's numeric ID from LDAP.
 * Fetches objectSID from AD, and grab the RID to use as uid number. If RFC2307
 * (unix attributes) are used in your AD schema, we will use this instead.
 * Some systems (SSSD, Samba rid idmap) use an offset to distinguish multiple 
 * domains, Observium does not currently support this.
 *
 * Private function for this auth module only.
 *
 * @param object LDAP search result for the user
 *
 * @return int User ID.
 */
function ad_internal_auth_user_id($result) {
    // If RFC2307 UidNumber is set up, use it.
    if (isset($result['uidnumber'][0])) {
        return $result['uidnumber'][0];
    } 
  
    // No RFC2307 UID found, convert SID S-1-5-21-4113566099-323201010-15454308-1104 to 1104 as our numeric unique ID
    $sid = explode('-', ldap_bin_to_str_sid($result['objectsid'][0]));
    $userid = $sid[count($sid)-1];
    print_debug("ad_internal_auth_user_id: Converted objectSid " . ldap_bin_to_str_sid($result['objectsid'][0]) . " to numeric user ID $userid");

    return $userid;
}

/**
 * Returns group DN for group name, if it is not yet a DN.
 * Private function for this auth module only.
 *
 * @param string $groupname Group name
 *
 * @return array Group DN
 */
function ad_internal_dn_from_groupname($groupname) {
    global $ds, $config, $cache;

    if (!isset($cache['ldap']['dn'][$groupname])) {
        // If the Base DN is not found in the name, we assume it's just the group name and not the DN, and search for the DN.
        // If it is, we just return the given string as it should be correct.
        if (strpos($groupname,$config['auth_ad_basedn']) === FALSE) {
            $filter_params   = [];
            $filter_params[] = ldap_filter_create('objectClass', 'group');
            $filter_params[] = ldap_filter_create('name', $groupname);
            $filter          = ldap_filter_combine($filter_params);

            print_debug("ad_internal_dn_from_groupname: Searching for group $groupname using filter: $filter on base " . $config['auth_ad_basedn']);

            $ldap_search  = ldap_search($ds, $config['auth_ad_basedn'], $filter, [ 'distinguishedName' ]);
            $entries = ldap_get_entries($ds, $ldap_search);

            if ($entries['count']) {
                [ $cache['ldap']['dn'][$groupname], ] = ldap_escape_filter_value($entries[0]['dn']);
                print_debug("ad_internal_dn_from_groupname: retrieved DN for $groupname from AD: " . $cache['ldap']['dn'][$groupname]);
            } else {
                $cache['ldap']['dn'][$groupname] = FALSE;
                print_debug("ad_internal_dn_from_groupname: unable to retrieve DN for $groupname from AD");
            }
        } else {
            $cache['ldap']['dn'][$groupname] = $groupname;
            print_debug("ad_internal_dn_from_groupname: retrieved DN for $groupname from group name itself: " . $cache['ldap']['dn'][$groupname]);
        }
    } else {
        print_debug("ad_internal_dn_from_groupname: retrieved DN for $groupname from cache: " . $cache['ldap']['dn'][$groupname]);
    }

    return $cache['ldap']['dn'][$groupname];
}

// EOF
