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

// Raritan External Environmental Sensors
$oids = snmpwalk_cache_oid($device, "externalSensorTable", [], "PDU-MIB");

// PDU-MIB::sensorID.1 = INTEGER: 1
// PDU-MIB::sensorID.2 = INTEGER: 2
// PDU-MIB::externalSensorType.1 = INTEGER: humidity(11)
//   <LIST OF TYPES: rmsCurrent(1), peakCurrent(2), unbalancedCurrent(3), rmsVoltage(4), activePower(5), apparentPower(6), powerFactor(7),
//     activeEnergy(8), apparentEnergy(9), temperature(10), humidity(11), airFlow(12), airPressure(13), onOff(14), trip(15),
//     vibration(16), waterDetection(17), smokeDetection(18), binary(19), contact(20), other(30), none(31)>
// PDU-MIB::externalSensorType.2 = INTEGER: temperature(10)
// PDU-MIB::externalSensorSerialNumber.1 = STRING: <AEI#######>
// PDU-MIB::externalSensorSerialNumber.2 = STRING: <AEI#######>
// PDU-MIB::externalSensorName.1 = STRING: <NAME ASSIGNED VIA WEB>
// PDU-MIB::externalSensorName.2 = STRING: <NAME ASSIGNED VIA WEB>
// PDU-MIB::externalSensorChannelNumber.1 = INTEGER: 1
// PDU-MIB::externalSensorChannelNumber.2 = INTEGER: 1
// PDU-MIB::externalSensorUnits.1 = INTEGER: percent(9)
// <LIST OF UNITS: none(-1), other(0), volt(1), amp(2), watt(3), voltamp(4), wattHour(5), voltampHour(6), degreeC(7), hertz(8), percent(9),
//     meterpersec(10), pascal(11), psi(12), g(13), degreeF(14), feet(15), inches(16), cm(17), meters(18)>
// PDU-MIB::externalSensorUnits.2 = INTEGER: degreeC(7)
// PDU-MIB::externalSensorDecimalDigits.1 = Gauge32: 0
// PDU-MIB::externalSensorDecimalDigits.2 = Gauge32: 1
// PDU-MIB::externalSensorLowerCriticalThreshold.1 = INTEGER: 3
// PDU-MIB::externalSensorLowerCriticalThreshold.2 = INTEGER: 180
// PDU-MIB::externalSensorLowerWarningThreshold.1 = INTEGER: 7
// PDU-MIB::externalSensorLowerWarningThreshold.2 = INTEGER: 200
// PDU-MIB::externalSensorUpperCriticalThreshold.1 = INTEGER: 90
// PDU-MIB::externalSensorUpperCriticalThreshold.2 = INTEGER: 350
// PDU-MIB::externalSensorUpperWarningThreshold.1 = INTEGER: 85
// PDU-MIB::externalSensorUpperWarningThreshold.2 = INTEGER: 330
// PDU-MIB::externalSensorState.1 = INTEGER: normal(4)
// PDU-MIB::externalSensorState.2 = INTEGER: normal(4)
// PDU-MIB::externalSensorValue.1 = INTEGER: 0
// PDU-MIB::externalSensorValue.2 = INTEGER: 0

$sensor_types = [
  'rmsCurrent'     => 'current',
  //'peakCurrent', 'unbalancedCurrent',
  'rmsVoltage'     => 'voltage',
  'activePower'    => 'power',
  'apparentPower'  => 'apower',
  'powerFactor'    => 'powerfactor',
  'activeEnergy'   => 'energy',
  'apparentEnergy' => 'energy',
  'temperature'    => 'temperature',
  'humidity'       => 'humidity',
  'airFlow'        => 'velocity', // No one know, but seems as this is LFM unit
  //'airPressure', 'onOff', 'trip', 'vibration', 'waterDetection', 'smokeDetection', 'binary', 'contact', 'other', 'none'
];
$sensor_units = [
    //none(-1), other(0),
    'volt'        => 'voltage',
    'amp'         => 'current',
    'watt'        => 'power',
    'voltamp'     => 'apower',
    'wattHour'    => 'energy',
    'voltampHour' => 'aenergy',
    'degreeC'     => 'temperature',
    'hertz'       => 'frequency',
    //'percent' => 'humidity',
    //meterpersec(10),
    'pascal'      => 'pressure',
    'psi'         => 'pressure',
    //g(13),
    'degreeF'     => 'temperature',
    //feet(15),
    //inches(16),
    //cm(17),
    //meters(18)
];
foreach ($oids as $index => $entry) {
    $descr = $entry['externalSensorName']; // The name set by the device's admin through Raritan's web interface.
    $oid   = ".1.3.6.1.4.1.13742.4.3.3.1.41.$index";
    $scale = si_to_scale('units', $entry['externalSensorDecimalDigits']);
    $value = $entry['externalSensorValue'];

    if (isset($sensor_types[$entry['externalSensorType']])) {
        $type = $sensor_types[$entry['externalSensorType']];
    } elseif (isset($sensor_units[$entry['externalSensorUnits']])) {
        $type = $sensor_units[$entry['externalSensorUnits']];
    } else {
        // FIXME. Statuses
        continue;
    }

    if (in_array($type, ['energy', 'aenergy'])) {
        // Counters
        discover_counter($device, $type, $mib, 'externalSensorValue', $oid, $index, $descr, $scale, $value);
    } elseif (isset($sensor_types[$entry['externalSensorType']]) && is_numeric($value)) {
        // Sensors
        $options               = [
          'limit_high'      => $entry['externalSensorUpperWarningThreshold'] * $scale,
          'limit_low'       => $entry['externalSensorLowerCriticalThreshold'] * $scale,
          'limit_high_warn' => $entry['externalSensorUpperCriticalThreshold'] * $scale,
          'limit_low_warn'  => $entry['externalSensorLowerWarningThreshold'] * $scale
        ];
        $options['rename_rrd'] = "raritan-0";
        // Units
        if ($sensor_types[$entry['externalSensorType']] === 'velocity') {
            //$options['sensor_unit'] = "LFM";
        }
        if ($entry['externalSensorUnits'] === 'degreeF') {
            $options['sensor_unit'] = "F";
        }

        discover_sensor_ng($device, $sensor_types[$entry['externalSensorType']], $mib, 'externalSensorValue', $oid, $index, NULL, $descr, $scale, $value, $options);
    }
}

// EOF
