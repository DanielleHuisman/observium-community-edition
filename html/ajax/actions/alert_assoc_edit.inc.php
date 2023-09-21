<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Currently edit allowed only for Admins
if (!$limitwrite) {
    print_json_status('failed', 'Action not allowed.');
    exit();
}

if ($alert_test = dbFetchRow("SELECT * FROM `alert_tests` WHERE `alert_test_id` = ?", [ $vars['alert_test_id'] ])) {

    if ($alert_test['alert_assoc'] !== $vars['alert_assoc']) {

        if (dbUpdate([ 'alert_assoc' => $vars['alert_assoc'] ], 'alert_tests', '`alert_test_id` = ?', [ $vars['alert_test_id'] ])) {
            update_alert_table($vars['alert_test_id']);
            print_json_status('ok', 'Associations updated.',
                              [ 'id'       => $vars['alert_test_id'],
                                'redirect' => generate_url([ 'page' => 'alert_check', 'alert_test_id' => $vars['alert_test_id'] ]) ]);
        } else {
            print_json_status('failed', 'Database was not updated.');
        }
    } else {
        print_json_status('warning', 'Associations not changed.');
    }
} else {
    print_json_status('failed', 'Alert Checker does not exist: [' . $vars['alert_test_id'] . ']');
}

// EOF
