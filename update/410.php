<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$sql = "SELECT * FROM `sensors` WHERE 1";
$sql .= generate_query_values_and(array_keys($config['counter_types']), 'sensor_class');
$sensors = dbFetchRows($sql);

if (count($sensors))
{
  echo 'Updating RRD DS names for counters: ';

  $counter_ds = 'counter:DERIVE:600:-20000:U';

  foreach ($sensors as $sensor)
  {
    // Skip, just remove deleted entries
    if ($sensor['sensor_deleted'])
    {
      dbDelete('sensors', '`sensor_id` = ?', [$sensor['sensor_id']]);
      echo('-');
      continue;
    }

    $device = device_by_id_cache($sensor['device_id']);
    //print_vars($sensor);

    // Convert sensor entry to counter entry
    $counter = [];
    foreach ($sensor as $key => $value)
    {
      // Skip not same columns and limits and empty params
      if (in_array($key, ['sensor_id', 'sensor_type']) ||
          str_contains_array($key, 'limit') || $value === '')
      {
        continue;
      }
      $counter_key = str_replace('sensor_', 'counter_', $key);
      $counter[$counter_key] = $value;
    }
    // Printersupply only fix
    if ($counter['measured_class'] == 'printersupply')
    {
      $counter['counter_class'] = 'printersupply';
    }
    // Counters require mib & object
    if (empty($counter['counter_mib']) || empty($counter['counter_object']))
    {
      // Printer-MIB-prtMarkerLifeCount -> Printer-MIB, prtMarkerLifeCount
      $mib_object = explode('-', $sensor['sensor_type']);
      $counter['counter_object'] = array_pop($mib_object);
      $counter['counter_mib'] = implode('-', $mib_object);
    }

    //print_vars($counter);
    // Add counter db
    $id = dbInsert($counter, 'counters');
    $counter['counter_id'] = $id;

    $sensor_rrd = get_sensor_rrd($device, $sensor);
    $counter_rrd = get_counter_rrd($device, $counter);

    // Now try to rename rrd and add counter DS
    if (rename_rrd($device, $sensor_rrd, $counter_rrd))
    {
      $status = rrdtool_add_ds($device, get_rrd_path($device, $counter_rrd), $counter_ds);
      dbDelete('sensors', '`sensor_id` = ?', [$sensor['sensor_id']]);
      echo('.');
    } else {
      // In other cases, just delete incorrect entries (will be recreated by discovery)
      dbDelete('sensors', '`sensor_id` = ?', [$sensor['sensor_id']]);
      echo('-');
    }
  }
}

// EOF

