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

// NOTE. This is old version of WebGraph-Thermo-Hygrometer-US-MIB
$mib = 'WebGraph-OLD-Thermo-Hygrometer-US-MIB';

//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroBinaryTempValue.1 = INTEGER: 266
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroBinaryTempValue.2 = INTEGER: 587
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroPortName.1 = STRING: "Temperatur"
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroPortName.2 = STRING: "rel. Feuchte"
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroPortText.1 = STRING: "Sensorbeschreibung 1"
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroPortText.2 = STRING: "Sensorbeschreibung 2"
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroPortSensorSelect.1 = Hex-STRING: 00 00 00 02
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroPortSensorSelect.2 = Hex-STRING: 00 00 00 01

//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroAlarmMin.1 = STRING: "10"
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroAlarmMax.1 = STRING: "25"
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroAlarmRHMin.1 = STRING: "10"
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroAlarmRHMax.1 = STRING: "85"
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroAlarmAHMin.1 = STRING: "1"
//WebGraph-OLD-Thermo-Hygrometer-US-MIB::wtWebGraphThermHygroAlarmAHMax.1 = STRING: "25"

$oids = snmpwalk_cache_oid($device, "wtWebGraphThermHygroBinaryTempValueTable", [], $mib);
if ($GLOBALS['snmp_status']) {
    $oids = snmpwalk_cache_oid($device, "wtWebGraphThermHygroPortTable", $oids, $mib);

    // Temperature
    if (is_numeric($oids[1]['wtWebGraphThermHygroBinaryTempValue'])) {
        $index    = 1;
        $scale    = 0.1;
        $descr    = $oids[1]['wtWebGraphThermHygroPortName'];
        $oid      = '.1.3.6.1.4.1.5040.1.2.9.1.4.1.1.1';
        $oid_name = 'wtWebGraphThermHygroBinaryTempValue';
        $value    = $oids[1]['wtWebGraphThermHygroBinaryTempValue'];

        $limits               = snmp_get_multi_oid($device, 'wtWebGraphThermHygroAlarmMin.1 wtWebGraphThermHygroAlarmMax.1', [], $mib);
        $limits['limit_high'] = trim($limits[1]['wtWebGraphThermHygroAlarmMax'], ' "');
        $limits['limit_low']  = trim($limits[1]['wtWebGraphThermHygroAlarmMin'], ' "');
        $options              = ['limit_high' => (is_numeric($limits['limit_high']) ? $limits['limit_high'] : NULL),
                                 'limit_low'  => (is_numeric($limits['limit_low']) ? $limits['limit_low'] : NULL)];

        //discover_sensor('temperature', $device, $oid, 'wtWebGraphThermHygroBinaryTempValue.1', 'wut', $descr, 0.1, $value, $options);
        $options['rename_rrd'] = "wut-wtWebGraphThermHygroBinaryTempValue.1";
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, $options);
    }

    // Humidity/Volts
    if (is_numeric($oids[2]['wtWebGraphThermHygroBinaryTempValue'])) {
        // Binary coded options for sensor 2:
        //        Octet 1: unused
        //        Octet 2: unused
        //        Octet 3: unused
        //        Octet 4:
        //                Bit 0  :        W&T Sensor rel. humidity (default)
        //                Bit 1  :        Skalar 0-2.5V
        //                Bit 2  :        Disconnect
        //   Bit 3-7:        unused"
        [, , , $octet] = explode(' ', $oids[2]['wtWebGraphThermHygroPortSensorSelect']);

        $index    = 2;
        $scale    = 0.1;
        $descr    = $oids[2]['wtWebGraphThermHygroPortName'];
        $oid      = '.1.3.6.1.4.1.5040.1.2.9.1.4.1.1.2';
        $oid_name = 'wtWebGraphThermHygroBinaryTempValue';
        $value    = $oids[2]['wtWebGraphThermHygroBinaryTempValue'];

        if ($octet == "01") {
            // Humidity
            $limits               = snmp_get_multi_oid($device, 'wtWebGraphThermHygroAlarmRHMin.1 wtWebGraphThermHygroAlarmRHMax.1', [], $mib);
            $limits['limit_high'] = trim($limits[1]['wtWebGraphThermHygroAlarmRHMax'], ' "');
            $limits['limit_low']  = trim($limits[1]['wtWebGraphThermHygroAlarmRHMin'], ' "');
            $options              = ['limit_high' => (is_numeric($limits['limit_high']) ? $limits['limit_high'] : NULL),
                                     'limit_low'  => (is_numeric($limits['limit_low']) ? $limits['limit_low'] : NULL)];

            //discover_sensor('humidity', $device, $oid, 'wtWebGraphThermHygroBinaryTempValue.2', 'wut', $descr, 0.1, $value, $options);
            $options['rename_rrd'] = "wut-wtWebGraphThermHygroBinaryTempValue.2";
            discover_sensor_ng($device, 'humidity', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, $options);
        } elseif ($octet == "02") {
            // Voltage? Not tested
            $options = [];
            //discover_sensor('voltage', $device, $oid, 'wtWebGraphThermHygroBinaryTempValue.2', 'wut', $descr, 0.1, $value);
            $options['rename_rrd'] = "wtWebGraphThermHygroBinaryTempValue.2";
            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, $options);
        }
    }
}

unset($oids, $oid, $descr, $options, $limits, $value);

// EOF
