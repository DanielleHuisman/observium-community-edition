<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

echo(" Sentry4-MIB ");

$scale = 0.01;
$scale_voltage = 0.1;

$sentry4_LineConfigEntry = snmpwalk_cache_threepart_oid($device, 'St4LineConfigEntry', array(), 'Sentry4-MIB');
$sentry4_LineMonitorEntry = snmpwalk_cache_threepart_oid($device, 'St4LineMonitorEntry', array(), 'Sentry4-MIB');
$sentry4_LineEventEntry = snmpwalk_cache_threepart_oid($device, 'St4LineEventConfigEntry', array(), 'Sentry4-MIB');
$sentry4_PhaseMonitorEntry = snmpwalk_cache_threepart_oid($device, 'St4PhaseMonitorEntry', array(), 'Sentry4-MIB');
$sentry4_PhaseEventEntry = snmpwalk_cache_threepart_oid($device, 'St4PhaseEventConfigEntry', array(), 'Sentry4-MIB');

$sentry4_OutletEntry = snmpwalk_cache_threepart_oid($device, 'st4OutletConfigEntry', array(), 'Sentry4-MIB');
$sentry4_OutletMonitorEntry = snmpwalk_cache_threepart_oid($device, 'st4OutletMonitorEntry', array(), 'Sentry4-MIB');
$sentry4_OutletEventEntry = snmpwalk_cache_threepart_oid($device, 'st4OutletEventConfigEntry', array(), 'Sentry4-MIB');

print_debug_vars($sentry4_LineMonitorEntry);

foreach ($sentry4_LineConfigEntry as $tower => $cords)
{
    foreach ($cords as $cord => $feeds)
    {
        foreach ($feeds as $feed => $entry)
        {
            $descr = str_replace('_', ', ', $entry['st4LineLabel']);
            $index = "$tower.$cord.$feed";

            $line_monitor_entry = $sentry4_LineMonitorEntry[$tower][$cord][$feed];
            $line_event_entry = $sentry4_LineEventEntry[$tower][$cord][$feed];
            $phase_monitor_entry = $sentry4_PhaseMonitorEntry[$tower][$cord][$feed];
            $phase_event_entry = $sentry4_PhaseEventEntry[$tower][$cord][$feed];

            //st4LineCurrent
            $oid   = '.1.3.6.1.4.1.1718.4.1.4.3.1.3.' . $index;
            if (isset($line_monitor_entry['st4LineCurrent']) && $line_monitor_entry['st4LineCurrent'] >= 0)
            {
                $limits = array('limit_high'      => $entry['st4LineCurrentCapacity'],
                                'limit_high_warn' => $line_event_entry['st4LineCurrentHighAlarm']);
                $value  = $line_monitor_entry['st4LineCurrent'];

                discover_sensor('current', $device, $oid, "st4LineCurrent.$index", 'sentry4', $descr, $scale, $value, $limits);
            } else {
                /// FIXME. States for $entry['infeedLoadStatus']
            }

            //st4PhaseVoltage
            $oid   = '.1.3.6.1.4.1.1718.4.1.5.3.1.3.' . $index;
            if (isset($phase_monitor_entry['st4PhaseVoltage']) && $phase_monitor_entry['st4PhaseVoltage'] >= 0)
            {
                $value = $phase_monitor_entry['st4PhaseVoltage'];

                discover_sensor('voltage', $device, $oid, "st4PhaseVoltage.$index", 'sentry4', $descr, $scale_voltage, $value);
            }

            //infeedPower
            $oid   = '.1.3.6.1.4.1.1718.4.1.5.3.1.9.' . $index;
            if (isset($phase_monitor_entry['st4PhaseApparentPower']) && $phase_monitor_entry['st4PhaseApparentPower'] >= 0)
            {
              $value = $phase_monitor_entry['st4PhaseApparentPower'];

              discover_sensor('power', $device, $oid, "st4PhaseApparentPower.$index", 'sentry4', $descr, 1, $value);
            }
        }

        //outletLoadValue
        foreach ($sentry4_OutletEntry[$tower][$feed] as $outlet => $ou_entry)
        {
              $descr = str_replace('_', ', ', $ou_entry['st4OutletName']);
              $index = "$tower.$cord.$outlet";

              $ou_monitor_entry = $sentry4_OutletMonitorEntry[$tower][$feed][$outlet];
              $ou_event_entry = $sentry4_OutletMonitorEntry[$tower][$feed][$outlet];

              $oid   = '.1.3.6.1.4.1.1718.4.1.8.3.1.3.' . $index;
              if (isset($ou_monitor_entry['st4OutletCurrent']) && $ou_monitor_entry['st4OutletCurrent'] >= 0)
              {
                 $limits = array('limit_high' => $ou_entry['st4OutletCurrentCapacity'],
                                 'limit_high_warn'  => $ou_event_entry['st4OutletCurrentHighAlarm']);
                 $value  = $ou_monitor_entry['st4OutletCurrent'];

                 discover_sensor('current', $device, $oid, "st4OutletCurrent.$index", 'sentry4', $descr, $scale, $value, $limits);
             } else {
                /// FIXME. States for $ou_entry['outletLoadStatus'], $ou_entry['outletStatus']
             }
        }
    }
}

// temperature/humidity sensor
$sentry4_TempSensorEntry = snmpwalk_cache_oid($device, 'st4TempSensorConfigEntry', array(), 'Sentry4-MIB');
$sentry4_TempSensorMonitorEntry = snmpwalk_cache_oid($device, 'st4TempSensorMonitorEntry', array(), 'Sentry4-MIB');
$sentry4_TempSensorEventEntry = snmpwalk_cache_oid($device, 'st4TempSensorEventConfigEntry', array(), 'Sentry4-MIB');

$temp_scale = snmp_get($device, '.1.3.6.1.4.1.1718.4.1.9.1.10.0', "-Ovq");

print_debug_vars($sentry4_TempSensorEntry);

foreach ($sentry4_TempSensorEntry as $index => $entry)
{
  $descr = $entry['st4TempSensorName'];

  //st4TempSensorValue
  $oid        = '.1.3.6.1.4.1.1718.4.1.9.3.1.1.'.$index;
  $entry_monitor = $sentry4_TempSensorMonitorEntry[$index];
  $entry_config = $sentry4_TempSensorEventEntry[$index];

    print_debug_vars($entry_monitor);
    print_debug_vars($entry_config);

  if (isset($entry_monitor['st4TempSensorValue']) && $entry_monitor['st4TempSensorValue'] >= 0)
  {
    $value      = $entry_monitor['st4TempSensorValue'];
    $limits     = array('limit_high' => $entry_config['st4TempSensorHighAlarm'],
                        'limit_low'  => $entry_config['st4TempSensorLowWarning']);
    $scale_temp = 0.1;
    if ($temp_scale == 1) # Fahrenheit
    {
      $limits['sensor_unit'] = 'F';
    }

      print_debug_vars($value);
      print_debug_vars($limits);

    discover_sensor('temperature', $device, $oid, "st4TempSensorValue.$index", 'sentry4', $descr, $scale_temp, $value, $limits);
  }
}

//tempHumidSensorHumidValue
$sentry4_HumidSensorEntry = snmpwalk_cache_oid($device, 'st4HumidSensorConfigEntry', array(), 'Sentry4-MIB');
$sentry4_HumidSensorMonitorEntry = snmpwalk_cache_oid($device, 'st4HumidSensorMonitorEntry', array(), 'Sentry4-MIB');
$sentry4_HumidSensorEventEntry = snmpwalk_cache_oid($device, 'st4HumidSensorEventConfigEntry', array(), 'Sentry4-MIB');

print_debug_vars($sentry4_HumidSensorEntry);

foreach ($sentry4_HumidSensorEntry as $index => $entry)
{
  $descr = $entry['st4HumidSensorName'];

  $oid        = '.1.3.6.1.4.1.1718.4.1.10.3.1.1.'.$index;
  $entry_monitor = $sentry4_HumidSensorMonitorEntry[$index];
  $entry_config = $sentry4_HumidSensorEventEntry[$index];

    print_debug_vars($entry_monitor);
    print_debug_vars($entry_config);

  if (isset($entry_monitor['st4HumidSensorValue']) && $entry_monitor['st4HumidSensorValue'] >= 0)
  {
    $limits     = array('limit_high' => $entry_config['st4HumidSensorHighAlarm'],
                        'limit_low'  => $entry_config['st4HumidSensorLowAlarm']);
    $value      = $entry_monitor['st4HumidSensorValue'];

      print_debug_vars($value);
      print_debug_vars($limits);

    discover_sensor('humidity', $device, $oid, "st4HumidSensorValue.$index", 'sentry4', $descr, 1, $value, $limits);
  }
}

// EOF
