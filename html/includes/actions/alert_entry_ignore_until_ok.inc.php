<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

if ($_SESSION['userlevel'] >= 8) {

  if (is_intnum($vars['form_alert_table_id']) && request_token_valid($vars)) {

    $alert_entry = get_alert_entry_by_id($vars['form_alert_table_id']);

    $update_array = array();
    if ($alert_entry['ignore_until_ok'] != 1) { $update_array['ignore_until_ok'] = '1'; }
    if ($alert_entry['alert_status'] == 0)    { $update_array['alert_status']    = '3'; }

    if (count($update_array)) {
      //r($alert_entry);
      dbUpdate($update_array, 'alert_table', 'alert_table_id = ?', array($alert_entry['alert_table_id']));
      $alert_device = device_by_id_cache($alert_entry['device_id']);
      print_message("Alert entry [{$vars['form_alert_table_id']}] for device '{$alert_device['hostname']}' suppressed.");
    }

    unset($update_array);

    // FIXME - eventlog? audit log?
  }

}

// EOF
