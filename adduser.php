#!/usr/bin/env php
<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     cli
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

chdir(dirname($argv[0]));

$options = getopt("d");
if (isset($options['d'])) {
    array_shift($argv);
} // for compatibility

include("includes/observium.inc.php");

print_message("%g" . OBSERVIUM_PRODUCT . " " . OBSERVIUM_VERSION . "\n%WAdd User%n\n", 'color');

if (OBS_DEBUG) {
    print_versions();
}

$auth_file = $config['html_dir'] . '/includes/authentication/' . $config['auth_mechanism'] . '.inc.php';
if (is_file($auth_file)) {
    // Include base auth functions calls
    include_once($config['html_dir'] . '/includes/sessions.inc.php');
    include_once($config['html_dir'] . '/includes/authenticate-functions.inc.php');

    include_once($auth_file);
} else {
    print_error("ERROR: no valid auth_mechanism defined.");
    exit();
}

if (auth_usermanagement()) {
    if (isset($argv[1]) && isset($argv[2]) && isset($argv[3])) {
        if (!auth_user_exists($argv[1])) {
            if (adduser($argv[1], $argv[2], $argv[3], @$argv[4])) {
                print_success("User " . $argv[1] . " added successfully.");
            } else {
                print_error("User " . $argv[1] . " creation failed!");
            }
        } else {
            print_warning("User " . $argv[1] . " already exists!");
        }
    } else {
        $msg = "%n
USAGE:
$scriptname <username> <password> <level 1-10> [email]

EXAMPLE:
%WADMIN%n:   $scriptname <username> <password> 10 [email]

USER LEVELS:" . PHP_EOL;

        foreach ($GLOBALS['config']['user_level'] as $level => $entry) {
            $msg .= '  ' . $level . ' - %W' . $entry['name'] . '%n (' . $entry['subtext'] . ')' . PHP_EOL;
        }
        $msg .= PHP_EOL . "%rInvalid arguments!%n";

        print_message($msg, 'color', FALSE);
    }
} else {
    print_error("Auth module does not allow adding users!");
}

// EOF
