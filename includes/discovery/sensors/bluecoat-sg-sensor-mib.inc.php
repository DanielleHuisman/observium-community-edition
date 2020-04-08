<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

//BLUECOAT-SG-SENSOR-MIB::deviceSensorTrapEnabled.11 = INTEGER: false(2)
//BLUECOAT-SG-SENSOR-MIB::deviceSensorUnits.11 = INTEGER: volts(4)
//BLUECOAT-SG-SENSOR-MIB::deviceSensorScale.11 = INTEGER: -2
//BLUECOAT-SG-SENSOR-MIB::deviceSensorValue.10 = INTEGER: 1194
//BLUECOAT-SG-SENSOR-MIB::deviceSensorValue.11 = INTEGER: 330
//BLUECOAT-SG-SENSOR-MIB::deviceSensorCode.11 = INTEGER: ok(1)
//BLUECOAT-SG-SENSOR-MIB::deviceSensorStatus.11 = INTEGER: ok(1)
//BLUECOAT-SG-SENSOR-MIB::deviceSensorTimeStamp.11 = Timeticks: (3426521929) 396 days, 14:06:59.29 Hundredths of seconds
//BLUECOAT-SG-SENSOR-MIB::deviceSensorName.11 = STRING: +3.3V bus voltage 2 (Vcc)

$sensor_array = snmpwalk_cache_multi_oid($device, 'deviceSensorValueTable', array(), 'BLUECOAT-SG-SENSOR-MIB');

$sensor_type_map = array(
  'volts'     => 'voltage',
  'rpm'       => 'fanspeed',
  'celsius'   => 'temperature',
  'dBm'       => 'dbm'
);

foreach ($sensor_array as $index => $entry)
{
  if ($sensor_type_map[$entry['deviceSensorUnits']] && is_numeric($entry['deviceSensorValue']) && is_numeric($index) &&
      $entry['deviceSensorStatus'] != 'unavailable' && $entry['deviceSensorStatus'] != 'nonoperational')
  {
    $ok      = TRUE;

    $options = array();
    $descr   = rewrite_entity_name($entry['deviceSensorName']);
    $oid     = ".1.3.6.1.4.1.3417.2.1.1.1.1.1.5.".$index;
    $type    = $sensor_type_map[$entry['deviceSensorUnits']];
    $scale   = si_to_scale($entry['deviceSensorScale']);
    $value   = $entry['deviceSensorValue'];
    $oid_name = 'bluecoat-sg-proxy-mib';

    if ($type == 'temperature')
    {
      if ($value * $scale > 200) { $ok = FALSE; }
    }
    if ($value == -127) { $ok = FALSE; }

    if ($ok)
    {
      $options['rename_rrd'] = "bluecoat-sg-proxy-mib-$index";
      discover_sensor_ng($device, $type, $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, $options);
    }
  }
}

unset($sensor_type_map, $oids, $oids_arista, $sensor_array, $index, $scale, $type, $value, $descr, $ok, $options);

// EOF
