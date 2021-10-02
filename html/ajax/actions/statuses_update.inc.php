<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage ajax
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

$rows_updated = 0;
$update_entities = [];
//r($vars);

foreach ($vars['status'] as $status_id => $status_update) {

  $update_array = [];
  if(is_entity_write_permitted('status', $status_id)) {
    $status = get_status_by_id($status_id);

    $device_id = $status['device_id'];

    $fields_switch = array('status_ignore');
    $fields_limit = array();

    // Switch selectors
    foreach ($fields_switch as $field) {
      $status_update[$field] = get_var_true($status_update[$field]) ? '1' : '0';
      if ($status_update[$field] != $status[$field]) {
        $update_array[$field] = $status_update[$field];
      }
    }

    if (count($update_array)) {
      dbUpdate($update_array, 'status', '`status_id` = ?', array($status['status_id']));
      $msg = 'Status updated (custom): ' . $status['status_type'] . ' ' . $status['status_id'] . ' ' . escape_html($status['status_descr']) . ' ';
      log_event($msg, $device_id, 'status', $status['status_id']);
      $rows_updated++;

      $update_entities[$status_id] = $update_array;
    }

    unset($update_array);

  } // End write permission check

} // end entity loop

// Query updated array
if ($rows_updated) {
  print_json_status('ok', $rows_updated.' status(es) updated.', [ 'update_array' => $update_entities ]);
} else {
  print_json_status('failed', 'No update performed.');
}

// EOF
