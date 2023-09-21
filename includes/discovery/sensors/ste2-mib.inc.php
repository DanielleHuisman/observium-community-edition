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

/**
 * STE2-MIB::sensIndex.1 = INTEGER: 2594
 * STE2-MIB::sensIndex.2 = INTEGER: 3594
 * STE2-MIB::sensName.1 = STRING: "Sensor 2594"
 * STE2-MIB::sensName.2 = STRING: "Sensor 3594"
 * STE2-MIB::sensState.1 = INTEGER: alarmlo(4)
 * STE2-MIB::sensState.2 = INTEGER: normal(1)
 * STE2-MIB::sensString.1 = STRING: "32.1"
 * STE2-MIB::sensString.2 = STRING: "32.0"
 * STE2-MIB::sensValue.1 = INTEGER: 321
 * STE2-MIB::sensValue.2 = INTEGER: 320
 * STE2-MIB::sensSN.1 = STRING: "26220A570520086C"
 * STE2-MIB::sensSN.2 = STRING: "280A0E570520081B"
 * STE2-MIB::sensUnit.1 = INTEGER: percent(4)
 * STE2-MIB::sensUnit.2 = INTEGER: celsius(1)
 * STE2-MIB::sensID.1 = INTEGER: 2594
 * STE2-MIB::sensID.2 = INTEGER: 3594
 */

$oids = snmpwalk_cache_oid($device, 'sensTable', [], 'STE2-MIB');

foreach ($oids as $index => $entry) {
    $descr = $entry['sensName'];

    $oid_name = 'sensValue';
    $oid_num  = ".1.3.6.1.4.1.21796.4.9.3.1.5.{$index}";
    $type     = $mib . '-' . $oid_name;
    $scale    = 0.1;
    $value    = $entry[$oid_name];

    $options = [];
    // sensUnit: none (0), celsius (1), fahrenheit (2), kelvin (3), percent(4), 5
    switch ($entry['sensUnit']) {
        case 'celsius':
            $sensor_type = 'temperature';
            break;
        case 'fahrenheit':
            $sensor_type            = 'temperature';
            $options['sensor_unit'] = 'F';
            break;
        case 'kelvin':
            $sensor_type            = 'temperature';
            $options['sensor_unit'] = 'K';
            break;
        case 'percent':
            $sensor_type = 'humidity';
            break;
        case 'none':
            continue 2; // continue foreach loop
        default:
            $sensor_type = 'status';
    }

    if ($entry['sensState'] !== 'invalid' && $sensor_type !== 'status') {
        //discover_sensor($sensor_type, $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
        discover_sensor_ng($device, $sensor_type, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
    }

    $oid_name = 'sensState';
    $oid_num  = ".1.3.6.1.4.1.21796.4.9.3.1.3.{$index}";
    $type     = 'ste2-SensorState';
    $value    = $entry[$oid_name];

    //discover_status($device, $oid_num, $oid_name.'.'.$index, $type, $descr, $value, [ 'entPhysicalClass' => 'other' ]);
    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

/**
 * STE2-MIB::inpIndex.1 = INTEGER: 1
 * STE2-MIB::inpIndex.2 = INTEGER: 2
 * STE2-MIB::inpValue.1 = INTEGER: off(0)
 * STE2-MIB::inpValue.2 = INTEGER: off(0)
 * STE2-MIB::inpName.1 = STRING: "Input 1"
 * STE2-MIB::inpName.2 = STRING: "Input 2"
 * STE2-MIB::inpAlarmState.1 = INTEGER: alarm(1)
 * STE2-MIB::inpAlarmState.2 = INTEGER: alarm(1)
 */

// EOF
