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

// ATEN-PE-CFG::deviceCurrent.1 = STRING: "0.19"
// ATEN-PE-CFG::deviceVoltage.1 = STRING: "242.83"
// ATEN-PE-CFG::devicePower.1 = STRING: "20.1480"
// ATEN-PE-CFG::devicePowerDissipation.1 = STRING: "8701.5152"
// ATEN-PE-CFG::inputMaxVoltage.1 = INTEGER: 240
// ATEN-PE-CFG::inputMaxCurrent.1 = INTEGER: 10
// ATEN-PE-CFG::powerCapacity.1 = INTEGER: 2400

// ATEN-PE-CFG::deviceIntegerCurrent.1 = INTEGER: 190
// ATEN-PE-CFG::deviceIntegerVoltage.1 = INTEGER: 242830
// ATEN-PE-CFG::deviceIntegerPower.1 = INTEGER: 20148
// ATEN-PE-CFG::deviceIntegerPowerDissipation.1 = INTEGER: 8701516

//ATEN-PE-CFG::deviceIntegerValueIndex.1 = INTEGER: 1
//ATEN-PE-CFG::deviceIntegerCurrent.1 = INTEGER: 2460
//ATEN-PE-CFG::deviceIntegerVoltage.1 = INTEGER: 232700
//ATEN-PE-CFG::deviceIntegerPower.1 = INTEGER: 516810
//ATEN-PE-CFG::deviceIntegerPowerDissipation.1 = INTEGER: 81713
//ATEN-PE-CFG::deviceMinCurMT.1 = INTEGER: -3000
//ATEN-PE-CFG::deviceMaxCurMT.1 = INTEGER: -3000
//ATEN-PE-CFG::deviceMinVolMT.1 = INTEGER: -3000
//ATEN-PE-CFG::deviceMaxVolMT.1 = INTEGER: -3000
//ATEN-PE-CFG::deviceMinPMT.1 = INTEGER: -3000
//ATEN-PE-CFG::deviceMaxPMT.1 = INTEGER: -3000
//ATEN-PE-CFG::deviceMaxPDMT.1 = INTEGER: -3000

$oids = snmpwalk_cache_oid($device, "deviceIntegerValueEntry", [], 'ATEN-PE-CFG');
$oids = snmpwalk_cache_oid($device, "deviceConfigEntry", $oids, 'ATEN-PE-CFG');
print_debug_vars($oids);

$count = safe_count($oids);
$scale = 0.001;

foreach ($oids as $index => $entry) {
    $descr = ($count > 1 ? "Device $index" : "Device");

    // Current
    if (is_numeric($entry['deviceIntegerCurrent']) && $entry['deviceIntegerCurrent'] != -2000000) {
        $oid     = '.1.3.6.1.4.1.21317.1.3.2.2.2.1.99.1.2.' . $index;
        $value   = $entry['deviceIntegerCurrent'];
        $options = ['limit_high' => (isset($entry['deviceMaxCurMT']) && $entry['deviceMaxCurMT'] > -3000 ? $entry['deviceMaxCurMT'] * 0.1 : NULL),
                    'limit_low'  => (isset($entry['deviceMinCurMT']) && $entry['deviceMinCurMT'] > -3000 ? $entry['deviceMinCurMT'] * 0.1 : NULL)];

        $options['rename_rrd'] = "aten-pe-deviceIntegerCurrent.$index";
        discover_sensor_ng($device, 'current', $mib, 'deviceIntegerCurrent', $oid, $index, NULL, $descr . ' Current', $scale, $value, $options);
    }

    // Voltage
    if (is_numeric($entry['deviceIntegerVoltage']) && $entry['deviceIntegerVoltage'] != -2000000) {
        $oid     = '.1.3.6.1.4.1.21317.1.3.2.2.2.1.99.1.3.' . $index;
        $value   = $entry['deviceIntegerVoltage'];
        $options = ['limit_high' => (isset($entry['deviceMaxVolMT']) && $entry['deviceMaxVolMT'] > -3000 ? $entry['deviceMaxVolMT'] * 0.1 : NULL),
                    'limit_low'  => (isset($entry['deviceMinVolMT']) && $entry['deviceMinVolMT'] > -3000 ? $entry['deviceMinVolMT'] * 0.1 : NULL)];

        $options['rename_rrd'] = "aten-pe-deviceIntegerVoltage.$index";
        discover_sensor_ng($device, 'voltage', $mib, 'deviceIntegerVoltage', $oid, $index, NULL, $descr . ' Voltage', $scale, $value, $options);
    }

    // Power
    if (is_numeric($entry['deviceIntegerPower']) && $entry['deviceIntegerPower'] != -2000000) {
        $oid     = '.1.3.6.1.4.1.21317.1.3.2.2.2.1.99.1.4.' . $index;
        $value   = $entry['deviceIntegerPower'];
        $options = ['limit_high' => (isset($entry['deviceMaxPMT']) && $entry['deviceMaxPMT'] > -3000 ? $entry['deviceMaxPMT'] * 0.1 : NULL),
                    'limit_low'  => (isset($entry['deviceMinPMT']) && $entry['deviceMinPMT'] > -3000 ? $entry['deviceMinPMT'] * 0.1 : NULL)];

        $options['rename_rrd'] = "aten-pe-deviceIntegerPower.$index";
        discover_sensor_ng($device, 'power', $mib, 'deviceIntegerPower', $oid, $index, NULL, $descr . ' Power', $scale, $value, $options);
    }

    /* FIXME. Currently unsupported
    // Power Dissipation
    if (is_numeric($entry['deviceIntegerPowerDissipation']) && $entry['deviceIntegerPowerDissipation'] != -2000000) {
      $oid     = '.1.3.6.1.4.1.21317.1.3.2.2.2.1.99.1.5.' . $index;
      $value   = $entry['deviceIntegerPowerDissipation'];
      $options = array('limit_high' => (isset($entry['deviceMaxPDMT']) && $entry['deviceMaxPDMT'] > -3000 ? $entry['deviceMaxPDMT'] * 0.1 : NULL),
                       'limit_low'  => (isset($entry['deviceMinPDMT']) && $entry['deviceMinPDMT'] > -3000 ? $entry['deviceMinPDMT'] * 0.1 : NULL));

      discover_sensor('counter', $device, $oid, "deviceIntegerPowerDissipation.$index", 'aten-pe', $descr . ' Power Dissipation', $scale, $value, $options);
    }
    */
}

//ATEN-PE-CFG::sensorIntegerValueIndex.1 = INTEGER: 1
//ATEN-PE-CFG::sensorIntegerValueIndex.2 = INTEGER: 2
//ATEN-PE-CFG::sensorIntegerValueIndex.3 = INTEGER: 3
//ATEN-PE-CFG::sensorIntegerValueIndex.4 = INTEGER: 4
//ATEN-PE-CFG::sensorIntegerValueIndex.5 = INTEGER: 5
//ATEN-PE-CFG::sensorIntegerValueIndex.6 = INTEGER: 6
// Scale 0.001
//ATEN-PE-CFG::sensorIntegerTemperature.1 = INTEGER: 26500
//ATEN-PE-CFG::sensorIntegerTemperature.2 = INTEGER: -1000000
//ATEN-PE-CFG::sensorIntegerTemperature.3 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerTemperature.4 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerTemperature.5 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerTemperature.6 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerHumidity.1 = INTEGER: 37000
//ATEN-PE-CFG::sensorIntegerHumidity.2 = INTEGER: -1000000
//ATEN-PE-CFG::sensorIntegerHumidity.3 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerHumidity.4 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerHumidity.5 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerHumidity.6 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerPressure.1 = INTEGER: -1000000
//ATEN-PE-CFG::sensorIntegerPressure.2 = INTEGER: -1000000
//ATEN-PE-CFG::sensorIntegerPressure.3 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerPressure.4 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerPressure.5 = INTEGER: -2000000
//ATEN-PE-CFG::sensorIntegerPressure.6 = INTEGER: -2000000
// Scale 0.1
//ATEN-PE-CFG::sensorMinTempMT.1 = INTEGER: 170
//ATEN-PE-CFG::sensorMinTempMT.2 = INTEGER: -3000
//ATEN-PE-CFG::sensorMinTempMT.3 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinTempMT.4 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinTempMT.5 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinTempMT.6 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxTempMT.1 = INTEGER: 330
//ATEN-PE-CFG::sensorMaxTempMT.2 = INTEGER: -3000
//ATEN-PE-CFG::sensorMaxTempMT.3 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxTempMT.4 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxTempMT.5 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxTempMT.6 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinHumMT.1 = INTEGER: 200
//ATEN-PE-CFG::sensorMinHumMT.2 = INTEGER: -3000
//ATEN-PE-CFG::sensorMinHumMT.3 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinHumMT.4 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinHumMT.5 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinHumMT.6 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxHumMT.1 = INTEGER: 600
//ATEN-PE-CFG::sensorMaxHumMT.2 = INTEGER: -3000
//ATEN-PE-CFG::sensorMaxHumMT.3 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxHumMT.4 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxHumMT.5 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxHumMT.6 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinPressMT.1 = INTEGER: -3000
//ATEN-PE-CFG::sensorMinPressMT.2 = INTEGER: -3000
//ATEN-PE-CFG::sensorMinPressMT.3 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinPressMT.4 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinPressMT.5 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMinPressMT.6 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxPressMT.1 = INTEGER: -3000
//ATEN-PE-CFG::sensorMaxPressMT.2 = INTEGER: -3000
//ATEN-PE-CFG::sensorMaxPressMT.3 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxPressMT.4 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxPressMT.5 = INTEGER: -2000000
//ATEN-PE-CFG::sensorMaxPressMT.6 = INTEGER: -2000000

$oids  = snmpwalk_cache_oid($device, "sensorIntegerValueEntry", [], 'ATEN-PE-CFG');
$oids  = snmpwalk_cache_oid($device, "deviceSensorTresholdEntry", $oids, 'ATEN-PE-CFG');
$scale = 0.001;

print_debug_vars($oids);
foreach ($oids as $index => $entry) {
    // Temperature
    if (is_numeric($entry['sensorIntegerTemperature']) && $entry['sensorIntegerTemperature'] > -1000000) {
        $oid     = '.1.3.6.1.4.1.21317.1.3.2.2.2.1.100.1.2.' . $index;
        $value   = $entry['sensorIntegerTemperature'];
        $options = ['limit_high' => (isset($entry['sensorMaxTempMT']) && $entry['sensorMaxTempMT'] > -3000 ? $entry['sensorMaxTempMT'] * 0.1 : NULL),
                    'limit_low'  => (isset($entry['sensorMinTempMT']) && $entry['sensorMinTempMT'] > -3000 ? $entry['sensorMinTempMT'] * 0.1 : NULL)];

        $options['rename_rrd'] = "aten-pe-sensorIntegerTemperature.$index";
        discover_sensor_ng($device, 'temperature', $mib, 'sensorIntegerTemperature', $oid, $index, NULL, "Temperature $index", $scale, $value, $options);
    }

    // Humidity
    if (is_numeric($entry['sensorIntegerHumidity']) && $entry['sensorIntegerHumidity'] > -1000000) {
        $oid     = '.1.3.6.1.4.1.21317.1.3.2.2.2.1.100.1.3.' . $index;
        $value   = $entry['sensorIntegerHumidity'];
        $options = ['limit_high' => (isset($entry['sensorMaxHumMT']) && $entry['sensorMaxHumMT'] > -3000 ? $entry['sensorMaxHumMT'] * 0.1 : NULL),
                    'limit_low'  => (isset($entry['sensorMinHumMT']) && $entry['sensorMinHumMT'] > -3000 ? $entry['sensorMinHumMT'] * 0.1 : NULL)];

        $options['rename_rrd'] = "aten-pe-sensorIntegerHumidity.$index";
        discover_sensor_ng($device, 'humidity', $mib, 'sensorIntegerHumidity', $oid, $index, NULL, "Humidity $index", $scale, $value, $options);
    }

    // Pressure
    if (is_numeric($entry['sensorIntegerPressure']) && $entry['sensorIntegerPressure'] > -1000000) {
        $oid     = '.1.3.6.1.4.1.21317.1.3.2.2.2.1.100.1.2.' . $index;
        $value   = $entry['sensorIntegerPressure'];
        $options = ['limit_high' => (isset($entry['sensorMaxPressMT']) && $entry['sensorMaxPressMT'] > -3000 ? $entry['sensorMaxPressMT'] * 0.1 : NULL),
                    'limit_low'  => (isset($entry['sensorMinPressMT']) && $entry['sensorMinPressMT'] > -3000 ? $entry['sensorMinPressMT'] * 0.1 : NULL)];

        $options['rename_rrd'] = "aten-pe-sensorIntegerPressure.$index";
        discover_sensor_ng($device, 'pressure', $mib, 'sensorIntegerPressure', $oid, $index, NULL, "Pressure $index", $scale, $value, $options);
    }
}

// Can be up to 42
// ATEN-PE-CFG::outletnumber.0 = INTEGER: 8
// ATEN-PE-CFG::outlet1Status.0 = INTEGER: on(2)
// ATEN-PE-CFG::outlet2Status.0 = INTEGER: on(2)
// ATEN-PE-CFG::outlet3Status.0 = INTEGER: off(1)
// ATEN-PE-CFG::outlet4Status.0 = INTEGER: off(1)
// ATEN-PE-CFG::outlet5Status.0 = INTEGER: off(1)
// ATEN-PE-CFG::outlet6Status.0 = INTEGER: off(1)
// ATEN-PE-CFG::outlet7Status.0 = INTEGER: off(1)
// ATEN-PE-CFG::outlet8Status.0 = INTEGER: off(1)
// ATEN-PE-CFG::outlet9Status.0 = INTEGER: not-support(7)
// ATEN-PE-CFG::outlet10Status.0 = INTEGER: not-support(7)

$max_outlets = snmp_get_oid($device, 'outletnumber.0', 'ATEN-PE-CFG');
for ($i = 1; $i <= $max_outlets; $i++) {
    $descr    = 'Outlet ' . $i . ' (' . snmp_get_oid($device, 'outletName.' . $i, 'ATEN-PE-CFG') . ')';
    $oid_name = 'outlet' . $i . 'Status';
    $oid_num  = snmp_translate($oid_name . '.0', $mib);
    $value    = snmp_get_oid($device, $oid_name . '.0', 'ATEN-PE-CFG');
    discover_status_ng($device, $mib, $oid_name, $oid_num, '0', 'outletStatus', $descr, $value, ['entPhysicalClass' => 'outlet']);
}

// EOF
