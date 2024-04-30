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

// ELTEX-PHY-MIB::eltexPhyTransceiverInfoConnectorType.9 = INTEGER: lc(7)
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoType.9 = INTEGER: sfp-sfpplus(3)
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoComplianceCode.9 = STRING: "1000BASE-LX"
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoWaveLength.9 = INTEGER: 1310
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoVendorName.9 = STRING: "OEM"
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoSerialNumber.9 = STRING: "SDB8951885"
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoFiberDiameter.9 = INTEGER: fiber9(1)
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoTransferDistance.9 = INTEGER: 10000
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoDiagnosticSupported.9 = INTEGER: true(1)
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoPartNumber.9 = STRING: "SFP-1.25G-1310D"
// ELTEX-PHY-MIB::eltexPhyTransceiverInfoVendorRevision.9 = STRING: "A0"

// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticUnits.9.temperature.1 = STRING: Degrees
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticUnits.9.supplyVoltage.1 = STRING: Volts
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticUnits.9.txBiasCurrent.1 = STRING: milliAmperes
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticUnits.9.txOpticalPower.1 = STRING: milliDecibel-milliWatts
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticUnits.9.rxOpticalPower.1 = STRING: milliDecibel-milliWatts
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticUnits.9.lossOfSignal.1 = STRING: Boolean
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighAlarmThreshold.9.temperature.1 = INTEGER: 110
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighAlarmThreshold.9.supplyVoltage.1 = INTEGER: 3600
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighAlarmThreshold.9.txBiasCurrent.1 = INTEGER: 80000
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighAlarmThreshold.9.txOpticalPower.1 = INTEGER: 15849
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighAlarmThreshold.9.rxOpticalPower.1 = INTEGER: 6310
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighAlarmThreshold.9.lossOfSignal.1 = INTEGER: 0
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighWarningThreshold.9.temperature.1 = INTEGER: 95
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighWarningThreshold.9.supplyVoltage.1 = INTEGER: 3500
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighWarningThreshold.9.txBiasCurrent.1 = INTEGER: 70000
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighWarningThreshold.9.txOpticalPower.1 = INTEGER: 12589
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighWarningThreshold.9.rxOpticalPower.1 = INTEGER: 5012
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticHighWarningThreshold.9.lossOfSignal.1 = INTEGER: 0
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowWarningThreshold.9.temperature.1 = INTEGER: -42
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowWarningThreshold.9.supplyVoltage.1 = INTEGER: 3050
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowWarningThreshold.9.txBiasCurrent.1 = INTEGER: 3000
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowWarningThreshold.9.txOpticalPower.1 = INTEGER: 794
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowWarningThreshold.9.rxOpticalPower.1 = INTEGER: 32
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowWarningThreshold.9.lossOfSignal.1 = INTEGER: 0
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowAlarmThreshold.9.temperature.1 = INTEGER: -45
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowAlarmThreshold.9.supplyVoltage.1 = INTEGER: 3000
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowAlarmThreshold.9.txBiasCurrent.1 = INTEGER: 2000
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowAlarmThreshold.9.txOpticalPower.1 = INTEGER: 631
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowAlarmThreshold.9.rxOpticalPower.1 = INTEGER: 25
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticLowAlarmThreshold.9.lossOfSignal.1 = INTEGER: 0
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticCurrentValue.9.temperature.1 = INTEGER: 25
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticCurrentValue.9.supplyVoltage.1 = INTEGER: 3
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticCurrentValue.9.txBiasCurrent.1 = INTEGER: 22
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticCurrentValue.9.txOpticalPower.1 = INTEGER: -7552
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticCurrentValue.9.rxOpticalPower.1 = INTEGER: -9147
// ELTEX-PHY-MIB::eltexPhyTransceiverDiagnosticCurrentValue.9.lossOfSignal.1 = INTEGER: 0

$dom_info = snmpwalk_cache_oid($device, 'eltexPhyTransceiverInfoTable', [], 'ELTEX-PHY-MIB');
if (!snmp_status()) {
    return;
}

$oids = snmpwalk_cache_threepart_oid($device, 'eltexPhyTransceiverDiagnosticTable', [], 'ELTEX-PHY-MIB');
foreach ($dom_info as $ifIndex => $info) {
    if ($info['eltexPhyTransceiverInfoConnectorType'] === 'unknown' ||
        $info['eltexPhyTransceiverInfoType'] === 'unknown' || !isset($oids[$ifIndex])) {
        print_debug("DOM sensors for ifIndex $ifIndex not exist.");
        continue;
    }

    foreach ($oids[$ifIndex] as $phyTransceiverDiagnosticType => $entries) {
        $multilane = safe_count($entries) > 1;
        foreach ($entries as $lane => $entry) {
            // scale (all possible units unknown)
            if (str_starts_with($entry['eltexPhyTransceiverDiagnosticUnits'], 'milli')) {
                $scale = si_to_scale('milli');
            } elseif (str_starts_with($entry['eltexPhyTransceiverDiagnosticUnits'], 'micro')) {
                $scale = si_to_scale('micro');
            } elseif (str_starts_with($entry['eltexPhyTransceiverDiagnosticUnits'], 'deci')) {
                $scale = si_to_scale('deci');
            } else {
                $scale = 1;
            }

            $entry['ifIndex'] = $ifIndex;
            $match            = [ 'measured_match' => [ 'entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%ifIndex%' ] ];
            $options          = entity_measured_match_definition($device, $match, $entry);

            //print_debug_vars($options);

            $name = $options['port_label'];
            if ($multilane) {
                $name .= " Lane " . $lane;
            }

            switch ($phyTransceiverDiagnosticType) {
                case 'temperature':
                    $descr = $name . " Temperature";
                    $index = $ifIndex . '.1.' . $lane;
                    $class = 'temperature';

                    // Limits
                    $options['limit_high']      = $entry['eltexPhyTransceiverDiagnosticHighAlarmThreshold'];
                    $options['limit_high_warn'] = $entry['eltexPhyTransceiverDiagnosticHighWarningThreshold'];
                    $options['limit_low']       = $entry['eltexPhyTransceiverDiagnosticLowAlarmThreshold'];
                    $options['limit_low_warn']  = $entry['eltexPhyTransceiverDiagnosticLowWarningThreshold'];
                    break;

                case 'supplyVoltage':
                    $descr = $name . " Voltage";
                    $index = $ifIndex . '.2.' . $lane;
                    $class = 'voltage';

                    // Limits
                    $options['limit_high']      = $entry['eltexPhyTransceiverDiagnosticHighAlarmThreshold']   * 0.001;
                    $options['limit_high_warn'] = $entry['eltexPhyTransceiverDiagnosticHighWarningThreshold'] * 0.001;
                    $options['limit_low']       = $entry['eltexPhyTransceiverDiagnosticLowAlarmThreshold']    * 0.001;
                    $options['limit_low_warn']  = $entry['eltexPhyTransceiverDiagnosticLowWarningThreshold']  * 0.001;
                    break;

                case 'txBiasCurrent':
                    $descr = $name . " Tx Bias";
                    $index = $ifIndex . '.3.' . $lane;
                    $class = 'current';

                    // Limits
                    $options['limit_high']      = $entry['eltexPhyTransceiverDiagnosticHighAlarmThreshold']   * 0.000001;
                    $options['limit_high_warn'] = $entry['eltexPhyTransceiverDiagnosticHighWarningThreshold'] * 0.000001;
                    $options['limit_low']       = $entry['eltexPhyTransceiverDiagnosticLowAlarmThreshold']    * 0.000001;
                    $options['limit_low_warn']  = $entry['eltexPhyTransceiverDiagnosticLowWarningThreshold']  * 0.000001;
                    break;

                case 'txOpticalPower':
                    $descr = $name . " TX Power";
                    $index = $ifIndex . '.4.' . $lane;
                    $class = 'dbm';

                    // Limits
                    $options['limit_high']      = value_to_si($entry['eltexPhyTransceiverDiagnosticHighAlarmThreshold']   * 0.000001, 'w', 'dbm');
                    $options['limit_high_warn'] = value_to_si($entry['eltexPhyTransceiverDiagnosticHighWarningThreshold'] * 0.000001, 'w', 'dbm');
                    $options['limit_low']       = value_to_si($entry['eltexPhyTransceiverDiagnosticLowAlarmThreshold']    * 0.000001, 'w', 'dbm');
                    $options['limit_low_warn']  = value_to_si($entry['eltexPhyTransceiverDiagnosticLowWarningThreshold']  * 0.000001, 'w', 'dbm');
                    // $options['limit_high']      = $entry['eltexPhyTransceiverDiagnosticHighAlarmThreshold']   * 0.001;
                    // $options['limit_high_warn'] = $entry['eltexPhyTransceiverDiagnosticHighWarningThreshold'] * 0.001;
                    // $options['limit_low']       = $entry['eltexPhyTransceiverDiagnosticLowAlarmThreshold']    * 0.001;
                    // $options['limit_low_warn']  = $entry['eltexPhyTransceiverDiagnosticLowWarningThreshold']  * 0.001;
                    break;

                case 'rxOpticalPower':
                    $descr = $name . " RX Power";
                    $index = $ifIndex . '.5.' . $lane;
                    $class = 'dbm';

                    // Limits
                    $options['limit_high']      = value_to_si($entry['eltexPhyTransceiverDiagnosticHighAlarmThreshold']   * 0.000001, 'w', 'dbm');
                    $options['limit_high_warn'] = value_to_si($entry['eltexPhyTransceiverDiagnosticHighWarningThreshold'] * 0.000001, 'w', 'dbm');
                    $options['limit_low']       = value_to_si($entry['eltexPhyTransceiverDiagnosticLowAlarmThreshold']    * 0.000001, 'w', 'dbm');
                    $options['limit_low_warn']  = value_to_si($entry['eltexPhyTransceiverDiagnosticLowWarningThreshold']  * 0.000001, 'w', 'dbm');
                    // $options['limit_high']      = $entry['eltexPhyTransceiverDiagnosticHighAlarmThreshold']   * 0.001;
                    // $options['limit_high_warn'] = $entry['eltexPhyTransceiverDiagnosticHighWarningThreshold'] * 0.001;
                    // $options['limit_low']       = $entry['eltexPhyTransceiverDiagnosticLowAlarmThreshold']    * 0.001;
                    // $options['limit_low_warn']  = $entry['eltexPhyTransceiverDiagnosticLowWarningThreshold']  * 0.001;
                    break;

                default:
                    continue 2;
            }

            $descr   .= ' ('.$info['eltexPhyTransceiverInfoVendorName'].' '.$info['eltexPhyTransceiverInfoPartNumber'].')';
            $oid_name = 'eltexPhyTransceiverDiagnosticCurrentValue';
            $oid_num  = '.1.3.6.1.4.1.35265.52.1.1.3.2.1.8.' . $index;
            $value    = $entry[$oid_name];
            discover_sensor_ng($device, $class, 'ELTEX-PHY-MIB', $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }
    }
}

// EOF
