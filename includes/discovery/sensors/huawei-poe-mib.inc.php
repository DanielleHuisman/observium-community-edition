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

////// Global PSE Statistics

//
//HUAWEI-POE-MIB::hwPoePower.0 = INTEGER: 0
//HUAWEI-POE-MIB::hwPoePowerRsvPercent.0 = INTEGER: 0
//HUAWEI-POE-MIB::hwPoePowerUtilizationThreshold.0 = INTEGER: 0

// Stacking:
//HUAWEI-POE-MIB::hwPoeSlotMaximumPower.0 = INTEGER: 739200 mW
//HUAWEI-POE-MIB::hwPoeSlotAvailablePower.0 = INTEGER: 339602
//HUAWEI-POE-MIB::hwPoeSlotReferencePower.0 = INTEGER: 123200
//HUAWEI-POE-MIB::hwPoeSlotConsumingPower.0 = INTEGER: 29998
//HUAWEI-POE-MIB::hwPoeSlotPeakPower.0 = INTEGER: 35563
//HUAWEI-POE-MIB::hwPoeSlotLegacyDetect.0 = INTEGER: disabled(2)
//HUAWEI-POE-MIB::hwPoeSlotPowerManagementManner.0 = INTEGER: auto(2)
//HUAWEI-POE-MIB::hwPoeSlotIsPoeDevice.0 = STRING: "yes"
//HUAWEI-POE-MIB::hwPoeSlotPowerRsvPercent.0 = INTEGER: 20
//HUAWEI-POE-MIB::hwPoeSlotPowerUtilizationThreshold.0 = INTEGER: 90

$oids = snmpwalk_cache_oid($device, 'hwPoeSlotEntry', [], $mib);

// Stacked devices
foreach ($oids as $index => $entry) {
    if ($entry['hwPoeSlotConsumingPower'] == 0 && $entry['hwPoeSlotIsPoeDevice'] != 'yes') {
        continue;
    }

    $descr    = "PoE Slot " . ($index + 1);
    $oid_name = 'hwPoeSlotConsumingPower';
    $oid_num  = ".1.3.6.1.4.1.2011.5.25.195.2.1.5.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];
    $scale    = 0.001;

    // Options
    $options = ['limit_high' => $entry['hwPoeSlotMaximumPower'] * $scale, // I not sure.. or hwPoeSlotAvailablePower or hwPoeSlotReferencePower
                'limit_low'  => 0]; // Hardcode 0 as lower limit. Low warning limit will be calculated.

    $options['limit_high_warn'] = $entry['hwPoeSlotPowerUtilizationThreshold'] > 0 ? $entry['hwPoeSlotPowerUtilizationThreshold'] / 100 : 0.9;
    $options['limit_high_warn'] *= $options['limit_high'];
    discover_sensor('power', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);
}

print_debug_vars($oids);

////// Per-port Statistics

//HUAWEI-POE-MIB::hwPoePortName.5 = STRING: "Ethernet0/0/1"
//HUAWEI-POE-MIB::hwPoePortEnable.5 = INTEGER: enabled(1)
//HUAWEI-POE-MIB::hwPoePortPriority.5 = INTEGER: low(3)
//HUAWEI-POE-MIB::hwPoePortMaximumPower.5 = INTEGER: 30000 mW
//HUAWEI-POE-MIB::hwPoePortPowerOnStatus.5 = STRING: "on"
//HUAWEI-POE-MIB::hwPoePortPowerStatus.5 = STRING: "Powered"
//HUAWEI-POE-MIB::hwPoePortPdClass.5 = INTEGER: 3
//HUAWEI-POE-MIB::hwPoePortReferencePower.5 = INTEGER: 15400
//HUAWEI-POE-MIB::hwPoePortConsumingPower.5 = INTEGER: 4240
//HUAWEI-POE-MIB::hwPoePortPeakPower.5 = INTEGER: 8745
//HUAWEI-POE-MIB::hwPoePortAveragePower.5 = INTEGER: 4181
//HUAWEI-POE-MIB::hwPoePortCurrent.5 = STRING: "82"
//HUAWEI-POE-MIB::hwPoePortVoltage.5 = STRING: "53"
//HUAWEI-POE-MIB::hwPoePortManualOperation.5 = INTEGER: powerOff(1)

//HUAWEI-POE-MIB::hwPoePortName.20 = STRING: "Ethernet0/0/16"
//HUAWEI-POE-MIB::hwPoePortEnable.20 = INTEGER: enabled(1)
//HUAWEI-POE-MIB::hwPoePortPriority.20 = INTEGER: low(3)
//HUAWEI-POE-MIB::hwPoePortMaximumPower.20 = INTEGER: 30000 mW
//HUAWEI-POE-MIB::hwPoePortPowerOnStatus.20 = STRING: "off"
//HUAWEI-POE-MIB::hwPoePortPowerStatus.20 = STRING: "Detecting"
//HUAWEI-POE-MIB::hwPoePortPdClass.20 = INTEGER: 0
//HUAWEI-POE-MIB::hwPoePortReferencePower.20 = INTEGER: 0
//HUAWEI-POE-MIB::hwPoePortConsumingPower.20 = INTEGER: 0
//HUAWEI-POE-MIB::hwPoePortPeakPower.20 = INTEGER: 0
//HUAWEI-POE-MIB::hwPoePortAveragePower.20 = INTEGER: 0
//HUAWEI-POE-MIB::hwPoePortCurrent.20 = STRING: "0"
//HUAWEI-POE-MIB::hwPoePortVoltage.20 = STRING: "0"
//HUAWEI-POE-MIB::hwPoePortManualOperation.20 = INTEGER: powerOff(1)

$oids = snmpwalk_cache_oid($device, 'hwPoePortEntry', [], $mib);

print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    if ($entry['hwPoePortEnable'] != 'enabled' ||
        (($entry['hwPoePortPowerOnStatus'] == 'off' || $entry['hwPoePortPowerStatus'] == 'Detecting') &&
         $entry['hwPoePortConsumingPower'] == 0 && $entry['hwPoePortCurrent'] == 0 && $entry['hwPoePortVoltage'] == 0)) {
        // Skip PoE disabled ports
        continue;
    }

    $options = ['entPhysicalIndex' => $index];
    $port    = get_port_by_ifIndex($device['device_id'], $index);
    // print_vars($port);

    if (is_array($port)) {
        $entry['ifDescr']                     = $port['port_label'];
        $options['measured_class']            = 'port';
        $options['measured_entity']           = $port['port_id'];
        $options['entPhysicalIndex_measured'] = $port['ifIndex'];
    } else {
        $entry['ifDescr'] = $entry['hwPoePortName'];
    }

    $descr    = $entry['ifDescr'] . ' PoE Power';
    $oid_name = 'hwPoePortConsumingPower';
    $oid_num  = ".1.3.6.1.4.1.2011.5.25.195.3.1.10.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];
    $scale    = 0.001;

    // Limits
    $options['limit_high'] = $entry['hwPoePortMaximumPower'] * $scale;
    if ($options['limit_high'] > 0) {
        $options['limit_high_warn'] = $options['limit_high'] * 0.9; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
    } else {
        unset($options['limit_high']);
    }

    discover_sensor('power', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

    $descr    = $entry['ifDescr'] . ' PoE Current';
    $oid_name = 'hwPoePortCurrent';
    $oid_num  = ".1.3.6.1.4.1.2011.5.25.195.3.1.13.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];
    $scale    = 0.001;

    unset($options['limit_high'], $options['limit_high_warn']);

    discover_sensor('current', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

    $descr    = $entry['ifDescr'] . ' PoE Voltage';
    $oid_name = 'hwPoePortVoltage';
    $oid_num  = ".1.3.6.1.4.1.2011.5.25.195.3.1.14.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];
    $scale    = 1;

    unset($options['limit_high'], $options['limit_high_warn']);

    discover_sensor('voltage', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

}

// EOF
