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

//AXIS-VIDEO-MIB::tempSensorStatus.common.1 = INTEGER: ok(1)
//AXIS-VIDEO-MIB::tempSensorStatus.common.2 = INTEGER: ok(1)
//AXIS-VIDEO-MIB::tempSensorValue.common.1 = INTEGER: 26
//AXIS-VIDEO-MIB::tempSensorValue.common.2 = INTEGER: 32

$mib = 'AXIS-VIDEO-MIB';

// Temperature Sensor
$oids = snmpwalk_cache_oid($device, 'tempSensorEntry', [], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    //if ($entry['tempSensorStatus'] == 'failure') { continue; } // ok(1), failure(2), outOfBoundary(3)

    // common(1), housing(2), rack(3), cpu(4)
    [$tempSensorType, $tempSensorId] = explode('.', $index);
    switch ($tempSensorType) {
        case '1':
            $descr = 'System temperature';
            break;
        case '2':
            $descr = 'Housing temperature';
            break;
        case '3':
            $descr = 'Rack temperature';
            break;
        case '4':
            $descr = 'CPU temperature';
            break;
        default:
            $descr = 'Temperature';
    }
    if (count($oids) > 1) {
        $descr .= ' ' . $tempSensorId;
    }

    $scale    = 1;
    $oid_name = 'tempSensorValue';
    $oid_num  = '.1.3.6.1.4.1.368.4.1.3.1.4.' . $index;
    $value    = $entry[$oid_name];

    if ($value <= 0) {
        continue;
    }


    discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

    $oid_name = 'tempSensorStatus';
    $oid_num  = '.1.3.6.1.4.1.368.4.1.3.1.3.' . $index;
    $type     = 'axisStatus';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

// Fan Sensor
$oids = snmpwalk_cache_oid($device, 'fanEntry', [], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    // common(1), housing(2), rack(3), cpu(4)
    [$SensorType, $SensorId] = explode('.', $index);
    switch ($SensorType) {
        case '1':
            $descr = 'System fan';
            break;
        case '2':
            $descr = 'Housing fan';
            break;
        case '3':
            $descr = 'Rack fan';
            break;
        case '4':
            $descr = 'CPU fan';
            break;
        default:
            $descr = 'Fan';
    }
    if (count($oids) > 1) {
        $descr .= ' ' . $SensorId;
    }

    $oid_name = 'fanStatus';
    $oid_num  = '.1.3.6.1.4.1.368.4.1.2.1.3.' . $index;
    $type     = 'axisStatus';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'fan']);
}

// EOF
