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

// SPS2v1-MIB::pduDevMonInletIndex.1 = INTEGER: 0
// SPS2v1-MIB::pduDevMonInletName.1 = STRING: "PDU"
// SPS2v1-MIB::pduDevMonInletBid.1 = INTEGER: 50177
// SPS2v1-MIB::pduDevMonInletFwType.1 = INTEGER: 12289
// SPS2v1-MIB::pduDevMonInletVoltPh1.1 = INTEGER: 2329 0.1v
// SPS2v1-MIB::pduDevMonInletVoltPh2.1 = INTEGER: 0 0.1v
// SPS2v1-MIB::pduDevMonInletVoltPh3.1 = INTEGER: 0 0.1v
// SPS2v1-MIB::pduDevMonInletPfPh1B1.1 = INTEGER: 87
// SPS2v1-MIB::pduDevMonInletPfPh2B1.1 = INTEGER: 0
// SPS2v1-MIB::pduDevMonInletPfPh3B1.1 = INTEGER: 0
// SPS2v1-MIB::pduDevMonInletPfPh1B2.1 = INTEGER: 91
// SPS2v1-MIB::pduDevMonInletPfPh2B2.1 = INTEGER: 0
// SPS2v1-MIB::pduDevMonInletPfPh3B2.1 = INTEGER: 0
// SPS2v1-MIB::pduDevMonInletCurrentPh1B1.1 = INTEGER: 95 0.01A
// SPS2v1-MIB::pduDevMonInletCurrentPh2B1.1 = INTEGER: 0 0.01A
// SPS2v1-MIB::pduDevMonInletCurrentPh3B1.1 = INTEGER: 0 0.01A
// SPS2v1-MIB::pduDevMonInletCurrentPh1B2.1 = INTEGER: 103 0.01A
// SPS2v1-MIB::pduDevMonInletCurrentPh2B2.1 = INTEGER: 0 0.01A
// SPS2v1-MIB::pduDevMonInletCurrentPh3B2.1 = INTEGER: 0 0.01A
// SPS2v1-MIB::pduDevMonInletActPowerPh1.1 = INTEGER: 4447 0.1W
// SPS2v1-MIB::pduDevMonInletActPowerPh2.1 = INTEGER: 0 0.1W
// SPS2v1-MIB::pduDevMonInletActPowerPh3.1 = INTEGER: 0 0.1W
// SPS2v1-MIB::pduDevMonInletAppPowerPh1.1 = INTEGER: 4913 0.1VA
// SPS2v1-MIB::pduDevMonInletAppPowerPh2.1 = INTEGER: 0 0.1VA
// SPS2v1-MIB::pduDevMonInletAppPowerPh3.1 = INTEGER: 0 0.1VA
// SPS2v1-MIB::pduDevMonInletEnergyPh1.1 = INTEGER: 67257 KWH
// SPS2v1-MIB::pduDevMonInletEnergyPh2.1 = INTEGER: 0 KWH
// SPS2v1-MIB::pduDevMonInletEnergyPh3.1 = INTEGER: 0 KWH
// SPS2v1-MIB::pduDevMonInletEnergyTimePh1.1 = STRING: "01/01/1970 02:00:00"
// SPS2v1-MIB::pduDevMonInletEnergyTimePh2.1 = STRING: "01/01/1970 02:00:00"
// SPS2v1-MIB::pduDevMonInletEnergyTimePh3.1 = STRING: "01/01/1970 02:00:00"
// SPS2v1-MIB::pduDevMonInletLoadBalance.1 = INTEGER: 0 1%
// SPS2v1-MIB::pduDevMonInletEvtOverLoad.1 = INTEGER: normal(1)
// SPS2v1-MIB::pduDevMonInletEvtStatusPh1.1 = INTEGER: critical(3)
// SPS2v1-MIB::pduDevMonInletEvtStatusPh2.1 = INTEGER: normal(1)
// SPS2v1-MIB::pduDevMonInletEvtStatusPh3.1 = INTEGER: normal(1)
// SPS2v1-MIB::pduDevMonInletEvtLoadBalance.1 = INTEGER: normal(1)
// SPS2v1-MIB::pduDevMonInletRcmValid.1 = INTEGER: invalid(1)
// SPS2v1-MIB::pduDevMonInletRcmCurrent.1 = INTEGER: 0 0.1mA
// SPS2v1-MIB::pduDevMonInletRcmAlarm.1 = INTEGER: normal(1)
// SPS2v1-MIB::pduDevMonInletPowerShare.1 = INTEGER: inactive(1)

// SPS2v1-MIB::pduDevMgmtInletIndex.10 = INTEGER: 9
// SPS2v1-MIB::pduDevMgmtInletName.10 = STRING: "PDU"
// SPS2v1-MIB::pduDevMgmtInletEnergyClr.10 = INTEGER: nothing(1)
// SPS2v1-MIB::pduDevMgmtInletOverLoadWarn.10 = INTEGER: 2200 W
// SPS2v1-MIB::pduDevMgmtInletOverLoadCrit.10 = INTEGER: 3520 W
// SPS2v1-MIB::pduDevMgmtInletLoadBalanceWarn.10 = INTEGER: 25
// SPS2v1-MIB::pduDevMgmtInletLoadBalanceCrit.10 = INTEGER: 50
// SPS2v1-MIB::pduDevMgmtInletOverVoltWarnPh1.10 = INTEGER: 2450 0.1V
// SPS2v1-MIB::pduDevMgmtInletOverVoltWarnPh2.10 = INTEGER: 2450 0.1V
// SPS2v1-MIB::pduDevMgmtInletOverVoltWarnPh3.10 = INTEGER: 2450 0.1V
// SPS2v1-MIB::pduDevMgmtInletOverVoltCritPh1.10 = INTEGER: 2500 0.1V
// SPS2v1-MIB::pduDevMgmtInletOverVoltCritPh2.10 = INTEGER: 2500 0.1V
// SPS2v1-MIB::pduDevMgmtInletOverVoltCritPh3.10 = INTEGER: 2500 0.1V
// SPS2v1-MIB::pduDevMgmtInletOverCurrTotalWarnPh1.10 = INTEGER: 2600 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrTotalWarnPh2.10 = INTEGER: 2600 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrTotalWarnPh3.10 = INTEGER: 2600 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrTotalCritPh1.10 = INTEGER: 3200 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrTotalCritPh2.10 = INTEGER: 3200 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrTotalCritPh3.10 = INTEGER: 3200 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB1WarnPh1.10 = INTEGER: 1300 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB1WarnPh2.10 = INTEGER: 1300 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB1WarnPh3.10 = INTEGER: 1300 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB1CritPh1.10 = INTEGER: 1600 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB1CritPh2.10 = INTEGER: 1600 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB1CritPh3.10 = INTEGER: 1600 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB2WarnPh1.10 = INTEGER: 1300 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB2WarnPh2.10 = INTEGER: 1300 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB2WarnPh3.10 = INTEGER: 1300 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB2CritPh1.10 = INTEGER: 1600 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB2CritPh2.10 = INTEGER: 1600 0.01A
// SPS2v1-MIB::pduDevMgmtInletOverCurrB2CritPh3.10 = INTEGER: 1600 0.01A
// SPS2v1-MIB::pduDevMgmtInletRcmThld.10 = INTEGER: 20 1mA
// SPS2v1-MIB::pduDevMgmtInletOverPfB1WarnPh1.10 = INTEGER: 90 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB1WarnPh2.10 = INTEGER: 90 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB1WarnPh3.10 = INTEGER: 90 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB1CritPh1.10 = INTEGER: 80 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB1CritPh2.10 = INTEGER: 80 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB1CritPh3.10 = INTEGER: 80 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB2WarnPh1.10 = INTEGER: 90 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB2WarnPh2.10 = INTEGER: 90 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB2WarnPh3.10 = INTEGER: 90 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB2CritPh1.10 = INTEGER: 80 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB2CritPh2.10 = INTEGER: 80 0.1%
// SPS2v1-MIB::pduDevMgmtInletOverPfB2CritPh3.10 = INTEGER: 80 0.1%

$oids = snmpwalk_cache_oid($device, 'pduDevMonInletTable',     [], 'SPS2v1-MIB');
$oids = snmpwalk_cache_oid($device, 'pduDevMgmtInletTable', $oids, 'SPS2v1-MIB');
foreach ($oids as $index => $entry) {
    if ($entry['pduDevMonInletBid'] == 0 && $entry['pduDevMonInletFwType'] == 0) {
        unset($oids[$index]); // Remove unused entries
    }
}
print_debug_vars($oids);

$inlet_count = safe_count($oids);
foreach ($oids as $index => $entry) {
    $name = $inlet_count > 1 ? " #$index" : '';

    $phase_count = ($entry['pduDevMonInletVoltPh2'] > 0) || ($entry['pduDevMonInletVoltPh3'] > 0) ? 3 : 1;

    for ($phase = 1; $phase <= $phase_count; $phase++) {
        $phase_name = $phase_count > 1 ? " (Phase $phase)" : '';

        $descr    = "Inlet" . $name . $phase_name . ' B1';
        $oid_name = 'pduDevMonInletCurrentPh' . $phase . 'B1';
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.01;
        $options  = [
            'limit_high'      => $entry['pduDevMgmtInletOverCurrB1CritPh' . $phase] * $scale,
            'limit_high_warn' => $entry['pduDevMgmtInletOverCurrB1WarnPh' . $phase] * $scale
        ];
        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Inlet" . $name . $phase_name . ' B2';
        $oid_name = 'pduDevMonInletCurrentPh' . $phase . 'B2';
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.01;
        $options  = [
            'limit_high'      => $entry['pduDevMgmtInletOverCurrB2CritPh' . $phase] * $scale,
            'limit_high_warn' => $entry['pduDevMgmtInletOverCurrB2WarnPh' . $phase] * $scale
        ];
        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Inlet" . $name . $phase_name;
        $oid_name = 'pduDevMonInletVoltPh' . $phase;
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.1;
        $options  = [
            'limit_high'      => $entry['pduDevMgmtInletOverVoltCritPh' . $phase] * $scale,
            'limit_high_warn' => $entry['pduDevMgmtInletOverVoltWarnPh' . $phase] * $scale
        ];
        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Inlet" . $name . $phase_name . ' B1';
        $oid_name = 'pduDevMonInletPfPh' . $phase . 'B1';
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.01;
        $options  = [
            'limit_low'      => $entry['pduDevMgmtInletOverPfB1CritPh' . $phase] * $scale,
            'limit_low_warn' => $entry['pduDevMgmtInletOverPfB1WarnPh' . $phase] * $scale
        ];
        discover_sensor_ng($device, 'powerfactor', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Inlet" . $name . $phase_name . ' B2';
        $oid_name = 'pduDevMonInletPfPh' . $phase . 'B2';
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.01;
        $options  = [
            'limit_low'      => $entry['pduDevMgmtInletOverPfB2CritPh' . $phase] * $scale,
            'limit_low_warn' => $entry['pduDevMgmtInletOverPfB2WarnPh' . $phase] * $scale
        ];
        discover_sensor_ng($device, 'powerfactor', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Inlet" . $name . $phase_name;
        $oid_name = 'pduDevMonInletActPowerPh' . $phase;
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.1;
        discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

        $descr    = "Inlet" . $name . $phase_name;
        $oid_name = 'pduDevMonInletAppPowerPh' . $phase;
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.1;
        discover_sensor_ng($device, 'apower', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

        $descr    = "Inlet" . $name . $phase_name;
        $oid_name = 'pduDevMonInletEvtStatusPh' . $phase;
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'inletStatus', $descr, $value);

        $descr    = "Inlet" . $name . $phase_name;
        $oid_name = 'pduDevMonInletEnergyPh' . $phase;
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 1000;
        discover_counter($device, 'energy', $mib, $oid_name, $oid_num, $index, $descr, $scale, $value);
    }
}

if ($outlet_count = snmp_get_oid($device, "pduDevMonOutletMasterPortSize.0", 'SPS2v1-MIB')) {
    $oids = snmpwalk_cache_oid($device, 'pduDevMonOutletMasterPortTable', [], 'SPS2v1-MIB');
    $oids = snmpwalk_cache_oid($device, 'pduDevMgmtOutletMasterPortTable', $oids, 'SPS2v1-MIB');

    foreach ($oids as $index => $entry) {
        // PDU reports all outlets as "normal" status, but really only as in count
        if ($index > $outlet_count) {
            break;
        }

        if ($entry['pduDevMgmtOutletMasterPortValid'] === 'offline') {
            continue;
        }

        $descr    = "Outlet $index State";
        $oid_name = "duDevMonOutletMasterPortRlyState";
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, 'outletPduState', $descr, $value);

        if ($entry['pduDevMonOutletMasterPortRlyState'] === 'off') {
            continue;
        }

        $descr    = "Outlet $index";
        $oid_name = 'pduDevMonOutletMasterPortVolt';
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.1;
        $options  = [
            'measured_entity_label' => "Outlet $index",
            'measured_class' => 'outlet'
        ];
        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Outlet $index";
        $oid_name = 'pduDevMonOutletMasterPortCurrent';
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.01;
        $options  = [
            'limit_high'      => $entry['pduDevMgmtOutletMasterPortOverCurrCrit'] * $scale,
            'limit_high_warn' => $entry['pduDevMgmtOutletMasterPortOverCurrWarn'] * $scale,
            'measured_entity_label' => "Outlet $index",
            'measured_class' => 'outlet'
        ];
        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Outlet $index";
        $oid_name = 'pduDevMonOutletMasterPortActPower';
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.1;
        $options  = [
            'limit_high'      => $entry['pduDevMgmtOutletMasterPortOverPowerCrit'] * $scale,
            'limit_high_warn' => $entry['pduDevMgmtOutletMasterPortOverPowerWarn'] * $scale,
            'measured_entity_label' => "Outlet $index",
            'measured_class' => 'outlet'
        ];
        discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Outlet $index";
        $oid_name = 'pduDevMonOutletMasterPortActPower';
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 0.1;
        $options  = [
            'measured_entity_label' => "Outlet $index",
            'measured_class' => 'outlet'
        ];
        discover_sensor_ng($device, 'apower', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);

        $descr    = "Outlet $index";
        $oid_name = 'pduDevMonOutletMasterPortEnergy';
        $oid_num  = snmp_translate($oid_name, $mib) . ".$index";
        $value    = $entry[$oid_name];
        $scale    = 1000;
        $options  = [
            'measured_entity_label' => "Outlet $index",
            'measured_class' => 'outlet'
        ];
        discover_counter($device, 'energy', $mib, $oid_name, $oid_num, $index, $descr, $scale, $value, $options);
    }
}

// EOF
