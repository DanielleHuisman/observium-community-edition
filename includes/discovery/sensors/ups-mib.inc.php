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

echo("Caching OIDs: ");

$scale         = 0.1;
$scale_current = 0.1;
$scale_battery = 0.1;
// hardware specific hacks
if ($device['os'] === 'poweralert') {
    // For poweralert use "incorrect" scale, see: http://jira.observium.org/browse/OBSERVIUM-1432
    // Fixed in firmware version 12.06.0068
    if (!empty($device['version'])) {
        $tl_version = $device['version'];
    } else {
        $tl_version = snmp_get_oid($device, '.1.3.6.1.4.1.850.10.1.2.3.0', 'TRIPPLITE-12X');
    }
    if (!version_compare($tl_version, '12.06.0068', '>=')) {
        // incorrect
        $scale_current = 1;
    }
} elseif ($device['os'] === 'snr-erd') {
    $scale_battery = 1;
}

$invalid_values = ['4294967295', '2147483647'];

/* SNR ERD-4
UPS-MIB::upsIdentManufacturer.0 = STRING: APC
UPS-MIB::upsIdentModel.0 = STRING: Smart-UPS
UPS-MIB::upsIdentUPSSoftwareVersion.0 = STRING: 665.6.I
UPS-MIB::upsBatteryStatus.0 = INTEGER: batteryNormal(2)
UPS-MIB::upsSecondsOnBattery.0 = INTEGER: 0 seconds
UPS-MIB::upsEstimatedMinutesRemaining.0 = INTEGER: 15300 minutes
UPS-MIB::upsEstimatedChargeRemaining.0 = INTEGER: 100 percent
UPS-MIB::upsBatteryVoltage.0 = INTEGER: 55 0.1 Volt DC
UPS-MIB::upsBatteryTemperature.0 = INTEGER: 24 degrees Centigrade
UPS-MIB::upsInputLineIndex.1 = INTEGER: 1
UPS-MIB::upsInputFrequency.1 = INTEGER: 50 0.1 Hertz
UPS-MIB::upsInputVoltage.1 = INTEGER: 237 RMS Volts
UPS-MIB::upsOutputSource.0 = INTEGER: normal(3)
UPS-MIB::upsOutputLineIndex.1 = INTEGER: 1
UPS-MIB::upsOutputVoltage.1 = INTEGER: 237 RMS Volts
UPS-MIB::upsOutputCurrent.1 = INTEGER: 0 0.1 RMS Amp
UPS-MIB::upsOutputPower.1 = INTEGER: 0 Watts
UPS-MIB::upsOutputPercentLoad.1 = INTEGER: 8 percent
UPS-MIB::upsTestId.0 = OID: UPS-MIB::upsTestNoTestsInitiated
UPS-MIB::upsTestElapsedTime.0 = INTEGER: 0
 */
/* Microtik RouterOS
UPS-MIB::upsIdentModel.0 = STRING: Back-UPS ES 550G FW:870.O3 .I USB FW:O3
UPS-MIB::upsIdentUPSSoftwareVersion.0 = STRING:
UPS-MIB::upsBatteryStatus.0 = INTEGER: batteryNormal(2)
UPS-MIB::upsEstimatedMinutesRemaining.0 = INTEGER: 60 minutes
UPS-MIB::upsEstimatedChargeRemaining.0 = INTEGER: 100 percent
UPS-MIB::upsBatteryVoltage.0 = INTEGER: 135 0.1 Volt DC
UPS-MIB::upsBatteryTemperature.0 = INTEGER: 0 degrees Centigrade
UPS-MIB::upsInputNumLines.0 = INTEGER: 1
UPS-MIB::upsInputFrequency.1 = INTEGER: 0 0.1 Hertz
UPS-MIB::upsInputVoltage.1 = INTEGER: 220 RMS Volts
UPS-MIB::upsOutputNumLines.0 = INTEGER: 1
UPS-MIB::upsOutputVoltage.1 = INTEGER: 0 RMS Volts
UPS-MIB::upsOutputPercentLoad.1 = INTEGER: 4 percent
UPS-MIB::upsAlarmsPresent.0 = Gauge32: 0
 */
/* Multiphase UPS
UPS-MIB::upsIdentManufacturer.0 = STRING: RPS SpA
UPS-MIB::upsIdentModel.0 = STRING: TT5K100
UPS-MIB::upsIdentUPSSoftwareVersion.0 = STRING: SWM022-02-21
UPS-MIB::upsIdentAgentSoftwareVersion.0 = STRING: AppVer. 02.17.001
UPS-MIB::upsIdentName.0 = STRING: Netman204
UPS-MIB::upsIdentAttachedDevices.0 = STRING:
UPS-MIB::upsBatteryStatus.0 = INTEGER: batteryNormal(2)
UPS-MIB::upsSecondsOnBattery.0 = INTEGER: 0 seconds
UPS-MIB::upsEstimatedMinutesRemaining.0 = INTEGER: 54 minutes
UPS-MIB::upsEstimatedChargeRemaining.0 = INTEGER: 100 percent
UPS-MIB::upsBatteryVoltage.0 = INTEGER: 2726 0.1 Volt DC
UPS-MIB::upsBatteryCurrent.0 = INTEGER: 30 0.1 Amp DC
UPS-MIB::upsBatteryTemperature.0 = INTEGER: 37 degrees Centigrade
UPS-MIB::upsInputLineBads.0 = Counter32: 0
UPS-MIB::upsInputNumLines.0 = INTEGER: 3
UPS-MIB::upsInputFrequency.1 = INTEGER: 499 0.1 Hertz
UPS-MIB::upsInputFrequency.2 = INTEGER: 499 0.1 Hertz
UPS-MIB::upsInputFrequency.3 = INTEGER: 499 0.1 Hertz
UPS-MIB::upsInputVoltage.1 = INTEGER: 229 RMS Volts
UPS-MIB::upsInputVoltage.2 = INTEGER: 228 RMS Volts
UPS-MIB::upsInputVoltage.3 = INTEGER: 229 RMS Volts
UPS-MIB::upsInputCurrent.1 = INTEGER: 245 0.1 RMS Amp
UPS-MIB::upsInputCurrent.2 = INTEGER: 257 0.1 RMS Amp
UPS-MIB::upsInputCurrent.3 = INTEGER: 254 0.1 RMS Amp
UPS-MIB::upsInputTruePower.1 = INTEGER: 0 Watts
UPS-MIB::upsInputTruePower.2 = INTEGER: 0 Watts
UPS-MIB::upsInputTruePower.3 = INTEGER: 0 Watts
UPS-MIB::upsOutputSource.0 = INTEGER: normal(3)
UPS-MIB::upsOutputFrequency.0 = INTEGER: 500 0.1 Hertz
UPS-MIB::upsOutputNumLines.0 = INTEGER: 3
UPS-MIB::upsOutputVoltage.1 = INTEGER: 231 RMS Volts
UPS-MIB::upsOutputVoltage.2 = INTEGER: 230 RMS Volts
UPS-MIB::upsOutputVoltage.3 = INTEGER: 230 RMS Volts
UPS-MIB::upsOutputCurrent.1 = INTEGER: 368 0.1 RMS Amp
UPS-MIB::upsOutputCurrent.2 = INTEGER: 200 0.1 RMS Amp
UPS-MIB::upsOutputCurrent.3 = INTEGER: 193 0.1 RMS Amp
UPS-MIB::upsOutputPower.1 = INTEGER: 6688 Watts
UPS-MIB::upsOutputPower.2 = INTEGER: 3944 Watts
UPS-MIB::upsOutputPower.3 = INTEGER: 4138 Watts
UPS-MIB::upsOutputPercentLoad.1 = INTEGER: 25 percent
UPS-MIB::upsOutputPercentLoad.2 = INTEGER: 13 percent
UPS-MIB::upsOutputPercentLoad.3 = INTEGER: 13 percent
UPS-MIB::upsBypassFrequency.0 = INTEGER: 499 0.1 Hertz
UPS-MIB::upsBypassNumLines.0 = INTEGER: 3
UPS-MIB::upsBypassVoltage.1 = INTEGER: 230 RMS Volts
UPS-MIB::upsBypassVoltage.2 = INTEGER: 228 RMS Volts
UPS-MIB::upsBypassVoltage.3 = INTEGER: 230 RMS Volts
UPS-MIB::upsBypassCurrent.1 = INTEGER: 0 0.1 RMS Amp
UPS-MIB::upsBypassCurrent.2 = INTEGER: 0 0.1 RMS Amp
UPS-MIB::upsBypassCurrent.3 = INTEGER: 0 0.1 RMS Amp
UPS-MIB::upsBypassPower.1 = INTEGER: 0 Watts
UPS-MIB::upsBypassPower.2 = INTEGER: 0 Watts
UPS-MIB::upsBypassPower.3 = INTEGER: 0 Watts
UPS-MIB::upsAlarmsPresent.0 = Gauge32: 0
UPS-MIB::upsTestId.0 = Wrong Type (should be OBJECT IDENTIFIER): Gauge32: 0
UPS-MIB::upsTestSpinLock.0 = INTEGER: 0
UPS-MIB::upsTestResultsSummary.0 = INTEGER: 0
UPS-MIB::upsTestResultsDetail.0 = STRING:
UPS-MIB::upsTestStartTime.0 = Timeticks: (0) 0:00:00.00
UPS-MIB::upsTestElapsedTime.0 = INTEGER: 0
UPS-MIB::upsShutdownType.0 = INTEGER: output(1)
UPS-MIB::upsShutdownAfterDelay.0 = INTEGER: -1 seconds
UPS-MIB::upsStartupAfterDelay.0 = INTEGER: -1 seconds
UPS-MIB::upsRebootWithDuration.0 = INTEGER: -1 seconds
UPS-MIB::upsAutoRestart.0 = INTEGER: 0
UPS-MIB::upsConfigInputVoltage.0 = INTEGER: 0 RMS Volts
UPS-MIB::upsConfigInputFreq.0 = INTEGER: 0 0.1 Hertz
UPS-MIB::upsConfigOutputVoltage.0 = INTEGER: 0 RMS Volts
UPS-MIB::upsConfigOutputFreq.0 = INTEGER: 0 0.1 Hertz
UPS-MIB::upsConfigOutputVA.0 = INTEGER: 0 Volt-Amps
UPS-MIB::upsConfigOutputPower.0 = INTEGER: 0 Watts
UPS-MIB::upsConfigLowBattTime.0 = INTEGER: 0 minutes
UPS-MIB::upsConfigAudibleStatus.0 = INTEGER: 0
UPS-MIB::upsConfigLowVoltageTransferPoint.0 = INTEGER: 0 RMS Volts
UPS-MIB::upsConfigHighVoltageTransferPoint.0 = INTEGER: 0 RMS Volts
 */

/* Huawei UPS
UPS-MIB::upsIdentManufacturer.0 = STRING: HUAWEI
UPS-MIB::upsIdentModel.0 = STRING: UPS2000 6kVA
UPS-MIB::upsIdentUPSSoftwareVersion.0 = STRING: V100R001C00SPC610
UPS-MIB::upsIdentAgentSoftwareVersion.0 = STRING: V100R002C02B150
UPS-MIB::upsIdentName.0 = STRING: ups2000
UPS-MIB::upsIdentAttachedDevices.0 = STRING: None
UPS-MIB::upsBatteryStatus.0 = Wrong Type (should be INTEGER): Gauge32: 2
UPS-MIB::upsSecondsOnBattery.0 = Wrong Type (should be INTEGER): Gauge32: 0
UPS-MIB::upsEstimatedMinutesRemaining.0 = Wrong Type (should be INTEGER): Gauge32: 4294967295
UPS-MIB::upsEstimatedChargeRemaining.0 = Wrong Type (should be INTEGER): Gauge32: 73
UPS-MIB::upsBatteryVoltage.0 = Wrong Type (should be INTEGER): Gauge32: 2708
UPS-MIB::upsBatteryCurrent.0 = INTEGER: 6 0.1 Amp DC
UPS-MIB::upsBatteryTemperature.0 = INTEGER: 2147483647 degrees Centigrade
UPS-MIB::upsInputLineBads.0 = Wrong Type (should be Counter32): INTEGER: 0
UPS-MIB::upsInputNumLines.0 = Wrong Type (should be INTEGER): Gauge32: 1
UPS-MIB::upsInputLineIndex.1 = INTEGER: 1
UPS-MIB::upsInputFrequency.1 = INTEGER: 500 0.1 Hertz
UPS-MIB::upsInputVoltage.1 = INTEGER: 227 RMS Volts
UPS-MIB::upsOutputSource.0 = INTEGER: normal(3)
UPS-MIB::upsOutputFrequency.0 = Wrong Type (should be INTEGER): Gauge32: 500
UPS-MIB::upsOutputNumLines.0 = Wrong Type (should be INTEGER): Gauge32: 1
UPS-MIB::upsOutputLineIndex.1 = INTEGER: 1
UPS-MIB::upsOutputVoltage.1 = INTEGER: 220 RMS Volts
UPS-MIB::upsOutputCurrent.1 = INTEGER: 0 0.1 RMS Amp
UPS-MIB::upsOutputPower.1 = INTEGER: 0 Watts
UPS-MIB::upsOutputPercentLoad.1 = INTEGER: 0 percent
UPS-MIB::upsBypassFrequency.0 = Wrong Type (should be INTEGER): Gauge32: 500
UPS-MIB::upsBypassNumLines.0 = Wrong Type (should be INTEGER): Gauge32: 1
UPS-MIB::upsBypassLineIndex.1 = INTEGER: 1
UPS-MIB::upsBypassVoltage.1 = INTEGER: 227 RMS Volts
UPS-MIB::upsAlarmsPresent.0 = Gauge32: 0
UPS-MIB::upsTestId.0 = Wrong Type (should be OBJECT IDENTIFIER): INTEGER: 0
UPS-MIB::upsTestSpinLock.0 = INTEGER: 0
UPS-MIB::upsTestResultsSummary.0 = INTEGER: 0
UPS-MIB::upsTestResultsDetail.0 = Wrong Type (should be OCTET STRING): INTEGER: 0
UPS-MIB::upsTestStartTime.0 = Wrong Type (should be Timeticks): INTEGER: 0
UPS-MIB::upsTestElapsedTime.0 = INTEGER: 0
UPS-MIB::upsShutdownType.0 = INTEGER: 0
UPS-MIB::upsShutdownAfterDelay.0 = INTEGER: 0 seconds
UPS-MIB::upsStartupAfterDelay.0 = INTEGER: 0 seconds
UPS-MIB::upsRebootWithDuration.0 = INTEGER: 0 seconds
UPS-MIB::upsAutoRestart.0 = INTEGER: 0
UPS-MIB::upsConfigInputVoltage.0 = INTEGER: 0 RMS Volts
UPS-MIB::upsConfigInputFreq.0 = INTEGER: 0 0.1 Hertz
UPS-MIB::upsConfigOutputVoltage.0 = INTEGER: 0 RMS Volts
UPS-MIB::upsConfigOutputFreq.0 = INTEGER: 0 0.1 Hertz
UPS-MIB::upsConfigOutputVA.0 = INTEGER: 0 Volt-Amps
UPS-MIB::upsConfigOutputPower.0 = INTEGER: 0 Watts
UPS-MIB::upsConfigLowBattTime.0 = INTEGER: 0 minutes
UPS-MIB::upsConfigAudibleStatus.0 = INTEGER: 0
UPS-MIB::upsConfigLowVoltageTransferPoint.0 = INTEGER: 0 RMS Volts
UPS-MIB::upsConfigHighVoltageTransferPoint.0 = INTEGER: 0 RMS Volts
 */
$descr_extra = '';
if ($device['type'] !== 'power' &&
    $descr_extra = snmp_get_oid($device, 'upsIdentModel.0', 'UPS-MIB')) {
    //print_debug_vars($descr_extra);
    $descr_extra = preg_replace('/ FW:.+$/', '', $descr_extra);
    if ($manufacturer = snmp_get_oid($device, 'upsIdentManufacturer.0', 'UPS-MIB')) {
        $descr_extra = "$manufacturer $descr_extra";
    }
    //print_debug_vars($descr_extra);
    $descr_extra = " ($descr_extra)";
}

// Check if total Input Current and Power more than 0
$ups_total = [
  'upsInputCurrent'   => 0,
  'upsInputTruePower' => 0,
  'upsOutputCurrent'  => 0,
  'upsOutputPower'    => 0,
  'upsBypassCurrent'  => 0,
  'upsBypassPower'    => 0,
];

/* Input */
echo("upsInput ");
$ups_array = snmpwalk_cache_oid($device, "upsInputTable", [], "UPS-MIB");
print_debug_vars($ups_array);

if (safe_count($ups_array)) {
    $ups_lines = snmp_get_oid($device, 'upsInputNumLines.0', 'UPS-MIB');

    // Check if total Input Current and Power more than 0
    foreach ($ups_array as $entry) {
        if (isset($entry['upsInputCurrent'])) {
            $ups_total['upsInputCurrent'] += (int)$entry['upsInputCurrent'];
        }
        if (isset($entry['upsInputTruePower'])) {
            $ups_total['upsInputTruePower'] += (int)$entry['upsInputTruePower'];
        }
    }

    foreach ($ups_array as $index => $entry) {

        $phase = $entry['upsInputLineIndex'];
        // Workaround if no upsInputLineIndex
        /*
         * UPS-MIB::upsInputLineBads.0 = 1
         * UPS-MIB::upsInputNumLines.0 = 1
         * UPS-MIB::upsInputLineIndex.1.0 = 1
         * UPS-MIB::upsInputFrequency.1.0 = 500
         * UPS-MIB::upsInputVoltage.1.0 = 215
         */
        if (safe_empty($phase)) {
            // some devices have incorrect indexes (with additional .0), see:
            // http://jira.observium.org/browse/OBSERVIUM-2157
            $phase = explode('.', $index)[0];
        }

        $descr = "Input";
        $options = [];
        if ($ups_lines > 1) {
            $descr .= " Phase $phase";

            $options  = [
                'measured_entity_label' => "Input Phase $phase",
                'measured_class' => 'phase'
            ];
        }
        $descr .= $descr_extra;

        ## Input voltage
        // FIXME maybe use upsConfigLowVoltageTransferPoint and upsConfigHighVoltageTransferPoint as limits? (upsConfig table)
        // Again poweralert report incorrect values in UPS-MIB
        $oid_name = 'upsInputVoltage';
        if (isset($entry[$oid_name]) &&
            !discovery_check_if_type_exist([ 'voltage->TRIPPLITE-PRODUCTS-tlpUpsInputPhaseVoltage',
                                             'voltage->HUAWEI-UPS-MIB-hwUpsInputVoltageA',
                                             'voltage->CPS-MIB-upsAdvanceInputLineVoltage' ], 'sensor')) {
            $oid = ".1.3.6.1.2.1.33.1.3.3.1.3.$index";
            $limits = [];
            if ($limit = snmp_cache_oid($device, "upsConfigInputVoltage.0", "UPS-MIB")) {
                $limits['limit_high'] = sensor_limit_high('voltage', $limit);
                $limits['limit_low']  = sensor_limit_low('voltage', $limit);
            }

            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $entry[$oid_name], array_merge($options, $limits));
        }

        ## Input frequency
        $oid_name = 'upsInputFrequency';
        if (isset($entry[$oid_name]) && $entry[$oid_name] > 0 &&
            !discovery_check_if_type_exist(['frequency->HUAWEI-UPS-MIB-hwUpsInputFrequency'], 'sensor')) {
            $scale_frequency = strlen($entry[$oid_name]) === 2 ? 1 : $scale; // 50 (incorrect) vs 500
            $oid             = ".1.3.6.1.2.1.33.1.3.3.1.2.$index";
            $limits = [];
            if ($limit = snmp_cache_oid($device, "upsConfigInputFreq.0", "UPS-MIB")) {
                $limits['limit_high'] = sensor_limit_high('frequency', $limit * $scale_frequency);
                $limits['limit_low']  = sensor_limit_low('frequency', $limit * $scale_frequency);
            }

            discover_sensor_ng($device, 'frequency', $mib, $oid_name, $oid, $index, NULL, $descr, $scale_frequency, $entry[$oid_name], array_merge($options, $limits));
        }

        ## Input current
        $oid_name = 'upsInputCurrent';
        if (isset($entry[$oid_name]) && $ups_total[$oid_name] > 0 &&
            !discovery_check_if_type_exist(['current->HUAWEI-UPS-MIB-hwUpsInputCurrentA'], 'sensor')) {
            $oid = ".1.3.6.1.2.1.33.1.3.3.1.4.$index";

            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid, $index, NULL, $descr, $scale_current, $entry[$oid_name], $options);
        }

        ## Input power
        $oid_name = 'upsInputTruePower';
        if (isset($entry[$oid_name]) && $ups_total[$oid_name] > 0) {
            $oid = ".1.3.6.1.2.1.33.1.3.3.1.5.$index";
            //discover_sensor('power', $device, $oid, "upsInputEntry.".$phase, 'ups-mib', $descr, $scale, $entry[$oid_name]);

            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $entry[$oid_name], $options);
        }

    }
}

/* Output */
echo("upsOutput ");
$ups_array = snmpwalk_cache_oid($device, "upsOutputTable", [], "UPS-MIB");
print_debug_vars($ups_array);

if (safe_count($ups_array)) {
    $ups_lines = snmp_get_oid($device, 'upsOutputNumLines.0', 'UPS-MIB');

    // Check if total Input Current and Power more than 0
    foreach ($ups_array as $entry) {
        if (isset($entry['upsOutputCurrent'])) {
            $ups_total['upsOutputCurrent'] += (int)$entry['upsOutputCurrent'];
        }
        if (isset($entry['upsOutputPower'])) {
            $ups_total['upsOutputPower'] += (int)$entry['upsOutputPower'];
        }
    }

    foreach ($ups_array as $index => $entry) {
        $phase = $entry['upsOutputLineIndex'];
        // Workaround if no upsInputLineIndex
        /*
         * UPS-MIB::upsOutputSource.0 = normal
         * UPS-MIB::upsOutputNumLines.0 = 1
         * UPS-MIB::upsOutputLineIndex.1.0 = 1
         * UPS-MIB::upsOutputVoltage.1.0 = 230
         * UPS-MIB::upsOutputVoltage.2.0 = 0
         * UPS-MIB::upsOutputVoltage.3.0 = 0
         * UPS-MIB::upsOutputPower.1.0 = 2700
         * UPS-MIB::upsOutputPercentLoad.1.0 = 45
         */
        if (safe_empty($phase)) {
            // some devices have incorrect indexes (with additional .0), see:
            // http://jira.observium.org/browse/OBSERVIUM-2157
            $phase = explode('.', $index)[0];
        }

        $descr = "Output";
        $options = [];
        if ($ups_lines > 1) {
            $descr .= " Phase $phase";

            $options  = [
                'measured_entity_label' => "Output Phase $phase",
                'measured_class' => 'phase'
            ];
        }
        $descr .= $descr_extra;

        ## Output voltage
        $oid_name = 'upsOutputVoltage';
        if (isset($entry[$oid_name]) && $entry[$oid_name] > 0 &&
            !discovery_check_if_type_exist([ 'voltage->TRIPPLITE-PRODUCTS-tlpUpsOutputLineVoltage',
                                             'voltage->HUAWEI-UPS-MIB-hwUpsOutputVoltageA',
                                             'voltage->CPS-MIB-upsAdvanceOutputVoltage' ], 'sensor')) {
            $oid = ".1.3.6.1.2.1.33.1.4.4.1.2.$index";
            $limits = [];
            if ($limit = snmp_cache_oid($device, "upsConfigOutputVoltage.0", "UPS-MIB")) {
                $limits['limit_high'] = sensor_limit_high('voltage', $limit);
                $limits['limit_low']  = sensor_limit_low('voltage', $limit);
            }

            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $entry[$oid_name], array_merge($options, $limits));
        }

        ## Output current
        $oid_name = 'upsOutputCurrent';
        if (isset($entry[$oid_name]) && $ups_total[$oid_name] > 0 &&
            !discovery_check_if_type_exist(['current->HUAWEI-UPS-MIB-hwUpsOutputCurrentA',
                                            'current->CPS-MIB-upsAdvanceOutputCurrent'], 'sensor')) {
            $oid = ".1.3.6.1.2.1.33.1.4.4.1.3.$index";

            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid, $index, NULL, $descr, $scale_current, $entry[$oid_name], $options);
        }

        ## Output power
        $oid_name = 'upsOutputPower';
        if (isset($entry[$oid_name]) && $ups_total[$oid_name] > 0 &&
            !discovery_check_if_type_exist(['power->HUAWEI-UPS-MIB-hwUpsOutputActivePowerA',
                                            'power->CPS-MIB-upsAdvanceOutputPower'], 'sensor')) {
            $oid = ".1.3.6.1.2.1.33.1.4.4.1.4.$index";

            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $entry[$oid_name], $options);
        }

        $oid_name = 'upsOutputPercentLoad';
        if (isset($entry[$oid_name]) &&
            !discovery_check_if_type_exist('load->HUAWEI-UPS-MIB-hwUpsOutputLoadA', 'sensor')) {
            $oid = ".1.3.6.1.2.1.33.1.4.4.1.5.$index";

            discover_sensor_ng($device, 'load', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $entry[$oid_name], $options);
        }

    }
}

/* Bypass */
echo("upsBypass ");
$ups_array = snmpwalk_cache_oid($device, "upsBypassTable", [], "UPS-MIB");
print_debug_vars($ups_array);

if (safe_count($ups_array)) {
    $ups_lines = snmp_get_oid($device, 'upsBypassNumLines.0', 'UPS-MIB');

    // Check if total Input Current and Power more than 0
    foreach ($ups_array as $entry) {
        if (isset($entry['upsBypassCurrent'])) {
            $ups_total['upsBypassCurrent'] += (int)$entry['upsBypassCurrent'];
        }
        if (isset($entry['upsBypassPower'])) {
            $ups_total['upsBypassPower'] += (int)$entry['upsBypassPower'];
        }
    }

    foreach ($ups_array as $index => $entry) {

        $phase = $entry['upsBypassLineIndex'];
        // Workaround if no upsBypassLineIndex
        if (safe_empty($phase)) {
            // some devices have incorrect indexes (with additional .0), see:
            // http://jira.observium.org/browse/OBSERVIUM-2157
            $phase = explode('.', $index)[0];
        }

        $descr = "Bypass";
        $options = [];
        if ($ups_lines > 1) {
            $descr .= " Phase $phase";

            $options  = [
                'measured_entity_label' => "Bypass Phase $phase",
                'measured_class' => 'phase'
            ];
        }
        $descr .= $descr_extra;

        ## Bypass voltage
        $oid_name = 'upsBypassVoltage';
        if (isset($entry[$oid_name]) && $entry[$oid_name] > 0 &&
            !discovery_check_if_type_exist(['voltage->TRIPPLITE-PRODUCTS-tlpUpsBypassLineVoltage',
                                            'voltage->HUAWEI-UPS-MIB-hwUpsBypassInputVoltageA'], 'sensor')) {
            $oid = ".1.3.6.1.2.1.33.1.5.3.1.2.$index";

            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $entry[$oid_name], $options);
        }

        ## Bypass current
        $oid_name = 'upsBypassCurrent';
        if (isset($entry[$oid_name]) && $ups_total[$oid_name] > 0) {
            $oid = ".1.3.6.1.2.1.33.1.5.3.1.3.$index";

            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid, $index, NULL, $descr, $scale_current, $entry[$oid_name], $options);
        }

        ## Bypass power
        $oid_name = 'upsBypassPower';
        if (isset($entry[$oid_name]) && $ups_total[$oid_name] > 0) {
            $oid = ".1.3.6.1.2.1.33.1.5.3.1.4.$index";

            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $entry[$oid_name], $options);
        }

    }
}

/* Battery */
echo("upsBattery ");
$ups_array = snmpwalk_cache_oid($device, "upsBattery", [], "UPS-MIB");
if (isset($ups_array[0])) {
    $index = 0;
    $entry = $ups_array[0];
    $descr = "Battery" . $descr_extra;

    $oid_name = 'upsBatteryTemperature';
    if (isset($entry[$oid_name]) &&
        $entry[$oid_name] != 0 && $entry[$oid_name] < 1000) { // Battery won't be freezing, 0 means no sensor.
        $oid = ".1.3.6.1.2.1.33.1.2.7.0";

        //discover_sensor('temperature', $device, $oid, "upsBatteryTemperature", 'ups-mib', "Battery".$descr_extra, 1, $ups_array[0]['upsBatteryTemperature']);
        $options = ['rename_rrd' => 'ups-mib-upsBatteryTemperature'];
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $entry[$oid_name], $options);
    }

    $oid_name = 'upsBatteryCurrent';
    if (isset($entry[$oid_name]) &&
        $entry[$oid_name] >= 0 && $entry[$oid_name] < 10000) {
        $oid = ".1.3.6.1.2.1.33.1.2.6.0";

        //discover_sensor('current', $device, $oid, "upsBatteryCurrent", 'ups-mib', "Battery" . $descr_extra, $scale_current, $ups_array[0]['upsBatteryCurrent']);
        $options = ['rename_rrd' => 'ups-mib-upsBatteryCurrent', 'limit_auto' => FALSE];
        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid, $index, NULL, $descr, $scale_current, $entry[$oid_name], $options);
    }

    $oid_name = 'upsBatteryVoltage';
    if (isset($entry[$oid_name])) {
        $oid = ".1.3.6.1.2.1.33.1.2.5.0"; # UPS-MIB:upsBatteryVoltage.0

        //discover_sensor('voltage', $device, $oid, "upsBatteryVoltage", 'ups-mib', "Battery" . $descr_extra, $scale, $ups_array[0]['upsBatteryVoltage']);
        $options = ['rename_rrd' => 'ups-mib-upsBatteryVoltage'];
        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, $scale_battery, $entry[$oid_name], $options);
    }

    $oid_name = 'upsEstimatedMinutesRemaining';
    if (isset($entry[$oid_name]) && !in_array($entry[$oid_name], $invalid_values) &&
        !discovery_check_if_type_exist(['runtime->HUAWEI-UPS-MIB-hwUpsBatteryBackupTime'], 'sensor') &&
        !($entry[$oid_name] == 0 && isset($entry['upsEstimatedChargeRemaining']) && $entry['upsEstimatedChargeRemaining'] > 10)) {

        $descr   = "Battery Runtime Remaining" . $descr_extra;
        $oid     = ".1.3.6.1.2.1.33.1.2.3.0";
        $options = ['rename_rrd' => 'mge-upsEstimatedMinutesRemaining.0'];
        if ($limit = snmp_get_oid($device, "upsConfigLowBattTime.0", "UPS-MIB")) {
            $options['limit_low'] = $limit;
        }
        //discover_sensor('runtime', $device, $oid, "upsEstimatedMinutesRemaining.0", 'mge', $descr . $descr_extra, 1, $value, $limits);
        discover_sensor_ng($device, 'runtime', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $entry[$oid_name], $options);
    }

    $oid_name = 'upsEstimatedChargeRemaining';
    if (isset($entry[$oid_name])) {
        $descr = "Battery Charge Remaining" . $descr_extra;
        $oid   = ".1.3.6.1.2.1.33.1.2.4.0";

        //discover_sensor('capacity', $device, $oid, "upsEstimatedChargeRemaining.0", 'ups-mib', $descr . $descr_extra, 1, $value);
        $options = ['rename_rrd' => 'ups-mib-upsEstimatedChargeRemaining.0'];
        discover_sensor_ng($device, 'capacity', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $entry[$oid_name], $options);
    }

    $oid_name = 'upsBatteryStatus';
    if (isset($entry[$oid_name])) {
        $descr = "Battery Status" . $descr_extra;
        $oid   = ".1.3.6.1.2.1.33.1.2.1.0";

        //discover_status($device, $oid, "upsBatteryStatus.0", 'ups-mib-battery-state', $descr . $descr_extra, $value, array( 'entPhysicalClass' => 'other' ));
        discover_status_ng($device, $mib, $oid_name, $oid, $index, 'ups-mib-battery-state', $descr, $entry[$oid_name], ['entPhysicalClass' => 'battery']);
    }
}

## Output Status
echo("upsOutputSource ");
$value = snmp_get_oid($device, 'upsOutputSource.0', $mib);
if (snmp_status()) {
    $descr = "Source of Output Power" . $descr_extra;
    $oid   = ".1.3.6.1.2.1.33.1.4.1.0";

    discover_status_ng($device, $mib, 'upsOutputSource', $oid, 0, 'ups-mib-output-state', $descr, $value, ['entPhysicalClass' => 'output']);
}

## Output Frequency
echo("upsOutputFrequency ");
$value = snmp_get_oid($device, 'upsOutputFrequency.0', $mib);
if (is_numeric($value) &&
    !discovery_check_if_type_exist(['frequency->HUAWEI-UPS-MIB-hwUpsOutputFrequency'], 'sensor')) {
    $scale_frequency = strlen($value) === 2 ? 1 : $scale; // 50 (incorrect) vs 500
    $descr           = "Output" . $descr_extra;
    $oid             = ".1.3.6.1.2.1.33.1.4.2.0";
    //discover_sensor('frequency', $device, $oid, "upsOutputFrequency", 'ups-mib', "Output".$descr_extra, $scale, $value);
    $options = ['rename_rrd' => 'ups-mib-upsOutputFrequency'];
    if ($limit = snmp_get_oid($device, "upsConfigOutputFreq.0", "UPS-MIB")) {
        $options['limit_high'] = sensor_limit_high('frequency', $limit * $scale_frequency);
        $options['limit_low']  = sensor_limit_low('frequency', $limit * $scale_frequency);
    }
    discover_sensor_ng($device, 'frequency', $mib, "upsOutputFrequency", $oid, 0, NULL, $descr, $scale_frequency, $value, $options);
}

## Bypass Frequency
echo("upsBypassFrequency ");
$value = snmp_get_oid($device, 'upsBypassFrequency.0', $mib);
if (is_numeric($value) &&
    !discovery_check_if_type_exist(['frequency->HUAWEI-UPS-MIB-hwUpsBypassInputFrequency'], 'sensor')) {
    $scale_frequency = strlen($value) === 2 ? 1 : $scale; // 50 (incorrect) vs 500
    $descr           = "Bypass" . $descr_extra;
    $oid             = ".1.3.6.1.2.1.33.1.5.1.0";
    //discover_sensor('frequency', $device, $oid, "upsBypassFrequency", 'ups-mib', "Bypass".$descr_extra, $scale, $value);
    $options = ['rename_rrd' => 'ups-mib-upsBypassFrequency'];
    discover_sensor_ng($device, 'frequency', $mib, "upsBypassFrequency", $oid, 0, NULL, $descr, $scale_frequency, $value, $options);
}

//UPS-MIB::upsTestId.0 = OID: UPS-MIB::upsTestNoTestsInitiated
//UPS-MIB::upsTestSpinLock.0 = INTEGER: 1
//UPS-MIB::upsTestResultsSummary.0 = INTEGER: noTestsInitiated(6)
//UPS-MIB::upsTestResultsDetail.0 = STRING: No test initiated.
//UPS-MIB::upsTestStartTime.0 = Timeticks: (0) 0:00:00.00
//UPS-MIB::upsTestElapsedTime.0 = INTEGER: 0
echo("upsTest ");
$ups_array = snmpwalk_cache_oid($device, "upsTest", [], "UPS-MIB");
if (isset($ups_array[0]['upsTestResultsSummary']) && $ups_array[0]['upsTestResultsSummary'] !== 'noTestsInitiated') {
    $descr          = "Diagnostics Results";
    $oid            = ".1.3.6.1.2.1.33.1.7.3.0";
    $value          = $ups_array[0]['upsTestResultsSummary'];
    $test_starttime = timeticks_to_sec($ups_array[0]['upsTestStartTime']);
    if ($test_starttime) {
        $test_sysUpime = timeticks_to_sec(snmp_get_oid($device, "sysUpTime.0", "SNMPv2-MIB"));
        if ($test_sysUpime) {
            $test_starttime = time() + $test_starttime - $test_sysUpime; // Unixtime of start test
            $descr          .= ' (last ' . format_unixtime($test_starttime) . ')';
        }
    }

    //discover_status($device, $oid, "upsTestResultsSummary.0", 'ups-mib-test-state', $descr . $descr_extra, $value, array('entPhysicalClass' => 'other'));
    discover_status_ng($device, $mib, 'upsTestResultsSummary', $oid, 0, 'ups-mib-test-state', $descr . $descr_extra, $value, ['entPhysicalClass' => 'other']);
}

unset($ups_array, $ups_total, $value, $index, $oid, $options, $descr);

// EOF
