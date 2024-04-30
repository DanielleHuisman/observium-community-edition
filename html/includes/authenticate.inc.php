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

// OBS_API set to TRUE only in html/api/index.php
is_api();

// OBS_GRAPH set to TRUE only in html/graph.php
is_graph();

// Detect AJAX request
is_ajax();

$debug_auth = FALSE; // Do not use this debug unless you Observium Developer ;)

@ini_set('session.referer_check', '');     // This config was causing so much trouble with Chrome
if (OBS_API) {
    @ini_set('session.name', 'OBSAPI');      // Session name for API
} else {
    @ini_set('session.name', 'OBSID');       // Session name for common Web UI
}
@ini_set('session.use_cookies', '1');      // Use cookies to store the session id on the client side
@ini_set('session.use_only_cookies', '1'); // This prevents attacks involved passing session ids in URLs
@ini_set('session.use_trans_sid', '0');    // Disable SID (no session id in url)

$currenttime   = time();
$lifetime      = 60 * 60 * 24;                     // Session lifetime (default one day)
$cookie_expire = $currenttime + 60 * 60 * 24 * 14; // Cookies expire time (14 days)
$cookie_path   = '/';                              // Cookie path
$cookie_domain = '';                               // RFC 6265, to have a "host-only" cookie is to NOT set the domain attribute.
/// FIXME. Some old browsers not supports secure/httponly cookies params.
$cookie_https = is_ssl();
// AJAX request not have access to cookies with httponly, and for example widgets lost auth
$cookie_httponly = FALSE;
//$cookie_httponly = TRUE;

// Use custom session lifetime
if (is_intnum($GLOBALS['config']['web_session_lifetime']) && $GLOBALS['config']['web_session_lifetime'] >= 0) {
    $lifetime = (int)$GLOBALS['config']['web_session_lifetime'];
}

@ini_set('session.gc_maxlifetime', $lifetime); // Session lifetime (for non "remember me" sessions)

/* PHP 7.1+ db sessions
@ini_set('session.gc_probability',  1);
@ini_set('session.gc_divisor',      10);
$session_handler = new Observium_Session();
//session_set_save_handler($handler, true);
//print_vars(ini_get('session.gc_probability'));
//print_vars(ini_get('session.gc_divisor'));
//print_vars(ini_get('session.gc_maxlifetime'));
*/

if (PHP_VERSION_ID >= 70300) {
    // Allows servers to assert that a cookie ought not to be sent along with cross-site requests.
    // Lax will sent the cookie for cross-domain GET requests, while Strict will not
    //@ini_set('session.cookie_samesite', 'Strict');
    $cookie_params = [
      'lifetime' => $lifetime,
      'path'     => $cookie_path,
      'domain'   => $cookie_domain,
      'secure'   => $cookie_https,
      'httponly' => $cookie_httponly,
      'samesite' => 'Lax' // 'Strict' /// FIXME. Set this configurable? See: https://jira.observium.org/browse/OBS-4214
    ];
    session_set_cookie_params($cookie_params);
} else {
    session_set_cookie_params($lifetime, $cookie_path, $cookie_domain, $cookie_https, $cookie_httponly);
}
//session_cache_limiter('private');

// Check for allowed by CIDR range
if (!session_allow_cidr()) {
    if ($debug_auth) {
        logfile('debug_auth.log', __LINE__ . " Remote IP not allowed!. IP=[" . get_remote_addr($config['web_session_ip_by_header']) . "]." . web_debug_log_message());
    }

    session_logout(FALSE, 'Remote IP not allowed in CIDR ranges');
    //reauth_with_message('Remote IP not allowed in CIDR ranges');
    display_error_http(403, 'Remote IP not allowed in CIDR ranges');
}

//register_shutdown_function('session_commit'); // This already done at end of script run
if (!session_is_active()) {
    session_regenerate();
}

if ($debug_auth && empty($_SESSION['authenticated'])) {
    logfile('debug_auth.log', __LINE__ . " NOT Authenticated!!!. IP=[" . get_remote_addr($config['web_session_ip_by_header']) . "]." . web_debug_log_message());
    //logfile('debug_auth.log', __LINE__ . ' ' . json_encode($_SESSION));
}

// Fallback to MySQL auth as default - FIXME do this in sqlconfig file?
if (!isset($config['auth_mechanism'])) {
    $config['auth_mechanism'] = "mysql";
}

// Trust Apache authenticated user, if configured to do so and username is available
if ($config['auth']['remote_user'] && is_valid_param($_SERVER['REMOTE_USER'], 'username')) {
    session_set_var('username', $_SERVER['REMOTE_USER']);
}

$auth_file = $config['html_dir'] . '/includes/authentication/' . $config['auth_mechanism'] . '.inc.php';
if (is_file($auth_file)) {
    if (isset($_SESSION['auth_mechanism']) && $_SESSION['auth_mechanism'] != $config['auth_mechanism']) {
        // Logout if AUTH mechanism changed
        session_logout();
        reauth_with_message('Authentication mechanism changed, please log in again!');
    } else {
        session_set_var('auth_mechanism', $config['auth_mechanism']);
    }

    // Always load mysql as backup
    include_once($config['html_dir'] . '/includes/authentication/mysql.inc.php');

    // Load primary module if not mysql
    if ($config['auth_mechanism'] !== 'mysql') {
        include_once($auth_file);
    }

    // Include base auth functions calls
    include_once($config['html_dir'] . '/includes/authenticate-functions.inc.php');
} else {
    session_logout();
    reauth_with_message('Invalid auth_mechanism defined, please correct your configuration!');
}

// Check logout
if ($_SESSION['authenticated'] && str_starts(ltrim($_SERVER['REQUEST_URI'], '/'), 'logout')) {
    // Do not use $vars and get_vars here!
    //print_vars($_SERVER['REQUEST_URI']);
    if (auth_can_logout()) {
        // No need for a feedback message if user requested a logout
        session_logout(function_exists('auth_require_login'));

        $redirect = auth_logout_url();
        if ($redirect) {
            redirect_to_url($redirect);
            exit();
        }
    }
    redirect_to_url($config['base_url']);
    exit();
}

$user_unique_id = session_unique_id(); // Get unique user id and check if IP changed (if required by config)

if (!$_SESSION['authenticated']) {
    if (isset($_GET['username'], $_GET['password']) &&
        is_valid_param($_GET['username'], 'username') && is_valid_param($_GET['password'], 'password')) {
        // GET Auth
        session_set_var('username', $_GET['username']);
        $auth_password = $_GET['password'];
        //r($_GET);
        //r($_SESSION);
    } elseif (isset($_POST['username'], $_POST['password']) &&
              is_valid_param($_POST['username'], 'username') && is_valid_param($_POST['password'], 'password')) {
        // POST Auth
        session_set_var('username', $_POST['username']);
        $auth_password = $_POST['password'];
    } elseif (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
        // Basic Auth
        session_set_var('username', $_SERVER['PHP_AUTH_USER']);
        $auth_password = $_SERVER['PHP_AUTH_PW'];
    } else {
        $auth_password = cookie_get_auth_password($user_unique_id, $debug_auth);
    }
}

// Clean user cookies
cookie_clean_user();

$auth_success = FALSE; // Variable for check if just logged

if (isset($_SESSION['username'])) {

    // Auth from COOKIEs
    $auth_success = cookie_auth($cookie_expire);

    // Auth from ...
    if (!$_SESSION['authenticated'] && (authenticate($_SESSION['username'], $auth_password) ||                       // login/password
                                        (auth_usermanagement() && auth_user_level($_SESSION['origusername']) >= 10))) // FIXME?
    {
        // If we get here, it means the password for the user was correct (authenticate() called)
        // Store encrypted password
        session_encrypt_password($auth_password, $user_unique_id);


        // If userlevel == 0 - user disabled and can not log in
        if (auth_user_level($_SESSION['username']) < 1) {
            session_logout(FALSE, 'User disabled');
            reauth_with_message('User login disabled');
            exit();
        }

        session_set_var('authenticated', TRUE);
        $auth_success = TRUE;
        dbInsert(['user'       => $_SESSION['username'],
                  'address'    => session_remote_address(),
                  'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                  'result'     => 'Logged In'], 'authlog');
        // Generate keys for cookie auth
        cookie_set_keys($user_unique_id, $auth_password, $cookie_expire, $cookie_path, $cookie_domain, $cookie_https);

    } elseif (!$_SESSION['authenticated']) {
        // Not authenticated
        session_set_var('auth_message', 'Authentication Failed');
        session_logout(function_exists('auth_require_login'));
    }

    // Retrieve user ID and permissions
    if ($_SESSION['authenticated']) {
        @session_start();
        if (!is_numeric($_SESSION['userlevel']) || !is_numeric($_SESSION['user_id']) || $_SESSION['user_id'] == -1) {
            $_SESSION['userlevel'] = auth_user_level($_SESSION['username']);
            $_SESSION['user_id']   = auth_user_id($_SESSION['username']);
        }

        //$level_permissions = auth_user_level_permissions($_SESSION['userlevel']);
        // If userlevel == 0 - user disabled an can not be logon
        if ($_SESSION['userlevel'] == 0) {
            //r($_SESSION); exit();
            session_logout(FALSE, 'User disabled');
            reauth_with_message('User login disabled');
            exit();
        }
        if ($_SESSION['userlevel'] < 5) {
            // Store user limited flag, required for quick permissions list generate
            $_SESSION['user_limited'] = TRUE;
        }
        session_write_close();

        // Hardcoded level permissions
        /// FIXME. It's seems unused?..

        $user_perms = [];

        foreach ($config['user_level'] as $level => $array) {
            if ($_SESSION['userlevel'] >= $level) {
                foreach ($array['roles'] as $entry) {
                    $user_perms[$entry] = $entry;
                }
            }
        }
        //print_vars($user_perms);

        //print_vars($_SESSION);
        //print_vars($level_permissions);

        // Generate a CSRF Token
        // https://stackoverflow.com/questions/6287903/how-to-properly-add-csrf-token-using-php
        // For validate use: request_token_valid($vars['requesttoken'])
        if (empty($_SESSION['requesttoken'])) {
            session_set_var('requesttoken', bin2hex(random_bytes(32)));
        }
        register_html_meta('csrf-token', $_SESSION['requesttoken']);

        // Now we can enable debug if user is privileged (Global Secure Read and greater)
        session_set_debug($debug_web_requested);

        //$a = utime();
        $permissions = permissions_cache($_SESSION['user_id']);
        //echo utime() - $a . 's for permissions cache';
    }

    if ($auth_success && !OBS_GRAPH && !OBS_API && !OBS_AJAX) {
        // If just logged in go to request uri, unless we're debugging or in API,
        // in which case we want to see authentication module output first.
        if (!OBS_DEBUG) {
            redirect_to_url($_SERVER['REQUEST_URI']);
        } else {
            print_message("Debugging mode has disabled redirect to front page; please click <a href=\"" . $_SERVER['REQUEST_URI'] . "\">here</a> to continue.");
        }
        exit();
    }
}

// Load user defined configs
if ($_SESSION['authenticated'] && $_SESSION['user_id']) {
    load_user_config($config, $_SESSION['user_id']);
}

///r($_SESSION);
///r($_COOKIE);
///r($permissions);

//logfile('debug_auth.log', __LINE__ . ' ' . 'auth session_commit() called at '.time());
//session_commit(); // Write and unblock current session (use session_set_var() and session_unset_var() for write to session variables!)

// EOF
