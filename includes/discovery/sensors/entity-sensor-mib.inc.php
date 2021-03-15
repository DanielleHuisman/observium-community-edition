<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

$entity_array = snmpwalk_cache_multi_oid($device, 'entPhySensorValue', $entity_array, 'ENTITY-MIB:ENTITY-SENSOR-MIB');
if ($GLOBALS['snmp_status'])
{
  $oids = array('entPhySensorType', 'entPhySensorScale', 'entPhySensorPrecision', 'entPhySensorOperStatus');
  foreach ($oids as $oid)
  {
    $entity_array = snmpwalk_cache_multi_oid($device, $oid, $entity_array, 'ENTITY-MIB:ENTITY-SENSOR-MIB');
  }

  if (is_array($GLOBALS['cache']['snmp'][$mib][$device['device_id']]))
  {
    // If this already received in inventory module, skip walking
    foreach ($GLOBALS['cache']['snmp'][$mib][$device['device_id']] as $index => $entry)
    {
      if (isset($entity_array[$index]))
      {
        $entity_array[$index] = array_merge($entity_array[$index], $entry);
      } else {
        $entity_array[$index] = $entry;
      }
    }
    print_debug('ENTITY-MIB already cached');
  } else {
    $oids = array('entPhysicalDescr', 'entPhysicalName', 'entPhysicalClass', 'entPhysicalContainedIn', 'entPhysicalParentRelPos');
    if (is_device_mib($device, 'ARISTA-ENTITY-SENSOR-MIB'))
    {
      $oids[] = 'entPhysicalAlias';
    }
    foreach ($oids as $oid)
    {
      $entity_array = snmpwalk_cache_multi_oid($device, $oid, $entity_array, 'ENTITY-MIB:CISCO-ENTITY-VENDORTYPE-OID-MIB');
      if (!$GLOBALS['snmp_status']) { break; }
    }
    $entity_array = snmpwalk_cache_twopart_oid($device, 'entAliasMappingIdentifier', $entity_array, 'ENTITY-MIB:IF-MIB');
    $GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']] = $entity_array;
  }

  if (is_device_mib($device, 'ARISTA-ENTITY-SENSOR-MIB'))
  {
    $oids_arista = array('aristaEntSensorThresholdLowWarning', 'aristaEntSensorThresholdLowCritical',
                         'aristaEntSensorThresholdHighWarning', 'aristaEntSensorThresholdHighCritical');
    foreach ($oids_arista as $oid)
    {
      $entity_array = snmpwalk_cache_multi_oid($device, $oid, $entity_array, 'ARISTA-ENTITY-SENSOR-MIB');
      if (!$GLOBALS['snmp_status']) { break; }
    }
  }

  $entitysensor = array(
    'voltsDC'   => 'voltage',
    'voltsAC'   => 'voltage',
    'amperes'   => 'current',
    'watts'     => 'power',
    'hertz'     => 'frequency',
    'percentRH' => 'humidity',
    'rpm'       => 'fanspeed',
    'celsius'   => 'temperature',
    'dBm'       => 'dbm',
    'truthvalue' => 'state'
  );

  foreach ($entity_array as $index => $entry)
  {
    if (isset($entitysensor[$entry['entPhySensorType']]) &&
        is_numeric($entry['entPhySensorValue']) &&
        is_numeric($index) &&
        $entry['entPhySensorOperStatus'] != 'unavailable' &&
        $entry['entPhySensorOperStatus'] != 'nonoperational')
    {
      $ok      = TRUE;
      $options = array('entPhysicalIndex' => $index);

      $oid   = ".1.3.6.1.2.1.99.1.1.1.4.$index";
      $type  = $entitysensor[$entry['entPhySensorType']];

      $descr = rewrite_entity_name($entry['entPhysicalDescr']);
      if ($entry['entPhysicalDescr'] && $entry['entPhysicalName'])
      {
        // Check if entPhysicalDescr equals entPhysicalName,
        // Also compare like this: 'TenGigabitEthernet2/1 Bias Current' and 'Te2/1 Bias Current'
        if (strpos($entry['entPhysicalDescr'], substr($entry['entPhysicalName'], 2)) === FALSE)
        {
          $descr = rewrite_entity_name($entry['entPhysicalDescr']) . ' - ' . rewrite_entity_name($entry['entPhysicalName']);
        }
      }
      elseif (!$entry['entPhysicalDescr'] && $entry['entPhysicalName'])
      {
        $descr = rewrite_entity_name($entry['entPhysicalName']);
      }
      elseif (!$entry['entPhysicalDescr'] && !$entry['entPhysicalName'])
      {
        // This is also trick for some retard devices like NetMan Plus
        $descr = nicecase($type) . " $index";
      }

      if ($device['os'] == 'asa' && $entry['entPhySensorScale'] == 'yocto' && $entry['entPhySensorPrecision'] == '0')
      {
        // Hardcoded fix for Cisco ASA 9.1.5 (can be other) bug when all scales equals yocto (OBSERVIUM-1110)
        $scale = 1;
      }
      elseif ($device['os'] == 'netman' && $type == 'temperature')
      {
        $scale = 0.1;
      }
      elseif (isset($entry['entPhySensorScale']))
      {
        $scale = si_to_scale($entry['entPhySensorScale'], $entry['entPhySensorPrecision']);
      } else {
        // Some devices not report scales, like NetMan Plus. But this is really HACK
        // Heh, I not know why only ups.. I'm not sure that this for all ups.. just I see this only on NetMan Plus.
        $scale = ($device['os_group'] == 'ups' && $type == 'temperature') ? 0.1 : 1;
      }
      $value = $entry['entPhySensorValue'];

      if ($type == 'temperature')
      {
        if ($value * $scale > 200 || $value == 0) { $ok = FALSE; }
      }
      if ($value == -127 ||
          $value == -1000000000)  // Optic RX/TX watt sensors on Arista
      {
        $ok = FALSE;
      }

      // Now try to search port bounded with sensor by ENTITY-MIB
      if ($ok && in_array($type, array('temperature', 'voltage', 'current', 'dbm', 'power')))
      {
        $port    = get_port_by_ent_index($device, $index);
        $options['entPhysicalIndex'] = $index;
        if (is_array($port))
        {
          $entry['ifDescr']            = $port['ifDescr'];
          $options['measured_class']   = 'port';
          $options['measured_entity']  = $port['port_id'];
          $options['entPhysicalIndex_measured'] = $port['ifIndex'];

          // Append port label for Extreme XOS, while it not have port information in descr
          if ($device['os_group'] == 'extremeware' && !str_exists($descr, [ $port['port_label'], $port['port_label_short'] ]))
          {
            $descr = $port['port_label'] . ' ' . $descr;
          }
        }
      }

      // Set thresholds for numeric sensors
      $limits = array();
      if (isset($entry['aristaEntSensorThresholdHighCritical']))
      {
        foreach (array('limit_high' => 'aristaEntSensorThresholdHighCritical', 'limit_low' => 'aristaEntSensorThresholdLowCritical',
                       'limit_low_warn' => 'aristaEntSensorThresholdLowWarning', 'limit_high_warn' => 'aristaEntSensorThresholdHighWarning') as $limit => $limit_oid)
        {
          if (abs($entry[$limit_oid]) != 1000000000)
          {
            $limits[$limit] = $entry[$limit_oid] * $scale;
          } else {
            // The MIB can return -1000000000 or +1000000000, if there should be no threshold there.
            $limits['limit_auto'] = FALSE;
          }
        }
      }

      // Check to make sure we've not already seen this sensor via cisco's entity sensor mib
      if ($type == 'state')
      {
        //if (isset($valid['status']['CISCO-ENTITY-SENSOR-MIB']['cisco-entity-sensor'][$index]))
        if (is_device_mib($device, 'CISCO-ENTITY-SENSOR-MIB')) // Complete ignore truthvalue on Cisco devices
        {
          $ok = FALSE;
        }
      }
      elseif (isset($valid['sensor'][$type]['CISCO-ENTITY-SENSOR-MIB-entSensorValue'][$index]))
      {
        $ok = FALSE;
      }

      if ($ok)
      {
        $options = array_merge($limits, $options);
        if ($type == 'state')
        {
          //truthvalue
          discover_status_ng($device, $mib, 'entPhySensorValue', $oid, $index, 'entity-truthvalue', $descr, $value, $options);
        } else {
          $options['rename_rrd'] = 'entity-sensor-'.$index;
          discover_sensor_ng($device, $type, $mib, 'entPhySensorValue', $oid, $index, NULL, $descr, $scale, $value, $options);
        }
      }
    } else {
      print_debug("Skipped:");
      print_debug_vars($entry);
    }
  }
}

unset($oids, $oids_arista, $entity_array, $index, $scale, $type, $value, $descr, $ok, $ifIndex, $sensor_port);

// EOF
