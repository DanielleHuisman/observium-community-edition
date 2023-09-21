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

*/

// SensorTypeEnumeration
$oid_types = [
  'rmsCurrent',
  'peakCurrent',
  'unbalancedCurrent',
  'rmsVoltage',
  'activePower',
  'apparentPower',
  'powerFactor',
  //'activeEnergy',   // currently not supported
  //'apparentEnergy', // currently not supported
  'temperature',
  'humidity',
  'airFlow',
  'airPressure',
  'onOff',
  /*
  trip(15),
  */
  'vibration',
  'waterDetection',
  'smokeDetection',
  'binary',
  /*
  contact(20),
  */
  'fanSpeed',
  'absoluteHumidity',
  /*
  other(30),
  none(31),
  */
  'illuminance',
  'doorContact',
  'tamperDetection',
  'motionDetection',
];

// SensorUnitsEnumeration
$oid_units = [
    /*
    none(-1),
    other(0),
    */
    'volt'            => ['type' => 'voltage'],
    'amp'             => ['type' => 'current'],
    'watt'            => ['type' => 'power'],
    'voltamp'         => ['type' => 'apower'],
    /*
    wattHour(5),
    voltampHour(6),
    */
    'degreeC'         => ['type' => 'temperature', 'unit' => 'C'],
    'hertz'           => ['type' => 'frequency'],
    'percent'         => ['type' => 'humidity'],
    'meterpersec'     => ['type' => 'velocity'],
    'pascal'          => ['type' => 'pressure'],
    'psi'             => ['type' => 'pressure', 'unit' => 'psi'],
    /*
    g(13),
    */
    'degreeF'         => ['type' => 'temperature', 'unit' => 'F'],
    /*
    feet(15),
    inches(16),
    cm(17),
    meters(18),
    */
    'rpm'             => ['type' => 'fanspeed'],
    /*
    degrees(20),
    */
    'lux'             => ['type' => 'illuminance'],
    /*
    grampercubicmeter(22),
    */
    'voltampReactive' => ['type' => 'rpower'],
];

$oids = snmpwalk_cache_oid($device, 'externalSensorConfigurationEntry', [], $mib);
$oids = snmpwalk_cache_oid($device, 'externalSensorMeasurementsEntry', $oids, $mib);
if (OBS_DEBUG > 1) {
    print_vars($oids);
}

foreach ($oids as $index => $entry) {
    if (!in_array($entry['externalSensorType'], $oid_types) || $entry['measurementsExternalSensorIsAvailable'] == 'false') {
        continue;
    }

    $descr = $entry['externalSensorName'];
    if ($entry['externalSensorPort']) {
        $descr .= ' - ' . $entry['externalSensorPort'];
    }

    $scale = si_to_scale($entry['externalSensorDecimalDigits'] * -1);

    $oid_name = 'measurementsExternalSensorValue';
    $oid_num  = '.1.3.6.1.4.1.13742.8.2.1.1.1.3.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    // Limits (based on enabled thresholds)
    //  SYNTAX BITS {
    //    lowerCritical(0),
    //    lowerWarning(1),
    //    upperWarning(2),
    //    upperCritical(3),
    // }
    $options      = [];
    $limits_flags = base_convert(str_replace(' ', '', $entry['externalSensorEnabledThresholds']), 16, 10);
    if (is_flag_set(bindec(10000000), $limits_flags)) // 0b 1000 0000
    {
        $options['limit_low'] = $entry['externalSensorLowerCriticalThreshold'] * $scale;
    }
    if (is_flag_set(bindec(1000000), $limits_flags)) // 0b 0100 0000
    {
        $options['limit_low_warn'] = $entry['externalSensorLowerWarningThreshold'] * $scale;
    }
    if (is_flag_set(bindec(100000), $limits_flags)) // 0b 0010 0000
    {
        $options['limit_high_warn'] = $entry['externalSensorUpperWarningThreshold'] * $scale;
    }
    if (is_flag_set(bindec(10000), $limits_flags)) // 0b 0001 0000
    {
        $options['limit_high'] = $entry['externalSensorUpperCriticalThreshold'] * $scale;
    }

    // Detect type & unit
    $unit = [];
    if (isset($oid_units[$entry['externalSensorUnits']])) {
        $unit = $oid_units[$entry['externalSensorUnits']];
    } else {
        // Other sensors based on SensorTypeEnumeration
        switch ($entry['externalSensorType']) {
            case 'powerFactor':
                $unit = ['type' => 'powerfactor'];
                break;

            // Status sensors
            case 'onOff':
            case 'waterDetection':
            case 'smokeDetection':
            case 'binary':
            case 'doorContact':
            case 'tamperDetection':
            case 'motionDetection':
                $oid_name = 'measurementsExternalSensorState';
                $oid_num  = '.1.3.6.1.4.1.13742.8.2.1.1.1.2.' . $index;
                $type     = 'emdSensorStateEnumeration';
                $value    = $entry[$oid_name];

                discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
                break;
        }
    }

    if (isset($unit['type'])) {
        if (isset($unit['unit'])) {
            $options['sensor_unit'] = $unit['unit'];
        }

        discover_sensor_ng($device, $unit['type'], $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
    }
}

// EOF
