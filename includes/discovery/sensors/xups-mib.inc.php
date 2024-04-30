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

$xups_array = snmpwalk_cache_oid($device, "xupsConfig", [], "XUPS-MIB");
$xups_array = snmpwalk_cache_oid($device, "xupsInput", $xups_array, "XUPS-MIB");
$xups_array = snmpwalk_cache_oid($device, "xupsOutput", $xups_array, "XUPS-MIB");
$xups_array = snmpwalk_cache_oid($device, "xupsBypass", $xups_array, "XUPS-MIB");

// XUPS-MIB::xupsConfigOutputVoltage.0 = INTEGER: 230 RMS Volts
// XUPS-MIB::xupsConfigInputVoltage.0 = INTEGER: 230 RMS Volts
// XUPS-MIB::xupsConfigOutputWatts.0 = INTEGER: 2700 Watts
// XUPS-MIB::xupsConfigOutputFreq.0 = INTEGER: 500 0.1 Hertz
// XUPS-MIB::xupsConfigDateAndTime.0 = STRING: 06/11/2020 06:39:49
// XUPS-MIB::xupsConfigLowOutputVoltageLimit.0 = INTEGER: 140 RMS Volts
// XUPS-MIB::xupsConfigHighOutputVoltageLimit.0 = INTEGER: 276 RMS Volts

$xups_base = $xups_array[0];
unset($xups_array[0]);

foreach ($xups_array as $index => $entry) {
    // Input
    if (isset($entry['xupsInputPhase'])) {
        $descr = "Input";
        $options = [];
        if ($xups_base['xupsInputNumPhases'] > 1) {
            $descr .= " Phase $index";

            $options  = [
                'measured_entity_label' => "Input Phase $index",
                'measured_class' => 'phase'
            ];
        }

        ## Input voltage
        $oid   = ".1.3.6.1.4.1.534.1.3.4.1.2.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                # XUPS-MIB::xupsInputVoltage.$index
        $value = $entry['xupsInputVoltage'];

        if ($value != 0 &&
            !isset($valid['sensor']['voltage']['mge-ups'][100 + $index])) {
            // Limits
            $limits = [];
            if ($xups_base['xupsConfigInputVoltage']) {
                $limits['limit_low'] = $xups_base['xupsConfigInputVoltage'] - 15;
                $limits['limit_high'] = $xups_base['xupsConfigInputVoltage'] + 15;
            }
            discover_sensor('voltage', $device, $oid, "xupsInputEntry." . $index, 'xups', $descr, 1, $value, array_merge($options, $limits));
        }

        ## Input current
        $oid = ".1.3.6.1.4.1.534.1.3.4.1.3.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   # XUPS-MIB::xupsInputCurrent.$index
        $value = $entry['xupsInputCurrent'];

        if ($value != 0 && $value < 10000 &&  // xupsInputCurrent.1 = 136137420 ? really? You're nuts.
            !isset($valid['sensor']['current']['mge-ups'][100 + $index])) {
            discover_sensor('current', $device, $oid, "xupsInputEntry." . $index, 'xups', $descr, 1, $value, $options);
        }

        ## Input power
        $oid   = ".1.3.6.1.4.1.534.1.3.4.1.4.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           # XUPS-MIB::xupsInputWatts.$index
        $value = $entry['xupsInputWatts'];
        if ($value != 0) {
            discover_sensor('power', $device, $oid, "xupsInputEntry." . $index, 'xups', $descr, 1, $value, $options);
        }
    }

    // Output
    // XUPS-MIB::xupsOutputLoad.0 = INTEGER: 3 percent
    // XUPS-MIB::xupsOutputFrequency.0 = INTEGER: 500 0.1 Hertz
    // XUPS-MIB::xupsOutputNumPhases.0 = INTEGER: 1

    // XUPS-MIB::xupsOutputPhase.1 = INTEGER: 1
    // XUPS-MIB::xupsOutputVoltage.1 = INTEGER: 230 RMS Volts
    // XUPS-MIB::xupsOutputCurrent.1 = INTEGER: 0 RMS Amps
    // XUPS-MIB::xupsOutputWatts.1 = INTEGER: 34 Watts
    // XUPS-MIB::xupsOutputId.1 = INTEGER: phase1toN(1)
    // XUPS-MIB::xupsOutputName.1 = STRING: "L1/A"
    // XUPS-MIB::xupsOutputCurrentHighPrecision.1 = INTEGER: 5 RMS tenth of Amps
    // XUPS-MIB::xupsOutputPercentLoad.1 = INTEGER: 3 percent
    // XUPS-MIB::xupsOutputVA.1 = INTEGER: 99 VA

    // XUPS-MIB::xupsOutputSource.0 = INTEGER: normal(3)
    // XUPS-MIB::xupsOutputAverageVoltage.0 = INTEGER: 229 RMS Volts
    // XUPS-MIB::xupsOutputAverageCurrent.0 = INTEGER: 0 RMS tenth of Amps
    // XUPS-MIB::xupsOutputTotalWatts.0 = INTEGER: 34 Watts
    // XUPS-MIB::xupsOutputTotalVA.0 = INTEGER: 98 VA
    // XUPS-MIB::xupsOutputAveragePowerFactor.0 = INTEGER: 33
    // XUPS-MIB::xupsOutput.10.0 = INTEGER: 3
    if (isset($entry['xupsOutputPhase'])) {
        $descr = "Output";
        $options = [];
        if ($xups_base['xupsOutputNumPhases'] > 1) {
            $descr .= " Phase $index";

            $options  = [
                'measured_entity_label' => "Output Phase $index",
                'measured_class' => 'phase'
            ];
        }

        ## Output voltage
        $oid   = ".1.3.6.1.4.1.534.1.4.4.1.2.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                # XUPS-MIB::xupsOutputVoltage.$index
        $value = $entry['xupsOutputVoltage'];
        if ($value != 0 &&
            !isset($valid['sensor']['voltage']['mge-ups'][$index])) {
            // Limits
            $limits = [];
            if ($xups_base['xupsConfigLowOutputVoltageLimit'] && $xups_base['xupsConfigHighOutputVoltageLimit']) {
                $limits['limit_low'] = $xups_base['xupsConfigLowOutputVoltageLimit'];
                $limits['limit_high'] = $xups_base['xupsConfigHighOutputVoltageLimit'];
            }
            discover_sensor('voltage', $device, $oid, "xupsOutputEntry." . $index, 'xups', $descr, 1, $value, array_merge($options, $limits));
        }

        ## Output current
        if (!isset($valid['sensor']['current']['mge-ups'][$index])) {
            $options['rename_rrd'] = 'xups-xupsOutputEntry.' . $index;
            if (isset($entry['xupsOutputCurrentHighPrecision']) && $entry['xupsOutputCurrentHighPrecision'] != 0) {
                // Prefer High precision
                $oid = ".1.3.6.1.4.1.534.1.4.4.1.7.$index";
                $value = $entry['xupsOutputCurrentHighPrecision'];
                $options['limit_auto'] = FALSE; // Not sure
                discover_sensor_ng($device, 'current', 'XUPS-MIB', 'xupsOutputCurrentHighPrecision', $oid, $index, NULL, $descr, 0.1, $value, $options);
            } elseif ($entry['xupsOutputCurrent'] != 0) {
                $oid = ".1.3.6.1.4.1.534.1.4.4.1.3.$index"; # XUPS-MIB::xupsOutputCurrent.$index
                $value = $entry['xupsOutputCurrent'];
                discover_sensor_ng($device, 'current', 'XUPS-MIB', 'xupsOutputCurrent', $oid, $index, NULL, $descr, 1, $value, $options);
            }
            unset($options['rename_rrd'], $options['limit_auto']);
            //discover_sensor('current', $device, $oid, "xupsOutputEntry.".$index, 'xups', $descr, 1, $value);
        }

        ## Output power
        $oid = ".1.3.6.1.4.1.534.1.4.4.1.4.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   # XUPS-MIB::xupsOutputWatts.$index
        $value = $entry['xupsOutputWatts'];
        if ($value != 0) {
            $limits = [];
            if ($xups_base['xupsConfigOutputWatts']) {
                $limits['limit_high_warn'] = $xups_base['xupsConfigOutputWatts'] * 0.8;
                $limits['limit_high']      = $xups_base['xupsConfigOutputWatts'] * 0.95;
            }
            discover_sensor('power', $device, $oid, "xupsOutputEntry." . $index, 'xups', $descr, 1, $value, array_merge($options, $limits));
        }

        ## Output Active power
        $oid   = ".1.3.6.1.4.1.534.1.4.4.1.9.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           # XUPS-MIB::xupsOutputVA.$index
        $value = $entry['xupsOutputVA'];
        if ($value != 0) {
            discover_sensor_ng($device, 'apower', 'XUPS-MIB', 'xupsOutputVA', $oid, $index, NULL, $descr, 1, $value, $options);
        }
    }

    // Bypass
    // XUPS-MIB::xupsBypassFrequency.0 = INTEGER: 500 0.1 Hertz
    // XUPS-MIB::xupsBypassNumPhases.0 = INTEGER: 1

    // XUPS-MIB::xupsBypassPhase.1 = INTEGER: 1
    // XUPS-MIB::xupsBypassVoltage.1 = INTEGER: 229 RMS Volts
    // XUPS-MIB::xupsBypassId.1 = INTEGER: phase1toN(1)
    // XUPS-MIB::xupsBypassName.1 = STRING: "L1/A"
    // XUPS-MIB::xupsBypassCurrentHighPrecision.1 = INTEGER: 0 RMS tenth of Amps

    // XUPS-MIB::xupsBypassAverageVoltage.0 = INTEGER: 229 RMS Volts
    // XUPS-MIB::xupsBypassAverageCurrent.0 = INTEGER: 0 RMS tenth of Amps
    if (isset($entry['xupsBypassPhase'])) {
        $descr = "Bypass";
        $options = [];
        if ($xups_base['xupsBypassNumPhases'] > 1) {
            $descr .= " Phase $index";

            $options  = [
                'measured_entity_label' => "Bypass Phase $index",
                'measured_class' => 'phase'
            ];
        }

        ## Bypass voltage
        $oid   = ".1.3.6.1.4.1.534.1.5.3.1.2.$index"; # XUPS-MIB::xupsBypassVoltage.$index
        $value = $entry['xupsBypassVoltage'];
        if ($value != 0) {
            discover_sensor('voltage', $device, $oid, "xupsBypassEntry." . $index, 'xups', $descr, 1, $value, $options);

            if (isset($entry['xupsBypassCurrentHighPrecision'])) { // && $entry['xupsBypassCurrentHighPrecision'] != 0)
                $oid   = ".1.3.6.1.4.1.534.1.5.3.1.5.$index";
                $value = $entry['xupsBypassCurrentHighPrecision'];
                discover_sensor_ng($device, 'current', 'XUPS-MIB', 'xupsBypassCurrentHighPrecision', $oid, $index, NULL, $descr, 0.1, $value, $options);
            }
        }
    }
}

$entry = $xups_base;

## Input frequency
$oid   = ".1.3.6.1.4.1.534.1.3.1.0";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             # XUPS-MIB::xupsInputFrequency.0
$scale = 0.1;
$value = $entry['xupsInputFrequency'];
if ($value != 0 &&
    !isset($valid['sensor']['frequency']['mge-ups'][101])) {
    discover_sensor('frequency', $device, $oid, "xupsInputFrequency.0", 'xups', "Input", $scale, $value);
}

## Output Load
$oid = ".1.3.6.1.4.1.534.1.4.1.0";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   # XUPS-MIB::xupsOutputLoad.0
$descr = "Output Load";
$value = $entry['xupsOutputLoad'];
if (!isset($valid['sensor']['load']['mge-ups']['mgoutputLoadPerPhase.1'])) {
    $limits = ['limit_high_warn' => 80, 'limit_high' => 95];
    discover_sensor('load', $device, $oid, "xupsOutputLoad.0", 'xups', $descr, 1, $value, $limits);
}

## Output Frequency
$oid = ".1.3.6.1.4.1.534.1.4.2.0";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         # XUPS-MIB::xupsOutputFrequency.0
$value = $entry['xupsOutputFrequency'];
if ($value != 0 &&
    !isset($valid['sensor']['frequency']['mge-ups'][1])) {
    $limits = [];
    if ($xups_base['xupsConfigOutputFreq']) {
        $limits['limit_low'] = $xups_base['xupsConfigOutputFreq'] * 0.099;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        // 50 -> 49,5
        $limits['limit_low_warn'] = $xups_base['xupsConfigOutputFreq'] * 0.0996;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            // 50 -> 49.8
        $limits['limit_high_warn'] = $xups_base['xupsConfigOutputFreq'] * 0.1004;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 // 50 -> 50.2
        $limits['limit_high'] = $xups_base['xupsConfigOutputFreq'] * 0.101;                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       // 50 -> 50,5
    }
    discover_sensor('frequency', $device, $oid, "xupsOutputFrequency.0", 'xups', "Output", $scale, $value, $limits);
}

## Output Power Factor
$oid   = ".1.3.6.1.4.1.534.1.4.9.5.0";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         # XUPS-MIB::xupsOutputAveragePowerFactor.0
$descr = "Output Power Factor";
$value = $entry['xupsOutputAveragePowerFactor'];
if ($value != 0) {
    discover_sensor_ng($device, 'powerfactor', 'XUPS-MIB', 'xupsOutputAveragePowerFactor', $oid, 0, NULL, $descr, 1, $value);
}

## Bypass Frequency
$oid   = ".1.3.6.1.4.1.534.1.5.1.0";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           # XUPS-MIB::xupsBypassFrequency.0
$value = $entry['xupsBypassFrequency'];
if ($value != 0) {
    discover_sensor('frequency', $device, $oid, "xupsBypassFrequency.0", 'xups', "Bypass", $scale, $value);
}

// xupsInputSource
$descr    = 'Input Source';
$oid_name = 'xupsInputSource';
$oid_num  = '.1.3.6.1.4.1.534.1.3.5.0';
$type     = 'xupsInputSource';
$value    = $entry[$oid_name];

discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);

// xupsOutputSource
$descr    = 'Output Source';
$oid_name = 'xupsOutputSource';
$oid_num  = '.1.3.6.1.4.1.534.1.4.5.0';
$type     = 'xupsOutputSource';
$value    = $entry[$oid_name];

discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);

// XUPS-MIB::xupsBatTimeRemaining.0 = INTEGER: 31500 seconds
// XUPS-MIB::xupsBatVoltage.0 = INTEGER: 104 Volts DC
// XUPS-MIB::xupsBatCapacity.0 = INTEGER: 100 percent
// XUPS-MIB::xupsBatteryAbmStatus.0 = INTEGER: batteryResting(4)
// XUPS-MIB::xupsBatteryLastReplacedDate.0 = STRING: 06/01/2020
// XUPS-MIB::xupsBatteryFailure.0 = INTEGER: no(2)
// XUPS-MIB::xupsBatteryNotPresent.0 = INTEGER: no(2)
// XUPS-MIB::xupsBatteryAged.0 = INTEGER: no(2)
// XUPS-MIB::xupsBatteryLowCapacity.0 = INTEGER: no(2)

// XUPS-MIB::xupsEnvAmbientTemp.0 = INTEGER: 29 degrees Centigrade
// XUPS-MIB::xupsEnvAmbientLowerLimit.0 = INTEGER: 0 degrees Centigrade
// XUPS-MIB::xupsEnvAmbientUpperLimit.0 = INTEGER: 40 degrees Centigrade
// XUPS-MIB::xupsEnvNumContacts.0 = INTEGER: 0

$xups_array = [];
$xups_array = snmpwalk_cache_oid($device, "xupsBattery", $xups_array, "XUPS-MIB");
$xups_array = snmpwalk_cache_oid($device, "xupsEnvironment", $xups_array, "XUPS-MIB");

$entry = $xups_array[0];

if ($entry['xupsBatteryNotPresent'] !== 'yes') {
    if (isset($entry['xupsBatTimeRemaining']) &&
        !isset($valid['sensor']['runtime']['mge']['upsmgBatteryRemainingTime.0'])) {
        $oid   = ".1.3.6.1.4.1.534.1.2.1.0"; # XUPS-MIB::xupsBatTimeRemaining.0
        $scale = 1 / 60;
        $value = $entry['xupsBatTimeRemaining'];

        discover_sensor('runtime', $device, $oid, "xupsBatTimeRemaining.0", 'xups', "Battery Runtime Remaining", $scale, $value);
    }

    if (isset($entry['xupsBatCapacity']) &&
        !isset($valid['sensor']['capacity']['mge']['upsmgBatteryLevel.0'])) {
        $oid   = ".1.3.6.1.4.1.534.1.2.4.0"; # XUPS-MIB::xupsBatCapacity.0
        $value = $entry['xupsBatCapacity'];

        discover_sensor('capacity', $device, $oid, "xupsBatCapacity.0", 'xups', "Battery Capacity", 1, $value);
    }

    if (isset($entry['xupsBatVoltage']) && $entry['xupsBatVoltage'] != 0 &&
        !isset($valid['sensor']['voltage']['mge']['upsmgBatteryVoltage.0'])) {
        $oid   = ".1.3.6.1.4.1.534.1.2.2.0"; # XUPS-MIB::xupsBatVoltage.0
        $value = $entry['xupsBatVoltage'];

        discover_sensor('voltage', $device, $oid, "xupsBatVoltage.0", 'xups', "Battery", 1, $value);
    }

    if (isset($entry['xupsBatCurrent']) &&
        !isset($valid['sensor']['current']['mge']['upsmgBatteryCurrent.0'])) {
        $oid   = ".1.3.6.1.4.1.534.1.2.3.0"; # XUPS-MIB::xupsBatCurrent.0
        $value = $entry['xupsBatCurrent'];

        discover_sensor('current', $device, $oid, "xupsBatCurrent.0", 'xups', "Battery", 1, $value);
    }

    // xupsBatteryAbmStatus
    $descr    = 'Battery Status';
    $oid_name = 'xupsBatteryAbmStatus';
    $oid_num  = '.1.3.6.1.4.1.534.1.2.5.0';
    $type     = 'xupsBatteryAbmStatus';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'battery']);

    // xupsBatteryFailure
    $descr    = 'Battery Failure';
    $oid_name = 'xupsBatteryFailure';
    $oid_num  = '.1.3.6.1.4.1.534.1.2.7.0';
    $type     = 'xupsBatteryFailure';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'battery']);

    // xupsBatteryAged
    $age      = $entry['xupsBatteryLastReplacedDate'];
    $descr    = "Battery over aged ($age)";
    $oid_name = 'xupsBatteryAged';
    $oid_num  = '.1.3.6.1.4.1.534.1.2.9.0';
    $type     = 'xupsBatteryWarning';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'battery']);

    // xupsBatteryLowCapacity
    $descr    = 'Battery Low Capacity';
    $oid_name = 'xupsBatteryLowCapacity';
    $oid_num  = '.1.3.6.1.4.1.534.1.2.10.0';
    $type     = 'xupsBatteryFailure';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'battery']);
}

// xupsBatteryNotPresent
$descr    = 'Battery Not Present';
$oid_name = 'xupsBatteryNotPresent';
$oid_num  = '.1.3.6.1.4.1.534.1.2.8.0';
$type     = 'xupsBatteryNotPresent';
$value    = $entry[$oid_name];

discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'battery']);

if (isset($entry['xupsEnvAmbientTemp'])) {
    $oid   = ".1.3.6.1.4.1.534.1.6.1.0"; # XUPS-MIB:xupsEnvAmbientTemp.0
    $value = $entry['xupsEnvAmbientTemp'];

    $limits = ['limit_low'  => $xups_array[0]['upsEnvAmbientLowerLimit'],
               'limit_high' => $xups_array[0]['upsEnvAmbientUpperLimit']];

    if ($value != 0) {
        discover_sensor('temperature', $device, $oid, "xupsEnvAmbientTemp.0", 'xups', "Ambient", 1, $value, $limits);
    }
}

unset($xups_array, $xups_base);

// EOF
