<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Sessions

function session_allow_cidr() {
    global $config;

    if (safe_count($config['web_session_cidr'])) {
        return match_network(get_remote_addr($config['web_session_ip_by_header']), $config['web_session_cidr']);
    }

    return TRUE;
}

function session_remote_address() {
    // Store logged remote IP with real proxied IP (if configured and available)
    $remote_addr        = get_remote_addr();
    $remote_addr_header = get_remote_addr(TRUE); // Remote addr by http header
    if ($remote_addr_header && $remote_addr !== $remote_addr_header) {
        return $remote_addr_header . ' (' . $remote_addr . ')';
    }

    return $remote_addr;
}

/**
 * Store cached device/port/etc permitted IDs into $_SESSION['cache']
 *
 * IDs collected in html/includes/cache-data.inc.php
 * This function used mostly in print_search() or print_form(), see html/includes/print/search.inc.php
 * Cached IDs from $_SESSION used in ajax forms by generate_query_permitted()
 *
 * @return null
 */
function permissions_cache_session() {
    if (!$_SESSION['authenticated']) {
        return;
    }

    if (isset($GLOBALS['permissions_cached_session'])) {

        $cache_expire = (get_time() - $GLOBALS['permissions_cached_session']) >= 300;
        if (!$cache_expire) {
            return;
        }
    } // skip if this function already run. FIXME?

    @session_start(); // Re-enable write to session

    if ($cache_expire) {
        unset($_SESSION['cache']);
    }

    // Store device IDs in SESSION var for use to check permissions with ajax queries
    foreach (['permitted', 'disabled', 'ignored'] as $key) {
        $_SESSION['cache']['devices'][$key] = $GLOBALS['cache']['devices'][$key];
    }

    // Store port IDs in SESSION var for use to check permissions with ajax queries
    // FIXME. Not actual, need different way for ajax
    foreach (['permitted', 'deleted', 'errored', 'ignored', 'poll_disabled', 'device_disabled', 'device_ignored'] as $key) {
        $_SESSION['cache']['ports'][$key] = $GLOBALS['cache']['ports'][$key];
    }

    $GLOBALS['permissions_cached_session'] = get_time();

    session_write_close(); // Write and close session
}

function session_set_debug($debug_web_requested = FALSE) {
    if (!defined('OBS_DEBUG')) // OBS_DEBUG not defined by default for unprivileged users in definitions
    {
        if ($_SESSION['userlevel'] < 7 || !$debug_web_requested) // Note, use $config['web_debug_unprivileged'] = TRUE;
        {
            // DO NOT ALLOW show debug output for users with privilege level less than "Global Secure Read"
            define('OBS_DEBUG', 0);
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            //ini_set('error_reporting', 0); // Use default php config
        } else {
            define('OBS_DEBUG', 1);

            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            //ini_set('error_reporting', E_ALL ^ E_NOTICE);
            ini_set('error_reporting', E_ALL ^ E_NOTICE ^ E_WARNING);
        }
    }
    // ini_set('display_errors', 1);
    // ini_set('display_startup_errors', 1);
    // ini_set('error_reporting', E_ALL ^ E_NOTICE);
}

// DOCME needs phpdoc block
function session_logout($relogin = FALSE, $message = NULL)
{
    global $debug_auth;

    // Save auth failure message for later re-use
    $auth_message = $_SESSION['auth_message'];

    if ($_SESSION['authenticated']) {
        $auth_log = 'Logged Out';
    } else {
        $auth_log = 'Authentication Failure';
    }
    if ($message) {
        $auth_log .= ' (' . $message . ')';
    }
    if ($debug_auth) {
        $debug_log = $GLOBALS['config']['log_dir'] . '/debug_logout_' . date("Y-m-d_H:i:s") . '.log';
        file_put_contents($debug_log, var_export($_SERVER, TRUE), FILE_APPEND);
        file_put_contents($debug_log, var_export($_SESSION, TRUE), FILE_APPEND);
        file_put_contents($debug_log, var_export($_COOKIE, TRUE), FILE_APPEND);
    }

    dbInsert(['user'       => $_SESSION['username'],
              'address'    => session_remote_address(),
              'user_agent' => $_SERVER['HTTP_USER_AGENT'],
              'result'     => $auth_log], 'authlog');
    if (isset($_COOKIE['ckey'])) {
        // Remove old ckeys from DB
        dbDelete('users_ckeys', "`username` = ? AND `user_ckey` = ?", [$_SESSION['username'], $_COOKIE['ckey']]);
    }
    // Unset cookies
    $cookie_params = session_get_cookie_params();
    $past          = time() - 3600;
    foreach ($_COOKIE as $cookie => $value) {
        if (empty($cookie_params['domain'])) {
            setcookie($cookie, '', $past, $cookie_params['path']);
        } else {
            setcookie($cookie, '', $past, $cookie_params['path'], $cookie_params['domain']);
        }
    }
    unset($_COOKIE);

    // Clean cache if possible
    if ($_SESSION['authenticated']) {
        $cache_tags = [ '__username=' . safe_cache_key($_SESSION['username']) ];
    } else {
        $cache_tags = [ '__anonymous' ];
    }
    del_cache_items($cache_tags);

    // Unset session
    @session_start();
    if ($relogin) {
        // Reset session and relogin (for example: HTTP auth)
        $_SESSION['relogin'] = TRUE;
        unset($_SESSION['authenticated'],
            $_SESSION['user_id'],
            $_SESSION['username'],
            $_SESSION['user_encpass'], $_SESSION['password'],
            $_SESSION['userlevel']);
        session_write_close();
        session_regenerate_id(TRUE);
    } else {
        // Kill current session, as authentication failed
        unset($_SESSION);
        session_unset();
        session_destroy();
        session_write_close();
        //setcookie(session_name(),'',0,'/');
        session_regenerate_id(TRUE);
        // Re-set auth failure message for use on login page
        //session_start();
        $_SESSION['auth_message'] = $message;
    }
}

/**
 * Safe session_regenerate_id that doesn't loose session
 *
 * Code borrowed from https://www.php.net/manual/en/function.session-regenerate-id.php
 */
function safe_session_regenerate_id() {
    global $debug_auth;

    // New session ID is required to set proper session ID
    // when session ID is not set due to unstable network.
    $new_session_id = session_create_id();
    if ($debug_auth) {
        logfile('debug_auth.log', __LINE__ . " Session ID regenerated. IP=[" . get_remote_addr($GLOBALS['config']['web_session_ip_by_header']) .
                                  "]. ID=[$new_session_id]." . web_debug_log_message());
    }
    $_SESSION['new_session_id'] = $new_session_id;

    // Set destroy timestamp
    $_SESSION['destroyed'] = time();

    // Write and close current session;
    session_write_close();

    $tmp = $_SESSION;  // Somehow the SESSION data is not kept

    // Start session with new session ID
    session_id($new_session_id);
    ini_set('session.use_strict_mode', 0);
    session_start();
    ini_set('session.use_strict_mode', 1);

    $_SESSION = $tmp;

    // New session does not need them
    unset($_SESSION['destroyed'], $_SESSION['new_session_id']);
}

/**
 * Regenerate session ID for prevent attacks session hijacking and session fixation.
 * Note, use this function after session_start() and before next session_commit()!
 *
 * Code borrowed from https://www.php.net/manual/en/function.session-regenerate-id.php
 *
 * @param int $lifetime_id Time in seconds for next regenerate session ID (default 30 min)
 */
function session_regenerate($lifetime_id = 1800)
{
    session_start();

    if (isset($_SESSION['destroyed'])) {
        // logfile('debug_auth.log', __LINE__ . ' session destroyed ' . json_encode($_SESSION));

        if ($_SESSION['destroyed'] < time() - 300) {
            // logfile('debug_auth.log', __LINE__ . ' < 300 - logout');

            // Should not happen usually. This could be attack or due to unstable network.
            // Remove all authentication status of this users session.
            session_logout(TRUE, 'Session destroyed');

            return;
        }

        if (isset($_SESSION['new_session_id'])) {
            // logfile('debug_auth.log', __LINE__ . ' got new_session_id: ' . $_SESSION['new_session_id']);

            // Not fully expired yet. Could be lost cookie by unstable network.
            // Try again to set proper session ID cookie.
            // NOTE: Do not try to set session ID again if you would like to remove
            // authentication flag.
            session_write_close();

            session_id($_SESSION['new_session_id']);
            // New session ID should exist
            session_start();

            return;
        }
    }

    $currenttime   = time();
    if ($lifetime_id != 1800 && is_numeric($lifetime_id) && $lifetime_id >= 300) {
        $lifetime_id = (int)$lifetime_id;
    } else {
        $lifetime_id = 1800;
    }

    if (isset($_SESSION['starttime'])) {
        // logfile('debug_auth.log', __LINE__ . ' ' . json_encode([$_SESSION['starttime'], $currenttime, $currenttime - $_SESSION['starttime']]));

        if (($currenttime - $_SESSION['starttime']) >= $lifetime_id &&
            !is_graph() && !is_ajax()) // Skip regenerate in graphs and in ajax
        {
            // logfile('debug_auth.log', __LINE__ . ' ' . 'session_regenerate() called at '.$currenttime);
            // ID Lifetime expired, regenerate
            safe_session_regenerate_id();

            // Clean cache from _SESSION first, this cache used in ajax calls
            if (isset($_SESSION['cache'])) {
                unset($_SESSION['cache']);
            }
            $_SESSION['starttime'] = $currenttime;
        }
    } else {
        $_SESSION['starttime'] = $currenttime;
    }
}

/**
 * Store encrypted password in $_SESSION['user_encpass'], required for some auth mechanism, i.e. ldap
 *
 * @param string $auth_password Plain password
 * @param string $key           Key for password encrypt
 *
 * @return string               Encrypted password
 * @throws Exception
 */
function session_encrypt_password($auth_password, $key) {
    global $config;

    // Store encrypted password
    if ($config['auth_mechanism'] === 'ldap' &&
        !($config['auth_ldap_bindanonymous'] || !safe_empty($config['auth_ldap_binddn'] . $config['auth_ldap_bindpw']))) {
        if (OBS_ENCRYPT) {
            if (OBS_ENCRYPT_MODULE === 'mcrypt') {
                $key .= get_unique_id();
            }
            // For some admin LDAP functions required store encrypted password in session (userslist)
            session_set_var('user_encpass', encrypt($auth_password, $key));
        } else {
            //session_set_var('user_encpass', base64_encode($auth_password));
            session_set_var('encrypt_required', 1);
        }
    }

    return $_SESSION['user_encpass'];
}

function session_decrypt_password() {
    if (!isset($_SESSION['encrypt_required'])) {
        $key = session_unique_id();
        if (OBS_ENCRYPT_MODULE === 'mcrypt') {
            $key .= get_unique_id();
        }
        return decrypt($_SESSION['user_encpass'], $key);
    }
    // WARNING, requires mcrypt or sodium
    return base64_decode($_SESSION['user_encpass'], TRUE);
}

// DOCME needs phpdoc block
function session_is_active()
{
    if (!is_cli()) {
        // logfile('debug_auth.log', __LINE__ . " CONSTANT? ".var_export(session_status(), TRUE)." vs ".var_export(PHP_SESSION_ACTIVE, TRUE));
        return session_status() === PHP_SESSION_ACTIVE;
    }

    // logfile('debug_auth.log', __LINE__ . " CLI?");
    return FALSE;
}

/**
 * Generate unique id for current user/browser, based on some unique params
 *
 * @return string
 */
function session_unique_id()
{
    global $config, $debug_auth;

    /* WiP. New User-Agent
    if ($debug_auth && isset($_SERVER['HTTP_SEC_CH_UA'])) {
        //print_message($_SERVER['HTTP_SEC_CH_UA']);
        //print_message($_SERVER['HTTP_SEC_CH_UA_MOBILE']);
        //r($_SERVER);
    }
    */

    $id  = $_SERVER['HTTP_USER_AGENT']; // User agent
    //$id .= $_SERVER['HTTP_ACCEPT'];     // Browser accept headers // WTF, this header different for main and js/ajax queries
    // Less entropy than HTTP_ACCEPT, but stable!
    $id .= $_SERVER['HTTP_ACCEPT_ENCODING'];
    $id .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];

    if ($config['web_session_ip']) {
        $remote_ip = get_remote_addr($config['web_session_ip_by_header']); // Remote address by header if configured
        $config['web_session_ipv6_prefix'] = ltrim($config['web_session_ipv6_prefix'], '/');
        if (is_numeric($config['web_session_ipv6_prefix']) &&
            $config['web_session_ipv6_prefix'] >= 1 && $config['web_session_ipv6_prefix'] <= 127 &&
            get_ip_version($remote_ip) === 6) {
            $remote_addr = Net_IPv6 ::getNetmask($remote_ip, (int)$config['web_session_ipv6_prefix']);
        } else {
            $remote_addr = $remote_ip;
        }
        $id .= $remote_addr;   // User IP address

        // Force reauth if remote IP changed
        if ($_SESSION['authenticated']) {
            if (isset($_SESSION['PREV_REMOTE_ADDR']) && $remote_addr !== $_SESSION['PREV_REMOTE_ADDR']) {
                if ($debug_auth) {
                    logfile('debug_auth.log', __LINE__ . " IP changed. IP=[$remote_ip]. ID=[$id]." . web_debug_log_message());
                    logfile('debug_auth.log', __LINE__ . ' ' . var_export($_SESSION, TRUE));
                }
                // FIXME. Hrm, I forgot why not just session_logout()
                //session_logout();
                unset($_SESSION['authenticated'],
                    $_SESSION['user_id'],
                    $_SESSION['username'],
                    $_SESSION['user_encpass'], $_SESSION['password'],
                    $_SESSION['userlevel'],
                    $_SESSION['PREV_REMOTE_ADDR']);
                reauth_with_message('Remote IP has changed.');
            }
            session_set_var('PREV_REMOTE_ADDR', $remote_addr); // Store current remote IP
        }
    }

    // Force reauth if user-agent or login changed
    if ($_SESSION['authenticated']) {
        // Check and validate if User agent not changed
        if (session_useragent_changed()) {
            if ($debug_auth) {
                logfile('debug_auth.log', __LINE__ . " UA changed. IP=[" . get_remote_addr($config['web_session_ip_by_header']) . "]. ID=[$id]." . web_debug_log_message());
                logfile('debug_auth.log', __LINE__ . ' ' . var_export($_SESSION, TRUE));
            }
            session_logout();
            reauth_with_message('Browser has changed.');
        }
        // Ie in API request can force different user
        if (session_user_changed()) {
            if ($debug_auth) {
                logfile('debug_auth.log', __LINE__ . " Username changed. IP=[" . get_remote_addr($config['web_session_ip_by_header']) . "]. ID=[$id]." . web_debug_log_message());
                logfile('debug_auth.log', __LINE__ . ' ' . var_export($_SESSION, TRUE));
            }
            session_logout();
            reauth_with_message('User login has changed.');
        }
        //if ($debug_auth)
        //{
        //  logfile('debug_auth.log', __LINE__ . ' ' . "IP=[".get_remote_addr($config['web_session_ip_by_header'])."]. ID=[$id]. URL=[".$_SERVER['REQUEST_URI']."]");
        //}
    }

    $user_unique_id = md5($id);
    // Next required JS cals:
    // resolution = screen.width+"x"+screen.height+"x"+screen.colorDepth;
    // timezone   = new Date().getTimezoneOffset();
    if (FALSE && $debug_auth) {
        $debug_log_array = [$user_unique_id, $remote_addr, $_SERVER['HTTP_USER_AGENT'],
                            $_SERVER['HTTP_ACCEPT_ENCODING'], $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                            $_COOKIE['OBSID']];
        logfile('debug_auth.log', __LINE__ . ' ' . json_encode($debug_log_array));
    }

    //print_vars($id);
    return $user_unique_id;
}

function session_useragent_changed() {
    $ua = md5($_SERVER['HTTP_USER_AGENT']);
    if (!isset($_SESSION['ua'])) {
        session_set_var('ua', $ua);
        return FALSE;
    }

    return $_SESSION['ua'] !== $ua;
}

function session_user_changed() {
    if (isset($_GET['username'], $_GET['password']) &&
        $_GET['username'] !== $_SESSION['username']) {
        // GET Auth
        return TRUE;
    }
    if (isset($_POST['username'], $_POST['password']) &&
        $_POST['username'] !== $_SESSION['username']) {
        // POST Auth
        return TRUE;
    }
    if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) &&
        $_SERVER['PHP_AUTH_USER'] !== $_SESSION['username']) {
        // Basic Auth
        return TRUE;
    }

    return FALSE;
}

/**
 * Use this function to write to the $_SESSION global variable.
 * This function prevents session blocking.
 * If the value is NULL, the session variable will be unset.
 *
 * @param string|array $var   A string representing the key or nested keys (e.g., 'key1->key2->key3') or an array of keys.
 * @param mixed        $value The value to set or NULL to unset the session variable.
 */
function session_set_var($var, $value)
{
    // Extract nested keys if they exist
    $keys = is_array($var) ? $var : explode('->', $var);


    // Start the session (unblock)
    @session_start();

    // Check if the session variable is unchanged and return early if it is
    if (get_value_by_keys($_SESSION, $keys) === $value) {
        return;
    }

    // Set or unset the session variable based on the value
    if (is_null($value)) {
        unset_value_by_keys($_SESSION, $keys);
    } else {
        set_value_by_keys($_SESSION, $keys, $value);
    }

    // Commit the session (write and block)
    session_write_close();
}

/**
 * Unset a session variable using the provided key or nested keys.
 *
 * @param string $var A string representing the key or nested keys (e.g., 'key1->key2->key3').
 */
function session_unset_var($var)
{
    session_set_var($var, NULL);
}

// Cookies

function cookie_get_auth_password($user_unique_id, $debug = FALSE) {
    if (OBS_ENCRYPT && isset($_COOKIE['ckey']) && is_string($_COOKIE['ckey'])) {
        ///DEBUG
        if ($debug) {
            $debug_log = $GLOBALS['config']['log_dir'] . '/debug_cookie_' . date("Y-m-d_H:i:s") . '.log';
            file_put_contents($debug_log, var_export($_SERVER, TRUE), FILE_APPEND);
            file_put_contents($debug_log, var_export($_SESSION, TRUE), FILE_APPEND);
            file_put_contents($debug_log, var_export($_COOKIE, TRUE), FILE_APPEND);
        }

        $ckey = dbFetchRow("SELECT * FROM `users_ckeys` WHERE `user_uniq` = ? AND `user_ckey` = ? LIMIT 1",
                           [ $user_unique_id, $_COOKIE['ckey'] ]);
        if (is_array($ckey) && $ckey['expire'] > get_time() && session_allow_cidr()) {
            // If userlevel == 0 - user disabled and can not be logon
            if (auth_user_level($ckey['username']) < 1) {
                session_logout(FALSE, 'User disabled');
                reauth_with_message('User login disabled');
                return FALSE;
            }

            session_set_var('username', $ckey['username']);
            $auth_password = decrypt($ckey['user_encpass'], $_COOKIE['dkey']);

            // Store encrypted password
            session_encrypt_password($auth_password, $user_unique_id);

            session_set_var('user_ckey_id', $ckey['user_ckey_id']);
            session_set_var('cookie_auth', TRUE);
            dbInsert([ 'user'       => $_SESSION['username'],
                       'address'    => session_remote_address(),
                       'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                       'result'     => 'Logged In (cookie)' ], 'authlog');
            //logfile('wui_auth_cookie.log', var_export($_SERVER, TRUE)); ///DEBUG
            return $auth_password;
        }
    }

    return FALSE;
}

function cookie_set_keys($user_unique_id, $auth_password, $cookie_expire, $cookie_path, $cookie_domain, $cookie_https, $cookie_httponly = FALSE) {
    if (OBS_ENCRYPT && isset($_POST['remember'])) {
        $ckey    = md5(random_string());
        $dkey    = md5(random_string());
        $encpass = encrypt($auth_password, $dkey);
        dbDelete('users_ckeys', "`username` = ? AND `expire` < ?", [ $_SESSION['username'], get_time('1hour') ]); // Remove old ckeys from DB
        dbInsert([ 'user_encpass' => $encpass,
                   'expire'       => $cookie_expire,
                   'username'     => $_SESSION['username'],
                   'user_uniq'    => $user_unique_id,
                   'user_ckey'    => $ckey ], 'users_ckeys');
        // AJAX request not have access to cookies with httponly, and for example widgets lost auth
        //setcookie("ckey", $ckey, $cookie_expire, $cookie_path, $cookie_domain, $cookie_https, $cookie_httponly);
        //setcookie("dkey", $dkey, $cookie_expire, $cookie_path, $cookie_domain, $cookie_https, $cookie_httponly);
        setcookie("ckey", $ckey, $cookie_expire, $cookie_path, $cookie_domain, $cookie_https, FALSE);
        setcookie("dkey", $dkey, $cookie_expire, $cookie_path, $cookie_domain, $cookie_https, FALSE);
        session_unset_var('user_ckey_id');
    }
}

function cookie_auth($cookie_expire) {
    if (isset($_SESSION['cookie_auth']) && $_SESSION['cookie_auth']) {
        @session_start();
        $_SESSION['authenticated'] = TRUE;

        dbUpdate([ 'expire' => $cookie_expire ], 'users_ckeys', '`user_ckey_id` = ?', [ $_SESSION['user_ckey_id'] ]);
        unset($_SESSION['user_ckey_id'], $_SESSION['cookie_auth']);
        session_write_close();

        return TRUE;
    }

    return FALSE;
}

function cookie_clean_user() {
    if (isset($_COOKIE['password'])) {
        setcookie("password", NULL);
    }
    if (isset($_COOKIE['username'])) {
        setcookie("username", NULL);
    }
    if (isset($_COOKIE['user_id'])) {
        setcookie("user_id", NULL);
    }
}

/**
 * Every time you call session_start(), PHP adds another
 * identical session cookie to the response header. Do this
 * enough times, and your response header becomes big enough
 * to choke the web server.
 *
 * This method clears out the duplicate session cookies. You can
 * call it after each time you've called session_start(), or call it
 * just before you send your headers.
 */
function clear_duplicate_cookies()
{
    // If headers have already been sent, there's nothing we can do
    if (headers_sent()) {
        return;
    }

    $cookies = [];
    foreach (headers_list() as $header) {
        // Identify cookie headers
        if (str_starts_with($header, 'Set-Cookie:')) {
            $cookies[] = $header;
        }
    }
    // Removes all cookie headers, including duplicates
    header_remove('Set-Cookie');

    // Restore one copy of each cookie
    foreach (array_unique($cookies) as $cookie) {
        header($cookie, FALSE);
    }
}

/**
 * Redirects to the front page with the specified authentication failure message.
 * In the case of 'remote', no redirect is performed (as this would create an infinite loop,
 * as there is no way to logout), so the message is simply printed.
 *
 * @param string $message Message to display to the user
 */
function reauth_with_message($message)
{
    global $config;

    // Detect AJAX request, do not write any messages or redirects there!
    if (OBS_API || OBS_AJAX) {
        // FIXME. But probably here required redirect to requested ajax page with params..
        return;
    }

    if ($config['auth_mechanism'] === 'remote') {
        print('<h1>' . $message . '</h1>');
    } elseif (!empty($_GET['lm']) && is_numeric($_GET['lc']) && $_GET['lc'] > 0) {
        // Already redirected page, prevent redirect loop
        return;
    } else {
        //$redirect_count = !empty($_GET['lm']) && is_numeric($_GET['lc']) ? $_GET['lc']++ : 1;
        session_set_var('auth_message', $message);
        //redirect_to_url($config['base_url'] . '?lm='.var_encode($message));
        // Message encrypted for prevent hijacking any custom messages
        redirect_to_url($config['base_url'] . '?lm=' . encrypt($message, OBSERVIUM_PRODUCT . OBSERVIUM_VERSION) . '&lc=1');
    }
    exit();
}

function web_debug_log_message() {
    return " URL=[" . $_SERVER['REQUEST_URI'] . "]. UA=[" . $_SERVER['HTTP_USER_AGENT'] . "]. REFERER=[" . $_SERVER['HTTP_REFERER'] . "].";
}

// EOF
