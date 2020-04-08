<?php

//print_r($_SERVER);

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

// All simple, OBS_API set to TRUE only in API index.php
if (!defined('OBS_API'))
{
  define('OBS_API', FALSE);
}

$debug_auth = FALSE; // Do not use this debug unless you Observium Developer ;)

if (version_compare(PHP_VERSION, '7.1.0', '<'))
{
  // Use sha1 to generate the session ID (option removed in php 7.1)
  // session.sid_length (Number of session ID characters - 22 to 256.
  // session.sid_bits_per_character (Bits used per character - 4 to 6.
  @ini_set('session.hash_function', '1');
}
@ini_set('session.referer_check', '');     // This config was causing so much trouble with Chrome
@ini_set('session.name', 'OBSID');         // Session name
@ini_set('session.use_cookies', '1');      // Use cookies to store the session id on the client side
@ini_set('session.use_only_cookies', '1'); // This prevents attacks involved passing session ids in URLs
@ini_set('session.use_trans_sid', '0');    // Disable SID (no session id in url)

$currenttime     = time();
$lifetime        = 60*60*24;                   // Session lifetime (default one day)
$cookie_expire   = $currenttime + 60*60*24*14; // Cookies expire time (14 days)
$cookie_path     = '/';                        // Cookie path
$cookie_domain   = '';                         // RFC 6265, to have a "host-only" cookie is to NOT set the domain attribute.
/// FIXME. Some old browsers not supports secure/httponly cookies params.
$cookie_https    = is_ssl();
$cookie_httponly = TRUE;

// Use custom session lifetime
if (is_numeric($GLOBALS['config']['web_session_lifetime']) && $GLOBALS['config']['web_session_lifetime'] >= 0)
{
  $lifetime = intval($GLOBALS['config']['web_session_lifetime']);
}

@ini_set('session.gc_maxlifetime',  $lifetime); // Session lifetime (for non "remember me" sessions)
session_set_cookie_params($lifetime, $cookie_path, $cookie_domain, $cookie_https, $cookie_httponly);
//session_cache_limiter('private');

//register_shutdown_function('session_commit'); // This already done at end of script run
if (!session_is_active())
{
  //logfile('debug_auth.log', 'session_start() called at '.$currenttime);
  session_commit(); // Write and close current session

  //session_start(); // session starts in session_regenerate too!
  session_regenerate(); // Note, use this function after session_start() and before next session_commit()!
  /*
  if (isset($_SESSION['starttime']))
  {
    if ($currenttime - $_SESSION['starttime'] >= $lifetime_id && !is_graph())
    {
      logfile('debug_auth.log', 'session_regenerate_id() called at '.$currenttime);
      // ID Lifetime expired, regenerate
      session_regenerate_id(TRUE);
      // Clean cache from _SESSION first, this cache used in ajax calls
      if (isset($_SESSION['cache'])) { unset($_SESSION['cache']); }
      $_SESSION['starttime'] = $currenttime;
    }
  } else {
    $_SESSION['starttime']   = $currenttime;
  }
  */

  //if (!is_graph())
  //{
  //  print_vars($vars); print_vars($_SESSION); print_vars($_COOKIE);
  //}
}

// Fallback to MySQL auth as default - FIXME do this in sqlconfig file?
if (!isset($config['auth_mechanism']))
{
  $config['auth_mechanism'] = "mysql";
}

// Trust Apache authenticated user, if configured to do so and username is available
if ($config['auth']['remote_user'] && $_SERVER['REMOTE_USER'] != '')
{
  session_set_var('username', $_SERVER['REMOTE_USER']);
}

$auth_file = $config['html_dir'].'/includes/authentication/' . $config['auth_mechanism'] . '.inc.php';
if (is_file($auth_file))
{
  if (isset($_SESSION['auth_mechanism']) && $_SESSION['auth_mechanism'] != $config['auth_mechanism'])
  {
    // Logout if AUTH mechanism changed
    session_logout();
    reauth_with_message('Authentication mechanism changed, please log in again!');
  } else {
    session_set_var('auth_mechanism', $config['auth_mechanism']);
  }

  // Always load mysql as backup
  include($config['html_dir'].'/includes/authentication/mysql.inc.php');

  // Load primary module if not mysql
  if ($config['auth_mechanism'] != 'mysql') { include($auth_file); }

  // Include base auth functions calls
  include($config['html_dir'].'/includes/authenticate-functions.inc.php');
} else {
  session_logout();
  reauth_with_message('Invalid auth_mechanism defined, please correct your configuration!');
}

// Check logout
if ($_SESSION['authenticated'] && str_starts(ltrim($_SERVER['REQUEST_URI'], '/'), 'logout'))
{
  // Do not use $vars and get_vars here!
  //print_vars($_SERVER['REQUEST_URI']);
  if (auth_can_logout())
  {
    // No need for a feedback message if user requested a logout
    session_logout(function_exists('auth_require_login'));

    $redirect = auth_logout_url();
    if ($redirect)
    {
      redirect_to_url($redirect);
      exit();
    }
  }
  redirect_to_url($config['base_url']);
  exit();
}

$user_unique_id = session_unique_id(); // Get unique user id and check if IP changed (if required by config)

// Store logged remote IP with real proxied IP (if configured and avialable)
$remote_addr = get_remote_addr();
$remote_addr_header = get_remote_addr(TRUE); // Remote addr by http header
if ($remote_addr_header && $remote_addr != $remote_addr_header)
{
  $remote_addr = $remote_addr_header . ' (' . $remote_addr . ')';
}

// Check if allowed auth by CIDR
$auth_allow_cidr = TRUE;
if (isset($config['web_session_cidr']) && count($config['web_session_cidr']))
{
  $auth_allow_cidr = match_network(get_remote_addr($config['web_session_ip_by_header']), $config['web_session_cidr']);
}

if (!$_SESSION['authenticated'] && isset($_GET['username']) && isset($_GET['password']))
{
  session_set_var('username', $_GET['username']);
  $auth_password        = $_GET['password'];
}
else if (!$_SESSION['authenticated'] && isset($_POST['username']) && isset($_POST['password']))
{
  session_set_var('username', $_POST['username']);
  $auth_password        = $_POST['password'];
}
else if (!$_SESSION['authenticated'] && isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
{
  session_set_var('username', $_SERVER['PHP_AUTH_USER']);
  $auth_password        = $_SERVER['PHP_AUTH_PW'];
}
else if (OBS_ENCRYPT && !$_SESSION['authenticated'] && isset($_COOKIE['ckey']))
{
    ///DEBUG
    if ($debug_auth)
    {
      $debug_log = $GLOBALS['config']['log_dir'].'/debug_cookie_'.date("Y-m-d_H:i:s").'.log';
      file_put_contents($debug_log, var_export($_SERVER,  TRUE), FILE_APPEND);
      file_put_contents($debug_log, var_export($_SESSION, TRUE), FILE_APPEND);
      file_put_contents($debug_log, var_export($_COOKIE,  TRUE), FILE_APPEND);
    }

  $ckey = dbFetchRow("SELECT * FROM `users_ckeys` WHERE `user_uniq` = ? AND `user_ckey` = ? LIMIT 1",
                          array($user_unique_id, $_COOKIE['ckey']));
  if (is_array($ckey))
  {
    if ($ckey['expire'] > $currenttime && $auth_allow_cidr)
    {
      session_set_var('username', $ckey['username']);
      $auth_password            = decrypt($ckey['user_encpass'], $_COOKIE['dkey']);

      // Store encrypted password
      session_encrypt_password($auth_password, $user_unique_id);

      // If userlevel == 0 - user disabled an can not be logon
      if (auth_user_level($ckey['username']) < 1)
      {
        session_logout(FALSE, 'User disabled');
	reauth_with_message('User login disabled');
      }

      session_set_var('user_ckey_id', $ckey['user_ckey_id']);
      session_set_var('cookie_auth', TRUE);
      dbInsert(array('user'       => $_SESSION['username'],
                     'address'    => $remote_addr,
                     'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                     'result'     => 'Logged In (cookie)'), 'authlog');

    }
  }
}

if ($_COOKIE['password']) { setcookie("password", NULL); }
if ($_COOKIE['username']) { setcookie("username", NULL); }
if ($_COOKIE['user_id'] ) { setcookie("user_id",  NULL); }

$auth_success = FALSE; // Variable for check if just logged

if (isset($_SESSION['username']))
{
  // Check for allowed by CIDR range
  if (!$auth_allow_cidr)
  {
    session_logout(FALSE, 'Remote IP not allowed in CIDR ranges');
    reauth_with_message('Remote IP not allowed in CIDR ranges');
  }

  // Auth from COOKIEs
  if ($_SESSION['cookie_auth'])
  {
    @session_start();
    $_SESSION['authenticated'] = TRUE;
    $auth_success              = TRUE;
    dbUpdate(array('expire' => $cookie_expire), 'users_ckeys', '`user_ckey_id` = ?', array($_SESSION['user_ckey_id']));
    unset($_SESSION['user_ckey_id'], $_SESSION['cookie_auth']);
    session_commit();
  }

  // Auth from ...
  if (!$_SESSION['authenticated'] && (authenticate($_SESSION['username'], $auth_password) ||                       // login/password
                                     (auth_usermanagement() && auth_user_level($_SESSION['origusername']) >= 10))) // FIXME?
  {
    // If we get here, it means the password for the user was correct (authenticate() called)
    // Store encrypted password
    session_encrypt_password($auth_password, $user_unique_id);


    // If userlevel == 0 - user disabled and can not log in
    if (auth_user_level($_SESSION['username']) < 1)
    {
      session_logout(FALSE, 'User disabled');
      reauth_with_message('User login disabled');
      exit();
    }

    session_set_var('authenticated', TRUE);
    $auth_success              = TRUE;
    dbInsert(array('user'       => $_SESSION['username'],
                   'address'    => $remote_addr,
                   'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                   'result'     => 'Logged In'), 'authlog');
    // Generate keys for cookie auth
    if (isset($_POST['remember']) && OBS_ENCRYPT)
    {
      $ckey = md5(strgen());
      $dkey = md5(strgen());
      $encpass = encrypt($auth_password, $dkey);
      dbDelete('users_ckeys', "`username` = ? AND `expire` < ?", array($_SESSION['username'], $currenttime - 3600)); // Remove old ckeys from DB
      dbInsert(array('user_encpass' => $encpass,
                     'expire'       => $cookie_expire,
                     'username'     => $_SESSION['username'],
                     'user_uniq'    => $user_unique_id,
                     'user_ckey'    => $ckey), 'users_ckeys');
      setcookie("ckey", $ckey, $cookie_expire, $cookie_path, $cookie_domain, $cookie_https, $cookie_httponly);
      setcookie("dkey", $dkey, $cookie_expire, $cookie_path, $cookie_domain, $cookie_https, $cookie_httponly);
      session_unset_var('user_ckey_id');
    }
  }
  else if (!$_SESSION['authenticated'])
  {
    // Not authenticated
    session_set_var('auth_message', 'Authentication Failed');
    session_logout(function_exists('auth_require_login'));
  }

  // Retrieve user ID and permissions
  if ($_SESSION['authenticated'])
  {
    @session_start();
    if (!is_numeric($_SESSION['userlevel']) || !is_numeric($_SESSION['user_id']))
    {
      $_SESSION['userlevel'] = auth_user_level($_SESSION['username']);
      $_SESSION['user_id']   = auth_user_id($_SESSION['username']);
    }

    $level_permissions = auth_user_level_permissions($_SESSION['userlevel']);
    // If userlevel == 0 - user disabled an can not be logon
    if (!$level_permissions['permission_access'])
    {
      session_logout(FALSE, 'User disabled');
      reauth_with_message('User login disabled');
      exit();
    }
    else if (!isset($_SESSION['user_limited']) || $_SESSION['user_limited'] != $level_permissions['limited'])
    {
      // Store user limited flag, required for quick permissions list generate
      $_SESSION['user_limited'] = $level_permissions['limited'];
    }
    session_commit();

    // Generate a CSRF Token
    // https://stackoverflow.com/questions/6287903/how-to-properly-add-csrf-token-using-php
    // For validate use: request_token_valid($vars['requesttoken'])
    if (empty($_SESSION['requesttoken']))
    {
      session_set_var('requesttoken', bin2hex(random_bytes(32)));
    }

    // Now we can enable debug if user is privileged (Global Secure Read and greater)
    if (!defined('OBS_DEBUG')) // OBS_DEBUG not defined by default for unprivileged users in definitions
    {
      if ($_SESSION['userlevel'] < 7 || !$debug_web_requested) // && !$config['permit_user_debug']) // Note, use $config['web_debug_unprivileged'] = TRUE;
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

    //$a = utime();
    $permissions = permissions_cache($_SESSION['user_id']);
    //echo utime() - $a . 's for permissions cache';


    // Add feeds & api keys after first auth
    if (OBS_ENCRYPT && !get_user_pref($_SESSION['user_id'], 'atom_key'))
    {
      // Generate unique token
      do
      {
        $atom_key = md5(strgen());
      }
      //while (dbFetchCell("SELECT COUNT(*) FROM `users_prefs` WHERE `pref` = ? AND `value` = ?;", array('atom_key', $atom_key)) > 0);
      while (dbExist('users_prefs', '`pref` = ? AND `value` = ?', array('atom_key', $atom_key)));
      set_user_pref($_SESSION['user_id'], 'atom_key', $atom_key);
    }
  }

  if ($auth_success && !OBS_API)
  {
    // If just logged in go to request uri, unless we're debugging or in API,
    // in which case we want to see authentication module output first.
    if (!OBS_DEBUG)
    {
      redirect_to_url($_SERVER['REQUEST_URI']);
    } else {
      print_message("Debugging mode has disabled redirect to front page; please click <a href=\"" . $_SERVER['REQUEST_URI'] . "\">here</a> to continue.");
    }
    exit();
  }
}

///r($_SESSION);
///r($_COOKIE);
///r($permissions);

//logfile('debug_auth.log', 'auth session_commit() called at '.time());
//session_commit(); // Write and unblock current session (use session_set_var() and session_unset_var() for write to session variables!)

/* Session manager specific functions */

// DOCME needs phpdoc block
function session_is_active()
{
  if (!is_cli())
  {
    if (version_compare(PHP_VERSION, '5.4.0', '>='))
    {
      return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
    } else {
      return session_id() === '' ? FALSE : TRUE;
    }
  }
  return FALSE;
}

/**
 * Generate unique id for current user/browser, based on some unique params
 *
 * @return string
 */
function session_unique_id()
{
  global $debug_auth;

  $id  = $_SERVER['HTTP_USER_AGENT']; // User agent
  //$id .= $_SERVER['HTTP_ACCEPT'];     // Browser accept headers // WTF, this header different for main and js/ajax queries
  // Less entropy than HTTP_ACCEPT, but stable!
  $id .= $_SERVER['HTTP_ACCEPT_ENCODING'];
  $id .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
  
  if ($GLOBALS['config']['web_session_ip'])
  {
    $remote_addr = get_remote_addr($config['web_session_ip_by_header']); // Remote address by header if configured
    $id .= $remote_addr;   // User IP address

    // Force reauth if remote IP changed
    if ($_SESSION['authenticated'])
    {
      if (isset($_SESSION['PREV_REMOTE_ADDR']) && $remote_addr != $_SESSION['PREV_REMOTE_ADDR'])
      {
        unset($_SESSION['authenticated'],
              $_SESSION['user_id'],
              $_SESSION['username'],
              $_SESSION['user_encpass'], $_SESSION['password'],
              $_SESSION['userlevel']);
      }
      session_set_var('PREV_REMOTE_ADDR', $remote_addr); // Store current remote IP
    }
  }

  // Next required JS cals:
  // resolution = screen.width+"x"+screen.height+"x"+screen.colorDepth;
  // timezone   = new Date().getTimezoneOffset();
  if ($debug_auth)
  {
    $debug_log_array = array(md5($id), $remote_addr, $_SERVER['HTTP_USER_AGENT'],
                             $_SERVER['HTTP_ACCEPT_ENCODING'], $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                             $_COOKIE['OBSID']);
    logfile('debug_auth.log', json_encode($debug_log_array));
  }

  return md5($id);
}

/**
 * Store encrypted password in $_SESSION['user_encpass'], required for some auth mechanism, ie ldap
 *
 * @param  string $auth_password Plain password
 * @param  string $key           Key for password encrypt
 * @return string                Encrypted password
 */
function session_encrypt_password($auth_password, $key)
{
  // Store encrypted password
  if ($GLOBALS['config']['auth_mechanism'] == 'ldap' &&
      !($GLOBALS['config']['auth_ldap_bindanonymous'] || strlen($GLOBALS['config']['auth_ldap_binddn'].$GLOBALS['config']['auth_ldap_bindpw'])))
  {
    if (OBS_ENCRYPT)
    {
      if (OBS_ENCRYPT_MODULE == 'mcrypt')
      {
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

// DOCME needs phpdoc block
function session_logout($relogin = FALSE, $message = NULL)
{
  global $debug_auth;

  // Save auth failure message for later re-use
  $auth_message = $_SESSION['auth_message'];

  if ($_SESSION['authenticated'])
  {
    $auth_log = 'Logged Out';
  } else {
    $auth_log = 'Authentication Failure';
  }
  if ($message)
  {
    $auth_log .= ' (' . $message . ')';
  }
  if ($debug_auth)
  {
    $debug_log = $GLOBALS['config']['log_dir'].'/'.date("Y-m-d_H:i:s").'.log';
    file_put_contents($debug_log, var_export($_SERVER,  TRUE), FILE_APPEND);
    file_put_contents($debug_log, var_export($_SESSION, TRUE), FILE_APPEND);
    file_put_contents($debug_log, var_export($_COOKIE,  TRUE), FILE_APPEND);
  }

  // Store logged remote IP with real proxied IP (if configured and avialable)
  $remote_addr = get_remote_addr();
  $remote_addr_header = get_remote_addr(TRUE); // Remote addr by http header
  if ($remote_addr_header && $remote_addr != $remote_addr_header)
  {
    $remote_addr = $remote_addr_header . ' (' . $remote_addr . ')';
  }
  dbInsert(array('user'       => $_SESSION['username'],
                 'address'    => $remote_addr,
                 'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                 'result'     => $auth_log), 'authlog');
  if (isset($_COOKIE['ckey'])) dbDelete('users_ckeys', "`username` = ? AND `user_ckey` = ?", array($_SESSION['username'], $_COOKIE['ckey'])); // Remove old ckeys from DB
  // Unset cookies
  $cookie_params = session_get_cookie_params();
  $past = time() - 3600;
  foreach ($_COOKIE as $cookie => $value)
  {
    if (empty($cookie_params['domain']))
    {
      setcookie($cookie, '', $past, $cookie_params['path']);
    } else {
      setcookie($cookie, '', $past, $cookie_params['path'], $cookie_params['domain']);
    }
  }
  unset($_COOKIE);

  // Clean cache if possible
  $cache_tags = array('__anonymous');
  if ($_SESSION['authenticated'])
  {
    $cache_tags = array('__username='.$_SESSION['username']);
  }
  del_cache_items($cache_tags);

  // Unset session
  @session_start();
  if ($relogin)
  {
    // Reset session and relogin (for example: HTTP auth)
    $_SESSION['relogin'] = TRUE;
    unset($_SESSION['authenticated'],
          $_SESSION['user_id'],
          $_SESSION['username'],
          $_SESSION['user_encpass'], $_SESSION['password'],
          $_SESSION['userlevel']);
    session_commit();
    session_regenerate_id(TRUE);
  } else {
    // Kill current session, as authentication failed
    unset($_SESSION);
    session_unset();
    session_destroy();
    session_commit();
    //setcookie(session_name(),'',0,'/');
    session_regenerate_id(TRUE);
    // Re-set auth failure message for use on login page
    //session_start();
    $_SESSION['auth_message'] = $message;
  }
}

/**
 * Regenerate session ID for prevent attacks session hijacking and session fixation.
 * Note, use this function after session_start() and before next session_commit()!
 *
 * @param int $lifetime_id Time in seconds for next regenerate session ID (default 30 min)
 */
function session_regenerate($lifetime_id = 1800)
{
  session_start();

  $currenttime   = time();
  if ($lifetime_id != 1800 && is_numeric($lifetime_id) && $lifetime_id >= 300)
  {
    $lifetime_id = intval($lifetime_id);
  } else {
    $lifetime_id = 1800;
  }

  if (isset($_SESSION['starttime']))
  {
    if ($currenttime - $_SESSION['starttime'] >= $lifetime_id && !is_graph())
    {
      //logfile('debug_auth.log', 'session_regenerate_id() called at '.$currenttime);
      // ID Lifetime expired, regenerate
      session_regenerate_id(TRUE);
      // Clean cache from _SESSION first, this cache used in ajax calls
      if (isset($_SESSION['cache'])) { unset($_SESSION['cache']); }
      $_SESSION['starttime'] = $currenttime;
    }
  } else {
    $_SESSION['starttime']   = $currenttime;
  }

  session_commit();
}

/**
 * Use this function for write to $_SESSION global var.
 * And prevent session blocking.
 * If value is NULL, this session variable will unset
 *
 * @param string $var
 * @param mixed $value
 */
function session_set_var($var, $value)
{
  //logfile('debug_auth.log', 'session_set_var() called at '.time());
  if (isset($_SESSION[$var]) && $_SESSION[$var] === $value) { return; } // Just return if session var unchanged

  @session_start(); // Unblock session again

  if (is_null($value))
  {
    unset($_SESSION[$var]);
  } else {
    $_SESSION[$var] = $value;
  }

  session_commit(); // Write and block session
}

function session_unset_var($var)
{
  session_set_var($var, NULL);
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

  if ($config['auth_mechanism'] == 'remote')
  {
    print('<h1>' . $message . '</h1>');
  } else {
    session_set_var('auth_message', $message);
    redirect_to_url($config['base_url']);
  }
  exit();
}

// EOF
