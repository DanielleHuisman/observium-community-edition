<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Temperature

// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.1.0 = Gauge32: 1
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.1.1 = Gauge32: 1
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.2.0 = Gauge32: 2
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesUnitIndex.2.1 = Gauge32: 2
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.1.0 = Gauge32: 0
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.1.1 = Gauge32: 1
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.2.0 = Gauge32: 0
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorIndex.2.1 = Gauge32: 1
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.1.0 = INTEGER: fixed(1)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.1.1 = INTEGER: fixed(1)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.2.0 = INTEGER: fixed(1)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorType.2.1 = INTEGER: fixed(1)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.1.0 = INTEGER: 35
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.1.1 = INTEGER: 29
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.2.0 = INTEGER: 33
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesTempSensorTemperature.2.1 = INTEGER: 28

$oid  = 'boxServicesTempSensorsTable';
$oids = snmpwalk_cache_oid($device, $oid, [], $mib);

// By first detect if device used old FAST-BOXSERVICES-PRIVATE-MIB, it use single key in boxServicesTempSensorsTable
if (safe_count($oids)) {
    $index = explode('.', key($oids));
    if (count($index) === 1) {
        print_debug('Device must use OLD-DNOS-BOXSERVICES-PRIVATE-MIB');
        return; // Exit from mib discovery
    }
}

// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMin.0 = INTEGER: 0
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesNormalTempRangeMax.0 = INTEGER: 45

$boxServicesNormalTempRangeMin = snmp_get_oid($device, 'boxServicesNormalTempRangeMin.0', $mib);
$boxServicesNormalTempRangeMax = snmp_get_oid($device, 'boxServicesNormalTempRangeMax.0', $mib);

foreach ($oids as $index => $entry) {
    [$unit, $iter] = explode('.', $index);

    // Temperature
    $value = $entry['boxServicesTempSensorTemperature'];
    $descr = "Unit $unit Sensor " . ($iter + 1);

    $sensor_oid = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.8.1.5.$index";
    $options    = [
      'limit_low'        => $boxServicesNormalTempRangeMin,
      'limit_high'       => $boxServicesNormalTempRangeMax,
      'entPhysicalClass' => 'temperature'];

    $options['rename_rrd'] = "$mib-boxServicesTempSensorTemperature.$index";
    discover_sensor_ng($device, 'temperature', $mib, 'boxServicesTempSensorTemperature', $sensor_oid, $index, NULL,
                       $descr, 1, $value, $options);

    // State
    $descr      = "Unit $unit Temperature Sensor " . ($iter + 1);
    $value      = $entry['boxServicesTempSensorState'];
    $status_oid = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.8.1.4.$index";
    $options    = ['entPhysicalClass' => 'temperature'];

    $options['rename_rrd'] = 'dnos-boxservices-temp-state-boxServicesFanItemState.%index%';
    discover_status_ng($device, $mib, 'boxServicesTempSensorState', $status_oid, $index, 'dnos-boxservices-temp-state', $descr, $value, $options);

}

// Unit / Stack Member Temperature State
//   Some devices (N2048 v6.0.1.3) don't provide state data for each sensor
//   in the boxServicesTempSensorsTable we walk above, but they do provide
//   overall state data for the Unit / Stack Member.
//
// boxServicesTempUnitState.1 = normal
// boxServicesTempUnitState.2 = normal
// boxServicesTempUnitState.3 = normal

$oid  = 'boxServicesTempUnitState';
$oids = snmpwalk_cache_oid($device, $oid, [], $mib);

foreach ($oids as $index => $entry) {
    $descr = "Unit $index Temperature";

    $value = $entry['boxServicesTempUnitState'];

    $status_oid = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.15.1.2.$index";
    $options    = ['entPhysicalClass' => 'temperature'];

    discover_status_ng($device, $mib, 'boxServicesTempUnitState', $status_oid, $index, 'dnos-boxservices-temp-state', $descr, $value, $options);

}

// Fans
//
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.0 = INTEGER: 0
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFansIndex.1 = INTEGER: 1
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemType.0 = INTEGER: fixed(1)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemType.1 = INTEGER: fixed(1)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.0 = INTEGER: operational(2)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanItemState.1 = INTEGER: operational(2)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.0 = INTEGER: 9056
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanSpeed.1 = INTEGER: 9230
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.0 = INTEGER: 0
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesFanDutyLevel.1 = INTEGER: 0

$oid          = 'boxServicesFansTable';
$oids         = snmpwalk_cache_oid($device, $oid, [], $mib);
$show_numbers = safe_count($oids) > 1;

foreach ($oids as $index => $entry) {
    if ($entry['boxServicesFanItemState'] == 'notpresent') {
        continue;
    }

    // State Sensor
    $value = $entry['boxServicesFanItemState'];
    //$descr = nicecase(rewrite_entity_name($entry['boxServicesFanItemType'])) .' Fan';
    //if ($show_numbers) { $descr .= ' '. ($index+1); }

    [$unit, $iter] = explode('.', $index);
    $descr = "Unit $unit Fan " . ($iter + 1) . ' (' . nicecase(rewrite_entity_name($entry['boxServicesFanItemType'])) . ')';

    $status_oid = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.6.1.3.$index";
    $options    = ['entPhysicalClass' => 'fan'];

    discover_status_ng($device, $mib, 'boxServicesFanItemState', $status_oid, $index, 'dnos-boxservices-state', $descr, $value, $options);

    if ($entry['boxServicesFanSpeed'] != 0) {
        $options['rename_rrd'] = "fastpath-boxservices-private-mib-boxServicesFanSpeed.$index";
        discover_sensor_ng(
          $device,
          'fanspeed',
          $mib,
          'boxServicesFanSpeed',
          ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.6.1.4.$index",
          $index,
          NULL,
          $descr,
          1,
          $entry['boxServicesFanSpeed'],
          $options);
    }

    if ($entry['boxServicesFanDutyLevel'] != 0) {
        $options['rename_rrd'] = "fastpath-boxservices-private-mib-boxServicesFanDutyLevel.$index";
        discover_sensor_ng(
          $device,
          'load',
          $mib,
          'boxServicesFanDutyLevel',
          ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.6.1.5.$index",
          $index,
          NULL,
          $descr,
          1,
          $entry['boxServicesFanDutyLevel'],
          $options);
    }

}

// Power Supplies
//
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex.0 = INTEGER: 0
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyIndex.1 = INTEGER: 1
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemType.0 = INTEGER: fixed(1)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemType.1 = INTEGER: removable(2)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemState.0 = INTEGER: operational(2)
// DNOS-BOXSERVICES-PRIVATE-MIB::boxServicesPowSupplyItemState.1 = INTEGER: notpresent(1)

$oid          = 'boxServicesPowSuppliesTable';
$oids         = snmpwalk_cache_oid($device, $oid, [], $mib);
$show_numbers = safe_count($oids) > 1;

foreach ($oids as $index => $entry) {
    if ($entry['boxServicesPowSupplyItemState'] === 'notpresent' ||
        ($entry['boxServicesPowSupplyItemType'] == 0 && $entry['boxServicesPowSupplyItemState'] === 'failed')) // This sensor not really exist)
    {
        continue;
    }

    // State Sensor
    $value = $entry['boxServicesPowSupplyItemState'];
    $descr = nicecase(rewrite_entity_name($entry['boxServicesPowSupplyItemType'])) . ' PSU';
    if ($show_numbers) {
        $descr .= ' ' . ($index + 1);
    }

    [$unit, $iter] = explode('.', $index);
    $descr = "Unit $unit PSU " . ($iter + 1) . ' (' . nicecase(rewrite_entity_name($entry['boxServicesPowSupplyItemType'])) . ')';

    $status_oid = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.7.1.3.$index";
    $options    = ['entPhysicalClass' => 'power'];

    //discover_status($device, $sensor_oid, "boxServicesPowSupplyItemState.$index", 'dnos-boxservices-state', $descr, $value, $options);
    discover_status_ng($device, $mib, 'boxServicesPowSupplyItemState', $status_oid, $index, 'dnos-boxservices-state', $descr, $value, $options);

}

// Power Usage
//
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitSampleTime.1.1 = STRING: "6d:03:46:39"
// ...
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitSampleTime.1.60 = STRING: "3d:16:45:40"
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitSampleTime.2.1 = STRING: "6d:04:46:39"
// ...
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitSampleTime.2.60 = STRING: "3d:17:45:40"
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitSampleTime.3.1 = STRING: "6d:04:46:39"
// ...
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitSampleTime.3.60 = STRING: "3d:17:45:40"
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitPowerConsumption.1.1 = INTEGER: 32616
// ...
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitPowerConsumption.1.60 = INTEGER: 32616
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitPowerConsumption.2.1 = INTEGER: 0
// ...
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitPowerConsumption.2.60 = INTEGER: 28992
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitPowerConsumption.3.1 = INTEGER: 0
// ...
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryUnitPowerConsumption.3.60 = INTEGER: 32616
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryStackPowerConsumption.1.1 = INTEGER: 94224
// ...
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryStackPowerConsumption.1.60 = INTEGER: 90600
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryStackPowerConsumption.2.1 = INTEGER: 0
// ...
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryStackPowerConsumption.2.60 = INTEGER: 94224
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryStackPowerConsumption.3.1 = INTEGER: 0
// ...
// DNOS-BOXSERVICES-PRIVATE-MIB::boxsPwrUsageHistoryStackPowerConsumption.3.60 = INTEGER: 94224

//$oid  = 'boxsUnitPwrUsageHistoryTable';
$oid = 'boxsPwrUsageHistoryUnitPowerConsumption';

$oids = snmpwalk_cache_oid($device, $oid, [], $mib);

// This may not hold up in the long run, but...
// Assume:
// 1. each unit has the same number of samples
// 2. the newest samples are in the highest numbered sample index
// 3. we will graph the most recent sample even if it's hours old
//   (See DNOS CLI "power-usage-history sampling-interval" command)
//
// Procedure:
// 1. Move array pointer to end of array.  (ex. key = "3.60")
// 2. Pull the samples per unit off the key/index.  (ex. "60")
end($oids);
[, $samples_per_unit] = explode('.', key($oids));

foreach ($oids as $index => $entry) {
    [$unit, $sample] = explode('.', $index);
    if ((int)$sample != $samples_per_unit) {
        continue;
    }

    $descr = "Unit $unit Power Usage";
    $value = $entry['boxsPwrUsageHistoryUnitPowerConsumption'];

    $sensor_oid = ".1.3.6.1.4.1.674.10895.5000.2.6132.1.1.43.1.9.1.4.$index";
    $options    = ['entPhysicalClass' => 'power'];

    if (is_numeric($value) && $value) {
        $options['rename_rrd'] = "$mib-boxsPwrUsageHistoryUnitPowerConsumption.$unit";
        discover_sensor_ng($device, 'power', $mib, 'boxsPwrUsageHistoryUnitPowerConsumption', $sensor_oid, $index, NULL, $descr, 0.001, $value, $options);
    }
}

// EOF
