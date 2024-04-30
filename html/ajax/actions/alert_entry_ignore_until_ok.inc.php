<?php

if ($_SESSION['userlevel'] >= 8) {

    if (is_intnum($vars['value'])) {

        $alert_entry = get_alert_entry_by_id($vars['value']);

        if(!count($alert_entry)) {
            print_json_status('failed', 'Alert entry not found. No update performed.');
            die;
        }

        $update_array = [];
        if ($alert_entry['ignore_until_ok'] != 1) {
            $update_array['ignore_until_ok'] = '1';
        }
        if ($alert_entry['alert_status'] == 0) {
            $update_array['alert_status'] = '3';
        }

        if (count($update_array)) {
            //r($alert_entry);
            dbUpdate($update_array, 'alert_table', 'alert_table_id = ?', [$alert_entry['alert_table_id']]);
            $alert_device = device_by_id_cache($alert_entry['device_id']);
            //print_message("Alert entry [{$vars['form_alert_table_id']}] for device '{$alert_device['hostname']}' suppressed.");
            print_json_status('ok', 'alert '.$vars['form_alert_table_id'].' ignored until ok. status updated.', ['update_array' => $update_array]);
        }

        unset($update_array);

        // FIXME - eventlog? audit log?
    }

} else {
    print_json_status('failed', 'Action not permitted. Not performed.');
}