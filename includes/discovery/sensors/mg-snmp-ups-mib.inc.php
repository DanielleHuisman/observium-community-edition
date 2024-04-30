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

$oids = snmpwalk_cache_oid($device, "upsmgInputPhaseTable", [], "MG-SNMP-UPS-MIB");

// Input
$numPhase = snmp_get_oid($device, "upsmgInputPhaseNum.0", "MG-SNMP-UPS-MIB");

// Great job MGE - my devices don't have mginputPhaseIndex, and mginputMinimumVoltage and mginputMaximumVoltage. are using different indexes.
if (safe_count(array_keys($oids)) > $numPhase) {
    unset($oids[0]);
}                       // Remove [0] key with above 2 fields, leaving 1.0 etc for actual phases.
$scale = 0.1;
foreach ($oids as $index => $entry) {
    $phase = explode('.', $index, 2)[0];

    if ($phase > $numPhase) {
        break;
    } // MGE returns 3 phase values even if their mgInputPhaseNum is 1. Doh.

    $descr = "Input";
    $options = [];
    if ($numPhase > 1) {
        $descr .= " Phase $phase";

        $options  = [
            'measured_entity_label' => "Input Phase $phase",
            'measured_class' => 'phase'
        ];
    }

    if (is_numeric($entry['mginputVoltage'])) {
        $oid   = ".1.3.6.1.4.1.705.1.6.2.1.2.$index"; // MG-SNMP-UPS-MIB:mginputVoltage.$index
        $value = $entry['mginputVoltage'];
        if ($value != 0) {
            // FIXME. Incorrect indexes!
            discover_sensor('voltage', $device, $oid, 100 + $index, 'mge-ups', $descr, $scale, $value, $options);
        }
    }

    if (is_numeric($entry['mginputCurrent'])) {
        $oid   = ".1.3.6.1.4.1.705.1.6.2.1.6.$index"; // MG-SNMP-UPS-MIB:mginputCurrent.$index
        $value = $entry['mginputCurrent'];
        if ($value != 0) {
            // FIXME. Incorrect indexes!
            discover_sensor('current', $device, $oid, 100 + $index, 'mge-ups', $descr, $scale, $value, $options);
        }
    }

    if (is_numeric($entry['mginputFrequency'])) {
        $oid   = ".1.3.6.1.4.1.705.1.6.2.1.3.$index"; // MG-SNMP-UPS-MIB:mginputFrequency.$index
        $value = $entry['mginputFrequency'];
        if ($value != 0) {
            // FIXME. Incorrect indexes!
            discover_sensor('frequency', $device, $oid, 100 + $index, 'mge-ups', $descr, $scale, $value, $options);
        }
    }
}

// Output

$oids  = snmpwalk_cache_oid($device, "upsmgOutput", [], "MG-SNMP-UPS-MIB");
$index = 0;
$entry = $oids[$index];

// MG-SNMP-UPS-MIB::upsmgOutputOnBattery.0 = INTEGER: no(2)
if (isset($entry['upsmgOutputOnBattery'])) {
    $descr    = 'Output On Battery';
    $oid_name = 'upsmgOutputOnBattery';
    $oid_num  = '.1.3.6.1.4.1.705.1.7.3.0';
    $type     = 'mge-status-state';
    $value    = $entry[$oid_name];

    //rename_rrd($device, "status-{$type}-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'battery']);
}

// MG-SNMP-UPS-MIB::upsmgOutputOnByPass.0 = INTEGER: no(2)
if (isset($entry['upsmgOutputOnByPass'])) {
    $descr    = 'Output On Bypass';
    $oid_name = 'upsmgOutputOnByPass';
    $oid_num  = '.1.3.6.1.4.1.705.1.7.4.0';
    $type     = 'mge-status-state';
    $value    = $entry[$oid_name];

    //rename_rrd($device, "status-{$type}-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'battery']);
}

// FIXME TODO: State sensors:
// MG-SNMP-UPS-MIB::upsmgOutputUnavailableByPass.0 = INTEGER: no(2)
if (isset($entry['upsmgOutputUnavailableByPass'])) {
    $descr    = 'Output Unavailable Bypass';
    $oid_name = 'upsmgOutputUnavailableByPass';
    $oid_num  = '.1.3.6.1.4.1.705.1.7.5.0';
    $type     = 'mge-status-state';
    $value    = $entry[$oid_name];

    //rename_rrd($device, "status-{$type}-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

// MG-SNMP-UPS-MIB::upsmgOutputNoByPass.0 = INTEGER: no(2)
if (isset($entry['upsmgOutputNoByPass'])) {
    $descr    = 'Output No Bypass';
    $oid_name = 'upsmgOutputNoByPass';
    $oid_num  = '.1.3.6.1.4.1.705.1.7.6.0';
    $type     = 'mge-status-state';
    $value    = $entry[$oid_name];

    //rename_rrd($device, "status-{$type}-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

// MG-SNMP-UPS-MIB::upsmgOutputUtilityOff.0 = INTEGER: no(2)
if (isset($entry['upsmgOutputUtilityOff'])) {
    $descr    = 'Output Utility Off';
    $oid_name = 'upsmgOutputUtilityOff';
    $oid_num  = '.1.3.6.1.4.1.705.1.7.7.0';
    $type     = 'mge-status-state';
    $value    = $entry[$oid_name];

    //rename_rrd($device, "status-{$type}-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

// MG-SNMP-UPS-MIB::upsmgOutputOnBoost.0 = INTEGER: no(2)
if (isset($entry['upsmgOutputOnBoost'])) {
    $descr    = 'Output On Boost';
    $oid_name = 'upsmgOutputOnBoost';
    $oid_num  = '.1.3.6.1.4.1.705.1.7.8.0';
    $type     = 'mge-status-state';
    $value    = $entry[$oid_name];

    //rename_rrd($device, "status-{$type}-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

// MG-SNMP-UPS-MIB::upsmgOutputInverterOff.0 = INTEGER: no(2)
if (isset($entry['upsmgOutputInverterOff'])) {
    $family_name = strtolower(snmp_get_oid($device, 'upsmgIdentFamilyName.0', 'MG-SNMP-UPS-MIB'));
    $family_name = str_replace('eaton ', '', $family_name);

    $descr    = "Output Inverter Off";
    $oid_name = 'upsmgOutputInverterOff';
    $oid_num  = '.1.3.6.1.4.1.705.1.7.9.0';
    $value    = $entry[$oid_name];

    // FIXME - find a better way to do this. Currently it seems all things starting with 8 and "ex" are online.
    if (in_array($family_name, ['ex', '9sx', '9135']) || $family_name[0] == 9) // Known Online UPSes
    {
        $type = 'mge-status-state';
        // This rename for old wrong indexes
        //rename_rrd($device, "status-{$type}-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
        // This rename for changed family name
        rename_rrd($device, "status-mge-status-inverter-{$oid_name}.{$index}", "status-{$type}-{$oid_name}.{$index}");
    } else {
        $type = 'mge-status-inverter';
        //rename_rrd($device, "status-mge-status-state-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
    }

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

// MG-SNMP-UPS-MIB::upsmgOutputOverLoad.0 = INTEGER: no(2)
if (isset($entry['upsmgOutputOverLoad'])) {
    $descr    = 'Output Over Load';
    $oid_name = 'upsmgOutputOverLoad';
    $oid_num  = '.1.3.6.1.4.1.705.1.7.10.0';
    $type     = 'mge-status-state';
    $value    = $entry[$oid_name];

    //rename_rrd($device, "status-{$type}-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

// MG-SNMP-UPS-MIB::upsmgOutputOverTemp.0 = INTEGER: no(2)
if (isset($entry['upsmgOutputOverTemp'])) {
    $descr    = 'Output Over Temperature';
    $oid_name = 'upsmgOutputOverTemp';
    $oid_num  = '.1.3.6.1.4.1.705.1.7.11.0';
    $type     = 'mge-status-state';
    $value    = $entry[$oid_name];

    //rename_rrd($device, "status-{$type}-{$oid_name}.{$index_rename}", "status-{$type}-{$oid_name}.{$index}");
    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
}

// MG-SNMP-UPS-MIB::upsmgOutputOnBuck.0 = INTEGER: 2

$scale        = 0.1;
$oids = snmpwalk_cache_oid($device, "upsmgOutputPhaseTable", [], "MG-SNMP-UPS-MIB");

$upsmgOutputPhaseNum = snmp_get_oid($device, "upsmgOutputPhaseNum.0", "MG-SNMP-UPS-MIB");

foreach ($oids as $index => $entry) {
    $descr = "Output";
    $options = [];
    if ($upsmgOutputPhaseNum > 1) {
        $descr .= " Phase $index";

        $options  = [
            'measured_entity_label' => "Output Phase $index",
            'measured_class' => 'phase'
        ];
    }

    if ($index > $upsmgOutputPhaseNum) {
        break;
    } // MGE returns 3 phase values even if their mgOutputPhaseNum is 1. Doh.

    $limits = [ 'limit_high' => 85, 'limit_high_warn' => 70 ];
    $oid   = ".1.3.6.1.4.1.705.1.7.2.1.4.$index"; // MG-SNMP-UPS-MIB:mgoutputLoadPerPhase.$index
    $value = $entry['mgoutputLoadPerPhase'];
    discover_sensor('load', $device, $oid, "mgoutputLoadPerPhase.$index", 'mge-ups', $descr . ' Load', 1, $value, array_merge($options, $limits));

    $oid   = ".1.3.6.1.4.1.705.1.7.2.1.2.$index"; // MG-SNMP-UPS-MIB:mgoutputVoltage.$index
    $value = $entry['mgoutputVoltage'];
    if ($value != 0) {
        discover_sensor('voltage', $device, $oid, $index, 'mge-ups', $descr, $scale, $value, $options);
    }

    $oid   = ".1.3.6.1.4.1.705.1.7.2.1.5.$index"; // MG-SNMP-UPS-MIB:mgoutputCurrent.$index
    $value = $entry['mgoutputCurrent'];
    if ($value != 0) {
        discover_sensor('current', $device, $oid, $index, 'mge-ups', $descr, $scale, $value, $options);
    }

    $oid   = ".1.3.6.1.4.1.705.1.7.2.1.3.$index"; // MG-SNMP-UPS-MIB:mgoutputFrequency.$index
    $value = $entry['mgoutputFrequency'];
    if ($value != 0) {
        discover_sensor('frequency', $device, $oid, $index, 'mge-ups', $descr, $scale, $value, $options);
    }
}

echo(" ");

// Battery data

$oids = snmpwalk_cache_oid($device, 'upsmgBattery', [], "MG-SNMP-UPS-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

foreach ($oids as $index => $entry) {
    $descr = "Battery";

    // MG-SNMP-UPS-MIB::upsmgBatteryVoltage.0 = 810
    if (isset($entry['upsmgBatteryVoltage'])) {
        $oid   = ".1.3.6.1.4.1.705.1.5.5.$index";
        $value = $entry['upsmgBatteryVoltage'];

        if ($value != 0) {
            discover_sensor('voltage', $device, $oid, "upsmgBatteryVoltage.$index", 'mge', $descr, $scale, $value);
        }
    }

    // MG-SNMP-UPS-MIB::upsmgBatteryCurrent.0 = 0
    if (isset($entry['upsmgBatteryCurrent'])) {
        $oid   = ".1.3.6.1.4.1.705.1.5.6.$index";
        $value = $entry['upsmgBatteryCurrent'];

        if ($value != 0) {
            discover_sensor('current', $device, $oid, "upsmgBatteryCurrent.$index", 'mge', $descr, $scale, $value);
        }
    }

    // MG-SNMP-UPS-MIB::upsmgBatteryTemperature.0 = INTEGER: 15
    if (isset($entry['upsmgBatteryTemperature'])) {
        $oid   = ".1.3.6.1.4.1.705.1.5.7.$index";
        $value = $entry['upsmgBatteryTemperature'];

        if ($value != 0) {
            discover_sensor('temperature', $device, $oid, "upsmgBatteryTemperature.$index", 'mge', $descr, 1, $value);
        }
    }

    // MG-SNMP-UPS-MIB::upsmgBatteryLevel.0 = INTEGER: 100
    if (isset($entry['upsmgBatteryLevel'])) {
        $oid    = ".1.3.6.1.4.1.705.1.5.2.$index";
        $limits = ['limit_low' => snmp_get_oid($device, "upsmgConfigLowBatteryLevel.0", "MG-SNMP-UPS-MIB")];
        $value  = $entry['upsmgBatteryLevel'];

        discover_sensor('capacity', $device, $oid, "upsmgBatteryLevel.$index", 'mge', $descr . ' Capacity', 1, $value, $limits);
    }

    // MG-SNMP-UPS-MIB::upsmgBatteryRemainingTime.0 = INTEGER: 12180
    if (isset($entry['upsmgBatteryRemainingTime'])) {
        $descr  = "Battery Runtime Remaining";
        $oid    = ".1.3.6.1.4.1.705.1.5.1.$index";
        $limits = ['limit_low' => snmp_get_oid($device, "upsmgConfigLowBatteryTime.0", "MG-SNMP-UPS-MIB")];
        $value  = $entry['upsmgBatteryRemainingTime'];
        $scale  = 1 / 60;

        // FIXME: Use this as limit?
        // MG-SNMP-UPS-MIB::upsmgConfigLowBatteryTime.0 = 180

        discover_sensor('runtime', $device, $oid, "upsmgBatteryRemainingTime.$index", 'mge', $descr, $scale, $value);
    }

    // MG-SNMP-UPS-MIB::upsmgBatteryFaultBattery.0 = no
    if (isset($entry['upsmgBatteryFaultBattery'])) {
        $descr = "Battery Fault";
        $oid   = ".1.3.6.1.4.1.705.1.5.9.$index";
        $value = $entry['upsmgBatteryFaultBattery'];
        discover_status($device, $oid, "upsmgBatteryFaultBattery.$index", 'mge-status-state', $descr, $value, ['entPhysicalClass' => 'battery']);
    }

    // MG-SNMP-UPS-MIB::upsmgBatteryChargerFault.0 = no
    if (isset($entry['upsmgBatteryChargerFault'])) {
        $descr = "Charger Fault";
        $oid   = ".1.3.6.1.4.1.705.1.5.15.$index";
        $value = $entry['upsmgBatteryChargerFault'];
        discover_status($device, $oid, "upsmgBatteryChargerFault.$index", 'mge-status-state', $descr, $value, ['entPhysicalClass' => 'battery']);
    }

    // MG-SNMP-UPS-MIB::upsmgBatteryLowBattery.0 = no
    // According to MGE, LowCondition is the correct indicator, so we ignore LowBattery.
    // MG-SNMP-UPS-MIB::upsmgBatteryLowCondition.0 = no
    if (isset($entry['upsmgBatteryLowCondition'])) {
        $descr = "Battery Low";
        $oid   = ".1.3.6.1.4.1.705.1.5.16.$index";
        $value = $entry['upsmgBatteryLowCondition'];
        discover_status($device, $oid, "upsmgBatteryLowCondition.$index", 'mge-status-state', $descr, $value, ['entPhysicalClass' => 'battery']);
    }

    // MG-SNMP-UPS-MIB::upsmgBatteryReplacement.0 = no
    if (isset($entry['upsmgBatteryReplacement'])) {
        $descr = "Battery Replacement Needed";
        $oid   = ".1.3.6.1.4.1.705.1.5.11.$index";
        $value = $entry['upsmgBatteryReplacement'];
        discover_status($device, $oid, "upsmgBatteryReplacement.$index", 'mge-status-state', $descr, $value, ['entPhysicalClass' => 'battery']);
    }

}

echo(" ");

// Environmental monitoring

$oids = snmpwalk_cache_oid($device, 'upsmgEnviron', [], "MG-SNMP-UPS-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

// MG-SNMP-UPS-MIB::upsmgEnvironAmbientTemp.0 = INTEGER: 0
// MG-SNMP-UPS-MIB::upsmgEnvironAmbientHumidity.0 = INTEGER: 0
$scale = 0.1;
foreach ($oids as $index => $entry) {
    $descr = "Ambient";

    $oid   = ".1.3.6.1.4.1.705.1.8.1.$index";
    $value = $entry['upsmgEnvironAmbientTemp'];

    if ($value != 0) { // Temp = 0 -> Sensor not available
        discover_sensor('temperature', $device, $oid, "upsmgEnvironAmbientTemp.$index", 'mge', $descr, $scale, $value);
    }

    $oid   = ".1.3.6.1.4.1.705.1.8.2.$index";
    $value = $entry['upsmgEnvironAmbientHumidity'];

    if ($value != 0) { // Humidity = 0 -> Sensor not available
        // Should be /10 on all devices but apparently not on all, let's try to work around:
        if ($value > 100) {
            $scale = 0.1;
        } else {
            $scale = 1;
        }
        discover_sensor('humidity', $device, $oid, "upsmgEnvironAmbientHumidity.$index", 'mge', $descr, $scale, $value);
    }
}

$oids = snmpwalk_cache_oid($device, 'upsmgConfigEnvironmentTable',    [], "MG-SNMP-UPS-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
$oids = snmpwalk_cache_oid($device, 'upsmgEnvironmentSensorTable', $oids, "MG-SNMP-UPS-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

// upsmgConfigSensorIndex.1 = 1
// upsmgConfigSensorName.1 = "Environment sensor"
// upsmgConfigTemperatureLow.1 = 5
// upsmgConfigTemperatureHigh.1 = 40
// upsmgConfigTemperatureHysteresis.1 = 2
// upsmgConfigHumidityLow.1 = 5
// upsmgConfigHumidityHigh.1 = 90
// upsmgConfigHumidityHysteresis.1 = 5
// upsmgConfigInput1Name.1 = "Input //1"
// upsmgConfigInput1ClosedLabel.1 = "closed"
// upsmgConfigInput1OpenLabel.1 = "open"
// upsmgConfigInput2Name.1 = "Input //2"
// upsmgConfigInput2ClosedLabel.1 = "closed"
// upsmgConfigInput2OpenLabel.1 = "open"
//
// upsmgEnvironmentIndex.1 = 1
// upsmgEnvironmentComFailure.1 = no
// upsmgEnvironmentTemperature.1 = 287
// upsmgEnvironmentTemperatureLow.1 = no
// upsmgEnvironmentTemperatureHigh.1 = no
// upsmgEnvironmentHumidity.1 = 17
// upsmgEnvironmentHumidityLow.1 = no
// upsmgEnvironmentHumidityHigh.1 = no
// upsmgEnvironmentInput1State.1 = open
// upsmgEnvironmentInput2State.1 = open

foreach ($oids as $index => $entry) {
    if ($entry['upsmgEnvironmentComFailure'] !== 'no') { // yes means no environment module present
        continue;
    }

    $descr = $entry['upsmgConfigSensorName'];

    $oid   = ".1.3.6.1.4.1.705.1.8.7.1.6.$index";
    $value = $entry['upsmgEnvironmentHumidity'];
    // FIXME warninglevels might need some other calculation instead of hysteresis
    $hysteresis = $entry['upsmgConfigHumidityHysteresis'];
    $limits     = ['limit_high'      => $entry['upsmgConfigHumidityHigh'],
                   'limit_low'       => $entry['upsmgConfigHumidityLow'],
                   'limit_high_warn' => $entry['upsmgConfigHumidityHigh'] - $hysteresis,
                   'limit_low_warn'  => $entry['upsmgConfigHumidityLow'] + $hysteresis];

    if ($value != 0) { // Humidity = 0 -> Sensor not available
        // Should be /10 on all devices but apparently not on all, let's try to work around:
        if ($value > 100) {
            $scale = 0.1;
        } else {
            $scale = 1;
        }
        discover_sensor('humidity', $device, $oid, $index, 'mge', $descr, $scale, $value, $limits);
    }

    $scale = 0.1;
    $oid   = '.1.3.6.1.4.1.705.1.8.7.1.3.' . $index;
    $value = $entry['upsmgEnvironmentTemperature'];
    // FIXME warninglevels might need some other calculation instead of hysteresis
    $hysteresis = $entry['upsmgConfigTemperatureHysteresis'];
    $limits     = ['limit_high'      => $entry['upsmgConfigTemperatureHigh'],
                   'limit_low'       => $entry['upsmgConfigTemperatureLow'],
                   'limit_high_warn' => $entry['upsmgConfigTemperatureHigh'] - $hysteresis,
                   'limit_low_warn'  => $entry['upsmgConfigTemperatureLow'] + $hysteresis];

    if ($value != 0) {
        discover_sensor('temperature', $device, $oid, $index, 'mge', $descr, $scale, $value, $limits);
    }
}

// EOF
