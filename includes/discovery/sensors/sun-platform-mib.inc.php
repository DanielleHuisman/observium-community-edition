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
SUN-PLATFORM-MIB::sunPlatNumericSensorBaseUnits.5 = INTEGER: degC(3)
SUN-PLATFORM-MIB::sunPlatNumericSensorBaseUnits.15 = INTEGER: watts(8)
SUN-PLATFORM-MIB::sunPlatNumericSensorExponent.5 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorExponent.15 = INTEGER: -3
SUN-PLATFORM-MIB::sunPlatNumericSensorRateUnits.5 = INTEGER: none(1)
SUN-PLATFORM-MIB::sunPlatNumericSensorRateUnits.15 = INTEGER: none(1)
SUN-PLATFORM-MIB::sunPlatNumericSensorCurrent.5 = INTEGER: 37
SUN-PLATFORM-MIB::sunPlatNumericSensorCurrent.15 = INTEGER: 224000
SUN-PLATFORM-MIB::sunPlatNumericSensorNormalMin.5 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorNormalMin.15 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorNormalMax.5 = INTEGER: 255
SUN-PLATFORM-MIB::sunPlatNumericSensorNormalMax.15 = INTEGER: 255
SUN-PLATFORM-MIB::sunPlatNumericSensorAccuracy.5 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorAccuracy.15 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorLowerThresholdNonCritical.5 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorLowerThresholdNonCritical.15 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorUpperThresholdNonCritical.5 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorUpperThresholdNonCritical.15 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorLowerThresholdCritical.5 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorLowerThresholdCritical.15 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorUpperThresholdCritical.5 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorUpperThresholdCritical.15 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorLowerThresholdFatal.5 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorLowerThresholdFatal.15 = INTEGER: -2147483648
SUN-PLATFORM-MIB::sunPlatNumericSensorUpperThresholdFatal.5 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorUpperThresholdFatal.15 = INTEGER: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorHysteresis.5 = Gauge32: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorHysteresis.15 = Gauge32: 0
SUN-PLATFORM-MIB::sunPlatNumericSensorEnabledThresholds.5 = BITS: 00
SUN-PLATFORM-MIB::sunPlatNumericSensorEnabledThresholds.15 = BITS: 00
SUN-PLATFORM-MIB::sunPlatNumericSensorRestoreDefaultThresholds.5 = INTEGER: reset(1)
SUN-PLATFORM-MIB::sunPlatNumericSensorRestoreDefaultThresholds.15 = INTEGER: reset(1)

SUN-PLATFORM-MIB::sunPlatEquipmentLocationName.5 = STRING: /CH/CMM
SUN-PLATFORM-MIB::sunPlatEquipmentLocationName.15 = STRING: /CH/BL0
*/

$SunPlatBaseUnits = [
    /*
    other(0),
    unknown(1),
    */
    'degC'  => ['type' => 'temperature', 'unit' => 'C'],
    'degF'  => ['type' => 'temperature', 'unit' => 'F'],
    'degK'  => ['type' => 'temperature', 'unit' => 'K'],
    'volts' => ['type' => 'voltage'],
    'amps'  => ['type' => 'current'],
    'watts' => ['type' => 'power'],
    /*
    joules(9),
    coulombs(10),
    */
    'va'    => ['type' => 'apower'],
    /*
    nits(12),
    lumens(13),
    */
    'lux'   => ['type' => 'illuminance'],
    /*
    candelas(15),
    */
    'kPa'   => ['type' => 'pressure'],
    'psi'   => ['type' => 'pressure', 'unit' => 'psi'],
    /*
    newtons(18),
    */
    'cfm'   => ['type' => 'airflow'], //, 'unit' => 'CFM'),
    'rpm'   => ['type' => 'fanspeed'],
    'hertz' => ['type' => 'frequency'],
    /*
    seconds(22),
    minutes(23),
    hours(24),
    days(25),
    weeks(26),
    mils(27),
    inches(28),
    feet(29),
    cubicInches(30),
    cubicFeet(31),
    meters(32),
    cubicCentimeters(33),
    cubicMeters(34),
    liters(35),
    fluidOunces(36),
    radians(37),
    steradians(38),
    revolutions(39),
    cycles(40),
    gravities(41),
    ounces(42),
    pounds(43),
    footPounds(44),
    ounceInches(45),
    gauss(46),
    gilberts(47),
    henries(48),
    farads(49),
    */
    'ohms'  => ['type' => 'resistance'],
    /*
    siemens(51),
    moles(52),
    becquerels(53),
    ppm(54),
    decibels(55),
    dBA(56),
    dbC(57),
    grays(58),
    sieverts(59),
    colorTemperatureDegK(60), // NOTE, for future, color temp always storied as Kelvin!
    bits(61),
    bytes(62),
    words(63),
    doubleWords(64),
    quadWords(65),
    percentage(66),
    */
];

$oids = snmpwalk_cache_oid($device, 'sunPlatNumericSensorEntry', [], $mib);
$oids = snmpwalk_cache_oid($device, 'sunPlatEquipmentLocationName', $oids, $mib);

foreach ($oids as $index => $entry) {
    if (!isset($entry['sunPlatNumericSensorBaseUnits']) ||
        !isset($SunPlatBaseUnits[$entry['sunPlatNumericSensorBaseUnits']]) ||
        !isset($entry['sunPlatNumericSensorCurrent'])) {
        continue;
    }

    $descr = $entry['sunPlatEquipmentLocationName'];
    $scale = si_to_scale($entry['sunPlatNumericSensorExponent']);

    $oid_name = 'sunPlatNumericSensorCurrent';
    $oid_num  = '.1.3.6.1.4.1.42.2.70.101.1.1.8.1.4.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    // Limits (based on enabled thresholds)
    //  SYNTAX BITS {
    //    lowerThresholdNonCritical(0),
    //    upperThresholdNonCritical(1),
    //    lowerThresholdCritical(2),
    //    upperThresholdCritical(3),
    //    lowerThresholdFatal(4),
    //    upperThresholdFatal(5)
    // }
    $options      = [];
    $limits_flags = base_convert(str_replace(' ', '', $entry['sunPlatNumericSensorEnabledThresholds']), 16, 10);
    if (is_flag_set(bindec(10), $limits_flags)) {
        $options['limit_low_warn'] = $entry['sunPlatNumericSensorLowerThresholdCritical'];
    }
    if (is_flag_set(bindec(100), $limits_flags)) {
        $options['limit_high_warn'] = $entry['sunPlatNumericSensorUpperThresholdCritical'];
    }
    if (is_flag_set(bindec(1000), $limits_flags)) {
        $options['limit_low'] = $entry['sunPlatNumericSensorLowerThresholdFatal'];
    }
    if (is_flag_set(bindec(10000), $limits_flags)) {
        $options['limit_high'] = $entry['sunPlatNumericSensorUpperThresholdFatal'];
    }

    $unit = $SunPlatBaseUnits[$entry['sunPlatNumericSensorBaseUnits']];
    if (isset($unit['unit'])) {
        $options['sensor_unit'] = $unit['unit'];
    }

    if (in_array($unit['type'], ['airflow', 'fanspeed']) && $entry['sunPlatNumericSensorRateUnits'] != 'none') {
        $scale = 1; // Hardcode scale for airflow/fanspeed since some devices report incorrect sunPlatNumericSensorExponent
        /*
        // fanspeed and airflow should be in per/min unit
        // none(1), perMicroSecond(2), perMilliSecond(3), perSecond(4), perMinute(5),
        // perHour(6), perDay(7), perWeek(8), perMonth(9), perYear(10)
        switch ($entry['sunPlatNumericSensorRateUnits'])
        {
          case 'perMicroSecond':
            $scale *= 60 * 0.000001;
            break;
          case 'perMilliSecond':
            $scale *= 60 * 0.001;
            break;
          case 'perSecond':
            $scale *= 60;
            break;
          case 'perHour':
            $scale /= 60;
            break;
        }
        */
    } elseif ($entry['sunPlatNumericSensorBaseUnits'] == 'kPa') {
        $scale /= 1000;
    }

    // Additionally set correct limits
    foreach (['limit_low_warn', 'limit_high_warn', 'limit_low', 'limit_high'] as $limit) {
        if (isset($options[$limit]) && $scale != 1) {
            $options[$limit] *= $scale;
        }
    }

    discover_sensor($unit['type'], $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
}

// EOF
