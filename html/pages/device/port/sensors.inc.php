<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     web
 * @copyright  (C) Adam Armstrong
 *
 */

// order dom sensors always by temperature, voltage, current, dbm, power
$order = ['temperature', 'voltage', 'current', 'dbm', 'power'];
print_sensor_table(['entity_type' => 'port', 'entity_id' => $port['port_id'], 'page' => 'device', 'show_class' => TRUE], $order);

// EOF
