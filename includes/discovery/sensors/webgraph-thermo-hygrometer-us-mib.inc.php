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

// NOTE. Based on old version of WebGraph-OLD-Thermo-Hygrometer-US-MIB, not tested
$mib = 'WebGraph-Thermo-Hygrometer-US-MIB';

//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroBinaryTempValue.1 = INTEGER: 266
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroBinaryTempValue.2 = INTEGER: 587
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroPortName.1 = STRING: "Temperatur"
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroPortName.2 = STRING: "rel. Feuchte"
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroPortText.1 = STRING: "Sensorbeschreibung 1"
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroPortText.2 = STRING: "Sensorbeschreibung 2"
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroPortSensorSelect.1 = Hex-STRING: 00 00 00 02
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroPortSensorSelect.2 = Hex-STRING: 00 00 00 01

//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroAlarmMin.1 = STRING: "10"
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroAlarmMax.1 = STRING: "25"
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroAlarmRHMin.1 = STRING: "10"
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroAlarmRHMax.1 = STRING: "85"
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroAlarmAHMin.1 = STRING: "1"
//WebGraph-Thermo-Hygrometer-US-MIB::wtWebGraphThermoHygroAlarmAHMax.1 = STRING: "25"

$oids = snmpwalk_cache_oid($device, "wtWebGraphThermoHygroBinaryTempValueTable", [], $mib);
if ($GLOBALS['snmp_status']) {
    $oids = snmpwalk_cache_oid($device, "wtWebGraphThermoHygroPortTable", $oids, $mib);

    // Temperature
    if (is_numeric($oids[1]['wtWebGraphThermoHygroBinaryTempValue'])) {
        $index    = 1;
        $scale    = 0.1;
        $descr    = $oids[1]['wtWebGraphThermoHygroPortName'];
        $oid      = '.1.3.6.1.4.1.5040.1.2.42.1.4.1.1.1';
        $oid_name = 'wtWebGraphThermoHygroBinaryTempValue';
        $value    = $oids[1]['wtWebGraphThermoHygroBinaryTempValue'];

        $limits               = snmp_get_multi_oid($device, 'wtWebGraphThermoHygroAlarmMin.1 wtWebGraphThermoHygroAlarmMax.1', [], $mib);
        $limits['limit_high'] = trim($limits[1]['wtWebGraphThermoHygroAlarmMax'], ' "');
        $limits['limit_low']  = trim($limits[1]['wtWebGraphThermoHygroAlarmMin'], ' "');
        $options              = ['limit_high' => (is_numeric($limits['limit_high']) ? $limits['limit_high'] : NULL),
                                 'limit_low'  => (is_numeric($limits['limit_low']) ? $limits['limit_low'] : NULL)];

        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, $options);
    }

    // Humidity/Volts
    if (is_numeric($oids[2]['wtWebGraphThermoHygroBinaryTempValue'])) {
        // Binary coded options for sensor 2:
        //        Octet 1: unused
        //        Octet 2: unused
        //        Octet 3: unused
        //        Octet 4:
        //                Bit 0  :        W&T Sensor rel. humidity (default)
        //                Bit 1  :        Skalar 0-2.5V
        //                Bit 2  :        Disconnect
        //   Bit 3-7:        unused"
        [, , , $octet] = explode(' ', $oids[2]['wtWebGraphThermoHygroPortSensorSelect']);

        $index    = 2;
        $scale    = 0.1;
        $descr    = $oids[2]['wtWebGraphThermoHygroPortName'];
        $oid      = '.1.3.6.1.4.1.5040.1.2.42.1.4.1.1.2';
        $oid_name = 'wtWebGraphThermoHygroBinaryTempValue';
        $value    = $oids[2]['wtWebGraphThermoHygroBinaryTempValue'];

        if ($octet == "01") {
            // Humidity
            $limits               = snmp_get_multi_oid($device, 'wtWebGraphThermoHygroAlarmRHMin.1 wtWebGraphThermoHygroAlarmRHMax.1', [], $mib);
            $limits['limit_high'] = trim($limits[1]['wtWebGraphThermoHygroAlarmRHMax'], ' "');
            $limits['limit_low']  = trim($limits[1]['wtWebGraphThermoHygroAlarmRHMin'], ' "');
            $options              = ['limit_high' => (is_numeric($limits['limit_high']) ? $limits['limit_high'] : NULL),
                                     'limit_low'  => (is_numeric($limits['limit_low']) ? $limits['limit_low'] : NULL)];

            discover_sensor_ng($device, 'humidity', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, $options);
        } elseif ($octet == "02") {
            // Voltage? Not tested
            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, $options);
        }
    }
}

unset($oids, $oid, $descr, $options, $limits, $value);

// EOF
