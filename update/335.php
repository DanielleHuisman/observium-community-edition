<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage update
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

foreach ($config['mibs'] as $mib => $s)
{
  foreach ($s['sensor'] as $t)
  {
    foreach ($t['tables'] as $entry)
    {
      $old_type = $mib . '-' . $entry['table'];
      $new_type = $mib . '-' . $entry['oid'];
      if ($old_type != $new_type)
      {
        foreach (dbFetchRows("SELECT * FROM `sensors` WHERE `sensor_type` = ?", array($old_type)) as $sensor)
        {
          $device = device_by_id_cache($sensor['device_id']);
          $db_updated = dbUpdate(array('sensor_type' => $new_type), 'sensors', '`sensor_id` = ?', array($sensor['sensor_id']));
          print_vars($db_updated);
          $old_rrd = array('descr' => $sensor['sensor_descr'], 'class' => $sensor['sensor_class'], 'index' => $sensor['sensor_index'], 'type' => $old_type);
          $new_rrd = array('descr' => $sensor['sensor_descr'], 'class' => $sensor['sensor_class'], 'index' => $sensor['sensor_index'], 'type' => $new_type);
          $rrd_updated = rename_rrd_entity($device, 'sensor', $old_rrd, $new_rrd);
          print_vars($rrd_updated);
          if ($db_updated || $rrd_updated)
          {
            echo('.');
            //print_vars($sensor);
          }
          //print_vars($sensor);
          //print_vars($mib);
          //print_vars($entry);
        }
      }
    }
  }
}

// EOF
