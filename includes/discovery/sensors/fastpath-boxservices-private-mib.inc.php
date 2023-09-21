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

if (safe_count($valid['sensor']['temperature']['DNOS-BOXSERVICES-PRIVATE-MIB']) ||
    safe_count($valid['sensor']['power']['DNOS-BOXSERVICES-PRIVATE-MIB'])) {
    // Exit from discovery, since already added valid sensors by DNOS-BOXSERVICES-PRIVATE-MIB
    // Note, DNOS-BOXSERVICES-PRIVATE-MIB and FASTPATH-BOXSERVICES-PRIVATE-MIB are crossed
    return;
}

// Retrieve temperature limits
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMin.0 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMax.0 = INTEGER: 57
$boxServicesNormalTempRangeMin = snmp_get($device, 'boxServicesNormalTempRangeMin.0', '-Ovq', 'FASTPATH-BOXSERVICES-PRIVATE-MIB');
$boxServicesNormalTempRangeMax = snmp_get($device, 'boxServicesNormalTempRangeMax.0', '-Ovq', 'FASTPATH-BOXSERVICES-PRIVATE-MIB');

// Initialize check variable to false
$boxServicesStackTempSensorsTable = FALSE;

// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.1.0 = INTEGER: 1
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.2.0 = INTEGER: 2
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorIndex.1.0 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorIndex.2.0 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorType.1.0 = INTEGER: fixed(1)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorType.2.0 = INTEGER: fixed(1)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorState.1.0 = INTEGER: normal(1)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorState.2.0 = INTEGER: normal(1)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorTemperature.1.0 = INTEGER: 28
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorTemperature.2.0 = INTEGER: 27

$oids = snmpwalk_cache_oid($device, 'boxServicesStackTempSensorsTable', [], 'FASTPATH-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry) {
    if (preg_match('/[\d:]/', $entry['boxServicesStackTempSensorType'])) {
        // Incorrect Stack table on new Dell firmwares,
        // should to use DNOS-BOXSERVICES-PRIVATE-MIB instead
        // FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorType.2.89 = Wrong Type (should be INTEGER): STRING: "239d:04:02:39"
        // FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesStackTempSensorType.2.90 = Wrong Type (should be INTEGER): STRING: "239d:03:02:38"
        print_debug('Device must use DNOS-BOXSERVICES-PRIVATE-MIB');
        return;
    }

    $boxServicesStackTempSensorsTable = TRUE;

    $descr = (count($oids) > 1 ? 'Stack Unit ' . $entry['boxServicesUnitIndex'] . ' ' : '') . 'Internal Temperature';
    $oid   = ".1.3.6.1.4.1.4413.1.1.43.1.9.1.5.$index";
    $value = $entry['boxServicesStackTempSensorTemperature'];

    $options = [
      'limit_low'        => $boxServicesNormalTempRangeMin,
      'limit_high'       => $boxServicesNormalTempRangeMax,
      'entPhysicalClass' => 'temperature',
    ];

    discover_sensor('temperature', $device, $oid, "boxServicesStackTempSensorTemperature.$index", 'fastpath-boxservices-private-mib', $descr, 1, $value, $options);

    $oid   = ".1.3.6.1.4.1.4413.1.1.43.1.9.1.4.$index";
    $value = $entry['boxServicesStackTempSensorState'];

    discover_status($device, $oid, "boxServicesStackTempSensorState.$index", 'boxServicesTempSensorState', $descr, $value, ['entPhysicalClass' => 'temperature']);
}

if (!$boxServicesStackTempSensorsTable) {
    // This table has been obsoleted by boxServicesStackTempSensorsTable - run it only if we didn't find that table.
    $oids = snmpwalk_cache_oid($device, 'boxServicesTempSensorsTable', [], 'FASTPATH-BOXSERVICES-PRIVATE-MIB');

    // FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.0 = INTEGER: 0
    // FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.0 = INTEGER: fixed(1)
    // FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorState.0 = INTEGER: normal(1)
    // FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.0 = INTEGER: 26

    foreach ($oids as $index => $entry) {
        $descr = 'Internal Temperature';
        $oid   = ".1.3.6.1.4.1.4413.1.1.43.1.8.1.4.$index";
        $value = $entry['boxServicesTempSensorTemperature'];

        $options = [
          'limit_low'        => $boxServicesNormalTempRangeMin,
          'limit_high'       => $boxServicesNormalTempRangeMax,
          'entPhysicalClass' => 'temperature',
        ];

        discover_sensor('temperature', $device, $oid, "boxServicesTempSensorTemperature.$index", 'fastpath-boxservices-private-mib', $descr, 1, $value, $options);

        $oid   = ".1.3.6.1.4.1.4413.1.1.43.1.8.1.3.$index";
        $value = $entry['boxServicesTempSensorState'];

        discover_status($device, $oid, "boxServicesTempSensorState.$index", 'boxServicesTempSensorState', $descr, $value, ['entPhysicalClass' => 'temperature']);
    }
}

// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.0 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.1 = INTEGER: 1
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.2 = INTEGER: 2
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.3 = INTEGER: 3
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.4 = INTEGER: 4
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.0 = INTEGER: operational(2)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.1 = INTEGER: operational(2)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.2 = INTEGER: operational(2)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.3 = INTEGER: operational(2)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.4 = INTEGER: operational(2)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.0 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.1 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.2 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.3 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.4 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.0 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.1 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.2 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.3 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.4 = INTEGER: 0

$oids = snmpwalk_cache_oid($device, 'boxServicesFansTable', [], 'FASTPATH-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry) {
    if ($entry['boxServicesFanItemState'] == 'notpresent') {
        continue;
    }

    $descr = 'Fan';
    if (count($oids) > 1) {
        $descr .= ' ' . ($index + 1);
    }

    $oid_name = 'boxServicesFanItemState';
    $oid_num  = '.1.3.6.1.4.1.4413.1.1.43.1.6.1.3.' . $index;
    $type     = 'boxServicesItemState';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'fan']);

    $scale    = 1;
    $oid_name = 'boxServicesFanSpeed';
    $oid_num  = '.1.3.6.1.4.1.4413.1.1.43.1.6.1.4.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    if ($value > 0) {
        discover_sensor('fanspeed', $device, $oid_num, $index, $type, $descr, $scale, $value);

        $scale    = 1;
        $oid_name = 'boxServicesFanDutyLevel';
        $oid_num  = '.1.3.6.1.4.1.4413.1.1.43.1.6.1.5.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];

        discover_sensor('load', $device, $oid_num, $index, $type, $descr, $scale, $value);
    }
}

// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex.0 = INTEGER: 0
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex.1 = INTEGER: 1
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemType.0 = INTEGER: fixed(1)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemType.1 = INTEGER: removable(2)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemState.0 = INTEGER: operational(2)
// FASTPATH-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemState.1 = INTEGER: operational(2)

$oids = snmpwalk_cache_oid($device, 'boxServicesPowSuppliesTable', [], 'FASTPATH-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry) {
    $descr = ucfirst($entry['boxServicesPowSupplyItemType'] . ' Power Supply');
    $oid   = ".1.3.6.1.4.1.4413.1.1.43.1.7.1.3.$index";
    $value = $entry['boxServicesPowSupplyItemState'];

    if ($entry['boxServicesPowSupplyItemState'] != 'notpresent') {
        discover_status($device, $oid, "boxServicesPowSupplyItemState.$index", 'boxServicesItemState', $descr, $value, ['entPhysicalClass' => 'power']);
    }
}

// EOF
