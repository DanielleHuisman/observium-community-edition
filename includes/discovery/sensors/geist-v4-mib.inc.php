<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$scale = 0.1;

// GEIST-V4-MIB::productTitle.0 = STRING: GBB15
// GEIST-V4-MIB::productVersion.0 = STRING: 1.5.4
// GEIST-V4-MIB::productFriendlyName.0 = STRING: GBB15
// GEIST-V4-MIB::productMacAddress.0 = Hex-STRING: 00 04 A3 7F 9F 83
// GEIST-V4-MIB::productUrl.0 = IpAddress: 76.79.48.112
// GEIST-V4-MIB::deviceCount.0 = INTEGER: 1
// GEIST-V4-MIB::temperatureUnits.0 = INTEGER: 1
// GEIST-V4-MIB::internalIndex.1 = INTEGER: 1
// GEIST-V4-MIB::internalSerial.1 = STRING: 670004A37F9F83C3
// GEIST-V4-MIB::internalName.1 = STRING: GBB15
// GEIST-V4-MIB::internalAvail.1 = Gauge32: 1
// GEIST-V4-MIB::internalTemp.1 = INTEGER: 262 0.1 Degrees
// GEIST-V4-MIB::internalHumidity.1 = INTEGER: 14 %
// GEIST-V4-MIB::internalDewPoint.1 = INTEGER: -33 0.1 Degrees
// GEIST-V4-MIB::internalIO1.1 = INTEGER: 100
// GEIST-V4-MIB::internalIO2.1 = INTEGER: 100
// GEIST-V4-MIB::internalIO3.1 = INTEGER: 100
// GEIST-V4-MIB::internalIO4.1 = INTEGER: 100
// GEIST-V4-MIB::internalRelayState.1 = Gauge32: 0

if (snmp_get_oid($device, 'temperatureUnits.0', $mib) === '0') // 0 - fahrenheit, 1 - celsius
{
  $temp_options = array('sensor_unit' => 'F');
} else {
  $temp_options = array('sensor_unit' => 'C');
}

$oids = snmpwalk_cache_multi_oid($device, 'internalTable', array(), 'GEIST-V4-MIB');
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  if ($entry['internalAvail'])
  {
    $descr = $entry['internalName'] . ' Temperature';

    $oid   = ".1.3.6.1.4.1.21239.5.1.2.1.5.$index";
    $value = $entry['internalTemp'];

    if (is_numeric($value))
    {
      discover_sensor('temperature', $device, $oid, 'internalTemp.'.$index, 'geist-v4-mib', $descr, $scale, $value, $temp_options);
    }

    $descr = $entry['internalName'] . ' Dew Point';
    $oid   = ".1.3.6.1.4.1.21239.5.1.2.1.7.$index";
    $value = $entry['internalDewPoint'];

    if (is_numeric($value))
    {
      // CLEANME not before 08/2018
      $old_rrd_array = array('descr' => $descr, 'class' => 'temperature', 'type' => 'geist-v4-mib', 'index' => 'internalDewPoint.'.$index);
      rename_rrd_entity($device, 'sensor', $old_rrd_array, array('class' => 'dewpoint'));
      unset($old_rrd_array);

      discover_sensor('dewpoint', $device, $oid, 'internalDewPoint.'.$index, 'geist-v4-mib', $descr, $scale, $value, $temp_options);
    }

    $descr = $entry['internalName'] . ' Humidity';
    $oid   = ".1.3.6.1.4.1.21239.5.1.2.1.6.$index";
    $value = $entry['internalHumidity'];

    if (is_numeric($value))
    {
      discover_sensor('humidity', $device, $oid, 'internalHumidity.'.$index, 'geist-v4-mib', $descr, 1, $value);
    }

    $descr = $entry['climateName'] . ' Analog I/O Sensor 1';
    $oid   = ".1.3.6.1.4.1.21239.5.1.2.1.8.$index";
    $value = $entry['internalIO1'];

    if ($value != '')
    {
      discover_status($device, $oid, 'internalIO1.'.$index, 'geist-v4-mib-io-state', $descr, $value, array('entPhysicalClass' => 'other'));
    }

    $descr = $entry['climateName'] . ' Analog I/O Sensor 2';
    $oid   = ".1.3.6.1.4.1.21239.5.1.2.1.9.$index";
    $value = $entry['internalIO2'];

    if ($value != '')
    {
      discover_status($device, $oid, 'internalIO2.'.$index, 'geist-v4-mib-io-state', $descr, $value, array('entPhysicalClass' => 'other'));
    }

    $descr = $entry['climateName'] . ' Analog I/O Sensor 3';
    $oid   = ".1.3.6.1.4.1.21239.5.1.2.1.10.$index";
    $value = $entry['internalIO3'];

    if ($value != '')
    {
      discover_status($device, $oid, 'internalIO3.'.$index, 'geist-v4-mib-io-state', $descr, $value, array('entPhysicalClass' => 'other'));
    }

    $descr = $entry['climateName'] . ' Analog I/O Sensor 4';
    $oid   = ".1.3.6.1.4.1.21239.5.1.2.1.11.$index";
    $value = $entry['internalIO4'];

    if ($value != '')
    {
      discover_status($device, $oid, 'internalIO4.'.$index, 'geist-v4-mib-io-state', $descr, $value, array('entPhysicalClass' => 'other'));
    }
  }
}

$oids = snmpwalk_cache_multi_oid($device, 'tempSensorEntry', array(), 'GEIST-V4-MIB');
print_debug_vars($oids);

foreach ($oids as $index => $entry)
{
  if ($entry['tempSensorAvail'])
  {
    $descr    = $entry['tempSensorName'] . ' ' . $index;

    $oid_name = 'tempSensorTemp';
    $oid_num  = ".1.3.6.1.4.1.21239.5.1.4.1.5.{$index}";
    $type     = $mib . '-' . $oid_name;
    $scale    = 0.1;
    $value    = $entry[$oid_name];

    discover_sensor('temperature', $device, $oid_num, $index, $type, $descr, $scale, $value, $temp_options);
  }
}

// Not supported yet (no test device available):
// - airFlowSensorTable
// - dewPointSensorTable
// - ccatSensorTable
// - t3hdSensorTable
// - thdSensorTable
// - rpmSensorTable

// EOF
