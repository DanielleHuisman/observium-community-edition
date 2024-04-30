<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

$entity_array = snmpwalk_cache_oid($device, 'entPhySensorValue', $entity_array, 'ENTITY-MIB:ENTITY-SENSOR-MIB');
if (!snmp_status()) {
    return;
}

$oids = [ 'entPhySensorType', 'entPhySensorScale', 'entPhySensorPrecision',
          'entPhySensorOperStatus', 'entPhySensorUnitsDisplay' ];
foreach ($oids as $oid) {
    $entity_array = snmpwalk_cache_oid($device, $oid, $entity_array, 'ENTITY-MIB:ENTITY-SENSOR-MIB');
}

if (is_array($GLOBALS['cache']['snmp'][$mib][$device['device_id']])) {
    // If this already received in inventory module, skip walking
    foreach ($GLOBALS['cache']['snmp'][$mib][$device['device_id']] as $index => $entry) {
        if (isset($entity_array[$index])) {
            $entity_array[$index] = array_merge($entity_array[$index], $entry);
        } else {
            $entity_array[$index] = $entry;
        }
    }
    print_debug('ENTITY-MIB already cached');
} else {
    $entity_mibs = snmp_mib_entity_vendortype($device, 'ENTITY-MIB');

    $snmp_flags = OBS_SNMP_ALL;
    snmp_log_error(OBS_SNMP_ERROR_OID_NOT_INCREASING, FALSE); // disable log error for next snmpwalk
    $entity_array = snmpwalk_cache_oid($device, "entPhysicalDescr", $entity_array, $entity_mibs);
    if (!snmp_status() && snmp_error_code() === OBS_SNMP_ERROR_OID_NOT_INCREASING) {

        // Try refetch with NOINCREASE
        $snmp_flags |= OBS_SNMP_NOINCREASE;
        print_debug("WARNING! snmpwalk error 'OID not increasing' detected, try snmpwalk with -Cc option.");

        $entity_array = snmpwalk_cache_oid($device, "entPhysicalDescr", $entity_array, $entity_mibs, NULL, $snmp_flags);
    }

    $oids = ['entPhysicalName', 'entPhysicalClass', 'entPhysicalContainedIn', 'entPhysicalParentRelPos'];
    if (is_device_mib($device, 'ARISTA-ENTITY-SENSOR-MIB')) {
        $oids[] = 'entPhysicalAlias';
    }

    if (snmp_status()) {
        foreach ($oids as $oid) {
            $entity_array = snmpwalk_cache_oid($device, $oid, $entity_array, $entity_mibs, NULL, $snmp_flags);
            if (!snmp_status()) {
                break;
            }
        }
        $entity_array = snmpwalk_cache_twopart_oid($device, 'entAliasMappingIdentifier', $entity_array, 'ENTITY-MIB:IF-MIB', NULL, $snmp_flags);

        $GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']] = $entity_array;
    }
}

// Extra thresholds
if (is_device_mib($device, 'CISCO-ENTITY-SENSOR-EXT-MIB')) {
    $oids_limits    = ['ceSensorExtThresholdValue', 'ceSensorExtThresholdSeverity', 'ceSensorExtThresholdRelation'];
    $t_entity_array = [];
    foreach ($oids_limits as $oid) {
        $t_entity_array = snmpwalk_cache_twopart_oid($device, $oid, $t_entity_array, 'CISCO-ENTITY-SENSOR-EXT-MIB');
        if (!snmp_status()) {
            break;
        }
    }

} elseif (is_device_mib($device, 'ARISTA-ENTITY-SENSOR-MIB')) {
    $oids_limits = ['aristaEntSensorThresholdLowWarning', 'aristaEntSensorThresholdLowCritical',
                    'aristaEntSensorThresholdHighWarning', 'aristaEntSensorThresholdHighCritical'];
    foreach ($oids_limits as $oid) {
        $entity_array = snmpwalk_cache_oid($device, $oid, $entity_array, 'ARISTA-ENTITY-SENSOR-MIB');
        if (!snmp_status()) {
            break;
        }
    }
} elseif (is_device_mib($device, 'ARUBAWIRED-POWERSUPPLY-MIB')) {
    $t_entity_array = snmpwalk_cache_oid($device, 'arubaWiredPSUName', [], 'ARUBAWIRED-POWERSUPPLY-MIB');
    $t_entity_array = snmpwalk_cache_oid($device, 'arubaWiredPSUMaximumPower', $t_entity_array, 'ARUBAWIRED-POWERSUPPLY-MIB');
}

$entitysensor = [
    'voltsDC'    => 'voltage',
    'voltsAC'    => 'voltage',
    'amperes'    => 'current',
    'watts'      => 'power',
    'hertz'      => 'frequency',
    'percentRH'  => 'humidity',
    'rpm'        => 'fanspeed',
    'celsius'    => 'temperature',
    'dBm'        => 'dbm',
    'truthvalue' => 'state'
];

/// DEVEL
//print_debug_vars($entity_array);
//if ($device['os'] === 'rittalpdu') { return; }

foreach ($entity_array as $index => $entry) {
    // Sensor Type/Class
    $type = NULL;
    if (isset($entitysensor[$entry['entPhySensorType']])) {
        $type = $entitysensor[$entry['entPhySensorType']];
    } elseif ($entry['entPhySensorType'] === 'other' && !safe_empty($entry['entPhySensorUnitsDisplay'])) {

    }

    if (!is_null($type) &&
        is_numeric($entry['entPhySensorValue']) &&
        is_numeric($index) &&
        $entry['entPhySensorOperStatus'] !== 'unavailable' &&
        $entry['entPhySensorOperStatus'] !== 'nonoperational') {

        $ok      = TRUE;
        $options = ['entPhysicalIndex' => $index];

        $oid = ".1.3.6.1.2.1.99.1.1.1.4.$index";
        //$type  = $entitysensor[$entry['entPhySensorType']];

        $descr = rewrite_entity_name($entry['entPhysicalDescr']);
        if ($entry['entPhysicalDescr'] && $entry['entPhysicalName']) {
            // Check if entPhysicalDescr equals entPhysicalName,
            // Also compare like this: 'TenGigabitEthernet2/1 Bias Current' and 'Te2/1 Bias Current'
            if (!str_contains($entry['entPhysicalDescr'], substr($entry['entPhysicalName'], 2))) {
                $descr = rewrite_entity_name($entry['entPhysicalDescr']) . ' - ' . rewrite_entity_name($entry['entPhysicalName']);
            }
        } elseif (!$entry['entPhysicalDescr'] && $entry['entPhysicalName']) {
            $descr = rewrite_entity_name($entry['entPhysicalName']);
        } elseif (!$entry['entPhysicalDescr'] && !$entry['entPhysicalName']) {
            // This is also trick for some retard devices like NetMan Plus
            $descr = nicecase($type) . " $index";
        }

        // Scale & hardware specific fixes
        if ($device['os'] === 'fortiswitch' && $entry['entPhySensorScale'] === 'units' &&
            $entry['entPhySensorPrecision'] == '1') {
            // https://jira.observium.org/browse/OBS-3658
            $entry['entPhySensorPrecision'] = 0;
        }
        if ($device['os'] === 'asa' && $entry['entPhySensorScale'] === 'yocto' &&
            $entry['entPhySensorPrecision'] == '0') {
            // Hardcoded fix for Cisco ASA 9.1.5 (can be other) bug when all scales equals yocto (OBS-1110)
            $scale = 1;
        } elseif ($device['os'] === 'netman' && $type === 'temperature') {
            $scale = 0.1;
        } elseif (isset($entry['entPhySensorScale'])) {
            $scale = si_to_scale($entry['entPhySensorScale'], $entry['entPhySensorPrecision']);
        } else {
            // Some devices not report scales, like NetMan Plus. But this is really HACK
            // Heh, I not know why only ups.. I'm not sure that this for all ups.. just I see this only on NetMan Plus.
            $scale = ($device['os_group'] === 'ups' && $type === 'temperature') ? 0.1 : 1;
        }
        $value = $entry['entPhySensorValue'];

        if ($type === 'temperature') {
            if (isset($valid['sensor'][$type]['ARUBAWIRED-TEMPSENSOR-MIB-arubaWiredTempSensorTemperature'])) {
                // duplicate sensors
                $ok = FALSE;
            } elseif ($value * $scale > 200 || $value == 0) {
                $ok = FALSE;
            }
        } elseif ($type === 'fanspeed') {
            if (isset($valid['sensor'][$type]['ARUBAWIRED-FAN-MIB-arubaWiredFanRPM'])) {
                // duplicate sensors
                $ok = FALSE;
            }
        } elseif ($type === 'power') {
            if (isset($valid['sensor'][$type]['ARUBAWIRED-POWERSUPPLY-MIB-arubaWiredPSUInstantaneousPower'])) {
                // duplicate sensors
                $ok = FALSE;
            }
        }

        if ($value == -127 || $value == -1000000000) {
            // Optic RX/TX watt sensors on Arista
            $ok = FALSE;
        } elseif ($value == 0 && safe_empty($entry['entPhysicalDescr']) && safe_empty($entry['entPhysicalName'])) {
            $ok = FALSE;
        }

        // Now try to search port bounded with sensor by ENTITY-MIB
        if ($ok && in_array($type, [ 'temperature', 'voltage', 'current', 'dbm', 'power' ])) {
            $port                        = get_port_by_ent_index($device, $index);
            $options['entPhysicalIndex'] = $index;
            if (is_array($port)) {
                $entry['ifDescr']                     = $port['ifDescr'];
                $options['measured_class']            = 'port';
                $options['measured_entity']           = $port['port_id'];
                $options['entPhysicalIndex_measured'] = $port['ifIndex'];

                // Append port label for Extreme XOS, while it not have port information in descr
                if ($device['os_group'] === 'extremeware' &&
                    !str_contains_array($descr, [$port['port_label'], $port['port_label_short']])) {
                    $descr = $port['port_label'] . ' ' . $descr;
                } elseif (isset($port['sensor_multilane']) && $port['sensor_multilane']) {
                    // Multilane sensors, some rewrites
                    [$match] = explode('/', $port['ifDescr']); // Ethernet56/1 -> Ethernet56
                    if (preg_match("! $match(\/(?<lane>\d))?!", $descr, $matches)) {
                        $descr = str_replace($matches[0], '', $descr);
                        $descr = $port['port_label'] . (isset($matches['lane']) ? ' Lane ' . $matches['lane'] : '') . ' ' . $descr;
                    }
                }
            }
        }

        // Set thresholds for numeric sensors
        $limits = [];
        if (isset($entry['aristaEntSensorThresholdHighCritical'])) {
            // ARISTA-ENTITY-SENSOR-MIB
            foreach (['limit_high'      => 'aristaEntSensorThresholdHighCritical',
                      'limit_low'       => 'aristaEntSensorThresholdLowCritical',
                      'limit_low_warn'  => 'aristaEntSensorThresholdLowWarning',
                      'limit_high_warn' => 'aristaEntSensorThresholdHighWarning'] as $limit => $limit_oid) {
                if (abs($entry[$limit_oid]) != 1000000000) {
                    $limits[$limit] = $entry[$limit_oid] * $scale;
                } else {
                    // The MIB can return -1000000000 or +1000000000, if there should be no threshold there.
                    $limits['limit_auto'] = FALSE;
                }
            }
        } elseif ($type === 'power' && is_device_mib($device, 'ARUBAWIRED-POWERSUPPLY-MIB')) {
            // ARUBAWIRED-POWERSUPPLY-MIB
            foreach ($t_entity_array as $t_index => $t_entry) {
                // ENTITY-SENSOR-MIB::entPhysicalName.7401 = STRING: Power sensor for power supply 1/2
                // ARUBAWIRED-POWERSUPPLY-MIB::arubaWiredPSUName.1.2 = STRING: 1/2
                if ($t_entry['arubaWiredPSUMaximumPower'] > 0 &&
                    str_ends($entry['entPhysicalName'], ' ' . $t_entry['arubaWiredPSUName'])) {
                    $limits['limit_high'] = $t_entry['arubaWiredPSUMaximumPower'];
                    break;
                }
            }
        } elseif (isset($t_entity_array[$index])) {
            // CISCO-ENTITY-SENSOR-EXT-MIB

            // Check thresholds for this entry
            foreach ($t_entity_array[$index] as $t_index => $t_entry) {
                if ($t_entry['ceSensorExtThresholdValue'] == "-32768") {
                    continue;
                }

                // CISCO-ENTITY-SENSOR-EXT-MIB::ceSensorExtThresholdSeverity.13.1 = INTEGER: critical(30)
                // CISCO-ENTITY-SENSOR-EXT-MIB::ceSensorExtThresholdSeverity.14.1 = INTEGER: critical(30)
                // CISCO-ENTITY-SENSOR-EXT-MIB::ceSensorExtThresholdRelation.13.1 = INTEGER: lessOrEqual(2)
                // CISCO-ENTITY-SENSOR-EXT-MIB::ceSensorExtThresholdRelation.14.1 = INTEGER: greaterOrEqual(4)
                // CISCO-ENTITY-SENSOR-EXT-MIB::ceSensorExtThresholdValue.13.1 = INTEGER: 2150
                // CISCO-ENTITY-SENSOR-EXT-MIB::ceSensorExtThresholdValue.14.1 = INTEGER: 55

                switch ($t_entry['ceSensorExtThresholdSeverity']) {

                    case 'critical':
                        // Prefer critical over major
                        if (in_array($t_entry['ceSensorExtThresholdRelation'], ['greaterOrEqual', 'greaterThan'])) {
                            if (isset($limits['limit_high'])) {
                                break;
                            } // Use first threshold entry
                            $limits['limit_high'] = $t_entry['ceSensorExtThresholdValue'] * $scale;
                        } elseif (in_array($t_entry['ceSensorExtThresholdRelation'], ['lessOrEqual', 'lessThan'])) {
                            if (isset($limits['limit_low'])) {
                                break;
                            } // Use first threshold entry
                            $limits['limit_low'] = $t_entry['ceSensorExtThresholdValue'] * $scale;
                        }
                        // FIXME. Not used: equalTo, notEqualTo
                        break;

                    case 'major':
                        // Prefer critical over major,
                        if (in_array($t_entry['ceSensorExtThresholdRelation'], ['greaterOrEqual', 'greaterThan'])) {
                            if (isset($limits['limit_high_major'])) {
                                break;
                            } // Use first threshold entry
                            $limits['limit_high_major'] = $t_entry['ceSensorExtThresholdValue'] * $scale;
                        } elseif (in_array($t_entry['ceSensorExtThresholdRelation'], ['lessOrEqual', 'lessThan'])) {
                            if (isset($limits['limit_low_major'])) {
                                break;
                            } // Use first threshold entry
                            $limits['limit_low_major'] = $t_entry['ceSensorExtThresholdValue'] * $scale;
                        }
                        break;

                    case 'minor':
                        if (in_array($t_entry['ceSensorExtThresholdRelation'], ['greaterOrEqual', 'greaterThan'])) {
                            if (isset($limits['limit_high_warn'])) {
                                break;
                            } // Use first threshold entry
                            $limits['limit_high_warn'] = $t_entry['ceSensorExtThresholdValue'] * $scale;
                        } elseif (in_array($t_entry['ceSensorExtThresholdRelation'], ['lessOrEqual', 'lessThan'])) {
                            if (isset($limits['limit_low_warn'])) {
                                break;
                            } // Use first threshold entry
                            $limits['limit_low_warn'] = $t_entry['ceSensorExtThresholdValue'] * $scale;
                        }
                        // FIXME. Not used: equalTo, notEqualTo
                        break;

                    case 'other':
                        // Probably here should be equalTo, notEqualTo.. never saw
                        break;
                }
            }

        }

        // Check to make sure we've not already seen this sensor via cisco's entity sensor mib
        if ($type === 'state') {
            //if (isset($valid['status']['CISCO-ENTITY-SENSOR-MIB']['cisco-entity-sensor'][$index]))
            if (is_device_mib($device, 'CISCO-ENTITY-SENSOR-MIB')) { // Complete ignore truthvalue on Cisco devices
                $ok = FALSE;
            }
        } elseif (isset($valid['sensor'][$type]['CISCO-ENTITY-SENSOR-MIB-entSensorValue'][$index])) {
            $ok = FALSE;
        }

        if ($ok) {
            $options = array_merge($limits, $options);
            if ($type === 'state') {
                //truthvalue
                discover_status_ng($device, $mib, 'entPhySensorValue', $oid, $index, 'entity-truthvalue', $descr, $value, $options);
            } else {
                $options['rename_rrd'] = 'entity-sensor-' . $index;
                discover_sensor_ng($device, $type, $mib, 'entPhySensorValue', $oid, $index, NULL, $descr, $scale, $value, $options);
            }
        }
    } else {
        print_debug("Skipped:");
        print_debug_vars($entry);
    }
}

unset($oids, $oids_arista, $entity_array, $index, $scale, $type, $value, $descr, $ok, $ifIndex, $sensor_port);

// EOF
