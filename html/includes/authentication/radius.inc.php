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
 * Initializes the RADIUS connection to the specified server(s). Cycles through all servers, throws error when no server can be reached.
 * Private function for this RADIUS module only.
 */
function radius_init()
{
    global $rad, $config;

    if (!is_resource($rad)) {
        $success = 0;
        $rad     = radius_auth_open();

        foreach ($config['auth_radius_server'] as $server) {
            if (radius_add_server($rad, $server, $config['auth_radius_port'], $config['auth_radius_secret'], $config['auth_radius_timeout'], $config['auth_radius_retries'])) {
                $success = 1;
            }
        }

        if (!$success) {
            print_error("Fatal error: Could not connect to configured RADIUS server(s).");
            session_logout();
            exit;
        }
    }
}

/**
 * Check username and password against RADIUS authentication backend.
 *
 * @param string $username User name to check
 * @param string $password User password to check
 *
 * @return int Authentication success (0 = fail, 1 = success) FIXME bool
 */
function radius_authenticate($username, $password)
{
    global $config, $rad;

    radius_init();
    if ($username && $rad) {
        //print_vars(radius_server_secret($rad));
        radius_create_request($rad, RADIUS_ACCESS_REQUEST);

        radius_put_attr($rad, RADIUS_USER_NAME, $username);
        switch (strtolower($config['auth_radius_method'])) {
            // CHAP-MD5 see RFC1994
            case 'chap':
            case 'chap_md5':
                $chapid = 1;             // Specify a CHAP identifier
                //$challenge = mt_rand(); // Generate a challenge
                //$cresponse = md5(pack('Ca*', $chapid, $password.$challenge), TRUE);

                new Crypt_CHAP(); // Pre load class
                $crpt             = new Crypt_CHAP_MD5();
                $crpt -> password = $password;
                $challenge        = $crpt -> challenge;
                $resp_md5         = $crpt -> challengeResponse();
                $resp             = pack('C', $chapid) . $resp_md5;
                radius_put_attr($rad, RADIUS_CHAP_PASSWORD, $resp);       // Add the Chap-Password attribute
                radius_put_attr($rad, RADIUS_CHAP_CHALLENGE, $challenge); // Add the Chap-Challenge attribute.
                break;

            // MS-CHAPv1 see RFC2433
            case 'mschapv1':
                $chapid = 1; // Specify a CHAP identifier
                $flags  = 1; // 0 = use LM-Response, 1 = use NT-Response (we not use old LM)

                new Crypt_CHAP(); // Pre load class
                $crpt             = new Crypt_CHAP_MSv1();
                $crpt -> password = $password;
                $challenge        = $crpt -> challenge;
                $resp_lm          = str_repeat("\0", 24);
                $resp_nt          = $crpt -> challengeResponse();
                $resp             = pack('CC', $chapid, $flags) . $resp_lm . $resp_nt;
                radius_put_vendor_attr($rad, RADIUS_VENDOR_MICROSOFT, RADIUS_MICROSOFT_MS_CHAP_RESPONSE, $resp);
                radius_put_vendor_attr($rad, RADIUS_VENDOR_MICROSOFT, RADIUS_MICROSOFT_MS_CHAP_CHALLENGE, $challenge);
                break;

            // MS-CHAPv2 see RFC2759
            case 'mschapv2':
                $chapid = 1; // Specify a CHAP identifier
                $flags  = 1; // 0 = use LM-Response, 1 = use NT-Response (we not use old LM)

                new Crypt_CHAP(); // Pre load class
                $crpt             = new Crypt_CHAP_MSv2();
                $crpt -> username = $username;
                $crpt -> password = $password;
                $challenge        = $crpt -> authChallenge;
                $challenge_p      = $crpt -> peerChallenge;
                $resp_nt          = $crpt -> challengeResponse();

                // Response: chapid, flags (1 = use NT Response), Peer challenge, reserved, Response
                $resp = pack('CCa16a8a24', $chapid, $flags, $challenge_p, str_repeat("\0", 8), $resp_nt);
                radius_put_vendor_attr($rad, RADIUS_VENDOR_MICROSOFT, RADIUS_MICROSOFT_MS_CHAP2_RESPONSE, $resp);
                radius_put_vendor_attr($rad, RADIUS_VENDOR_MICROSOFT, RADIUS_MICROSOFT_MS_CHAP_CHALLENGE, $challenge);
                break;

            // PAP (Plaintext)
            default:
                radius_put_attr($rad, RADIUS_USER_PASSWORD, $password);
        }

        // Puts standard attributes
        $radius_ip = get_ip_version($config['auth_radius_nas_address']) ? $config['auth_radius_nas_address'] : $_SERVER['SERVER_ADDR'];
        if (get_ip_version($radius_ip) == 6) {
            // FIXME, not sure that this work correctly
            radius_put_attr($rad, RADIUS_NAS_IPV6_ADDRESS, $radius_ip);
        } else {
            radius_put_addr($rad, RADIUS_NAS_IP_ADDRESS, $radius_ip);
        }
        $radius_id = (empty($config['auth_radius_id']) ? get_localhost() : $config['auth_radius_id']);
        radius_put_attr($rad, RADIUS_NAS_IDENTIFIER, $radius_id);
        //radius_put_attr($rad, RADIUS_NAS_PORT_TYPE, RADIUS_VIRTUAL);
        //radius_put_attr($rad, RADIUS_SERVICE_TYPE, RADIUS_FRAMED);
        //radius_put_attr($rad, RADIUS_FRAMED_PROTOCOL, RADIUS_PPP);
        radius_put_attr($rad, RADIUS_CALLING_STATION_ID, isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');

        $response = radius_send_request($rad);
        //print_vars($response);
        switch ($response) {
            case RADIUS_ACCESS_ACCEPT:
                // An Access-Accept response to an Access-Request indicating that the RADIUS server authenticated the user successfully.
                //echo 'Authentication successful';
                return 1;
                break;
            case RADIUS_ACCESS_REJECT:
                // An Access-Reject response to an Access-Request indicating that the RADIUS server could not authenticate the user.
                //echo 'Authentication failed';
                break;
            case RADIUS_ACCESS_CHALLENGE:
                // An Access-Challenge response to an Access-Request indicating that the RADIUS server requires further information
                // in another Access-Request before authenticating the user.
                //echo 'Challenge required';
                break;
            default:
                print_error('A RADIUS error has occurred: ' . radius_strerror($rad));
        }
    }

    //session_logout();
    return 0;
}

/**
 * Check if the backend allows a specific user to change their password.
 * This is not currently possible using the RADIUS backend.
 *
 * @param string $username Username to check
 *
 * @return bool TRUE if password change is possible, FALSE if it is not
 */
function radius_auth_can_change_password($username = "")
{
    return 0;
}

/**
 * Changes a user's password.
 * This is not currently possible using the RADIUS backend.
 *
 * @param string $username Username to modify the password for
 * @param string $password New password
 *
 * @return bool TRUE if password change is successful, FALSE if it is not
 */
function radius_auth_change_password($username, $newpassword)
{
    # Not supported
    return FALSE;
}

/**
 * Check if the backend allows user management at all (create/delete/modify users).
 * This is not currently possible using the RADIUS backend.
 *
 * @return bool TRUE if user management is possible, FALSE if it is not
 */
function radius_auth_usermanagement()
{
    return 0;
}

/**
 * Adds a new user to the user backend.
 * This is not currently possible using the RADIUS backend.
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
function radius_adduser($username, $password, $level, $email = "", $realname = "", $can_modify_passwd = '1')
{
    // Not supported
    return FALSE;
}

/**
 * Check if a user, specified by username, exists in the user backend.
 * This will only return users that have logged in at least once and inserted into MySQL
 *
 * @param string $username Username to check
 *
 * @return bool TRUE if the user exists, FALSE if they do not
 */
function radius_auth_user_exists($username)
{
    return dbExist('users', '`username` = ? AND `type` = ?', [$username, 'radius']);
}

/**
 * Retrieve user auth level for specified user.
 *
 * @param string $username Username to retrieve the auth level for
 *
 * @return int User's auth level
 */
function radius_auth_user_level($username)
{
    global $config, $rad, $cache;

    $rad_userlevel = 0;
    if (isset($config['auth_radius_groups'])) {
        // If groups set, try to search group attribute and set user level

        if (!isset($cache['radius']['level'][$username])) {
            if ($config['auth_radius_groupmemberattr'] == 18 || strtolower($config['auth_radius_groupmemberattr']) === 'reply-message') {
                // Reply-Message (18)
                $attribute = RADIUS_REPLY_MESSAGE;
            } else {
                // Filter-Id (11)
                $attribute = RADIUS_FILTER_ID;
            }

            $rad_groups = [];
            while ($rad_attr = radius_get_attr($rad)) {
                if ($rad_attr['attr'] == $attribute) {
                    $rad_groups[] = radius_cvt_string($rad_attr['data']);
                    //r($rad_attr);
                    //break;
                }
            }
            //r($rad_groups);

            foreach ($rad_groups as $rad_group) {
                if (isset($config['auth_radius_groups'][$rad_group]) && $config['auth_radius_groups'][$rad_group]['level'] > $rad_userlevel) {
                    $rad_userlevel = intval($config['auth_radius_groups'][$rad_group]['level']);
                }
            }
            $cache['radius']['level'][$username] = $rad_userlevel;
        } else {
            $rad_userlevel = $cache['radius']['level'][$username];
        }
    } else {
        // Old non-groups, by default always user level 10
        if (strlen($username) > 0) {
            $rad_userlevel = 10;
        }
    }

    // If we don't already have an entry for this RADIUS user in the MySQL database, create one
    if (!radius_auth_user_exists($username)) {
        $user_id = radius_auth_user_id($username);
        create_mysql_user($username, $user_id, $rad_userlevel, 'radius');
    } else {
        // Update the user's level in MySQL if it doesn't match. This is really informational only.
        if (dbFetchCell("SELECT `level` FROM `users` WHERE `username` = ? AND `type` = ?", [$username, 'radius']) != $rad_userlevel) {
            $user_id = radius_auth_user_id($username);
            dbUpdate(['level' => $rad_userlevel, 'user_id' => $user_id], 'users', '`username` = ? AND `type` = ?', [$username, 'radius']);
        }
    }

    return $rad_userlevel;
}

/**
 * Retrieve user id for specified user.
 * Returns a hash of the username.
 *
 * @param string $username Username to retrieve the ID for
 *
 * @return int User's ID
 */
function radius_auth_user_id($username)
{
    //return -1;
    return string_to_id('radius|' . $username);
}

/**
 * Deletes a user from the user database.
 * This is not currently possible using the RADIUS backend.
 *
 * @param string $username Username to delete
 *
 * @return bool TRUE if user deletion is successful, FALSE if it is not
 */
function radius_deluser($username)
{
    // Not supported
    return FALSE;
}

/**
 * Retrieve list of users with all details.
 * This is not currently possible using the RADIUS backend.
 *
 * @return array Rows of user data
 */
function radius_auth_user_list()
{
    // Send list of users from MySQL
    return dbFetchRows("SELECT * FROM `users` WHERE `type` = ?", ['radius']);
}

// EOF
