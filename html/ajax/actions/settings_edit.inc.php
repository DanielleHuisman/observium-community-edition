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

// Edit user settings

// Allowed only for authenticated
if (!$_SESSION['authenticated'] || $_SESSION['user_id'] != $vars['user_id']) {
    print_json_status('failed', "Unauthenticated");
    //print_json_status('failed', var_export($vars, TRUE));
    return;
}

$user_id = $_SESSION['user_id'];

foreach (process_sql_vars($vars) as $param => $entry) {
    // This sets:
    // $deletes = array();
    // $sets = array();
    // $errors = array();
    // $set_attribs = array(); // set obs_attribs
    $$param = $entry;
}

$updates = 0;

// Set fields that were submitted with custom value
if (safe_count($sets)) {
    $query = 'SELECT * FROM `users_prefs` WHERE `user_id` = ?' . generate_query_values_and(array_keys($sets), 'pref');
    // Fetch current rows in config file so we know which one to UPDATE and which one to INSERT
    $in_db = [];
    foreach (dbFetchRows($query, [$user_id]) as $row) {
        $in_db[$row['pref']] = $row['value'];
    }

    foreach ($sets as $key => $value) {
        $serialize = serialize($value);
        if (!isset($in_db[$key]) || $serialize !== $in_db[$key]) {
            set_user_pref($user_id, $key, $serialize);
            $updates++;
        }
    }
}

// Delete fields that were reset to default
if (safe_count($deletes)) {
    dbDelete('users_prefs', '`user_id` = ? ' . generate_query_values_and($deletes, 'pref'), [$user_id]);
    $updates++;
}

/*
// Set obs attribs, example for syslog trigger
//r($set_attribs);
foreach ($set_attribs as $attrib => $value) {
  set_obs_attrib($attrib, $value);
}
*/
if ($updates) {
    $status  = 'ok';
    $message = "Settings updated. Please note Web UI setting takes effect only after reload the page.";

    if (safe_count($errors)) {
        $status  = 'warning';
        $message .= ' Errors: ' . implode('; ', $errors) . '.';
    }
    print_json_status($status, $message, ['reload' => TRUE]);

} elseif (safe_count($errors)) {
    $status  = 'failed';
    $message = 'Errors: ' . implode('; ', $errors) . '.';
    print_json_status($status, $message);
}

//process_sql_vars($vars);
//print_json_status('ok', var_export(process_sql_vars($vars), TRUE));
//print_json_status('ok', "Settings updated. Please note Web UI setting takes effect only after reload the page.");

// EOF
