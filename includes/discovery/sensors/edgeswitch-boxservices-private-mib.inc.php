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
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.1.0 = Gauge32: 1
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.1.1 = Gauge32: 1
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.1.0 = Gauge32: 0
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.1.1 = Gauge32: 1
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.1.0 = INTEGER: fixed(1)
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.1.1 = INTEGER: fixed(1)
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.1.0 = INTEGER: 50
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.1.1 = INTEGER: 33
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempUnitIndex.1 = Gauge32: 1
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempUnitState.1 = INTEGER: normal(1)
EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesTempUnitTemperature.1 = INTEGER: 52
*/

$oids = snmpwalk_cache_oid($device, 'boxServicesTempSensorsTable', [], 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

// By first detect if device used old FAST-BOXSERVICES-PRIVATE-MIB, it use single key in boxServicesTempSensorsTable
$first_key = current(array_keys($oids));
if (count(explode('.', $first_key)) === 1) {
    print_debug('Device must use old FASTPATH-BOXSERVICES-PRIVATE-MIB');
    return;
}

// Retrieve temperature limits
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMin.0 = INTEGER: -5
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMax.0 = INTEGER: 85

$boxServicesNormalTempRangeMin = snmp_get_oid($device, 'boxServicesNormalTempRangeMin.0', 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');
$boxServicesNormalTempRangeMax = snmp_get_oid($device, 'boxServicesNormalTempRangeMax.0', 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry) {
    $boxServicesStackTempSensorsTable = TRUE;

    $descr = 'Internal Sensor ' . $entry['boxServicesTempSensorIndex'];
    if ($entry['boxServicesUnitIndex'] > 1) {
        $descr .= ' (Unit ' . $entry['boxServicesUnitIndex'] . ')';
    }
    $oid   = ".1.3.6.1.4.1.4413.1.1.43.1.8.1.5.$index";
    $value = $entry['boxServicesTempSensorTemperature'];

    $options = [
      'limit_low'        => $boxServicesNormalTempRangeMin,
      'limit_high'       => $boxServicesNormalTempRangeMax,
      'entPhysicalClass' => 'chassis',
    ];

    if ($value != 0) {
        $options['rename_rrd'] = "edgeswitch-boxservices-private-mib-boxServicesTempSensorTemperature.$index";
        discover_sensor_ng($device, 'temperature', $mib, 'boxServicesTempSensorTemperature', $oid, $index, NULL, $descr, 1, $value, $options);
    }
}

// Statuses

$oids = snmpwalk_cache_oid($device, 'boxServicesTempUnitState', [], 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry) {
    $oid   = ".1.3.6.1.4.1.4413.1.1.43.1.15.1.2.$index";
    $value = $entry['boxServicesTempUnitState'];
    $descr = 'Temperature Status';
    if (count($oids) > 1) {
        $descr .= ' (Unit ' . $entry['index'] . ')';
    }

    discover_status_ng($device, $mib, 'boxServicesTempUnitState', $oid, $index, 'edgeswitch-boxServicesTempSensorState', $descr, $value, ['entPhysicalClass' => 'chassis', 'rename_rrd' => 'edgeswitch-boxServicesTempSensorState-%index%']);
}

//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.0 = INTEGER: 0
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemType.0 = INTEGER: removable(2)
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.0 = INTEGER: operational(2)
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.0 = INTEGER: 7938
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.0 = INTEGER: 32
// or
// EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.1.0 = INTEGER: 0
// EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemType.1.0 = INTEGER: fixed(1)
// EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.1.0 = INTEGER: operational(2)
// EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.1.0 = INTEGER: 3765
// EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.1.0 = INTEGER: 56
// EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesFanUnitIndex.1.0 = Gauge32: 1

$oids = snmpwalk_cache_oid($device, 'boxServicesFansTable', [], 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry) {
    if ($entry['boxServicesFanItemState'] == 'notpresent') {
        continue;
    }

    $descr = ucfirst($entry['boxServicesFanItemType']) . ' Fan ' . $entry['boxServicesFansIndex'];
    if ($entry['boxServicesFanUnitIndex'] > 1) {
        $descr .= ' (Unit ' . $entry['boxServicesFanUnitIndex'] . ')';
    }

    $oid_name = 'boxServicesFanSpeed';
    $oid_num  = ".1.3.6.1.4.1.4413.1.1.43.1.6.1.4.{$index}";
    $type     = $mib . '-' . $oid_name;
    $scale    = 1;
    $value    = $entry[$oid_name];

    if ($value != 0) {
        discover_sensor_ng($device, 'fanspeed', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

        $oid_name = 'boxServicesFanDutyLevel';
        $oid_num  = ".1.3.6.1.4.1.4413.1.1.43.1.6.1.5.{$index}";
        $type     = $mib . '-' . $oid_name;
        $scale    = 1;
        $value    = $entry[$oid_name];

        discover_sensor_ng($device, 'load', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);
    }

    $oid_name = 'boxServicesFanItemState';
    $oid_num  = ".1.3.6.1.4.1.4413.1.1.43.1.6.1.3.{$index}";
    $type     = 'edgeswitch-boxServicesItemState';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, 'boxServicesFanItemState', $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'fan', 'rename_rrd' => 'edgeswitch-boxServicesItemState-boxServicesFanItemState.%index%']);
}

//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex.0 = INTEGER: 0
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemType.0 = INTEGER: fixed(1)
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemState.0 = INTEGER: operational(2)
// or:
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex.1.0 = INTEGER: 0
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemType.1.0 = INTEGER: fixed(1)
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemState.1.0 = INTEGER: operational(2)
//EdgeSwitch-BOXSERVICES-PRIVATE-MIB::boxServicesPowerSuppUnitIndex.1.0 = Gauge32: 1

$oids = snmpwalk_cache_oid($device, 'boxServicesPowSuppliesTable', [], 'EdgeSwitch-BOXSERVICES-PRIVATE-MIB');

foreach ($oids as $index => $entry) {
    if ($entry['boxServicesPowSupplyItemType'] == 0 && $entry['boxServicesPowSupplyItemState'] === 'failed') {
        continue;
    } // This sensor not really exist

    $descr = ucfirst($entry['boxServicesPowSupplyItemType']) . ' Power Supply ' . $entry['boxServicesPowSupplyIndex'];
    if ($entry['boxServicesPowerSuppUnitIndex'] > 1) {
        $descr .= ' (Unit ' . $entry['boxServicesPowerSuppUnitIndex'] . ')';
    }
    $oid   = ".1.3.6.1.4.1.4413.1.1.43.1.7.1.3.$index";
    $value = $entry['boxServicesPowSupplyItemState'];

    if ($value !== 'notpresent') {
        discover_status_ng($device, $mib, 'boxServicesPowSupplyItemState', $oid, $index, 'edgeswitch-boxServicesItemState', $descr, $value, ['entPhysicalClass' => 'powerSupply', 'rename_rrd' => 'edgeswitch-boxServicesItemState-boxServicesPowSupplyItemState.%index%']);
    }
}

// EOF
