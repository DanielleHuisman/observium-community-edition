<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) Adam Armstrong
 *
 */

/* THIS IS ONE BIG VULNERABILITY! */

ini_set('allow_url_fopen', 0);

include_once("../includes/observium.inc.php");

if (!$config['web_iframe'] && is_iframe()) {
    print_error_permission("Not allowed to run in a iframe!");
    die();
}

include($config['html_dir'] . "/includes/authenticate.inc.php");

if (($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) && !$_SESSION['authenticated']) {
    // not authenticated
    die("Unauthenticated");
}

$vars = get_vars('GET');

if ($_SESSION['userlevel'] > 7) {
    include($config['install_dir'] . "/includes/weathermap/editor.php");
} else {
    echo("Unauthorised Access Prohibited.");
    exit;
}

// EOF
