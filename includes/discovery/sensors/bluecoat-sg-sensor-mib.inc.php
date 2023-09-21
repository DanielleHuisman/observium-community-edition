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

//BLUECOAT-SG-SENSOR-MIB::deviceSensorTrapEnabled.11 = INTEGER: false(2)
//BLUECOAT-SG-SENSOR-MIB::deviceSensorUnits.11 = INTEGER: volts(4)
//BLUECOAT-SG-SENSOR-MIB::deviceSensorScale.11 = INTEGER: -2
//BLUECOAT-SG-SENSOR-MIB::deviceSensorValue.10 = INTEGER: 1194
//BLUECOAT-SG-SENSOR-MIB::deviceSensorValue.11 = INTEGER: 330
//BLUECOAT-SG-SENSOR-MIB::deviceSensorCode.11 = INTEGER: ok(1)
//BLUECOAT-SG-SENSOR-MIB::deviceSensorStatus.11 = INTEGER: ok(1)
//BLUECOAT-SG-SENSOR-MIB::deviceSensorTimeStamp.11 = Timeticks: (3426521929) 396 days, 14:06:59.29 Hundredths of seconds
//BLUECOAT-SG-SENSOR-MIB::deviceSensorName.11 = STRING: +3.3V bus voltage 2 (Vcc)

$sensor_array = snmpwalk_cache_oid($device, 'deviceSensorValueTable', [], 'BLUECOAT-SG-SENSOR-MIB');

$sensor_classes = [
    //'other'     => 'state', // ?
    //'truthvalue' => 'state', // ?
    'volts'   => 'voltage',
    'rpm'     => 'fanspeed',
    'celsius' => 'temperature',
    'dBm'     => 'dbm'
];

foreach ($sensor_array as $index => $entry) {
    if (isset($sensor_classes[$entry['deviceSensorUnits']]) && is_numeric($entry['deviceSensorValue']) && is_numeric($index) &&
        $entry['deviceSensorStatus'] !== 'unavailable' && $entry['deviceSensorStatus'] !== 'nonoperational') {

        $descr    = rewrite_entity_name($entry['deviceSensorName']);
        $oid      = ".1.3.6.1.4.1.3417.2.1.1.1.1.1.5." . $index;
        $class    = $sensor_classes[$entry['deviceSensorUnits']];
        $scale    = si_to_scale($entry['deviceSensorScale']);
        $value    = $entry['deviceSensorValue'];
        $oid_name = 'bluecoat-sg-proxy-mib';

        if ($class === 'temperature' && ($value * $scale) > 200) {
            continue;
        } // only why not possible convert to definitions
        if ($value == -127) {
            continue;
        }

        $options                 = [];
        $options['rename_rrd']   = "bluecoat-sg-proxy-mib-$index";
        $options['oid_scale_si'] = "deviceSensorScale"; // need walk this oid on every poll

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, $options);
    }
}

unset($sensor_classes, $oids, $oids_arista, $sensor_array, $index, $scale, $class, $value, $descr, $ok, $options);

// EOF
