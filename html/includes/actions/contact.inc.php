<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Secure Write Actions
if (!$securewrite) {
    return;
}
switch ($vars['action']) {
    case 'contact_alert_checker_add': // new ([target]_[type]_[action])

        if (safe_empty($vars['contact_id'])) {
            return;
        }
        if (is_numeric($vars['alert_checker_id'])) {
            $vars['alert_test_id'] = $vars['alert_checker_id'];
        } elseif (!is_numeric($vars['alert_test_id'])) {
            return;
        }

        $rows_updated = 0;
        foreach ((array)$vars['contact_id'] as $contact_id) {
            if (!is_numeric($contact_id)) {
                continue;
            }

            $id = dbInsert('alert_contacts_assoc', [ 'aca_type'         => 'alert', // $vars['type']
                                                     'contact_id'       => $contact_id,
                                                     'alert_checker_id' => $vars['alert_test_id'] ]);
            if ($id) {
                $rows_updated++;
            }
        }

        return $rows_updated;

    case 'contact_alert_checker_addall':

        if (!is_numeric($vars['alert_test_id']) || !$vars['confirm_add_all']) {
            return;
        }
        $exist_contacts = dbFetchColumn('SELECT `contact_id` FROM `alert_contacts_assoc` WHERE `aca_type` = ? AND `alert_checker_id` = ?', [ 'alert', $vars['alert_test_id'] ]);
        //print_vars($exist_contacts);
        $sql = "SELECT `contact_id` FROM `alert_contacts` WHERE `contact_disabled` = 0 AND `contact_method` != 'syscontact'" .
            generate_query_values_and($exist_contacts, 'contact_id', '!='); // exclude exist contacts
        //print_vars($sql);
        $rows_updated = 0;
        foreach (dbFetchColumn($sql) as $contact_id) {
            $id = dbInsert('alert_contacts_assoc', [ 'aca_type'         => 'alert',
                                                     'contact_id'       => $contact_id,
                                                     'alert_checker_id' => $vars['alert_test_id'] ]);
            if ($id) {
                $rows_updated++;
            }
        }

        return $rows_updated;

    case 'contact_alert_checker_delete':

        if (safe_empty($vars['contact_id'])) {
            return;
        }
        if (!is_numeric($vars['alert_test_id'])) {
            return;
        }
        $rows_updated = 0;
        foreach ((array)$vars['contact_id'] as $contact_id) {
            if (!is_numeric($contact_id)) {
                continue;
            }

            $rows_updated += dbDelete('alert_contacts_assoc', '`aca_type` = ? AND `contact_id` = ? AND `alert_checker_id` = ?', [ 'alert', $contact_id, $vars['alert_test_id'] ]);
        }

        return $rows_updated;

    case 'contact_alert_checker_deleteall':
        if (!is_numeric($vars['alert_test_id']) || !$vars['confirm_delete_all']) {
            return;
        }

        return dbDelete('alert_contacts_assoc', '`aca_type` = ? AND `alert_checker_id` = ?', [ 'alert', $vars['alert_test_id'] ]);

    case 'contact_syslog_rule_add':
        if (!is_numeric($vars['la_id']) || safe_empty($vars['contact_id'])) {
            return;
        }
        $rows_updated = 0;
        foreach ((array)$vars['contact_id'] as $contact_id) {
            $id = dbInsert('alert_contacts_assoc', [ 'aca_type'         => 'syslog', // $vars['type']
                                                     'contact_id'       => $vars['contact_id'],
                                                     'alert_checker_id' => $vars['la_id'] ]);
            if ($id) {
                $rows_updated++;
            }
        }

        if ($rows_updated) {
            set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script
        }

        return $rows_updated;

    case 'contact_syslog_rule_delete':
        if (!is_numeric($vars['la_id']) || safe_empty($vars['contact_id'])) {
            return;
        }
        $rows_updated = 0;
        foreach ((array)$vars['contact_id'] as $contact_id) {
            if (!is_numeric($contact_id)) {
                continue;
            }

            $rows_updated += dbDelete('alert_contacts_assoc', '`aca_type` = ? AND `contact_id` = ? AND `alert_checker_id` = ?', [ 'syslog', $contact_id, $vars['la_id'] ]);
        }

        if ($rows_updated) {
            set_obs_attrib('syslog_rules_changed', time()); // Trigger reload syslog script
        }

        return $rows_updated;
}

// Admin only actions
if (!$readwrite) { // Only valid forms from level 10 users
    return;
}
switch ($vars['action']) {

    case 'contact_add':

        // Only proceed if the contact_method is valid in our transports array
        if (is_array($config['transports'][$vars['contact_method']])) {
            foreach ($config['transports'][$vars['contact_method']]['parameters'] as $section => $parameters) {
                foreach ($parameters as $parameter => $param_data) {
                    if (isset($vars['contact_' . $vars['contact_method'] . '_' . $parameter])) {

                        $value = smart_quotes($vars['contact_' . $vars['contact_method'] . '_' . $parameter]);
                        // Validate if passed correct JSON
                        if ($param_data['format'] === 'json' && !valid_json_notification($value)) {
                            // Incorrect JSON
                            print_error('Contact not added. Incorrect JSON.');
                            break 2;
                        }
                        $endpoint_data[$parameter] = $value;
                    }
                }
            }

            if ($endpoint_data) {
                dbInsert('alert_contacts', ['contact_descr' => $vars['contact_descr'], 'contact_endpoint' => safe_json_encode($endpoint_data), 'contact_method' => $vars['contact_method']]);
            }
        }
        break;

    case 'contact_edit':

        $update_state = [];
        $contact      = get_contact_by_id($vars['contact_id']);

        foreach (safe_json_decode($contact['contact_endpoint']) as $field => $value) {
            $contact['endpoint_parameters'][$field] = $value;
        }

        $update_state['contact_disabled'] = get_var_true($vars['contact_enabled']) ? 0 : 1;

        if (!safe_empty($vars['contact_descr']) && $vars['contact_descr'] != $contact['contact_descr']) {
            $update_state['contact_descr'] = $vars['contact_descr'];
        }

        $data = $config['transports'][$contact['contact_method']];
        if (!safe_count($data['parameters']['global'])) {
            // Temporary until we separate "global" out.
            $data['parameters']['global'] = [];
        }
        if (!safe_count($data['parameters']['optional'])) {
            $data['parameters']['optional'] = [];
        }
        // Plan: add defaults for transport types to global settings, which we use by default, then be able to override the settings via this GUI
        // This needs supporting code in the transport to check for set variable and if not, use the global default

        $update_endpoint = $contact['endpoint_parameters'];
        foreach (array_merge((array)$data['parameters']['required'],
                             (array)$data['parameters']['global'],
                             (array)$data['parameters']['optional']) as $parameter => $param_data) {
            if ((isset($data['parameters']['optional'][$parameter]) || // Allow optional param as empty
                    is_array($vars['contact_endpoint_' . $parameter]) || strlen($vars['contact_endpoint_' . $parameter])) &&
                smart_quotes($vars['contact_endpoint_' . $parameter]) != $contact['endpoint_parameters'][$parameter]) {

                $value = smart_quotes($vars['contact_endpoint_' . $parameter]);
                // Validate if passed correct JSON
                if ($param_data['format'] === 'json' && !valid_json_notification($value)) {
                    //r($value);
                    //r($param_data);
                    // Incorrect JSON
                    print_error('Contact not updated. Incorrect JSON.');
                    break 2;
                }
                $update_endpoint[$parameter] = $value;
            }
        }
        //r($update_endpoint);
        $update_endpoint = safe_json_encode($update_endpoint);
        if ($update_endpoint != $contact['contact_endpoint']) {
            //r($update_endpoint);
            //r($contact['contact_endpoint']);
            $update_state['contact_endpoint'] = $update_endpoint;
        }

        // custom template
        $vars['contact_message_custom'] = get_var_true($vars['contact_message_custom']);
        if ($vars['contact_message_custom'] != (bool)$contact['contact_message_custom']) {
            $update_state['contact_message_custom'] = $vars['contact_message_custom'] ? '1' : '0';
        }
        if ($vars['contact_message_custom'] && $vars['contact_message_template'] != $contact['contact_message_template']) {
            $update_state['contact_message_template'] = $vars['contact_message_template'];
        }
        //r($contact);
        //r($vars);

        if ($rows_updated = dbUpdate($update_state, 'alert_contacts', 'contact_id = ?', [$vars['contact_id']])) {
            print_success('Contact updated.');
        }
        break;

    case 'contact_delete':
        if (get_var_true($vars['confirm_' . $vars['contact_id']], 'confirm')) {
            $rows_deleted = dbDelete('alert_contacts', '`contact_id` = ?', [$vars['contact_id']]);
            $rows_deleted += dbDelete('alert_contacts_assoc', '`contact_id` = ?', [$vars['contact_id']]);

            if ($rows_deleted) {
                print_success('Deleted contact and all associations (' . $vars['contact_id'] . ')');
            }
        }
        unset($vars['contact_id']);
        break;

}

unset($vars['action'], $vars['confirm'], $vars['confirm_' . $vars['contact_id']], $vars['requesttoken']);

// EOF