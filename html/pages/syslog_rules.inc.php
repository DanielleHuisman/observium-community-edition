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

if (!$readonly && isset($vars['action']) &&
    request_token_valid($vars['requesttoken'])) {
    switch ($vars['action']) {
        case 'edit_syslog_rule':
            $update_array = ['la_name'    => $vars['la_name'],
                             'la_descr'   => $vars['la_descr'],
                             'la_rule'    => $vars['la_rule'],
                             'la_disable' => (isset($vars['la_disable']) ? 1 : 0)];
            $rows_updated = dbUpdate($update_array, 'syslog_rules', '`la_id` = ?', [$vars['la_id']]);

            if ($rows_updated) {
                set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script
                print_message('Syslog Rule updated (' . $vars['la_id'] . ')');
            }
            unset($vars['la_id']);
            break;

        case 'delete_syslog_rule':
            if (get_var_true($vars['confirm'], 'confirm')) {
                $rows_deleted = dbDelete('syslog_rules_assoc', '`la_id` = ?', [$vars['la_id']]);
                $rows_deleted += dbDelete('syslog_rules', '`la_id` = ?', [$vars['la_id']]);
                $rows_deleted += dbDelete('syslog_alerts', '`la_id` = ?', [$vars['la_id']]);
                $rows_deleted += dbDelete('alert_contacts_assoc', '`aca_type` = ? AND `alert_checker_id` = ?', ['syslog', $vars['la_id']]);

                if ($rows_deleted) {
                    set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script
                    print_message('Deleted all traces of Syslog Rule (' . $vars['la_id'] . ')');
                }
            }
            unset($vars['la_id']);
            break;
    }

}

// End Actions

print_syslog_rules_table($vars);

if (isset($vars['la_id'])) {
    // Pagination
    $vars['pagination'] = TRUE;

    print_logalert_log($vars);
}

register_html_title('Syslog Rules');

// EOF
