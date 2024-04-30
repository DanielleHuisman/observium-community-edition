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

if ($_SESSION['userlevel'] < 7) {
    print_error_permission();
    return;
}

include($config['html_dir'] . '/includes/alerting-navbar.inc.php');

// Begin Actions
$readonly = $_SESSION['userlevel'] < 10; // Currently edit allowed only for Admins

// Hardcode Device sysContact
if (!dbExist('alert_contacts', '`contact_method` = ?', ['syscontact'])) {
    $syscontact = [
      'contact_descr'    => 'Device sysContact',
      'contact_method'   => 'syscontact',
      'contact_endpoint' => '{"syscontact":"device"}',
      //'contact_disabled'         => '0',
      //'contact_disabled_until'   => NULL,
      //'contact_message_custom'   => 0,
      //'contact_message_template' => NULL
    ];
    dbInsert($syscontact, 'alert_contacts');
}


print_syslog_rules_table($vars);

if (isset($vars['la_id'])) {
    // Pagination
    $vars['pagination'] = TRUE;

    print_logalert_log($vars);
}

register_html_title('Syslog Rules');

// EOF
