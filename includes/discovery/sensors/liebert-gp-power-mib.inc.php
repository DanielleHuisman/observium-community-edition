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

// Mostly all sensors for this MIB same as in UPS-MIB,
// here added sensors not exist in UPS-MIB

$mib = 'LIEBERT-GP-POWER-MIB';

//LIEBERT-GP-POWER-MIB::lgpPwrMeasurementPoint.1.1 = OID: LIEBERT-GP-POWER-MIB::lgpPwrSource1Input
//LIEBERT-GP-POWER-MIB::lgpPwrMeasurementPoint.2.1 = OID: LIEBERT-GP-POWER-MIB::lgpPwrMeasBypass
//LIEBERT-GP-POWER-MIB::lgpPwrMeasurementPoint.3.1 = OID: LIEBERT-GP-POWER-MIB::lgpPwrOutputToLoad
//LIEBERT-GP-POWER-MIB::lgpPwrMeasurementPoint.4.1 = OID: LIEBERT-GP-POWER-MIB::lgpPwrMeasSystemOutput
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementVoltsLL.1.1 = INTEGER: 393 Volt
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementCurrent.1.1 = INTEGER: 87 Amp
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementCurrent.3.1 = INTEGER: 82 Amp
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementVA.3.1 = INTEGER: 18600 Volt-Amp
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementVA.4.1 = INTEGER: 56300 Volt-Amp
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementTruePower.3.1 = INTEGER: 17600 Watt
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementTruePower.4.1 = INTEGER: 52700 Watt
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementCrestFactorCurrent.3.1 = INTEGER: 16 0.1 Crest Factor
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementPowerFactor.1.1 = INTEGER: 95 0.01 Power Factor
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementPowerFactor.3.1 = INTEGER: 94 0.01 Power Factor
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementVAR.3.1 = INTEGER: 6000 Volt-Amp-Reactive
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementVAR.4.1 = INTEGER: 19800 Volt-Amp-Reactive
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementPercentLoad.3.1 = INTEGER: 33 percent
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementVolts.1.1 = INTEGER: 228 Volt
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementVolts.2.1 = INTEGER: 228 Volt
//LIEBERT-GP-POWER-MIB::lgpPwrLineMeasurementVolts.3.1 = INTEGER: 231 Volt

$oids = snmpwalk_cache_twopart_oid($device, 'lgpPwrLineMeasurementEntry', [], $mib);
print_debug_vars($oids);

foreach ($oids as $id => $entry1) {
    $phase_count = count($entry1);
    foreach ($entry1 as $phase => $entry) {
        $index = $id . '.' . $phase;
        switch ($entry['lgpPwrMeasurementPoint']) {
            case 'lgpPwrSource1Input':
            case 'lgpPwrSourcePdu1Input':
                $descr = 'Input';
                break;
            case 'lgpPwrSource2Input':
            case 'lgpPwrSourcePdu2Input':
                $descr = 'Input2';
                break;
            case 'lgpPwrOutputToLoad':
                $descr = 'Output';
                break;
            case 'lgpPwrMeasBattery':
                $descr = 'Battery';
                break;
            case 'lgpPwrMeasBypass':
                $descr = 'Bypass';
                break;
            //case 'lgpPwrMeasDcBus':
            //  $descr = 'DC Bus';
            //  break;
            //case 'lgpPwrMeasSystemOutput':
            //  $descr = 'System';
            //  break;
            //case 'lgpPwrMeasBatteryCabinet':
            //  $descr = 'Battery Cabinet';
            //  break;
            default:
                continue 2;
        }
        if ($phase_count > 1 || $phase > 1) {
            $descr .= ' Phase ' . $phase;
        }

        $scale    = 1;
        $oid_name = 'lgpPwrLineMeasurementVA';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.5.2.3.1.8.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];

        if (isset($entry[$oid_name])) {
            discover_sensor('apower', $device, $oid_num, $index, $type, $descr, $scale, $value);
        }

        $scale    = 1;
        $oid_name = 'lgpPwrLineMeasurementVAR';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.5.2.3.1.18.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];

        if (isset($entry[$oid_name])) {
            discover_sensor('rpower', $device, $oid_num, $index, $type, $descr, $scale, $value);
        }

        $scale    = 0.1;
        $oid_name = 'lgpPwrLineMeasurementCrestFactorCurrent';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.5.2.3.1.13.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];

        if (isset($entry[$oid_name])) {
            discover_sensor('crestfactor', $device, $oid_num, $index, $type, $descr, $scale, $value);
        }

        $scale    = 0.01;
        $oid_name = 'lgpPwrLineMeasurementPowerFactor';
        $oid_num  = '.1.3.6.1.4.1.476.1.42.3.5.2.3.1.14.' . $index;
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];

        if (isset($entry[$oid_name])) {
            discover_sensor('powerfactor', $device, $oid_num, $index, $type, $descr, $scale, $value);
        }
    }
}

// EOF
