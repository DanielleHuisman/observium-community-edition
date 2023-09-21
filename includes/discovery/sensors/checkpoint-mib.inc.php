<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$chkpnt['temp'] = snmpwalk_cache_oid($device, 'tempertureSensorEntry', [], 'CHECKPOINT-MIB');
$chkpnt['fan']  = snmpwalk_cache_oid($device, 'fanSpeedSensorEntry', [], 'CHECKPOINT-MIB');
$chkpnt['volt'] = snmpwalk_cache_oid($device, 'voltageSensorEntry', [], 'CHECKPOINT-MIB');

foreach ($chkpnt['temp'] as $index => $entry) {
    $oid    = '.1.3.6.1.4.1.2620.1.6.7.8.1.1.3.' . $index;
    $descr  = $entry['tempertureSensorName'];
    $object = 'tempertureSensorValue';
    $value  = $entry['tempertureSensorValue'];
    if ($entry['tempertureSensorValue'] > 0 && $value <= 1000) {
        discover_sensor_ng($device, 'temperature', $mib, $object, $oid, $index, NULL, $descr, 1, $value, ['rename_rrd' => "checkpoint-$index"]);
    }
}

foreach ($chkpnt['fan'] as $index => $entry) {
    $oid    = '.1.3.6.1.4.1.2620.1.6.7.8.2.1.3.' . $index;
    $object = 'fanSpeedSensorValue';
    $descr  = $entry['fanSpeedSensorName'];
    $value  = $entry['fanSpeedSensorValue'];
    if ($entry['fanSpeedSensorValue'] > 0) {
        discover_sensor_ng($device, 'fanspeed', $mib, $object, $oid, $index, NULL, $descr, 1, $value, ['rename_rrd' => "checkpoint-$index"]);
    }
}

foreach ($chkpnt['volt'] as $index => $entry) {
    $oid    = '.1.3.6.1.4.1.2620.1.6.7.8.3.1.3.' . $index;
    $object = 'voltageSensorValue';
    $descr  = $entry['voltageSensorName'];
    $value  = $entry['voltageSensorValue'];
    if (is_numeric($value) && $value > 0) {
        discover_sensor_ng($device, 'voltage', $mib, $object, $oid, $index, NULL, $descr, 1, $value, ['rename_rrd' => "checkpoint-$index"]);
    }
}

// EOF
