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

echo(" Sentry4-MIB ");

// temperature/humidity sensor
$sentry4_TempSensorEntry        = snmpwalk_cache_oid($device, 'st4TempSensorConfigEntry', [], 'Sentry4-MIB');
$sentry4_TempSensorMonitorEntry = snmpwalk_cache_oid($device, 'st4TempSensorMonitorEntry', [], 'Sentry4-MIB');
$sentry4_TempSensorEventEntry   = snmpwalk_cache_oid($device, 'st4TempSensorEventConfigEntry', [], 'Sentry4-MIB');

$temp_unit = snmp_get($device, '.1.3.6.1.4.1.1718.4.1.9.1.10.0', "-Ovq");
$scale_temp = 0.1;

print_debug_vars($sentry4_TempSensorEntry);

foreach ($sentry4_TempSensorEntry as $index => $entry) {
    $descr = $entry['st4TempSensorName'];

    //st4TempSensorValue
    $oid           = '.1.3.6.1.4.1.1718.4.1.9.3.1.1.' . $index;
    $entry_monitor = $sentry4_TempSensorMonitorEntry[$index];
    $entry_config  = $sentry4_TempSensorEventEntry[$index];

    print_debug_vars($entry_monitor);
    print_debug_vars($entry_config);

    if (isset($entry_monitor['st4TempSensorValue']) && $entry_monitor['st4TempSensorValue'] >= 0) {
        $value      = $entry_monitor['st4TempSensorValue'];
        $limits     = ['limit_high' => $entry_config['st4TempSensorHighAlarm'],
                       'limit_low'  => $entry_config['st4TempSensorLowWarning']];

        if ($temp_unit == 1) # Fahrenheit
        {
            $limits['sensor_unit'] = 'F';
        }

        print_debug_vars($value);
        print_debug_vars($limits);

        discover_sensor('temperature', $device, $oid, "st4TempSensorValue.$index", 'sentry4', $descr, $scale_temp, $value, $limits);
    }
}

//tempHumidSensorHumidValue
$sentry4_HumidSensorEntry        = snmpwalk_cache_oid($device, 'st4HumidSensorConfigEntry', [], 'Sentry4-MIB');
$sentry4_HumidSensorMonitorEntry = snmpwalk_cache_oid($device, 'st4HumidSensorMonitorEntry', [], 'Sentry4-MIB');
$sentry4_HumidSensorEventEntry   = snmpwalk_cache_oid($device, 'st4HumidSensorEventConfigEntry', [], 'Sentry4-MIB');

print_debug_vars($sentry4_HumidSensorEntry);

foreach ($sentry4_HumidSensorEntry as $index => $entry) {
    $descr = $entry['st4HumidSensorName'];

    $oid           = '.1.3.6.1.4.1.1718.4.1.10.3.1.1.' . $index;
    $entry_monitor = $sentry4_HumidSensorMonitorEntry[$index];
    $entry_config  = $sentry4_HumidSensorEventEntry[$index];

    print_debug_vars($entry_monitor);
    print_debug_vars($entry_config);

    if (isset($entry_monitor['st4HumidSensorValue']) && $entry_monitor['st4HumidSensorValue'] >= 0) {
        $limits = ['limit_high' => $entry_config['st4HumidSensorHighAlarm'],
                   'limit_low'  => $entry_config['st4HumidSensorLowAlarm']];
        $value  = $entry_monitor['st4HumidSensorValue'];

        print_debug_vars($value);
        print_debug_vars($limits);

        discover_sensor('humidity', $device, $oid, "st4HumidSensorValue.$index", 'sentry4', $descr, 1, $value, $limits);
    }
}

// EOF
