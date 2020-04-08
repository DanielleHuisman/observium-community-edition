<?php

/* Observium Network Management and Monitoring System
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

global $graphs;

$query  = 'SELECT DISTINCT `sensor_class` FROM `sensors` WHERE `device_id` = ? AND `sensor_deleted` = ?';
$query .= generate_query_values(array_keys($config['sensor_types']), 'sensor_class'); // Limit by known classes

$sensor_classes = dbFetchColumn($query, array($device['device_id'], '0'));
//$count = dbFetchCell('SELECT COUNT(*) FROM `sensors` WHERE `device_id` = ? AND `sensor_deleted` = ?;', array($device['device_id'], '0'));

//print_cli_data("Sensor Count", $count);

if (count($sensor_classes) > 0)
{
  // Cache device sensors attribs (currently need for get sensor_addition attrib)
  get_device_entities_attribs($device['device_id'], 'sensor');
  //print_vars($GLOBALS['cache']['entity_attribs']);

  poll_cache_oids($device, 'sensor', $oid_cache);

  global $table_rows;
  $table_rows = array();

  // Call poll_sensor for each sensor type that we support.
  global $multi_update_db;
  $multi_update_db = array();

  foreach ($sensor_classes as $sensor_class)
  {
    $sensor_class_data = $config['sensor_types'][$sensor_class];
    poll_sensor($device, $sensor_class, $sensor_class_data['symbol'], $oid_cache);
  }

  if (count($multi_update_db))
  {
    print_debug("MultiUpdate sensors DB.");
    // Multiupdate required all UNIQUE keys!
    dbUpdateMulti($multi_update_db, 'sensors');
  }

  $headers = array('Descr', 'Class', 'Type', 'Origin', 'Value', 'Event', 'Last Changed');
  print_cli_table($table_rows, $headers);
}

// EOF
