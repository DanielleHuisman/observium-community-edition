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

if (!$readwrite) { // Only valid forms from level 10 users
    return;
}

switch ($vars['action']) {

    case 'syslog_rule_edit':
        $update_array = [ 'la_name'    => $vars['la_name'],
                          'la_descr'   => $vars['la_descr'],
                          'la_rule'    => $vars['la_rule'],
                          'la_disable' => (isset($vars['la_disable']) ? 1 : 0) ];
        $rows_updated = dbUpdate($update_array, 'syslog_rules', '`la_id` = ?', [$vars['la_id']]);

        if ($rows_updated) {
            set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script
            print_message('Syslog Rule updated (' . $vars['la_id'] . ')');
        }
        break;

    case 'syslog_rule_delete':
        if (get_var_true($vars['confirm'], 'confirm')) {
            $rows_deleted = dbDelete('syslog_rules_assoc', '`la_id` = ?', [$vars['la_id']]);
            $rows_deleted += dbDelete('syslog_rules', '`la_id` = ?', [$vars['la_id']]);
            $rows_deleted += dbDelete('syslog_alerts', '`la_id` = ?', [$vars['la_id']]);
            $rows_deleted += dbDelete('alert_contacts_assoc', '`aca_type` = ? AND `alert_checker_id` = ?', ['syslog', $vars['la_id']]);

            if ($rows_deleted) {
                set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script
                print_message('Deleted all traces of Syslog Rule (' . $vars['la_id'] . ')');
            }
            unset($vars['la_id']);
        }
        break;
}

unset($vars['action'], $vars['confirm'], $vars['requesttoken']);

// EOF
