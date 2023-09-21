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

ini_set('allow_url_fopen', 0);

include_once("../includes/observium.inc.php");

if (!$config['web_iframe'] && is_iframe()) {
    print_error_permission("Not allowed to run in a iframe!");
    die();
}

include($config['html_dir'] . "/includes/authenticate.inc.php");

if ($_SERVER['REMOTE_ADDR'] != $_SERVER['SERVER_ADDR']) {
    if (!$_SESSION['authenticated']) {
        // not authenticated
        die("Unauthenticated");
    }
}

$vars = get_vars('GET');

if ($_SESSION['userlevel'] > 7) {
    include($config['install_dir'] . "/includes/weathermap/editor.php");
} else {
    echo("Unauthorised Access Prohibited.");
    exit;
}



