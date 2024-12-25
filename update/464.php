<?php

/**
* Observium
*
*   This file is part of Observium.
*
* @package    observium
* @subpackage update
 * @copyright  (C) Adam Armstrong
*
*/

// Update alert entry RRDs to use entity_type-entity_id-alert_test_id as the filename

echo "Renaming RRD files for alerts: [";

foreach (dbFetchRows("SELECT * FROM `alert_table`") AS $entry)
{
  $device = device_by_id_cache($entry['device_id']);
  $filename = "alert-".$entry['alert_test_id']."-".$entry['entity_type']."-".$entry['entity_id'].".rrd";
  $old_filename = "alert-".$entry['alert_table_id'].".rrd";
  $status = rename_rrd($device, $old_filename, $filename);
  echo ".";
}

echo "]\n";

// EOF

