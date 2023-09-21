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

$mib = 'LIEBERT-GP-PDU-MIB';

//LIEBERT-GP-PDU-MIB::lgpPduPsEntryId.1.1 = Gauge32: 1
//LIEBERT-GP-PDU-MIB::lgpPduPsEntrySysAssignLabel.1.1 = STRING: PEM: 1-1
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryModel.1.1 = STRING: MPHR1245
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryWiringType.1.1 = INTEGER: three-phase-5-wire-L1-L2-L3-N-PE(4)
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryEpInputRated.1.1 = Gauge32: 230 VoltRMS
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryEcInputRated.1.1 = Gauge32: 320 0.1 Amp-AC-RMS
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryFreqRated.1.1 = Gauge32: 50 Hertz
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryEnergyAccum.1.1 = Gauge32: 46790 0.1 Kilowatt-Hour
//LIEBERT-GP-PDU-MIB::lgpPduPsEntrySerialNum.1.1 = STRING: V16K2600793
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryFirmwareVersion.1.1 = STRING: 0.4.5.3
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryPwrTotal.1.1 = Gauge32: 4607 Watt
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryEcNeutral.1.1 = Gauge32: 55 0.1 Amp-AC-RMS
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryEcNeutralThrshldOvrWarn.1.1 = Gauge32: 40 Percent
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryEcNeutralThrshldOvrAlarm.1.1 = Gauge32: 45 Percent
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryUnbalancedLoadThrshldAlarm.1.1 = Gauge32: 0 Percent
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryApTotal.1.1 = Gauge32: 4667 VoltAmp
//LIEBERT-GP-PDU-MIB::lgpPduPsEntryPfTotal.1.1 = INTEGER: 0 0.01 Power Factor

$lgpPduPsEntry = snmpwalk_cache_oid($device, 'lgpPduPsEntry', [], $mib);
print_debug_vars($lgpPduPsEntry);

foreach ($lgpPduPsEntry as $index => $entry) {
    $descr = $entry['lgpPduPsEntrySysAssignLabel'] . ' Total';

    $scale    = 1;
    $oid_name = 'lgpPduPsEntryPwrTotal';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.20.1.65.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('power', $device, $oid_num, $index, $type, $descr, $scale, $value);

    $scale    = 0.1;
    $oid_name = 'lgpPduPsEntryEcNeutral';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.20.1.70.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];
    // convert percent limits to absolute values
    $options = ['limit_high_warn' => $entry['lgpPduPsEntryEcNeutralThrshldOvrWarn'] * $entry['lgpPduPsEntryEcInputRated'] * 0.001,
                'limit_high'      => $entry['lgpPduPsEntryEcNeutralThrshldOvrAlarm'] * $entry['lgpPduPsEntryEcInputRated'] * 0.001];

    discover_sensor('current', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

    $scale    = 1;
    $oid_name = 'lgpPduPsEntryApTotal';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.20.1.90.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('apower', $device, $oid_num, $index, $type, $descr, $scale, $value);

    $scale    = 0.01;
    $oid_name = 'lgpPduPsEntryPfTotal';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.20.1.95.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    if ($value > 0) {
        discover_sensor('powerfactor', $device, $oid_num, $index, $type, $descr, $scale, $value);
    }
}

$oids = snmpwalk_cache_oid($device, 'lgpPduPsLineEntry', [], $mib);
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    [$lgpPduEntryIndex, $lgpPduPsEntryIndex, $lgpPduPsLineEntryIndex] = explode('.', $index);
    $pdu_index = $lgpPduEntryIndex . '.' . $lgpPduPsEntryIndex;

    if (isset($lgpPduPsEntry[$pdu_index]['lgpPduPsEntrySysAssignLabel'])) {
        $descr = $lgpPduPsEntry[$pdu_index]['lgpPduPsEntrySysAssignLabel'];
    } else {
        $descr = 'PEM: ' . $lgpPduEntryIndex . '-' . $lgpPduPsEntryIndex;
    }
    $descr .= ' Phase ' . str_ireplace('phase', '', $entry['lgpPduPsLineEntryLine']); // add phase name

    if (isset($entry['lgpPduPsLineEntryEpLNTenths'])) {
        $scale    = 0.1;
        $oid_name = 'lgpPduPsLineEntryEpLNTenths';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.40.1.19.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
    } else {
        $scale    = 1;
        $oid_name = 'lgpPduPsLineEntryEpLN';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.40.1.20.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
    }

    discover_sensor('voltage', $device, $oid_num, $index, $type, $descr, $scale, $value);

    if (isset($entry['lgpPduPsLineEntryEcHundredths'])) {
        $scale    = 0.01;
        $oid_name = 'lgpPduPsLineEntryEcHundredths';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.40.1.22.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
    } else {
        $scale    = 0.1;
        $oid_name = 'lgpPduPsLineEntryEc';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.40.1.21.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
    }
    // convert percent limits to absolute values
    $options = ['limit_high_warn' => $entry['lgpPduPsLineEntryEcThrshldOvrWarn'] * $lgpPduPsEntry[$pdu_index]['lgpPduPsEntryEcInputRated'] * 0.001,
                'limit_high'      => $entry['lgpPduPsLineEntryEcThrshldOvrAlarm'] * $lgpPduPsEntry[$pdu_index]['lgpPduPsEntryEcInputRated'] * 0.001];

    discover_sensor('current', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

    $scale    = 1;
    $oid_name = 'lgpPduPsLineEntryPwrLN';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.40.1.63.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('power', $device, $oid_num, $index, $type, $descr, $scale, $value);

    $scale    = 1;
    $oid_name = 'lgpPduPsLineEntryApLN';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.40.1.65.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('apower', $device, $oid_num, $index, $type, $descr, $scale, $value);

    $scale    = 0.01;
    $oid_name = 'lgpPduPsLineEntryPfLN';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.30.40.1.67.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    if ($value > 0) {
        discover_sensor('powerfactor', $device, $oid_num, $index, $type, $descr, $scale, $value);
    }
}

$lgpPduRbEntry = snmpwalk_cache_oid($device, 'lgpPduRbEntry', [], $mib);
print_debug_vars($lgpPduRbEntry);

foreach ($lgpPduRbEntry as $index => $entry) {
}

$oids = snmpwalk_cache_oid($device, 'lgpPduRcpEntry', [], $mib);
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    [$lgpPduEntryIndex, $lgpPduRbEntryIndex, $lgpPduRcpEntryIndex] = explode('.', $index);
    $rb_index = $lgpPduEntryIndex . '.' . $lgpPduRbEntryIndex;

    if (strlen($entry['lgpPduRcpEntryUsrLabel'])) {
        $descr = $entry['lgpPduRcpEntryUsrLabel'];

        if (strlen($entry['lgpPduRcpEntryUsrTag1'])) {
            $descr .= ' (' . $entry['lgpPduRcpEntryUsrTag1'] . ')';
        }
    } else {
        $descr = $entry['lgpPduRcpEntrySysAssignLabel'];
    }

    if (isset($entry['lgpPduRcpEntryEpTenths'])) {
        $scale    = 0.1;
        $oid_name = 'lgpPduRcpEntryEpTenths';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.56.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
    } else {
        $scale    = 1;
        $oid_name = 'lgpPduRcpEntryEp';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.55.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
    }

    discover_sensor('voltage', $device, $oid_num, $index, $type, $descr, $scale, $value);

    if (isset($entry['lgpPduRcpEntryEcHundredths'])) {
        $scale    = 0.01;
        $oid_name = 'lgpPduRcpEntryEcHundredths';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.61.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
    } else {
        $scale    = 0.1;
        $oid_name = 'lgpPduRcpEntryEc';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.60.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];
    }
    // convert percent limits to absolute values
    $options = ['limit_high_warn' => $entry['lgpPduRcpEntryEcThrshldOverWarn'] * $lgpPduRbEntry[$rb_index]['lgpPduRbEntryEcRated'] * 0.001,
                'limit_high'      => $entry['lgpPduRcpEntryEcThrshldOverAlarm'] * $lgpPduRbEntry[$rb_index]['lgpPduRbEntryEcRated'] * 0.001];

    discover_sensor('current', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

    $scale    = 1;
    $oid_name = 'lgpPduRcpEntryPwrOut';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.65.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('power', $device, $oid_num, $index, $type, $descr, $scale, $value);

    $scale    = 1;
    $oid_name = 'lgpPduRcpEntryApOut';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.70.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('apower', $device, $oid_num, $index, $type, $descr, $scale, $value);

    $scale    = 0.01;
    $oid_name = 'lgpPduRcpEntryPf';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.75.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    if ($value > 0) {
        discover_sensor('powerfactor', $device, $oid_num, $index, $type, $descr, $scale, $value);
    }

    $scale    = 0.01;
    $oid_name = 'lgpPduRcpEntryEcCrestFactor';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.162.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('crestfactor', $device, $oid_num, $index, $type, $descr, $scale, $value);

    $scale    = 0.1;
    $oid_name = 'lgpPduRcpEntryFreq';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.80.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('frequency', $device, $oid_num, $index, $type, $descr, $scale, $value);

    // FIXME, this operational/administrative states, need migrate to outlets status polling
    $oid_name = 'lgpPduRcpEntryPwrState';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.95.' . $index;
    $type     = 'lgpPduRcpEntryPwrState';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, 'Power: ' . $descr, $value, ['entPhysicalClass' => 'outlet']);

    $oid_name = 'lgpPduRcpEntryOperationCondition';
    $oid_num  = '.1.3.6.1.4.1.476.1.42.3.8.50.20.1.210.' . $index;
    $type     = 'lgpPduRcpEntryOperationCondition';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, 'Condition: ' . $descr, $value, ['entPhysicalClass' => 'outlet']);
}

// EOF
