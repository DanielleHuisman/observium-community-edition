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

if (!isset($GLOBALS['cache']['entity-mib'])) {
    $entity_array = snmpwalk_cache_oid($device, 'entPhysicalDescr', [], 'ENTITY-MIB');
} else {
    $entity_array = $GLOBALS['cache']['entity-mib'];
}

//EXTREME-SYSTEM-MIB::extremeFanNumber.101 = INTEGER: 101
//EXTREME-SYSTEM-MIB::extremeFanNumber.302 = INTEGER: 302
//EXTREME-SYSTEM-MIB::extremeFanOperational.101 = INTEGER: true(1)
//EXTREME-SYSTEM-MIB::extremeFanOperational.302 = INTEGER: true(1)
//EXTREME-SYSTEM-MIB::extremeFanEntPhysicalIndex.101 = INTEGER: 6
//EXTREME-SYSTEM-MIB::extremeFanEntPhysicalIndex.302 = INTEGER: 0
//EXTREME-SYSTEM-MIB::extremeFanSpeed.101 = INTEGER: 3233
//EXTREME-SYSTEM-MIB::extremeFanSpeed.302 = INTEGER: 7021

$oids['FanStatus'] = snmpwalk_cache_oid($device, 'extremeFanStatusTable', [], $mib);
//print_vars($oids);

foreach ($oids['FanStatus'] as $index => $entry) {
    if (empty($entity_array[$entry['extremeFanEntPhysicalIndex']]['entPhysicalDescr'])) {
        $descr = 'Fan ' . $index;
    } else {
        $descr = $entity_array[$entry['extremeFanEntPhysicalIndex']]['entPhysicalDescr'];
    }

    $oid_name = 'extremeFanSpeed';
    $oid_num  = ".1.3.6.1.4.1.1916.1.1.1.9.1.4.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];
    $options  = ['entPhysicalIndex' => $entry['extremeFanEntPhysicalIndex']];

    discover_sensor_ng($device, 'fanspeed', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $options);

    // Fan Status
    $oid_name = 'extremeFanOperational';
    $oid_num  = '.1.3.6.1.4.1.1916.1.1.1.9.1.2.' . $index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, 'extremeTruthValue', $descr, $value, array_merge($options, ['entPhysicalClass' => 'fan']));

}

//EXTREME-SYSTEM-MIB::extremePowerSupplyNumber.1 = INTEGER: 1
//EXTREME-SYSTEM-MIB::extremePowerSupplyNumber.2 = INTEGER: 2
//EXTREME-SYSTEM-MIB::extremePowerSupplyStatus.1 = INTEGER: presentOK(2)
//EXTREME-SYSTEM-MIB::extremePowerSupplyStatus.2 = INTEGER: presentPowerOff(4)
//EXTREME-SYSTEM-MIB::extremePowerSupplyInputVoltage.1 = INTEGER: unknown(4)
//EXTREME-SYSTEM-MIB::extremePowerSupplyInputVoltage.2 = INTEGER: unknown(4)
//EXTREME-SYSTEM-MIB::extremePowerSupplySerialNumber.1 = STRING: "1430W-80424"
//EXTREME-SYSTEM-MIB::extremePowerSupplySerialNumber.2 = ""
//EXTREME-SYSTEM-MIB::extremePowerSupplyEntPhysicalIndex.1 = INTEGER: 3
//EXTREME-SYSTEM-MIB::extremePowerSupplyEntPhysicalIndex.2 = INTEGER: 5
//EXTREME-SYSTEM-MIB::extremePowerSupplyFan1Speed.1 = INTEGER: notPresent(-1)
//EXTREME-SYSTEM-MIB::extremePowerSupplyFan1Speed.2 = INTEGER: notPresent(-1)
//EXTREME-SYSTEM-MIB::extremePowerSupplyFan2Speed.1 = INTEGER: notPresent(-1)
//EXTREME-SYSTEM-MIB::extremePowerSupplyFan2Speed.2 = INTEGER: notPresent(-1)
//EXTREME-SYSTEM-MIB::extremePowerSupplySource.1 = INTEGER: ac(2)
//EXTREME-SYSTEM-MIB::extremePowerSupplySource.2 = INTEGER: ac(2)
//EXTREME-SYSTEM-MIB::extremePowerSupplyInputPowerUsage.1 = INTEGER: 74800
//EXTREME-SYSTEM-MIB::extremePowerSupplyInputPowerUsage.2 = INTEGER: 0
//EXTREME-SYSTEM-MIB::extremePowerMonSupplyNumOutput.1 = INTEGER: 1
//EXTREME-SYSTEM-MIB::extremePowerMonSupplyNumOutput.2 = INTEGER: 0
//EXTREME-SYSTEM-MIB::extremePowerSupplyInputPowerUsageUnitMultiplier.1 = INTEGER: milli(-3)
//EXTREME-SYSTEM-MIB::extremePowerSupplyInputPowerUsageUnitMultiplier.2 = INTEGER: milli(-3)

$oids['PowerSupply'] = snmpwalk_cache_oid($device, 'extremePowerSupplyTable', [], $mib);
//print_vars($oids);

foreach ($oids['PowerSupply'] as $index => &$entry) {
    if (empty($entity_array[$entry['extremePowerSupplyEntPhysicalIndex']]['entPhysicalDescr'])) {
        $name = "Power Supply $index";
    } else {
        $name = $entity_array[$entry['extremePowerSupplyEntPhysicalIndex']]['entPhysicalDescr'];
        if (!preg_match('/[0-9]/', $name)) {
            // Append index if name not contain any number for identification
            $name .= " $index";
        }
    }
    $entry['name'] = $name;
    $options       = ['entPhysicalIndex' => $entry['extremePowerSupplyEntPhysicalIndex']];

    // Power Status
    $descr    = $name;
    $oid_name = 'extremePowerSupplyStatus';
    $oid_num  = '.1.3.6.1.4.1.1916.1.1.1.27.1.2.' . $index;
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, 'extremePowerSupplyStatus', $descr, $value, array_merge($options, ['entPhysicalClass' => 'powersupply']));

    $oid_name = 'extremePowerSupplyInputPowerUsage';
    $value    = $entry[$oid_name];
    if ($value > 0) {
        $oid_num = ".1.3.6.1.4.1.1916.1.1.1.27.1.9.$index";
        $type    = $mib . '-' . $oid_name;

        discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, si_to_scale($entry['extremePowerSupplyInputPowerUsageUnitMultiplier']), $value, $options);
    }
}

//EXTREME-SYSTEM-MIB::extremePowerSupplyIndex.1.1 = INTEGER: 1
//EXTREME-SYSTEM-MIB::extremePowerSupplyIndex.2.1 = INTEGER: 2
//EXTREME-SYSTEM-MIB::extremePowerSupplyOutputSensorIdx.1.1 = INTEGER: 1
//EXTREME-SYSTEM-MIB::extremePowerSupplyOutputSensorIdx.2.1 = INTEGER: 1
//EXTREME-SYSTEM-MIB::extremePowerSupplyOutputVoltage.1.1 = INTEGER: 12060
//EXTREME-SYSTEM-MIB::extremePowerSupplyOutputVoltage.2.1 = INTEGER: 0
//EXTREME-SYSTEM-MIB::extremePowerSupplyOutputCurrent.1.1 = INTEGER: 4900
//EXTREME-SYSTEM-MIB::extremePowerSupplyOutputCurrent.2.1 = INTEGER: 0
//EXTREME-SYSTEM-MIB::extremePowerSupplyOutputUnitMultiplier.1.1 = INTEGER: milli(-3)
//EXTREME-SYSTEM-MIB::extremePowerSupplyOutputUnitMultiplier.2.1 = INTEGER: milli(-3)

$oids['PowerSupplyOutput'] = snmpwalk_cache_twopart_oid($device, 'extremePowerSupplyOutputPowerTable', [], $mib);
//print_vars($oids);

foreach ($oids['PowerSupplyOutput'] as $extremePowerSupplyIndex => $entry1) {
    $supply_count = count($entry1);
    $supply       = $oids['PowerSupply'][$extremePowerSupplyIndex];
    foreach ($entry1 as $extremePowerSupplyOutputSensorIdx => $entry) {
        $index = $extremePowerSupplyIndex . '.' . $extremePowerSupplyOutputSensorIdx;
        $descr = $supply['name'] . ' Output';
        if ($supply_count > 1) {
            $descr .= ' ' . $extremePowerSupplyOutputSensorIdx;
        }
        $options = ['entPhysicalIndex' => $supply['extremePowerSupplyEntPhysicalIndex']];
        $scale   = si_to_scale($entry['extremePowerSupplyOutputUnitMultiplier']);

        $oid_name = 'extremePowerSupplyOutputVoltage';
        $value    = $entry[$oid_name];
        if ($value > 0) {
            $oid_num = ".1.3.6.1.4.1.1916.1.1.1.38.1.3.$index";
            $type    = $mib . '-' . $oid_name;

            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }

        $oid_name = 'extremePowerSupplyOutputCurrent';
        $value    = $entry[$oid_name];
        if ($value > 0) {
            $oid_num = ".1.3.6.1.4.1.1916.1.1.1.38.1.4.$index";
            $type    = $mib . '-' . $oid_name;

            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }
    }
}

// FIXME, actual only for stacked devices, or it same as power supply power usage
//EXTREME-SYSTEM-MIB::extremeSystemPowerUsageValue.0 = INTEGER: 74800
//EXTREME-SYSTEM-MIB::extremeSystemPowerUsageUnitMultiplier.0 = INTEGER: milli(-3)

$oids['SystemPowerUsage'] = snmp_get_multi_oid($device, 'extremeSystemPowerUsageValue.0 extremeSystemPowerUsageUnitMultiplier.0', [], $mib);
//print_vars($oids);

$index = 0;
$entry = $oids['SystemPowerUsage'][$index];
$descr = 'Total Power Usage';
$scale = si_to_scale($entry['extremeSystemPowerUsageUnitMultiplier']);

$oid_name = 'extremeSystemPowerUsageValue';
$value    = $entry[$oid_name];
if ($value > 0) {
    $oid_num = ".1.3.6.1.4.1.1916.1.1.1.40.1.$index";
    $type    = $mib . '-' . $oid_name;

    discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);
}

unset($oids);

// EOF
