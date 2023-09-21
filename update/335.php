<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage db
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

foreach ($config['mibs'] as $mib => $s) {
  foreach ($s['sensor'] as $t) {
    foreach ($t['tables'] as $entry) {
      $old_type = $mib . '-' . $entry['table'];
      $new_type = $mib . '-' . $entry['oid'];
      if ($old_type !== $new_type) {
        foreach (dbFetchRows("SELECT * FROM `sensors` WHERE `sensor_type` = ?", array($old_type)) as $sensor) {
          $device = device_by_id_cache($sensor['device_id']);
          $db_updated = dbUpdate(array('sensor_type' => $new_type), 'sensors', '`sensor_id` = ?', array($sensor['sensor_id']));
          print_vars($db_updated);
          $old_rrd = 'sensor-'.$sensor['sensor_class'].'-'.$old_type.'-'.$sensor['sensor_index'].'.rrd';
          $new_rrd = 'sensor-'.$sensor['sensor_class'].'-'.$new_type.'-'.$sensor['sensor_index'].'.rrd';
          $rrd_updated = rename_rrd($device, $old_rrd, $new_rrd);
          print_vars($rrd_updated);
          if ($db_updated || $rrd_updated) {
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
