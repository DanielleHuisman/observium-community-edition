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

// Teracom MIBs are stupid

// TERACOM-TCW241-MIB::temperatureUnit.0 = INTEGER: celcius(0)
$temp_unit = snmp_get_oid($device, 'temperatureUnit.0', $mib);
$flags     = OBS_QUOTES_TRIM | OBS_SNMP_DISPLAY_HINT; // disable use display-hint, for correct scales

$invalid = ['0', '-2147483648'];

// 1-Wire sensors
for ($i = 1; $i <= 8; $i++) {
    // TERACOM-TCW241-MIB::s1description.0 = STRING: S1:TSH2xx
    // TERACOM-TCW241-MIB::s11MAXInt.0 = INTEGER: 85.000
    // TERACOM-TCW241-MIB::s11MINInt.0 = INTEGER: -40.000
    // TERACOM-TCW241-MIB::s11HYSTInt.0 = INTEGER: 8.500
    // TERACOM-TCW241-MIB::s12MAXInt.0 = INTEGER: 100.000
    // TERACOM-TCW241-MIB::s12MINInt.0 = INTEGER: .000
    // TERACOM-TCW241-MIB::s12HYSTInt.0 = INTEGER: 10.000
    // TERACOM-TCW241-MIB::s11Int.0 = INTEGER: 17.875
    // TERACOM-TCW241-MIB::s12Int.0 = INTEGER: 27.500
    // TERACOM-TCW241-MIB::s1ID.0 = STRING: 01FBBC5A1800FF40

    $oids = [
      "s{$i}description.0", "s{$i}ID.0",
      "s{$i}1Int.0", "s{$i}1MAXInt.0", "s{$i}1MINInt.0",
      "s{$i}2Int.0", "s{$i}2MAXInt.0", "s{$i}2MINInt.0",
    ];
    $data = snmp_get_multi_oid($device, $oids, [], $mib, NULL, $flags);
    if ($data[0]['s' . $i . 'ID'] === '0000000000000000') {
        continue;
    }

    if (!in_array($data[0]["s{$i}1Int"], $invalid)) {
        $descr = '#1 ' . $data[0]["s{$i}description"] . ' (' . $data[0]["s{$i}ID"] . ')';

        $oid_num  = ".1.3.6.1.4.1.38783.3.3.1.$i.1.0";
        $oid_name = "s{$i}1Int";
        $value    = $data[0][$oid_name];
        $scale    = 0.001;
        $options  = [
          'limit_low'  => $data[0]["s{$i}1MINInt"] * $scale,
          'limit_high' => $data[0]["s{$i}1MAXInt"] * $scale
        ];
        if ($temp_unit === 'fahrenheit') {
            $options['sensor_unit'] = 'F';
        }
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, 0, NULL, $descr, $scale, $value, $options);
    }
    if (!in_array($data[0]["s{$i}2Int"], $invalid)) {
        $descr = '#2 ' . $data[0]["s{$i}description"] . ' (' . $data[0]["s{$i}ID"] . ')';

        $oid_num  = ".1.3.6.1.4.1.38783.3.3.1.$i.2.0";
        $oid_name = "s{$i}2Int";
        $value    = $data[0][$oid_name];
        $scale    = 0.001;
        $options  = [
          'limit_low'  => $data[0]["s{$i}2MINInt"] * $scale,
          'limit_high' => $data[0]["s{$i}2MAXInt"] * $scale
        ];
        if ($temp_unit === 'fahrenheit') {
            $options['sensor_unit'] = 'F';
        }
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, 0, NULL, $descr, $scale, $value, $options);
    }
}

// Analog inputs
for ($i = 1; $i <= 4; $i++) {
    $oids  = [
      "voltage{$i}description.0",
      "voltage{$i}Int.0", "voltage{$i}max.0", "voltage{$i}min.0",
    ];
    $data  = snmp_get_multi_oid($device, $oids, [], $mib, NULL, $flags);
    $descr = $data[0]["voltage{$i}description"];
    switch (substr($descr, 0, 2)) {
        case 'I ':
            $type  = 'current';
            $descr = substr($descr, 2);
            break;
        case 'F ':
            $type  = 'frequency';
            $descr = substr($descr, 2);
            break;
        case 'H ':
            $type  = 'humidity';
            $descr = substr($descr, 2);
            break;
        default:
            $type = 'voltage';
    }

    if (!in_array($data[0]["voltage{$i}Int"], $invalid)) {
        $oid_num  = ".1.3.6.1.4.1.38783.3.3.2.$i.0";
        $oid_name = "voltage{$i}Int";
        $value    = $data[0][$oid_name];
        $scale    = 0.001;
        $options  = [
          'limit_low'  => $data[0]["voltage{$i}min"] * $scale,
          'limit_high' => $data[0]["voltage{$i}max"] * $scale
        ];
        discover_sensor_ng($device, $type, $mib, $oid_name, $oid_num, 0, NULL, $descr, $scale, $value, $options);
    }
}

// Digital inputs
for ($i = 1; $i <= 4; $i++) {
    $oids = [
      "digitalInput{$i}description.0", "digitalInput{$i}State.0",
    ];
    $data = snmp_get_multi_oid($device, $oids, [], $mib);

    $descr    = $data[0]["digitalInput{$i}description"];
    $oid_num  = ".1.3.6.1.4.1.38783.3.3.3.$i.0";
    $oid_name = "digitalInput{$i}State";
    $value    = $data[0][$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid_num, 0, 'teracom-digitalin-state', $descr, $value, ['entPhysicalClass' => 'other']);
}

// Relay outputs
for ($i = 1; $i <= 4; $i++) {
    $oids = [
      "relay{$i}description.0", "relay{$i}State.0",
    ];
    $data = snmp_get_multi_oid($device, $oids, [], $mib);

    $descr    = $data[0]["relay{$i}description"];
    $oid_num  = ".1.3.6.1.4.1.38783.3.3.4.$i.1.0";
    $oid_name = "relay{$i}State";
    $value    = $data[0][$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid_num, 0, 'teracom-relay-state', $descr, $value, ['entPhysicalClass' => 'other']);
}

// EOF