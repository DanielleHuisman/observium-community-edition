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

foreach ($vars['sensors'] as $sensor_id => $sensor_update) {

  $update_array = [];
  if (is_entity_write_permitted('sensor', $sensor_id)) {

    $sensor = get_sensor_by_id($sensor_id);

    $device_id = $sensor['device_id'];

    if (!$sensor['sensor_state']) {
      // Normal sensors
      $fields_switch = [ 'sensor_ignore', 'sensor_custom_limit' ];
      $fields_limit  = [ 'sensor_limit', 'sensor_limit_warn', 'sensor_limit_low_warn', 'sensor_limit_low' ];

    } else {
      // State sensors not allow edit limits
      $fields_switch = array('sensor_ignore');
      $fields_limit = array();
    }

    // Switch selectors
    foreach ($fields_switch as $field) {
      $sensor_update[$field] = get_var_true($sensor_update[$field]) ? '1' : '0';
      if ($sensor_update[$field] != $sensor[$field]) {
        $update_array[$field] = $sensor_update[$field];
      }
    }

    // Limits
    if ($sensor_update['sensor_reset_limit']) {
      // Reset limits
      if ($sensor['sensor_custom_limit']) {
        $update_array['sensor_custom_limit'] = '0';
      }
      $update_array['sensor_limit_low']      = [ 'NULL' ];
      $update_array['sensor_limit_low_warn'] = [ 'NULL' ];
      $update_array['sensor_limit_warn']     = [ 'NULL' ];
      $update_array['sensor_limit']          = [ 'NULL' ];
    } elseif ($sensor_update['sensor_custom_limit']) {
      foreach ($fields_limit as $field) {
        $sensor_update[$field] = !is_numeric($sensor_update[$field]) ? [ 'NULL' ] : (float)$sensor_update[$field];
        $sensor[$field]        = !is_numeric($sensor[$field])        ? [ 'NULL' ] : (float)$sensor[$field];
        if ($sensor_update[$field] !== $sensor[$field]) {
          $update_array[$field] = $sensor_update[$field];
        }
      }
    }

    if (count($update_array)) {
      dbUpdate($update_array, 'sensors', '`sensor_id` = ?', array($sensor['sensor_id']));
      $msg = 'Sensor updated (custom): ' . $sensor['sensor_class'] . ' ' . $sensor['sensor_type'] . ' ' . $sensor['sensor_id'] . ' ' . escape_html($sensor['sensor_descr']) . ' ';
      if ($update_array['sensor_limit_low']) {
          $msg .= '[L: ' . $update_array['sensor_limit_low'] . ']';
      }
      if ($update_array['sensor_limit_low_warn']) {
          $msg .= '[Lw: ' . $update_array['sensor_limit_low_warn'] . ']';
      }
      if ($update_array['sensor_limit_warn']) {
          $msg .= '[Hw: ' . $update_array['sensor_limit_warn'] . ']';
      }
      if ($update_array['sensor_limit']) {
          $msg .= '[H: ' . $update_array['sensor_limit'] . ']';
      }
      log_event($msg, $device_id, 'sensor', $sensor['sensor_id']);
      $rows_updated++;

      $update_entities[$sensor_id] = $update_array;
    }

    unset($update_array);

  } // End write permission check

} // end sensors loop

// Query updated sensors array
if ($rows_updated) {
  print_json_status('ok', $rows_updated.' sensor(s) updated.', [ 'update_array' => $update_entities ]);
} else {
  print_json_status('failed', 'No update performed.');
}

// EOF
