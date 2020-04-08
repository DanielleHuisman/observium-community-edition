<?php

/**
 * Observium Network Management and Monitoring System
 * Copyright (C) 2006-2015, Adam Armstrong - http://www.observium.org
 *
 * @package    observium
 * @subpackage webui
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$sql = "SELECT DISTINCT `measured_entity` FROM `sensors` WHERE `device_id` = ? AND `measured_class` = ? ORDER BY `entPhysicalIndex` * 1";
$show_header = TRUE;
foreach (dbFetchColumn($sql, array($device['device_id'], 'port')) as $port_id)
{
  print_sensor_table(array('entity_type' => 'port', 'entity_id' => $port_id, 'page' => 'device', 'show_class' => TRUE, 'show_header' => $show_header));
  $show_header = FALSE;
}

// EOF
