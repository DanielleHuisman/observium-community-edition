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

// BETTER-NETWORKS-ETHERNETBOX-MIB

$mib = 'BETTER-NETWORKS-ETHERNETBOX-MIB';

echo("$mib ");

switch (snmp_get($device, 'tempunit.0', '-Oqvn', $mib)) {
    case 1:
        $tempunit = 'F';
        break;
    case 2:
        $tempunit = 'K';
        break;
    default:
        $tempunit = 'C';
}

$oids = snmpwalk_cache_oid($device, 'sensorEntry', [], $mib);
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    $descr = $entry['name'];

    if (is_numeric($entry['valueint10'])) {
        $scale    = 0.1;
        $oid_name = 'valueint10';
        $oid_num  = ".1.3.6.1.4.1.14848.2.1.2.1.5.{$index}";
    } else {
        $scale    = 1;
        $oid_name = 'valueint';
        $oid_num  = ".1.3.6.1.4.1.14848.2.1.2.1.4.{$index}";
    }
    $type    = $mib . '-' . $oid_name;
    $value   = $entry[$oid_name];
    $options = [];

    $sensor_type = FALSE;
    // 0=no sensor, 1=temperature, 2=brightness, 3=humidity, 4= switch contact 5 = voltage detector 6 = smoke sensor
    switch ($entry['sensortype']) {
        case 1:
            $sensor_type            = 'temperature';
            $options['sensor_unit'] = $tempunit;
            break;
        case 2:
            $sensor_type = 'illuminance';
            break;
        case 3:
            $sensor_type           = 'humidity';
            $options['limit_high'] = 90;
            $options['limit_low']  = 10;
            break;
    }
    if ($sensor_type) {
        discover_sensor_ng($device, $sensor_type, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
    }

}

// EOF
