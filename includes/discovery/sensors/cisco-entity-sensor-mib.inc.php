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

$entity_array = snmpwalk_cache_oid($device, 'entSensorValueEntry', $entity_array, 'CISCO-ENTITY-SENSOR-MIB');
if (snmp_status()) {
    if (is_array($GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']])) {
        // If this already received in inventory module, skip walking
        foreach ($GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']] as $index => $entry) {
            if (isset($entity_array[$index])) {
                $entity_array[$index] = array_merge($entity_array[$index], $entry);
            } else {
                $entity_array[$index] = $entry;
            }
        }
        print_debug("ENTITY-MIB already cached");
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

        if (snmp_status()) {
            $oids = ['entPhysicalName', 'entPhysicalClass', 'entPhysicalContainedIn', 'entPhysicalParentRelPos', 'entPhysicalVendorType'];
            foreach ($oids as $oid) {
                $entity_array = snmpwalk_cache_oid($device, $oid, $entity_array, $entity_mibs, NULL, $snmp_flags);
                if (!snmp_status()) {
                    break;
                }
            }
            $entity_array                                                 = snmpwalk_cache_twopart_oid($device, 'entAliasMappingIdentifier', $entity_array, 'ENTITY-MIB:IF-MIB', NULL, $snmp_flags);
            $GLOBALS['cache']['snmp']['ENTITY-MIB'][$device['device_id']] = $entity_array;
        }
    }

    $t_oids         = ['entSensorThresholdSeverity', 'entSensorThresholdRelation', 'entSensorThresholdValue'];
    $t_entity_array = [];
    foreach ($t_oids as $oid) {
        $t_entity_array = snmpwalk_cache_twopart_oid($device, $oid, $t_entity_array, 'CISCO-ENTITY-SENSOR-MIB');
    }

    // http://tools.cisco.com/Support/SNMP/do/BrowseOID.do?local=en&translate=Translate&typeName=SensorDataType
    /* sensor measurement data types. valid values are:
        other(1): a measure other than those listed below
        unknown(2): unknown measurement, or arbitrary, relative numbers
        voltsAC(3): electric potential
        voltsDC(4): electric potential
        amperes(5): electric current
        watts(6): power
        hertz(7): frequency
        celsius(8): temperature
        percentRH(9): percent relative humidity
        rpm(10): shaft revolutions per minute
        cmm(11): cubic meters per minute (airflow)
        truthvalue(12): value takes { true(1), false(2) }
        specialEnum(13): value takes user defined enumerated values
        dBm(14): dB relative to 1mW of power
    */

    $c_entitysensor = [
      'voltsAC'    => 'voltage',
      'voltsDC'    => 'voltage',
      'amperes'    => 'current',
      'watts'      => 'power',
      'hertz'      => 'frequency',
      'celsius'    => 'temperature',
      'percentRH'  => 'humidity',
      'rpm'        => 'fanspeed',
      'cmm'        => 'airflow',
      'truthvalue' => 'state',
      //'specialEnum' => 'gauge', // This sensors seems as useless
      'dBm'        => 'dbm'
    ];

    print_vars($entity_array);

    $i = [];
    foreach ($entity_array as $index => $entry) {
        if (is_numeric($index) && isset($c_entitysensor[$entry['entSensorType']]) &&
            is_numeric($entry['entSensorValue']) && $entry['entSensorStatus'] === 'ok') {
            $ok      = TRUE;
            $options = ['entPhysicalIndex' => $index];

            $descr = rewrite_entity_name($entry['entPhysicalDescr']);
            if ($device['os'] === 'cisco-firepower') {
                $descr = $entry['entPhysicalName'];
            } elseif ($entry['entPhysicalDescr'] && $entry['entPhysicalName']) {
                // Check if entPhysicalDescr equals entPhysicalName,
                // Also compare like this: 'TenGigabitEthernet2/1 Bias Current' and 'Te2/1 Bias Current'
                if (!str_starts($entry['entPhysicalDescr'], substr($entry['entPhysicalName'], 0, 2))) {
                    $descr .= " - " . rewrite_entity_name($entry['entPhysicalName']);
                }
            } elseif (safe_empty($entry['entPhysicalDescr'])) {
                $descr = rewrite_entity_name($entry['entPhysicalName']);
            }

            // Set description based on measured entity if it exists
            if (is_numeric($entry['entSensorMeasuredEntity']) && $entry['entSensorMeasuredEntity']) {

                if ($entity_array[$entry['entSensorMeasuredEntity']]['entPhysicalClass'] == 'chassis') {

                    // Skip Chassis parents

                } elseif ($device['os'] === 'cisco-firepower') {
                    // FirePOWER has next to useless layout of ENTITY-SENSOR-MIB. Sensors don't have an ENTITY-MIB entry, so there is no way to tell apart individual sensors on the same entity.
                    // We just use the index to make them look less identical. It sucks, though.
                    $descr = rewrite_entity_name($entity_array[$entry['entSensorMeasuredEntity']]['entPhysicalName']) . ' (' . $index . ')';
                } else {
                    $measured_descr = rewrite_entity_name($entity_array[$entry['entSensorMeasuredEntity']]['entPhysicalDescr']);
                    if (safe_empty($measured_descr)) {
                        $measured_descr = rewrite_entity_name($entity_array[$entry['entSensorMeasuredEntity']]['entPhysicalName']);
                    }
                    if ($measured_descr && !str_starts($measured_descr, substr($descr, 0, 2))) {
                        $descr = $measured_descr . " - " . $descr;
                    }
                }
            }

            // If name contains description and is longer than description, then it likely contains the parent, so just use it.
            if (strstr($entry['entPhysicalName'], $entry['entPhysicalDescr']) && strlen($entry['entPhysicalName']) > strlen($entry['entPhysicalDescr'])) {
                $descr = $entry['entPhysicalName'];
            }

            $oid   = ".1.3.6.1.4.1.9.9.91.1.1.1.1.4.$index";
            $value = $entry['entSensorValue'];
            switch ($c_entitysensor[$entry['entSensorType']]) {
                case 'state':
                    // Statuses
                    $type = 'state';
                    // 1:other, 2:unknown, 3:chassis, 4:backplane, 5:container, 6:powerSupply,
                    // 7:fan, 8:sensor, 9:module, 10:port, 11:stack, 12:cpu
                    $options['entPhysicalClass'] = $entry['entPhysicalClass'];
                    $sensor_type                 = 'cisco-entity-state';
                    break;

                case 'counter':
                    // Counters
                    $type = 'counter';
                    //$options['measured_class'] = $entry['entPhysicalClass'];
                    $sensor_type = 'cisco-entity-state';
                    break;

                default:
                    // Normal sensors
                    $type        = $c_entitysensor[$entry['entSensorType']];
                    $sensor_type = 'cisco-entity-sensor';
                    if ($entry['entSensorType'] === 'cmm') {
                        $options['sensor_unit'] = 'CMM';
                    }
            }

            // Returning blatantly broken value. IGNORE.
            if ($value == "-32768" || $value == "-127") {
                $ok = FALSE;
            } elseif (safe_empty($descr)) {
                // Empty description. Lots of these on Nexus and Cisco APIC
                $i[$type]++;
                $descr = 'Sensor ' . $i[$type];
                if ($value == 0) {
                    $ok = FALSE;
                }
            }

            print_debug_vars($descr);
            print_debug_vars($scale);
            print_debug_vars($entry);

            // Now try to search port bounded with sensor by ENTITY-MIB
            if ($ok && in_array($type, ['temperature', 'voltage', 'current', 'dbm', 'power'])) {
                $port                        = get_port_by_ent_index($device, $index);
                $options['entPhysicalIndex'] = $index;
                if (is_array($port)) {
                    $entry['ifDescr']                     = $port['port_label'];
                    $options['measured_class']            = 'port';
                    $options['measured_entity']           = $port['port_id'];
                    $options['measured_entity_label']     = $port['port_label'];
                    $options['entPhysicalIndex_measured'] = $port['ifIndex'];

                    // Fix Port description based on vendor type
                    if ($entry['entPhysicalVendorType'] && !str_icontains_array($descr, ['Power', 'Current', 'Voltage', 'Temperature'])) {
                        switch ($entry['entPhysicalVendorType']) {
                            case 'cevSensorTransceiverVoltage':
                                $descr .= ' Transceiver Voltage';
                                break;
                            case 'cevSensorTransceiverCurrent':
                                $descr .= ' Transceiver Bias Current';
                                break;
                            case 'cevSensorTransceiverTemp':
                                $descr .= ' Transceiver Temperature';
                                break;
                            case 'cevSensorTransceiverRxPwr':
                                $descr .= ' Transceiver Receive Power';
                                break;
                            case 'cevSensorTransceiverTxPwr':
                                $descr .= ' Transceiver Transmit Power';
                                break;
                        }
                    }
                }

            }

            // Append nice measured entity label for well sorting
            if ($sensor_type === 'cisco-entity-sensor' && empty($options['measured_entity_label']) &&
                preg_match('/^(?<class>[\w\-]+?( \w+)?)\d* (?<entity>\S+)$/', $entry['entPhysicalName'], $matches)) {
                $options['measured_entity_label'] = $matches['entity'];

                if (empty($options['entPhysicalClass'])) {
                    // capacity 0/PM1/SP
                    // chassis ASR-9001-S
                    // cpu 0/RSP0/CPU0
                    // current 0/PM1/SP
                    // daughtercard 0/0/CPU0
                    // fan0 0/FT0/SP
                    // fantray 0/FT0/SP
                    // module 0/RSP0/CPU0
                    // module mau 0/0/2/1
                    // power-module 0/PM1/SP
                    // power Rx 0/0/2/1
                    // power Tx 0/0/2/1
                    // slot 0/RSP0
                    // slot mau 0/0/2/3
                    // speed 0/FT0/SP
                    // subslot 0/0/2
                    // temperature 0/RSP0/CPU0
                    // voltage 0/RSP0/CPU0
                    switch (strtolower($matches['class'])) {
                        case 'fan':
                        case 'fantray':
                        case 'cpu':
                        case 'chassis':
                        case 'module':
                            $options['entPhysicalClass'] = strtolower($matches['class']);
                            break;
                        case 'power-module':
                        case 'power module':
                            $options['entPhysicalClass'] = 'powerSupply';
                            break;
                        default:
                            if (str_contains($entry['entPhysicalName'], 'Power Module')) {
                                $options['entPhysicalClass'] = 'powerSupply';
                            }
                    }
                }
            }

            // Set thresholds for numeric sensors
            $limits = [];
            $scale  = NULL;
            if ($c_entitysensor[$entry['entSensorType']] !== 'state') {
                $precision = $entry['entSensorPrecision'];
                if ($device['os'] === 'iosxe' && $precision > 0 &&
                    version_compare($device['version'], '16.11', '>=') && version_compare($device['version'], '16.12', '<')) {
                    // See: https://jira.observium.org/browse/OBS-3026
                    // Note, issue not actual on firmware less than 16.11, not sure if fixed on newer firmwares
                    // Seems as not actual on 16.12.x, see: https://jira.observium.org/browse/OBS-3707
                    // I not sure that this fully correct, but for issue case - works
                    $precision -= 1;
                } elseif ($device['os'] === 'iosxr' && $precision > 0 && $type === 'power' &&
                          $device['version'] === '6.4.2' && preg_match('/ASR\-90\d{2}/', $device['hardware'])) {
                    // See: https://jira.observium.org/browse/OBS-4079
                    // ASR9k 32bit platform issue for power sensor
                    $precision -= 1;
                }
                $scale = si_to_scale($entry['entSensorScale'], $precision);

                // Check thresholds for this entry
                foreach ($t_entity_array[$index] as $t_index => $t_entry) {
                    if ($t_entry['entSensorThresholdValue'] == "-32768") {
                        continue;
                    }

                    /* Sometime thresholds have duplicate limits, see:
                     * https://jira.observium.org/browse/OBS-2752
                      entSensorType.1019 = celsius
                      entSensorScale.1019 = units
                      entSensorPrecision.1019 = 0
                      entSensorValue.1019 = 24
                      entSensorStatus.1019 = ok
                      entSensorThresholdSeverity.1019.1 = minor
                      entSensorThresholdSeverity.1019.2 = major
                      entSensorThresholdSeverity.1019.3 = critical
                      entSensorThresholdSeverity.1019.4 = critical
                      entSensorThresholdRelation.1019.1 = greaterOrEqual
                      entSensorThresholdRelation.1019.2 = greaterOrEqual
                      entSensorThresholdRelation.1019.3 = greaterOrEqual
                      entSensorThresholdRelation.1019.4 = greaterOrEqual
                      entSensorThresholdValue.1019.1 = 55
                      entSensorThresholdValue.1019.2 = 65
                      entSensorThresholdValue.1019.3 = 75
                      entSensorThresholdValue.1019.4 = 100
                     */
                    /* Another case, when critical limits is zero, see:
                     * https://jira.observium.org/browse/OBS-2819
                      entSensorThresholdSeverity.2130160.1 = minor
                      entSensorThresholdSeverity.2130160.2 = minor
                      entSensorThresholdSeverity.2130160.3 = major
                      entSensorThresholdSeverity.2130160.4 = major
                      entSensorThresholdSeverity.2130160.5 = critical
                      entSensorThresholdSeverity.2130160.6 = critical
                      entSensorThresholdRelation.2130160.1 = lessOrEqual
                      entSensorThresholdRelation.2130160.2 = greaterOrEqual
                      entSensorThresholdRelation.2130160.3 = lessOrEqual
                      entSensorThresholdRelation.2130160.4 = greaterOrEqual
                      entSensorThresholdRelation.2130160.5 = lessOrEqual
                      entSensorThresholdRelation.2130160.6 = greaterOrEqual
                      entSensorThresholdValue.2130160.1 = 31000000
                      entSensorThresholdValue.2130160.2 = 34650000
                      entSensorThresholdValue.2130160.3 = 29700000
                      entSensorThresholdValue.2130160.4 = 36300000
                      entSensorThresholdValue.2130160.5 = 0
                      entSensorThresholdValue.2130160.6 = 0
                     */
                    /* Another case, when minor limits is zero, see:
                     * https://jira.observium.org/browse/OBS-2819?focusedCommentId=21323#comment-21323
                      entSensorThresholdSeverity.2224410.1 = minor
                      entSensorThresholdSeverity.2224410.2 = minor
                      entSensorThresholdSeverity.2224410.3 = major
                      entSensorThresholdSeverity.2224410.4 = major
                      entSensorThresholdSeverity.2224410.5 = critical
                      entSensorThresholdSeverity.2224410.6 = critical
                      entSensorThresholdRelation.2224410.1 = lessOrEqual
                      entSensorThresholdRelation.2224410.2 = greaterOrEqual
                      entSensorThresholdRelation.2224410.3 = lessOrEqual
                      entSensorThresholdRelation.2224410.4 = greaterOrEqual
                      entSensorThresholdRelation.2224410.5 = lessOrEqual
                      entSensorThresholdRelation.2224410.6 = greaterOrEqual
                      entSensorThresholdValue.2224410.1 = 0
                      entSensorThresholdValue.2224410.2 = 0
                      entSensorThresholdValue.2224410.3 = -1059981
                      entSensorThresholdValue.2224410.4 = 450002
                      entSensorThresholdValue.2224410.5 = -1459670
                      entSensorThresholdValue.2224410.6 = 749999
                     */
                    switch ($t_entry['entSensorThresholdSeverity']) {

                        case 'critical':
                            // Prefer critical over major
                            if (in_array($t_entry['entSensorThresholdRelation'], ['greaterOrEqual', 'greaterThan'])) {
                                if (isset($limits['limit_high'])) {
                                    break;
                                } // Use first threshold entry
                                $limits['limit_high'] = $t_entry['entSensorThresholdValue'] * $scale;
                            } elseif (in_array($t_entry['entSensorThresholdRelation'], ['lessOrEqual', 'lessThan'])) {
                                if (isset($limits['limit_low'])) {
                                    break;
                                } // Use first threshold entry
                                $limits['limit_low'] = $t_entry['entSensorThresholdValue'] * $scale;
                            }
                            // FIXME. Not used: equalTo, notEqualTo
                            break;

                        case 'major':
                            // Prefer critical over major,
                            if (in_array($t_entry['entSensorThresholdRelation'], ['greaterOrEqual', 'greaterThan'])) {
                                if (isset($limits['limit_high_major'])) {
                                    break;
                                } // Use first threshold entry
                                $limits['limit_high_major'] = $t_entry['entSensorThresholdValue'] * $scale;
                            } elseif (in_array($t_entry['entSensorThresholdRelation'], ['lessOrEqual', 'lessThan'])) {
                                if (isset($limits['limit_low_major'])) {
                                    break;
                                } // Use first threshold entry
                                $limits['limit_low_major'] = $t_entry['entSensorThresholdValue'] * $scale;
                            }
                            break;

                        case 'minor':
                            if (in_array($t_entry['entSensorThresholdRelation'], ['greaterOrEqual', 'greaterThan'])) {
                                if (isset($limits['limit_high_warn'])) {
                                    break;
                                } // Use first threshold entry
                                $limits['limit_high_warn'] = $t_entry['entSensorThresholdValue'] * $scale;
                            } elseif (in_array($t_entry['entSensorThresholdRelation'], ['lessOrEqual', 'lessThan'])) {
                                if (isset($limits['limit_low_warn'])) {
                                    break;
                                } // Use first threshold entry
                                $limits['limit_low_warn'] = $t_entry['entSensorThresholdValue'] * $scale;
                            }
                            // FIXME. Not used: equalTo, notEqualTo
                            break;

                        case 'other':
                            // Probably here should be equalTo, notEqualTo.. never saw
                            break;
                    }
                }

                // Fixes for high thresholds issues in IOS-XR (and others)
                if (!isset($limits['limit_high']) && isset($limits['limit_high_major'])) {
                    // Use major thresholds if critical not set
                    $limits['limit_high'] = $limits['limit_high_major'];
                    unset($limits['limit_high_major']);
                } elseif (isset($limits['limit_high']) && $limits['limit_high'] == 0 &&
                          isset($limits['limit_high_major']) && $limits['limit_high_major'] != 0) {
                    // critical limit is zero, use major
                    $limits['limit_high'] = $limits['limit_high_major'];
                    unset($limits['limit_high_major']);
                }
                if (isset($limits['limit_high_warn']) && $limits['limit_high_warn'] == 0 &&
                    isset($limits['limit_high_major']) && $limits['limit_high_major'] != 0) {
                    // minor limit is zero, use major
                    $limits['limit_high_warn'] = $limits['limit_high_major'];
                }

                // Fixes for low thresholds issues in IOS-XR (and others)
                if (!isset($limits['limit_low']) && isset($limits['limit_low_major'])) {
                    // Use major thresholds if critical not set
                    $limits['limit_low'] = $limits['limit_low_major'];
                    unset($limits['limit_low_major']);
                } elseif (isset($limits['limit_low']) && $limits['limit_low'] == 0 &&
                          isset($limits['limit_low_major']) && $limits['limit_low_major'] != 0) {
                    // critical limit is zero, use major
                    $limits['limit_low'] = $limits['limit_low_major'];
                    unset($limits['limit_low_major']);
                }
                if (isset($limits['limit_low_warn']) && $limits['limit_low_warn'] == 0 &&
                    isset($limits['limit_low_major']) && $limits['limit_low_major'] != 0) {
                    // minor limit is zero, use major
                    $limits['limit_low_warn'] = $limits['limit_low_major'];
                }
                unset($limits['limit_high_major'], $limits['limit_low_major']);

                // This port sensors really always return 0, see:
                // https://jira.observium.org/browse/OBS-3335
                /*
                  entPhysicalDescr.2134256 = Voltage Sensor
                  entPhysicalVendorType.2134256 = cevSensorNCS4KVol
                  entPhysicalContainedIn.2134256 = 2134017
                  entPhysicalClass.2134256 = sensor
                  entPhysicalParentRelPos.2134256 = 4
                  entPhysicalName.2134256 = GigabitEthernet0/0/0/1-3.3 V
                  entPhysicalHardwareRev.2134256 =
                  entPhysicalFirmwareRev.2134256 =
                  entPhysicalSoftwareRev.2134256 =
                  entPhysicalSerialNum.2134256 =
                  entPhysicalMfgName.2134256 =
                  entPhysicalModelName.2134256 = N/A
                  entPhysicalAlias.2134256 =
                  entPhysicalAssetID.2134256 =
                  entPhysicalIsFRU.2134256 = false

                  entSensorType.2134256 = voltsDC
                  entSensorScale.2134256 = milli
                  entSensorPrecision.2134256 = 4
                  entSensorValue.2134256 = 0
                  entSensorStatus.2134256 = ok
                  entSensorValueTimeStamp.2134256 = 0:0:00:00.00
                  entSensorValueUpdateRate.2134256 = 10
                  entSensorMeasuredEntity.2134256 = 2134017
                  entSensorThresholdSeverity.2134256.1 = minor
                  entSensorThresholdSeverity.2134256.2 = minor
                  entSensorThresholdSeverity.2134256.3 = major
                  entSensorThresholdSeverity.2134256.4 = major
                  entSensorThresholdSeverity.2134256.5 = critical
                  entSensorThresholdSeverity.2134256.6 = critical
                  entSensorThresholdRelation.2134256.1 = lessOrEqual
                  entSensorThresholdRelation.2134256.2 = greaterOrEqual
                  entSensorThresholdRelation.2134256.3 = lessOrEqual
                  entSensorThresholdRelation.2134256.4 = greaterOrEqual
                  entSensorThresholdRelation.2134256.5 = lessOrEqual
                  entSensorThresholdRelation.2134256.6 = greaterOrEqual
                  entSensorThresholdValue.2134256.1 = 0
                  entSensorThresholdValue.2134256.2 = 0
                  entSensorThresholdValue.2134256.3 = 0
                  entSensorThresholdValue.2134256.4 = 0
                  entSensorThresholdValue.2134256.5 = 0
                  entSensorThresholdValue.2134256.6 = 0
                 */
                if ($options['measured_class'] === 'port' && $value == 0 &&
                    $limits['limit_high'] == 0 && $limits['limit_high_warn'] == 0 &&
                    $limits['limit_low'] == 0 && $limits['limit_low_warn'] == 0) {
                    unset($limits['limit_high'], $limits['limit_high_warn'], $limits['limit_low'], $limits['limit_low_warn']);
                    if (!($type === 'dbm' || $port['ifOperStatus'] === 'up')) {
                        $ok = FALSE;
                    }
                }

                // Some Cisco sensors have all limits as same value (f.u. cisco), then leave only one limit
                if ((float_cmp($limits['limit_high'], $limits['limit_low']) === 0) &&
                    (float_cmp($limits['limit_high_warn'], $limits['limit_low_warn']) === 0) &&
                    (float_cmp($limits['limit_high'], $limits['limit_high_warn']) === 0)) {
                    unset($limits['limit_high_warn'], $limits['limit_low_warn'], $limits['limit_low']);
                }

                // https://jira.observium.org/browse/OBS-3597
                // End Threshold code
            }

            if ($ok) {
                $options = array_merge($limits, $options);
                if ($type === 'state') {
                    //truthvalue
                    $options['rename_rrd'] = 'cisco-entity-state-' . $index;
                    discover_status_ng($device, $mib, 'entSensorValue', $oid, $index, $sensor_type, $descr, $value, $options);
                } elseif ($type === 'counter') {
                    discover_counter($device, $entry['entPhysicalClass'], $mib, 'entSensorValue', $oid, $index, $descr, $scale, $value, $options);
                } else {
                    $options['rename_rrd'] = 'cisco-entity-sensor-' . $index;
                    discover_sensor_ng($device, $type, $mib, 'entSensorValue', $oid, $index, NULL, $descr, $scale, $value, $options);
                }
            }
        }
    }
}

unset($oids, $t_oids, $entity_array, $t_entity_array, $index, $scale, $type, $value, $descr, $ok, $ifIndex, $sensor_port);

// EOF
