<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$sql         = "SELECT DISTINCT `measured_entity` FROM `sensors` WHERE `device_id` = ? AND `measured_class` = ? ORDER BY `entPhysicalIndex_measured` * 1";
$show_header = TRUE;
// order dom sensors always by temperature, voltage, current, dbm, power
$order = ['temperature', 'voltage', 'current', 'dbm', 'power'];
foreach (dbFetchColumn($sql, [$device['device_id'], 'port']) as $port_id) {
    print_sensor_table(['entity_type' => 'port', 'entity_id' => $port_id, 'page' => 'device', 'show_class' => TRUE, 'show_header' => $show_header], $order);
    $show_header = FALSE;
}

// EOF
