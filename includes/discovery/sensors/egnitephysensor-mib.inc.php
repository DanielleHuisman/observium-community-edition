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

/*
EGNITEPHYSENSOR-MIB::querxPhySensorIndex.1 = INTEGER: 1
EGNITEPHYSENSOR-MIB::querxPhySensorIndex.2 = INTEGER: 2
EGNITEPHYSENSOR-MIB::querxPhySensorIndex.3 = INTEGER: 3
EGNITEPHYSENSOR-MIB::querxPhySensorIndex.4 = INTEGER: 4
EGNITEPHYSENSOR-MIB::querxPhySensorType.1 = INTEGER: celsius(8)
EGNITEPHYSENSOR-MIB::querxPhySensorType.2 = INTEGER: percentRH(9)
EGNITEPHYSENSOR-MIB::querxPhySensorType.3 = INTEGER: celsius(8)
EGNITEPHYSENSOR-MIB::querxPhySensorType.4 = INTEGER: other(1)
EGNITEPHYSENSOR-MIB::querxPhySensorScale.1 = INTEGER: units(9)
EGNITEPHYSENSOR-MIB::querxPhySensorScale.2 = INTEGER: units(9)
EGNITEPHYSENSOR-MIB::querxPhySensorScale.3 = INTEGER: units(9)
EGNITEPHYSENSOR-MIB::querxPhySensorScale.4 = INTEGER: units(9)
EGNITEPHYSENSOR-MIB::querxPhySensorPrecision.1 = INTEGER: 1
EGNITEPHYSENSOR-MIB::querxPhySensorPrecision.2 = INTEGER: 0
EGNITEPHYSENSOR-MIB::querxPhySensorPrecision.3 = INTEGER: 1
EGNITEPHYSENSOR-MIB::querxPhySensorPrecision.4 = INTEGER: 1
EGNITEPHYSENSOR-MIB::querxPhySensorValue.1 = INTEGER: 194
EGNITEPHYSENSOR-MIB::querxPhySensorValue.2 = INTEGER: 37
EGNITEPHYSENSOR-MIB::querxPhySensorValue.3 = INTEGER: 42
EGNITEPHYSENSOR-MIB::querxPhySensorValue.4 = INTEGER: 9958
EGNITEPHYSENSOR-MIB::querxPhySensorOperStatus.1 = INTEGER: ok(1)
EGNITEPHYSENSOR-MIB::querxPhySensorOperStatus.2 = INTEGER: ok(1)
EGNITEPHYSENSOR-MIB::querxPhySensorOperStatus.3 = INTEGER: ok(1)
EGNITEPHYSENSOR-MIB::querxPhySensorOperStatus.4 = INTEGER: ok(1)
EGNITEPHYSENSOR-MIB::querxPhySensorUnitsDisplay.1 = STRING: "deg C"
EGNITEPHYSENSOR-MIB::querxPhySensorUnitsDisplay.2 = STRING: "% RH"
EGNITEPHYSENSOR-MIB::querxPhySensorUnitsDisplay.3 = STRING: "deg C"
EGNITEPHYSENSOR-MIB::querxPhySensorUnitsDisplay.4 = STRING: "hPa"
EGNITEPHYSENSOR-MIB::querxPhySensorValueTimeStamp.1 = Timeticks: (247662300) 28 days, 15:57:03.00
EGNITEPHYSENSOR-MIB::querxPhySensorValueTimeStamp.2 = Timeticks: (247662300) 28 days, 15:57:03.00
EGNITEPHYSENSOR-MIB::querxPhySensorValueTimeStamp.3 = Timeticks: (247662300) 28 days, 15:57:03.00
EGNITEPHYSENSOR-MIB::querxPhySensorValueTimeStamp.4 = Timeticks: (247662300) 28 days, 15:57:03.00
EGNITEPHYSENSOR-MIB::querxPhySensorValueUpdateRate.1 = Gauge32: 1000 milliseconds
EGNITEPHYSENSOR-MIB::querxPhySensorValueUpdateRate.2 = Gauge32: 1000 milliseconds
EGNITEPHYSENSOR-MIB::querxPhySensorValueUpdateRate.3 = Gauge32: 1000 milliseconds
EGNITEPHYSENSOR-MIB::querxPhySensorValueUpdateRate.4 = Gauge32: 1000 milliseconds
EGNITEPHYSENSOR-MIB::querxPhySensorName.1 = STRING: "Temperature"
EGNITEPHYSENSOR-MIB::querxPhySensorName.2 = STRING: "Humidity"
EGNITEPHYSENSOR-MIB::querxPhySensorName.3 = STRING: "Dew point"
EGNITEPHYSENSOR-MIB::querxPhySensorName.4 = STRING: "Pressure"
 */

$egnitesensor = [
    //'other'      => '',
    //'unknown'    => '',
    'voltsAC'   => 'voltage',
    'voltsDC'   => 'voltage',
    'amperes'   => 'current',
    'watts'     => 'power',
    'hertz'     => 'frequency',
    'celsius'   => 'temperature',
    'percentRH' => 'humidity',
    'rpm'       => 'fanspeed',
    'cmm'       => 'airflow',
    //'truthvalue' => 'state' // Need example
];

$oids = snmpwalk_cache_oid($device, 'querxPhySensorEntry', [], 'EGNITEPHYSENSOR-MIB');
print_debug_vars($oids);
foreach ($oids as $index => $entry) {
    if (in_array($entry['querxPhySensorOperStatus'], ['unavailable', 'nonoperational'])) {
        continue;
    }

    $options = [];
    $descr   = $entry['querxPhySensorName'];

    // Non common sensor classes:
    if ($entry['querxPhySensorType'] == 'celsius' && str_icontains_array($descr, 'Dew')) {
        $class = 'dewpoint';
    } elseif ($entry['querxPhySensorType'] == 'other') {
        // Probably can be some other sensor classes
        if (str_icontains_array($descr, 'Pressure') || str_ends($entry['querxPhySensorUnitsDisplay'], ['Pa', 'psi'])) {
            $class = 'pressure';
            //$options['sensor_unit'] = $entry['querxPhySensorUnitsDisplay'];
        } else {
            // Unknown, need examples
            continue;
        }
    } elseif (isset($egnitesensor[$entry['querxPhySensorType']])) {
        $class = $egnitesensor[$entry['querxPhySensorType']];
    } else {
        // Skip unknown
        continue;
    }

    $scale = si_to_scale($entry['querxPhySensorScale'], $entry['querxPhySensorPrecision']);

    $oid_name = 'querxPhySensorValue';
    $oid_num  = ".1.3.6.1.4.1.3444.1.14.1.2.1.5.$index";
    //$type     = $mib . '-' . $oid_name;
    $value = $entry[$oid_name];

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
}

// EOF
