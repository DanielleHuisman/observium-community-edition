<?php

  $alerts = dbFetchRows("SELECT * FROM `alert_table` WHERE `device_id` = 0");

  echo ("Fixing alert_table device_id: ");

  foreach($alerts AS $alert)
  {
    if($alert['device_id'] == '0' && $device_id = get_device_id_by_entity_id($alert['entity_id'], $alert['entity_type'])) {
      dbUpdate(['device_id' => $device_id], 'alert_table', '`alert_table_id` = ?', [$alert['alert_table_id']]);
      echo ".";
    } else { echo "!"; }
  }

  echo PHP_EOL;
