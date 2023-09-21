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
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableTransceiverTemp = INTEGER: 22
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableTransceiverSupply = INTEGER: 3290100
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableTxBias = INTEGER: 9900
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableTxOutput = INTEGER: -5515
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableRxOpticalPower = INTEGER: -8986
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableDataReady = INTEGER: 1
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableLOS = INTEGER: 0
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableTxFault = INTEGER: 0
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableSFPEepromQualified = INTEGER: 2
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableRxOpticalPower1 = INTEGER: -8986
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableRxOpticalPower2 = INTEGER: -8986
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableRxOpticalPower3 = INTEGER: -8986
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableTxBias1 = INTEGER: 9888
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableTxBias2 = INTEGER: 9892
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableTxBias3 = INTEGER: 9892
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableLOS1 = INTEGER: 0
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableLOS2 = INTEGER: 0
RADLAN-PHY-MIB::rlPhyTestGetResult.49.rlPhyTestTableLOS3 = INTEGER: 0

RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableTransceiverTemp = INTEGER: degree(7)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableTransceiverSupply = INTEGER: microVolt(8)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableTxBias = INTEGER: microAmper(10)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableTxOutput = INTEGER: milidbm(17)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableRxOpticalPower = INTEGER: milidbm(17)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableDataReady = INTEGER: boolean(2)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableLOS = INTEGER: boolean(2)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableTxFault = INTEGER: boolean(2)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableSFPEepromQualified = INTEGER: boolean(2)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableRxOpticalPower1 = INTEGER: milidbm(17)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableRxOpticalPower2 = INTEGER: milidbm(17)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableRxOpticalPower3 = INTEGER: milidbm(17)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableTxBias1 = INTEGER: microAmper(10)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableTxBias2 = INTEGER: microAmper(10)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableTxBias3 = INTEGER: microAmper(10)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableLOS1 = INTEGER: boolean(2)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableLOS2 = INTEGER: boolean(2)
RADLAN-PHY-MIB::rlPhyTestGetUnits.49.rlPhyTestTableLOS3 = INTEGER: boolean(2)

RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableTransceiverTemp = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableTransceiverSupply = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableTxBias = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableTxOutput = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableRxOpticalPower = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableDataReady = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableLOS = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableTxFault = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableSFPEepromQualified = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableRxOpticalPower1 = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableRxOpticalPower2 = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableRxOpticalPower3 = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableTxBias1 = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableTxBias2 = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableTxBias3 = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableLOS1 = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableLOS2 = INTEGER: success(2)
RADLAN-PHY-MIB::rlPhyTestGetStatus.49.rlPhyTestTableLOS3 = INTEGER: success(2)
*/

$oids     = snmpwalk_cache_twopart_oid($device, 'rlPhyTestGetResult', [], 'RADLAN-PHY-MIB');
$oids     = snmpwalk_cache_twopart_oid($device, 'rlPhyTestGetStatus', $oids, 'RADLAN-PHY-MIB');
$new_oids = [];
foreach ($oids as $index => $entry) {
    // Skip all non-dom entries
    if (!isset($entry['rlPhyTestTableTransceiverTemp']) || $entry['rlPhyTestTableTransceiverTemp']['rlPhyTestGetResult'] == 0) {
        continue;
    }

    $new_oids[$index] = $entry;

    // Detect multilane
    $multilane = FALSE;
    if (isset($entry['rlPhyTestTableRxOpticalPower1']) && in_array($entry['rlPhyTestTableRxOpticalPower1']['rlPhyTestGetStatus'], ['success', 'inProgress'])) {
        // FIXME. Seems as incorrectly report as multi-lane
        //$multilane = TRUE;
    }
    $new_oids[$index]['multilane'] = $multilane;
}
if (count($new_oids) == 0) {
    // Stop walk if not exist DOM sensors
    return;
}

// Get additional OIDs
$oids = snmpwalk_cache_twopart_oid($device, 'rlPhyTestGetUnits', $oids, 'RADLAN-PHY-MIB');

// Vendor specific
$extra_oids = [];
if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdType.49.temperature = INTEGER: temperature(0)
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdType.49.supply = INTEGER: supply(1)
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdType.49.txBias = INTEGER: txBias(2)
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdType.49.txOutput = INTEGER: txOutput(3)
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdType.49.rxOpticalPower = INTEGER: txOutput(3)
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighAlarm.49.temperature = INTEGER: 90
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighAlarm.49.supply = INTEGER: 3600000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighAlarm.49.txBias = INTEGER: 65000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighAlarm.49.txOutput = INTEGER: 999
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighAlarm.49.rxOpticalPower = INTEGER: 999
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighWarning.49.temperature = INTEGER: 85
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighWarning.49.supply = INTEGER: 3500000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighWarning.49.txBias = INTEGER: 55000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighWarning.49.txOutput = INTEGER: -3000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdHighWarning.49.rxOpticalPower = INTEGER: -3000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowWarning.49.temperature = INTEGER: -5
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowWarning.49.supply = INTEGER: 3100000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowWarning.49.txBias = INTEGER: 3000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowWarning.49.txOutput = INTEGER: -9501
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowWarning.49.rxOpticalPower = INTEGER: -18997
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowAlarm.49.temperature = INTEGER: -10
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowAlarm.49.supply = INTEGER: 3000000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowAlarm.49.txBias = INTEGER: 1000
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowAlarm.49.txOutput = INTEGER: -13497
    // ELTEX-MES-PHYSICAL-DESCRIPTION-MIB::eltPhdTransceiverThresholdLowAlarm.49.rxOpticalPower = INTEGER: -23011
    $oids = snmpwalk_cache_twopart_oid($device, 'eltPhdTransceiverThresholdTable', $oids, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB');
}
print_debug_vars($oids);

foreach ($new_oids as $ifIndex => $entry1) {
    $multilane = $entry1['multilane'];
    foreach ($oids[$ifIndex] as $oid_name => $entry) {
        if (!in_array($entry['rlPhyTestGetStatus'], ['success', 'inProgress'])) {
            continue;
        }

        switch ($entry['rlPhyTestGetUnits']) {
            case 'microVolt':
            case 'microAmper':
            case 'microOham':
            case 'microWatt':
                $scale = si_to_scale('micro');
                break;
            case 'milidbm':
                $scale = si_to_scale('milli');
                break;
            case 'decidbm':
                $scale = si_to_scale('deci');
                break;
            default:
                $scale = 1;
        }

        $entry['ifIndex'] = $ifIndex;
        $match            = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%ifIndex%']];
        $options          = entity_measured_match_definition($device, $match, $entry);
        //print_debug_vars($options);

        $name = $options['port_label'];

        switch ($oid_name) {
            case 'rlPhyTestTableTransceiverTemp':
                $descr = $name . " Temperature";
                $index = $ifIndex . '.5';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'temperature';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    $entry_limits               = $oids[$ifIndex]['temperature'];
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'];
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'];
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'];
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'];
                }
                break;

            case 'rlPhyTestTableTransceiverSupply':
                $descr = $name . " Voltage";
                $index = $ifIndex . '.6';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'voltage';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microVolt
                    $entry_limits               = $oids[$ifIndex]['supply'];
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.000001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.000001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.000001;
                }
                break;

            case 'rlPhyTestTableTxBias':
                if ($multilane) {
                    $name .= " Lane 1";
                }
                $descr = $name . " Tx Bias";
                $index = $ifIndex . '.7';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'current';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microAmper
                    $entry_limits               = $oids[$ifIndex]['txBias'];
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.000001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.000001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.000001;
                }
                break;

            case 'rlPhyTestTableTxBias1':
                if (!$multilane) {
                    continue 2;
                } // Skip when not multi-lane

                $name  .= " Lane 2";
                $descr = $name . " Tx Bias";
                $index = $ifIndex . '.30';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'current';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microAmper
                    $entry_limits               = $oids[$ifIndex]['txBias'];
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.000001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.000001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.000001;
                }
                break;

            case 'rlPhyTestTableTxBias2':
                if (!$multilane) {
                    continue 2;
                } // Skip when not multi-lane

                $name  .= " Lane 3";
                $descr = $name . " Tx Bias";
                $index = $ifIndex . '.31';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'current';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microAmper
                    $entry_limits               = $oids[$ifIndex]['txBias'];
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.000001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.000001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.000001;
                }
                break;

            case 'rlPhyTestTableTxBias3':
                if (!$multilane) {
                    continue 2;
                } // Skip when not multi-lane

                $name  .= " Lane 4";
                $descr = $name . " Tx Bias";
                $index = $ifIndex . '.32';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'current';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microAmper
                    $entry_limits               = $oids[$ifIndex]['txBias'];
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.000001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.000001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.000001;
                }
                break;

            case 'rlPhyTestTableTxOutput':
                $descr = $name . " TX Power";
                $index = $ifIndex . '.8';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'dbm';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microWatt (really milli dBm)
                    $entry_limits = $oids[$ifIndex]['txOutput'];
                    // $options['limit_high']      = value_to_si($entry_limits['eltPhdTransceiverThresholdHighAlarm']   * 0.000001, 'w', 'dbm');
                    // $options['limit_high_warn'] = value_to_si($entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001, 'w', 'dbm');
                    // $options['limit_low']       = value_to_si($entry_limits['eltPhdTransceiverThresholdLowAlarm']    * 0.000001, 'w', 'dbm');
                    // $options['limit_low_warn']  = value_to_si($entry_limits['eltPhdTransceiverThresholdLowWarning']  * 0.000001, 'w', 'dbm');
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.001;
                }
                break;

            case 'rlPhyTestTableRxOpticalPower':
                if ($multilane) {
                    $name .= " Lane 1";
                }
                $descr = $name . " RX Power";
                $index = $ifIndex . '.9';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'dbm';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microWatt
                    $entry_limits = $oids[$ifIndex]['rxOpticalPower'];
                    // $options['limit_high']      = value_to_si($entry_limits['eltPhdTransceiverThresholdHighAlarm']   * 0.000001, 'w', 'dbm');
                    // $options['limit_high_warn'] = value_to_si($entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001, 'w', 'dbm');
                    // $options['limit_low']       = value_to_si($entry_limits['eltPhdTransceiverThresholdLowAlarm']    * 0.000001, 'w', 'dbm');
                    // $options['limit_low_warn']  = value_to_si($entry_limits['eltPhdTransceiverThresholdLowWarning']  * 0.000001, 'w', 'dbm');
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.001;
                }
                break;

            case 'rlPhyTestTableRxOpticalPower1':
                if (!$multilane) {
                    continue 2;
                } // Skip when not multi-lane

                $name  .= " Lane 2";
                $descr = $name . " RX Power";
                $index = $ifIndex . '.27';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'dbm';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microWatt
                    $entry_limits = $oids[$ifIndex]['rxOpticalPower'];
                    // $options['limit_high']      = value_to_si($entry_limits['eltPhdTransceiverThresholdHighAlarm']   * 0.000001, 'w', 'dbm');
                    // $options['limit_high_warn'] = value_to_si($entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001, 'w', 'dbm');
                    // $options['limit_low']       = value_to_si($entry_limits['eltPhdTransceiverThresholdLowAlarm']    * 0.000001, 'w', 'dbm');
                    // $options['limit_low_warn']  = value_to_si($entry_limits['eltPhdTransceiverThresholdLowWarning']  * 0.000001, 'w', 'dbm');
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.001;
                }
                break;

            case 'rlPhyTestTableRxOpticalPower2':
                if (!$multilane) {
                    continue 2;
                } // Skip when not multi-lane

                $name  .= " Lane 3";
                $descr = $name . " RX Power";
                $index = $ifIndex . '.28';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'dbm';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microWatt
                    $entry_limits = $oids[$ifIndex]['rxOpticalPower'];
                    // $options['limit_high']      = value_to_si($entry_limits['eltPhdTransceiverThresholdHighAlarm']   * 0.000001, 'w', 'dbm');
                    // $options['limit_high_warn'] = value_to_si($entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001, 'w', 'dbm');
                    // $options['limit_low']       = value_to_si($entry_limits['eltPhdTransceiverThresholdLowAlarm']    * 0.000001, 'w', 'dbm');
                    // $options['limit_low_warn']  = value_to_si($entry_limits['eltPhdTransceiverThresholdLowWarning']  * 0.000001, 'w', 'dbm');
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.001;
                }
                break;

            case 'rlPhyTestTableRxOpticalPower3':
                if (!$multilane) {
                    continue 2;
                } // Skip when not multi-lane

                $name  .= " Lane 4";
                $descr = $name . " RX Power";
                $index = $ifIndex . '.29';
                $oid   = ".1.3.6.1.4.1.89.90.1.2.1.3.$index";
                $class = 'dbm';

                // Limits
                if (is_device_mib($device, 'ELTEX-MES-PHYSICAL-DESCRIPTION-MIB', FALSE)) {
                    // microWatt
                    $entry_limits = $oids[$ifIndex]['rxOpticalPower'];
                    // $options['limit_high']      = value_to_si($entry_limits['eltPhdTransceiverThresholdHighAlarm']   * 0.000001, 'w', 'dbm');
                    // $options['limit_high_warn'] = value_to_si($entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.000001, 'w', 'dbm');
                    // $options['limit_low']       = value_to_si($entry_limits['eltPhdTransceiverThresholdLowAlarm']    * 0.000001, 'w', 'dbm');
                    // $options['limit_low_warn']  = value_to_si($entry_limits['eltPhdTransceiverThresholdLowWarning']  * 0.000001, 'w', 'dbm');
                    $options['limit_high']      = $entry_limits['eltPhdTransceiverThresholdHighAlarm'] * 0.001;
                    $options['limit_high_warn'] = $entry_limits['eltPhdTransceiverThresholdHighWarning'] * 0.001;
                    $options['limit_low']       = $entry_limits['eltPhdTransceiverThresholdLowAlarm'] * 0.001;
                    $options['limit_low_warn']  = $entry_limits['eltPhdTransceiverThresholdLowWarning'] * 0.001;
                }
                break;

            default:
                continue 2;
        }
        $value = $entry['rlPhyTestGetResult'];
        discover_sensor_ng($device, $class, $mib, 'rlPhyTestGetResult', $oid, $index, NULL, $descr, $scale, $value, $options);
    }
}

// EOF
