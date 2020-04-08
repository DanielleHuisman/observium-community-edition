<?php

if ($_SESSION['userlevel'] > 8)
{

  if(is_numeric($vars['form_alert_table_id']))
  {

    $alert_entry = get_alert_entry_by_id($vars['form_alert_table_id']);

    $update_array = array();
    if($alert_entry['ignore_until_ok'] != 1) { $update_array['ignore_until_ok'] = '1'; }
    if($alert_entry['alert_status'] == 0)    { $update_array['alert_status']    = '3'; }

    if(count($update_array))
    {
      dbUpdate($update_array, 'alert_table', 'alert_table_id = ?', array($alert_entry['alert_table_id']));
    }

    unset($update_array);

    // FIXME - eventlog? audit log?
  }

}

?>
