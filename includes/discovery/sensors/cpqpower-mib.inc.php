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

$hpups_array = [];
$hpups_array = snmpwalk_cache_oid($device, 'upsInput', $hpups_array, 'CPQPOWER-MIB');
$hpups_array = snmpwalk_cache_oid($device, 'upsOutput', $hpups_array, 'CPQPOWER-MIB');
$hpups_array = snmpwalk_cache_oid($device, 'upsBypass', $hpups_array, 'CPQPOWER-MIB');

foreach (array_slice(array_keys($hpups_array), 1) as $phase) {
    # Skip garbage output:
    # upsOutput.6.0 = 0
    # upsOutput.7.0 = 0
    # upsOutput.8.0 = 0
    if (!isset($hpups_array[$phase]['upsInputPhase'])) {
        break;
    }

    # Input
    $index = $hpups_array[$phase]['upsInputPhase'];
    $descr = 'Input';
    if ($hpups_array[0]['upsInputNumPhases'] > 1) {
        $descr .= " Phase $index";
    }

    ## Input voltage
    $oid   = ".1.3.6.1.4.1.232.165.3.3.4.1.2.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             # CPQPOWER-MIB:upsInputVoltage.$index
    $value = $hpups_array[$phase]['upsInputVoltage'];

    $options = ['rename_rrd' => "CPQPOWER-MIB-upsInputEntry.$index"];
    discover_sensor_ng($device, 'voltage', $mib, 'upsInputVoltage', $oid, $index, NULL, $descr, 1, $value, $options);

    ## Input current
    $oid = ".1.3.6.1.4.1.232.165.3.3.4.1.3.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         # CPQPOWER-MIB:upsInputCurrent.$index
    $value = $hpups_array[$phase]['upsInputCurrent'];

    if ($value < 10000) { # upsInputCurrent.1 = 136137420 ? really? You're nuts.
        $options = ['rename_rrd' => "CPQPOWER-MIB-upsInputEntry.$index"];
        discover_sensor_ng($device, 'current', $mib, 'upsInputCurrent', $oid, $index, NULL, $descr, 1, $value, $options);
    }

    ## Input power
    $oid     = ".1.3.6.1.4.1.232.165.3.3.4.1.4.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             # CPQPOWER-MIB:upsInputWatts.$index
    $value   = $hpups_array[$phase]['upsInputWatts'];
    $options = ['rename_rrd' => "CPQPOWER-MIB-upsInputEntry.$index"];
    discover_sensor_ng($device, 'power', $mib, 'upsInputWatts', $oid, $index, NULL, $descr, 1, $value, $options);

    # Output
    $index = $hpups_array[$phase]['upsOutputPhase'];
    $descr = 'Output';
    if ($hpups_array[0]['upsOutputNumPhases'] > 1) {
        $descr .= " Phase $index";
    }

    ## Output voltage
    $oid     = ".1.3.6.1.4.1.232.165.3.4.4.1.2.$index"; # CPQPOWER-MIB:upsOutputVoltage.$index
    $value   = $hpups_array[$phase]['upsOutputVoltage'];
    $options = ['rename_rrd' => "CPQPOWER-MIB-upsOutputEntry.$index"];
    discover_sensor_ng($device, 'voltage', $mib, 'upsOutputVoltage', $oid, $index, NULL, $descr, 1, $value, $options);

    ## Output current
    $oid     = ".1.3.6.1.4.1.232.165.3.4.4.1.3.$index"; # CPQPOWER-MIB:upsOutputCurrent.$index
    $value   = $hpups_array[$phase]['upsOutputCurrent'];
    $options = ['rename_rrd' => "CPQPOWER-MIB-upsOutputEntry.$index"];
    discover_sensor_ng($device, 'current', $mib, 'upsOutputCurrent', $oid, $index, NULL, $descr, 1, $value, $options);

    ## Output power
    $oid     = ".1.3.6.1.4.1.232.165.3.4.4.1.4.$index"; # CPQPOWER-MIB:upsOutputWatts.$index
    $value   = $hpups_array[$phase]['upsOutputWatts'];
    $options = ['rename_rrd' => "CPQPOWER-MIB-upsOutputEntry.$index"];
    discover_sensor_ng($device, 'power', $mib, 'upsOutputWatts', $oid, $index, NULL, $descr, 1, $value, $options);

    ## Output Load
    $oid     = '.1.3.6.1.4.1.232.165.3.4.1.0'; # CPQPOWER-MIB:upsOutputLoad.$index
    $descr   = 'Output Load';
    $value   = $hpups_array[$phase]['upsOutputLoad'];
    $options = ['rename_rrd' => "CPQPOWER-MIB-upsOutputLoad.0"];
    discover_sensor_ng($device, 'capacity', $mib, 'upsOutputLoad', $oid, '0', NULL, $descr, 1, $value, $options);                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       // FIXME load?

    # Bypass
    $index = $hpups_array[$phase]['upsBypassPhase'];
    $descr = 'Bypass';
    if ($hpups_array[0]['upsBypassNumPhases'] > 1) {
        $descr .= " Phase $index";
    }

    ## Bypass voltage
    $oid     = ".1.3.6.1.4.1.232.165.3.5.3.1.2.$index";                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     # CPQPOWER-MIB:upsBypassVoltage.$index
    $value   = $hpups_array[$phase]['upsBypassVoltage'];
    $options = ['rename_rrd' => "CPQPOWER-MIB-upsBypassEntry.$index"];
    discover_sensor_ng($device, 'voltage', $mib, 'upsBypassVoltage', $oid, $index, NULL, $descr, 1, $value, $options);
}

$scale = 0.1;

## Input frequency
$oid     = '.1.3.6.1.4.1.232.165.3.3.1.0';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               # CPQPOWER-MIB:upsInputFrequency.0
$value = $hpups_array[0]['upsInputFrequency'];
$options = ['rename_rrd' => "CPQPOWER-MIB-upsInputFrequency.0"];
discover_sensor_ng($device, 'frequency', $mib, 'upsInputFrequency', $oid, '0', NULL, 'Input', $scale, $value, $options);

## Output Frequency
$oid = '.1.3.6.1.4.1.232.165.3.4.2.0';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           # CPQPOWER-MIB:upsOutputFrequency.0
$value = $hpups_array[0]['upsOutputFrequency'];
$options = ['rename_rrd' => "CPQPOWER-MIB-upsOutputFrequency.0"];
discover_sensor_ng($device, 'frequency', $mib, 'upsOutputFrequency', $oid, '0', NULL, 'Output', $scale, $value, $options);

## Bypass Frequency
$oid     = '.1.3.6.1.4.1.232.165.3.5.1.0';                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       # CPQPOWER-MIB:upsBypassFrequency.0
$value   = $hpups_array[0]['upsBypassFrequency'];
$options = ['rename_rrd' => "CPQPOWER-MIB-upsBypassFrequency.0"];
discover_sensor_ng($device, 'frequency', $mib, 'upsBypassFrequency', $oid, '0', NULL, 'Bypass', $scale, $value, $options);

unset($hpups_array);

#Check for PDU mgmt module
//echo("Caching OIDs: ");
//echo("pduIdentTable ");
//CPQPOWER-MIB::pduIdentIndex.1 = INTEGER: 0
//CPQPOWER-MIB::pduIdentIndex.2 = INTEGER: 1
//CPQPOWER-MIB::pduName.1 = STRING: "PDU A"
//CPQPOWER-MIB::pduName.2 = STRING: "PDU B"
//CPQPOWER-MIB::pduStatus.1 = INTEGER: ok(2)
//CPQPOWER-MIB::pduStatus.2 = INTEGER: ok(2)
//CPQPOWER-MIB::pduOutputIndex.1 = INTEGER: 0
//CPQPOWER-MIB::pduOutputIndex.2 = INTEGER: 1
//CPQPOWER-MIB::pduOutputLoad.1 = INTEGER: 6
//CPQPOWER-MIB::pduOutputLoad.2 = INTEGER: 6
//CPQPOWER-MIB::pduOutputHeat.1 = INTEGER: 2302
//CPQPOWER-MIB::pduOutputHeat.2 = INTEGER: 2296
//CPQPOWER-MIB::pduOutputPower.1 = INTEGER: 673
//CPQPOWER-MIB::pduOutputPower.2 = INTEGER: 671
//CPQPOWER-MIB::pduOutputNumBreakers.1 = INTEGER: 3
//CPQPOWER-MIB::pduOutputNumBreakers.2 = INTEGER: 3
$hppdu_array = snmpwalk_cache_oid($device, 'pduIdentTable', [], 'CPQPOWER-MIB');
$hppdu_array = snmpwalk_cache_oid($device, 'pduOutputTable', $hppdu_array, 'CPQPOWER-MIB');
foreach ($hppdu_array as $index => $entry) {
    // Monitor PDU Status
    $oid   = ".1.3.6.1.4.1.232.165.2.1.2.1.8.$index";
    $descr = $entry['pduName'] . ' Status';
    if (!empty($entry['pduStatus'])) {
        discover_status_ng($device, $mib, 'pduStatus', $oid, $index, 'cpqpower-pdu-status', $descr, $entry['pduStatus'], ['entPhysicalClass' => 'power', 'rename_rrd' => 'cpqpower-pdu-status-%index%']);
    }

    // Monitor PDU Output load
    $oid    = ".1.3.6.1.4.1.232.165.2.3.1.1.2.$index";
    $descr  = $entry['pduName'] . ' Load';
    $limits = [];
    if (!empty($entry['pduOutputLoad']) && $entry['pduOutputLoad'] != '-1') {
        $options = ['rename_rrd' => "CPQPOWER-MIB-%index%"];
        discover_sensor_ng($device, 'capacity', $mib, 'pduOutputLoad', $oid, $index, NULL, $descr, 1, $entry['pduOutputLoad'], $options);

        // Find power limit by measure the reported output power divided by the reported load of the PDU
        $pdu_maxload  = 100 * float_div($entry['pduOutputPower'], $entry['pduOutputLoad']);
        $pdu_warnload = 0.8 * $pdu_maxload;
        $limits       = ['limit_high'      => round($pdu_maxload, 2),
                         'limit_high_warn' => round($pdu_warnload, 2)];
    }

    // Monitor PDU Power
    $oid   = ".1.3.6.1.4.1.232.165.2.3.1.1.4.$index";
    $descr = $entry['pduName'] . ' Output Power';

    if (!empty($entry['pduOutputPower']) && $entry['pduOutputPower'] != '-1') {
        $options               = $limits;
        $options['rename_rrd'] = "CPQPOWER-MIB-%index%";
        discover_sensor_ng($device, 'power', $mib, 'pduOutputPower', $oid, $index, NULL, $descr, 1, $entry['pduOutputPower'], $options);
    }
}

//CPQPOWER-MIB::breakerIndex.1.1 = INTEGER: 1
//CPQPOWER-MIB::breakerIndex.2.6 = INTEGER: 6
//CPQPOWER-MIB::breakerCurrent.1.1 = INTEGER: 1
//CPQPOWER-MIB::breakerCurrent.2.6 = INTEGER: 0
//CPQPOWER-MIB::breakerVoltage.1.1 = INTEGER: 230
//CPQPOWER-MIB::breakerVoltage.2.6 = INTEGER: 0
//CPQPOWER-MIB::breakerPercentLoad.1.1 = INTEGER: 9
//CPQPOWER-MIB::breakerPercentLoad.2.6 = INTEGER: 0
//CPQPOWER-MIB::breakerStatus.1.1 = INTEGER: 0
//CPQPOWER-MIB::breakerStatus.2.6 = INTEGER: 0
$hppdu_breaker_array = snmpwalk_cache_oid($device, 'pduOutputBreakerTable', [], 'CPQPOWER-MIB');
foreach ($hppdu_breaker_array as $index => $entry) {
    if ($entry['breakerVoltage'] <= 0) {
        continue;
    }

    [$breaker_output, $breaker_unit] = explode('.', $index, 2);
    $breaker_descr = 'Breaker ' . $hppdu_array[$breaker_output]['pduName'] . ' Unit ' . $breaker_unit;

    // Find powerlimit by measure the reported output power devivded by the reported load of the PDU
    //$breaker_maxload = 100 * ($entry['breakerCurrent'] / $entry['breakerPercentLoad']);
    $breaker_maxload       = float_div($entry['breakerCurrent'], $entry['breakerPercentLoad']); // breakerCurrent already scaled by 100
    $breaker_warnload      = 0.8 * $breaker_maxload;
    $limits                = ['limit_high'      => round($breaker_maxload, 2),
                              'limit_high_warn' => round($breaker_warnload, 2)];
    $descr                 = $breaker_descr . ' Current';
    $oid                   = ".1.3.6.1.4.1.232.165.2.3.2.1.3.$index";
    $options               = $limits;
    $options['rename_rrd'] = "CPQPOWER-MIB-%index%";
    discover_sensor_ng($device, 'current', $mib, 'breakerCurrent', $oid, $index, NULL, $descr, 0.01, $entry['breakerCurrent'], $options);

    $descr   = $breaker_descr . ' Voltage';
    $oid     = ".1.3.6.1.4.1.232.165.2.3.2.1.2.$index";
    $options = ['rename_rrd' => "CPQPOWER-MIB-%index%"];
    discover_sensor_ng($device, 'voltage', $mib, 'breakerVoltage', $oid, $index, NULL, $descr, 1, $entry['breakerVoltage'], $options);

    $descr   = $breaker_descr . ' Load';
    $oid     = ".1.3.6.1.4.1.232.165.2.3.2.1.4.$index";
    $options = ['rename_rrd' => "CPQPOWER-MIB-%index%"];
    discover_sensor_ng($device, 'capacity', $mib, 'breakerPercentLoad', $oid, $index, NULL, $descr, 1, $entry['breakerPercentLoad'], $options);

    $descr = $breaker_descr . ' Status';
    $oid   = ".1.3.6.1.4.1.232.165.2.3.2.1.5.$index";
    discover_status_ng($device, $mib, 'breakerStatus', $oid, $index, 'cpqpower-pdu-breaker-status', $descr, $entry['breakerStatus'], ['entPhysicalClass' => 'power', 'rename_rrd' => 'cpqpower-pdu-breaker-status-%index%']);
}

unset($hpups_array, $hppdu_breaker_array);

// EOF
