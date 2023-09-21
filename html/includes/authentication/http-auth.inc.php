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

if (!$_SESSION['authenticated'] && !is_cli()) {
    if (isset($_SERVER['PHP_AUTH_USER'])) {
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];
    } elseif (isset($_SERVER['HTTP_AUTHENTICATION'])) {
        if (str_starts_with(strtolower($_SERVER['HTTP_AUTHENTICATION']), 'basic')) {
            [ $username, $password ] = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
        }
    }

    if ($_SESSION['relogin'] || empty($username) || !mysql_authenticate($username, $password)) {
        http_auth_require_login();
    } else {
        session_set_var('username', $username);
        session_set_var('password', $password);
    }
}

/**
 * This function forces a login prompt via basic HTTP authentication by making the browser believe
 * the authentication has failed. Required to log out a basic HTTP auth session.
 */
function http_auth_require_login()
{
    $realm = escape_html($GLOBALS['config']['login_message']);
    header('WWW-Authenticate: Basic realm="' . $realm . '"');
    header('HTTP/1.1 401 Unauthorized');

    print_error_permission();

    session_logout();
    die();
}

// EOF
