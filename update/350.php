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

// Update netbotz sensors to newer style. Also use correct OID instead of the integer oid!

$sensors = dbFetchRows("SELECT * FROM `sensors` WHERE `sensor_type` = 'netbotz'");

foreach($sensors as $sensor)
{

  $new_sensor = $sensor;

  switch($sensor['sensor_class'])
  {
    case 'humidity':
      $o_s = '.1.3.6.1.4.1.5528.100.4.1.2.1.8.';
      $o_r = '.1.3.6.1.4.1.5528.100.4.1.2.1.2.';
      $new_sensor['sensor_type'] = 'humiSensor';
    case 'temperature':
      $o_s = '.1.3.6.1.4.1.5528.100.4.1.1.1.8.';
      $o_r = '.1.3.6.1.4.1.5528.100.4.1.1.1.2.';
      $new_sensor['sensor_type'] = 'tempSensor';
  }
  $new_sensor['sensor_oid'] = str_replace($o_s, $o_r, $sensor['sensor_oid']);

  $old_file = get_sensor_rrd($device, $sensor);
  $new_file = get_sensor_rrd($device, $new_sensor);

  rename_rrd($device, $old_file, $new_file);

  dbUpdate(array('sensor_type' => $new_sensor['sensor_type'], 'sensor_oid' => $new_sensor['sensor_oid']), 'sensors', '`sensor_id` = ?', array($sensor['sensor_id']));

}

?>
