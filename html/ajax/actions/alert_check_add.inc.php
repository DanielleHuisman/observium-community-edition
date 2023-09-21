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

// Currently allowed only for Admins and Limit Write
if (!$limitwrite) {
    print_json_status('failed', 'Action not allowed.');
    return;
}

$ok = TRUE;
foreach (['entity_type', 'alert_name', 'alert_severity', 'alert_conditions'] as $var) {
    if (!isset($vars[$var]) || strlen($vars[$var]) == '0') {
        $ok       = FALSE;
        $failed[] = $var;
    }
}

if ($ok) {
    if (dbExist('alert_tests', '`entity_type` = ? AND `alert_name` = ?', [$vars['entity_type'], $vars['alert_name']])) {
        print_json_status('failed', "Alert Checker '{$vars['alert_name']}' already exist.");
        return;
    }

    $check_array = [];

    $conditions = [];
    foreach (explode("\n", trim($vars['alert_conditions'])) as $cond) {
        $condition = [];
        [$condition['metric'], $condition['condition'], $condition['value']] = explode(" ", trim($cond), 3);
        $conditions[] = $condition;
    }
    $check_array['conditions']        = safe_json_encode($conditions);
    $check_array['alert_assoc']       = $vars['alert_assoc'];
    $check_array['entity_type']       = $vars['entity_type'];
    $check_array['alert_name']        = $vars['alert_name'];
    $check_array['alert_message']     = $vars['alert_message'];
    $check_array['severity']          = $vars['alert_severity'];
    $check_array['suppress_recovery'] = get_var_true($vars['alert_send_recovery']) ? 0 : 1;
    $check_array['alerter']           = NULL;
    $check_array['and']               = $vars['alert_and'];
    $check_array['delay']             = $vars['alert_delay'];
    $check_array['enable']            = '1';

    $check_id = dbInsert('alert_tests', $check_array);

    if (is_numeric($check_id)) {
        update_alert_table($check_id);

        print_json_status('ok', '', ['id' => $check_id, 'redirect' => generate_url(['page' => 'alert_check', 'alert_test_id' => $check_id])]);
    } else {

        print_json_status('failed', 'Alert creation failed. Please note that the alert name <b>must</b> be unique.');
    }
} else {

    print_json_status('failed', 'Missing required data. (' . implode(", ", $failed) . ')');
}

// EOF
