<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/// FIXME. Unused anymore?

// Global write permissions required.
if ($_SESSION['userlevel'] < 10) {
    print_error_permission();
    return;
}

include($config['html_dir'] . "/includes/alerting-navbar.inc.php");

// Regenerate alerts

echo generate_box_open();


$checkers = cache_alert_rules();
$assocs   = cache_alert_assoc();

foreach ($assocs as $assoc) {
    $checkers[$assoc['alert_test_id']]['assocs'][] = $assoc;
}

foreach ($checkers as $alert) {

    echo '<h3>Updating Alert <b>' . escape_html($alert['alert_name']) . '</b></h3>';
    echo '<br />';

    //r($alert);

    update_alert_table($alert);

}

del_obs_attrib('alerts_require_rebuild');

echo generate_box_close();

unset($vars['action']);

// EOF
