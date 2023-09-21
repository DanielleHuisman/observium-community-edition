<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

// Inlets
// PWTv1-MIB::pduPwrMonitoringInletNum.0 = INTEGER: 1
// PWTv1-MIB::inletIndex.1 = INTEGER: 0
// PWTv1-MIB::inletPowerAll.1 = INTEGER: 11114 0.1W
// PWTv1-MIB::inletResetFrom.1 = STRING: "07/02/2022 15:40:15"
// PWTv1-MIB::inletEnergy.1 = INTEGER: 10526559 KWh
// PWTv1-MIB::inletStatus.1 = INTEGER: normal(1)
// PWTv1-MIB::inletCurrPhase1.1 = INTEGER: 526 0.01A
// PWTv1-MIB::inletCurrPhase2.1 = INTEGER: 0 0.01A
// PWTv1-MIB::inletCurrPhase3.1 = INTEGER: 0 0.01A
// PWTv1-MIB::inletVoltPhase1.1 = INTEGER: 2291 0.1V
// PWTv1-MIB::inletVoltPhase2.1 = INTEGER: 0 0.1V
// PWTv1-MIB::inletVoltPhase3.1 = INTEGER: 0 0.1V
// PWTv1-MIB::inletPowerPhase1.1 = INTEGER: 11114 0.1W
// PWTv1-MIB::inletPowerPhase2.1 = INTEGER: 0 0.1W
// PWTv1-MIB::inletPowerPhase3.1 = INTEGER: 0 0.1W

if ($inlet_count = snmp_get_oid($device, 'pduPwrMonitoringInletNum.0', 'PWTv1-MIB')) {

    $oids = snmpwalk_cache_oid($device, 'pduPwrMonitoringInletStatusTable', [], 'PWTv1-MIB');
    $oids = snmpwalk_cache_oid($device, 'pduPwrMonitoringInletCfgTable', $oids, 'PWTv1-MIB');
    foreach ($oids as $index => $entry) {
        // PDU reports all inlets as "normal" status, but really only as in count
        if ($index > $inlet_count) {
            break;
        }

        $name = $inlet_count > 1 ? " #$index" : '';

        $descr    = "Inlet Total" . $name;
        $oid_num  = ".1.3.6.1.4.1.42610.1.4.4.1.6.1.2.1.2.$index";
        $oid_name = 'inletPowerAll';
        $value    = $entry[$oid_name];
        $scale    = 0.1;
        $options  = [
            'limit_high'      => $entry['inletCfgLoadCritical'] * 1000 * $scale,
            'limit_high_warn' => $entry['inletCfgLoadWarning'] * 1000 * $scale
        ];
        discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Inlet Total" . $name;
        $oid_num  = ".1.3.6.1.4.1.42610.1.4.4.1.6.1.2.1.4.$index";
        $oid_name = 'inletEnergy';
        $value    = $entry[$oid_name];
        $scale    = 1000;
        $options  = [];
        discover_counter($device, 'energy', $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, $options);

        $descr    = "Inlet" . $name;
        $oid_num  = ".1.3.6.1.4.1.42610.1.4.4.1.6.1.2.1.5.$index";
        $oid_name = 'inletStatus';
        $value    = $entry[$oid_name];
        $options  = [];
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'inletStatus', $descr, $value, $options);

        $phase_count = ($entry['inletVoltPhase2'] > 0) || ($entry['inletVoltPhase3'] > 0) ? 3 : 1;

        for ($phase = 1; $phase <= $phase_count; $phase++) {
            $phase_name = $phase_count > 1 ? " (Phase $phase)" : '';

            $descr    = "Inlet" . $name . $phase_name;
            $oid_name = 'inletCurrPhase' . $phase;
            $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
            $value    = $entry[$oid_name];
            $scale    = 0.1;
            $options  = [
                'limit_high'      => $entry['inletCfgTotalCurrCritPhase' . $phase] * $scale,
                'limit_high_warn' => $entry['inletCfgTotalCurrWarnPhase' . $phase] * $scale
            ];
            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

            $descr    = "Inlet" . $name . $phase_name;
            $oid_name = 'inletVoltPhase' . $phase;
            $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
            $value    = $entry[$oid_name];
            $scale    = 0.1;
            $options  = [
                'limit_high'      => $entry['inletCfgVoltCritPhase' . $phase] * $scale,
                'limit_high_warn' => $entry['inletCfgVoltWarnPhase' . $phase] * $scale
            ];
            discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

            $descr    = "Inlet" . $name . $phase_name;
            $oid_name = 'inletFreqPhase' . $phase;
            $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
            $value    = $entry[$oid_name];
            $scale    = 0.01;
            $options  = [];
            discover_sensor_ng($device, 'frequency', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

            if ($phase_count > 1) {
                // Three Phase
                $descr    = "Inlet" . $name . $phase_name;
                $oid_name = 'inletPowerPhase' . $phase;
                $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
                $value    = $entry[$oid_name];
                $scale    = 0.1;
                $options  = [];
                discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

                $descr    = "Inlet" . $name . $phase_name;
                $oid_name = 'inletStatusPhase' . $phase;
                $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
                $value    = $entry[$oid_name];
                $options  = [];
                discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'inletStatus', $descr, $value, $options);
            }
        }
    }
}

// Outlets
// PWTv1-MIB::pduPwrMonitoringOutletNumPduA.0 = INTEGER: 0
// PWTv1-MIB::pduPwrMonitoringOutletNumPduB.0 = INTEGER: 0
// PWTv1-MIB::pduPwrMonitoringOutletNumPduC.0 = INTEGER: 0
// PWTv1-MIB::pduPwrMonitoringOutletNumPduD.0 = INTEGER: 0
// PWTv1-MIB::pduPwrMonitoringOutletNumPduE.0 = INTEGER: 0
// PWTv1-MIB::pduPwrMonitoringOutletNumPduF.0 = INTEGER: 0
// PWTv1-MIB::pduPwrMonitoringOutletNumPduG.0 = INTEGER: 0
// PWTv1-MIB::pduPwrMonitoringOutletNumPduH.0 = INTEGER: 0

// PWTv1-MIB::outletPduAIndex.11 = INTEGER: 10
// PWTv1-MIB::outletPduAState.11 = INTEGER: off(1)
// PWTv1-MIB::outletPduACurrent.11 = INTEGER: 0 0.01A
// PWTv1-MIB::outletPduAPwrFactor.11 = INTEGER: 0 0.1%
// PWTv1-MIB::outletPduAPower.11 = INTEGER: 0 0.1W
// PWTv1-MIB::outletPduAEnergy.11 = INTEGER: 0 KWh
// PWTv1-MIB::outletPduAResetFrom.11 = STRING: "07/02/2022 15:40:15"
// PWTv1-MIB::outletPduAStatus.11 = INTEGER: normal(1)
// PWTv1-MIB::outletPduAAppPower.11 = INTEGER: 0 0.1W
// PWTv1-MIB::outletPduAVoltage.11 = INTEGER: 0 0.1V

// PWTv1-MIB::outletCfgPduAIndex.11 = INTEGER: 10
// PWTv1-MIB::outletCfgPduAName.11 = STRING: "outlet 11"
// PWTv1-MIB::outletCfgPduADelayOnStatus.11 = INTEGER: nodelay(1)
// PWTv1-MIB::outletCfgPduADelayOnTime.11 = INTEGER: 11 seconds
// PWTv1-MIB::outletCfgPduADelayOffStatus.11 = INTEGER: nodelay(1)
// PWTv1-MIB::outletCfgPduADelayOffTime.11 = INTEGER: 11 seconds
// PWTv1-MIB::outletCfgPduAReboot.11 = INTEGER: 5 seconds
// PWTv1-MIB::outletCfgPduAOverCurrCritical.11 = INTEGER: 160 0.1A
// PWTv1-MIB::outletCfgPduAOverCurrWarning.11 = INTEGER: 130 0.1A
// PWTv1-MIB::outletCfgPduAOverPwrCritical.11 = INTEGER: 2500 1W
// PWTv1-MIB::outletCfgPduAOverPwrWarning.11 = INTEGER: 2000 1W
// PWTv1-MIB::outletCtlPduAIndex.11 = INTEGER: 10
// PWTv1-MIB::outletCtlPduAControl.11 = INTEGER: nothing(1)
foreach ([ 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H' ] as $char) {
    if ($outlet_count = snmp_get_oid($device, "pduPwrMonitoringOutletNumPdu{$char}.0", 'PWTv1-MIB')) {
        $oids = snmpwalk_cache_oid($device, 'pduPwrMonitoringOutletStatusTablePdu' . $char, [], 'PWTv1-MIB');
        $oids = snmpwalk_cache_oid($device, 'pduPwrMonitoringOutletCfgTablePdu' . $char, $oids, 'PWTv1-MIB');

        $name = "Pdu $char";
        foreach ($oids as $index => $entry) {
            // PDU reports all outlets as "normal" status, but really only as in count
            if ($index > $outlet_count) {
                break;
            }

            $descr    = "Outlet $index State ($name)";
            $oid_name = "outletPdu{$char}State";
            $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
            $value    = $entry[$oid_name];
            $options  = [];
            discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'outletPduState', $descr, $value, $options);

            $descr    = "Outlet $index ($name)";
            $oid_name = "outletPdu{$char}Current";
            $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
            $value    = $entry[$oid_name];
            $scale    = 0.01;
            $options  = [
              'limit_high'      => $entry["outletCfgPdu{$char}OverCurrCritical"] * 0.1,
              'limit_high_warn' => $entry["outletCfgPdu{$char}OverCurrWarning"] * 0.1
            ];
            discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

            $descr    = "Outlet $index ($name)";
            $oid_name = "outletPdu{$char}Power";
            $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
            $value    = $entry[$oid_name];
            $scale    = 0.1;
            $options  = [
              'limit_high'      => $entry["outletCfgPdu{$char}OverPwrCritical"],
              'limit_high_warn' => $entry["outletCfgPdu{$char}OverPwrWarning"]
            ];
            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

            $descr    = "Outlet $index ($name)";
            $oid_name = "outletPdu{$char}Energy";
            $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
            $value    = $entry[$oid_name];
            $scale    = 1000;
            $options  = [];
            if ($value > 0) {
                discover_counter($device, 'energy', $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, $options);
            }

            $descr    = "Outlet $index ($name)";
            $oid_name = "outletPdu{$char}Status";
            $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
            $value    = $entry[$oid_name];
            $options  = [];
            discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'inletStatus', $descr, $value, $options);
        }
    }
}
unset($inlet_count, $phase_count, $oids, $outlet_count);

// EOF
