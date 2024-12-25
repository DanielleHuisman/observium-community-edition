<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

/// Collect data about inputs :

$inputs   = snmpwalk_cache_twopart_oid($device, 'inputTable', [], 'EATON-EPDU-MIB');
$inputs   = snmpwalk_cache_twopart_oid($device, 'inputTotal', $inputs, 'EATON-EPDU-MIB');

$inputs_o = snmpwalk_cache_threepart_oid($device, 'inputVoltageTable', [], 'EATON-EPDU-MIB');
$inputs_o = snmpwalk_cache_threepart_oid($device, 'inputCurrentTable', $inputs_o, 'EATON-EPDU-MIB');
$inputs_o = snmpwalk_cache_threepart_oid($device, 'inputPowerTable',   $inputs_o, 'EATON-EPDU-MIB');

foreach ($inputs as $unit_id => $unit_data) {
    //echo "Unit $unit_id".PHP_EOL;

    foreach ($unit_data as $input_id => $input_data) {
        //echo "  Input $input_id".PHP_EOL;

        $input_index = $unit_id . "." . $input_id;
        $descr       = "Unit $unit_id Input $input_id";

        if (isset($input_data['inputFrequency'])) {
            $oid_num = ".1.3.6.1.4.1.534.6.6.7.3.1.1.3." . $input_index;
            discover_sensor('frequency', $device, $oid_num, "inputFrequency.$input_index", 'eaton-epdu-mib', "$descr Frequency", 0.1, $input_data['inputFrequency']);
        }

        if (isset($input_data['inputFrequencyStatus'])) {
            discover_status($device, ".1.3.6.1.4.1.534.6.6.7.3.1.1.4." . $input_index, "inputFrequencyStatus." . $input_index, 'inputFrequencyStatus', "$descr Frequency Status", $input_data['inputFrequencyStatus'], ['entPhysicalClass' => 'input']);
        }

        if (isset($input_data['inputTotalVA']) && is_numeric($input_data['inputTotalVA'])) {
            $oid   = ".1.3.6.1.4.1.534.6.6.7.3.5.1.3." . $input_index;
            $value = $input_data['inputTotalVA'];
            discover_sensor('apower', $device, $oid, "inputTotalVA.$input_index", 'eaton-epdu-mib', "$descr Total", 1, $value);
        }

        if (isset($input_data['inputTotalWatts']) && is_numeric($input_data['inputTotalWatts'])) {
            $oid   = ".1.3.6.1.4.1.534.6.6.7.3.5.1.4." . $input_index;
            $value = $input_data['inputTotalWatts'];
            discover_sensor('power', $device, $oid, "inputTotalWatts.$input_index", 'eaton-epdu-mib', "$descr Total", 1, $value);
        }

        if (isset($input_data['inputTotalWh']) && is_numeric($input_data['inputTotalWh'])) {
            $oid_num  = ".1.3.6.1.4.1.534.6.6.7.3.5.1.5." . $input_index;
            $oid_name = 'inputTotalWh';
            $value    = $input_data[$oid_name];
            discover_counter($device, 'energy', $mib, $oid_name, $oid_num, $input_index, "$descr Total", 1, $value);
        }

        if (isset($input_data['inputTotalPowerFactor']) && is_numeric($input_data['inputTotalPowerFactor'])) {
            $oid   = ".1.3.6.1.4.1.534.6.6.7.3.5.1.7." . $input_index;
            $value = $input_data['inputTotalPowerFactor'];
            discover_sensor('powerfactor', $device, $oid, "inputTotalPowerFactor.$input_index", 'eaton-epdu-mib', "$descr Total", 0.001, $value);
        }

        if (isset($input_data['inputTotalVAR']) && is_numeric($input_data['inputTotalVAR'])) {
            $oid   = ".1.3.6.1.4.1.534.6.6.7.3.5.1.8." . $input_index;
            $value = $input_data['inputTotalVAR'];
            discover_sensor('rpower', $device, $oid, "inputTotalVAR.$input_index", 'eaton-epdu-mib', "$descr Total", 1, $value);
        }
        
        if (is_array($inputs_o[$unit_id][$input_id])) {

            $num_phase = safe_count($inputs_o[$unit_id][$input_id]);

            foreach ($inputs_o[$unit_id][$input_id] as $id => $entry) {
                //print_r($entry);

                $entry_oid = $input_index . "." . $id;

                if ($num_phase > 1) {
                    $options  = [
                        'measured_entity_label' => "Unit $unit_id Input $input_id " . $entry['inputPowerMeasType'],
                        'measured_class' => 'phase'
                    ];
                } else {
                    $options = [];
                }

                if (isset($entry['inputVoltage']) && is_numeric($entry['inputVoltage'])) {
                    $descr        = "Unit $unit_id Input $input_id " . $entry['inputVoltageMeasType'];
                    $oid          = ".1.3.6.1.4.1.534.6.6.7.3.2.1.3." . $entry_oid;
                    $status_oid   = ".1.3.6.1.4.1.534.6.6.7.3.2.1.4." . $entry_oid;
                    $status_descr = $descr . " Voltage";
                    $value        = $entry['inputVoltage'];
                    $status_value = $entry['inputVoltageThStatus'];
                    $limits       = ['limit_low'  => $entry['inputVoltageThLowerCritical'] * 0.001, 'limit_low_warn' => $entry['inputVoltageThLowerWarning'] * 0.001,
                                     'limit_high' => $entry['inputVoltageThUpperCritical'] * 0.001, 'limit_high_warn' => $entry['inputVoltageThUpperWarning'] * 0.001];

                    discover_sensor('voltage', $device, $oid, "inputVoltage.$entry_oid", 'eaton-epdu-mib', $descr, 0.001, $value, array_merge($options, $limits));
                    discover_status($device, $status_oid, "inputVoltageThStatus." . $entry_oid, 'inputVoltageThStatus', $status_descr, $status_value, ['entPhysicalClass' => 'input']);
                }

                if (isset($entry['inputCurrent']) && is_numeric($entry['inputCurrent'])) {
                    $descr        = "Unit $unit_id Input $input_id " . $entry['inputCurrentMeasType'] . " (" . $entry['inputCurrentCapacity'] * 0.001 . "A)";
                    $oid          = ".1.3.6.1.4.1.534.6.6.7.3.3.1.4." . $entry_oid;
                    $value        = $entry['inputCurrent'];
                    $status_oid   = ".1.3.6.1.4.1.534.6.6.7.3.3.1.5." . $entry_oid;
                    $status_value = $entry['inputCurrentThStatus'];
                    $status_descr = $descr . " Current";

                    $limits = ['limit_low'  => $entry['inputCurrentThLowerCritical'] * 0.001, 'limit_low_warn' => $entry['inputCurrentThLowerWarning'] * 0.001,
                               'limit_high' => $entry['inputCurrentThUpperCritical'] * 0.001, 'limit_high_warn' => $entry['inputCurrentThUpperWarning'] * 0.001];

                    discover_sensor('current', $device, $oid, "inputCurrent.$entry_oid", 'eaton-epdu-mib', $descr, 0.001, $value, array_merge($options, $limits));
                    discover_status($device, $status_oid, "inputCurrentThStatus." . $entry_oid, 'inputCurrentThStatus', $status_descr, $status_value, ['entPhysicalClass' => 'input']);

                }

                if (isset($entry['inputVA']) && is_numeric($entry['inputVA'])) {
                    $descr = "Unit $unit_id Input $input_id " . $entry['inputPowerMeasType'];
                    $oid   = ".1.3.6.1.4.1.534.6.6.7.3.4.1.3." . $entry_oid;
                    $value = $entry['inputVA'];
                    discover_sensor('apower', $device, $oid, "inputVA.$entry_oid", 'eaton-epdu-mib', $descr, 1, $value, $options);
                }

                if (isset($entry['inputWatts']) && is_numeric($entry['inputWatts'])) {
                    $descr = "Unit $unit_id Input $input_id " . $entry['inputPowerMeasType'];
                    $oid   = ".1.3.6.1.4.1.534.6.6.7.3.4.1.4." . $entry_oid;
                    $value = $entry['inputWatts'];
                    discover_sensor('power', $device, $oid, "inputWatts.$entry_oid", 'eaton-epdu-mib', $descr, 1, $value, $options);
                }

                if (isset($entry['inputWh']) && is_numeric($entry['inputWh'])) {
                    $oid_num  = ".1.3.6.1.4.1.534.6.6.7.3.4.1.5." . $entry_oid;
                    $oid_name = 'inputWh';
                    $value    = $entry[$oid_name];
                    discover_counter($device, 'energy', $mib, $oid_name, $oid_num, $entry_oid, $descr, 1, $value, $options);
                }

                if (isset($entry['inputPowerFactor']) && is_numeric($entry['inputPowerFactor'])) {
                    $descr = "Unit $unit_id Input $input_id " . $entry['inputPowerMeasType'];
                    $oid   = ".1.3.6.1.4.1.534.6.6.7.3.4.1.7." . $entry_oid;
                    $value = $entry['inputPowerFactor'];
                    discover_sensor('powerfactor', $device, $oid, "inputPowerFactor.$entry_oid", 'eaton-epdu-mib', $descr, 0.001, $value, $options);
                }

                if (isset($entry['inputVAR']) && is_numeric($entry['inputVAR'])) {
                    $descr = "Unit $unit_id Input $input_id " . $entry['inputPowerMeasType'];
                    $oid   = ".1.3.6.1.4.1.534.6.6.7.3.4.1.8." . $entry_oid;
                    $value = $entry['inputVAR'];
                    discover_sensor('rpower', $device, $oid, "inputVAR.$entry_oid", 'eaton-epdu-mib', $descr, 1, $value, $options);
                }

            }
        }
    }
}

// Collect data about outputs

// EATON-EPDU-MIB::outletID.0.2 = STRING: 2
// EATON-EPDU-MIB::outletName.0.2 = STRING: "Outlet A2"
// EATON-EPDU-MIB::outletParentCount.0.2 = INTEGER: 1
// EATON-EPDU-MIB::outletType.0.2 = INTEGER: iecC13(1)
// EATON-EPDU-MIB::outletDesignator.0.2 = STRING: A2
// EATON-EPDU-MIB::outletPhaseID.0.2 = INTEGER: phase1toN(2)
// EATON-EPDU-MIB::outletCurrentCapacity.0.2 = INTEGER: 10000
// EATON-EPDU-MIB::outletCurrent.0.2 = INTEGER: 815
// EATON-EPDU-MIB::outletCurrentThStatus.0.2 = INTEGER: good(0)
// EATON-EPDU-MIB::outletCurrentThLowerWarning.0.2 = INTEGER: 0
// EATON-EPDU-MIB::outletCurrentThLowerCritical.0.2 = INTEGER: -1
// EATON-EPDU-MIB::outletCurrentThUpperWarning.0.2 = INTEGER: 8000
// EATON-EPDU-MIB::outletCurrentThUpperCritical.0.2 = INTEGER: 10000
// EATON-EPDU-MIB::outletCurrentCrestFactor.0.2 = INTEGER: 2355
// EATON-EPDU-MIB::outletCurrentPercentLoad.0.2 = INTEGER: -1
// EATON-EPDU-MIB::outletVA.0.2 = INTEGER: 199
// EATON-EPDU-MIB::outletWatts.0.2 = INTEGER: 177
// EATON-EPDU-MIB::outletWh.0.2 = Gauge32: 21931
// EATON-EPDU-MIB::outletWhTimer.0.2 = Wrong Type (should be Counter32): Gauge32: 1663159425
// EATON-EPDU-MIB::outletPowerFactor.0.2 = INTEGER: -892
// EATON-EPDU-MIB::outletVAR.0.2 = INTEGER: -90
// EATON-EPDU-MIB::outletControlStatus.0.2 = INTEGER: on(1)
// EATON-EPDU-MIB::outletControlOffCmd.0.2 = INTEGER: -1
// EATON-EPDU-MIB::outletControlOnCmd.0.2 = INTEGER: -1
// EATON-EPDU-MIB::outletControlRebootCmd.0.2 = INTEGER: -1
// EATON-EPDU-MIB::outletControlPowerOnState.0.2 = INTEGER: lastState(2)
// EATON-EPDU-MIB::outletControlSequenceDelay.0.2 = INTEGER: 2
// EATON-EPDU-MIB::outletControlRebootOffTime.0.2 = INTEGER: 10
// EATON-EPDU-MIB::outletControlSwitchable.0.2 = INTEGER: switchable(1)
// EATON-EPDU-MIB::outletControlShutoffDelay.0.2 = INTEGER: 120

$outlets = snmpwalk_cache_twopart_oid($device, 'outletTable', [], 'EATON-EPDU-MIB');
$outlets = snmpwalk_cache_twopart_oid($device, 'outletCurrentTable', $outlets, 'EATON-EPDU-MIB');
$outlets = snmpwalk_cache_twopart_oid($device, 'outletPowerTable',   $outlets, 'EATON-EPDU-MIB');

foreach ($outlets as $unit_id => $unit_data) {
    foreach ($unit_data as $outlet_id => $entry) {

        $outlet_index    = $unit_id . "." . $outlet_id;
        $outlet_descr    = "Unit $unit_id " . $entry['outletName'] . " (" . $entry['outletType'] . ")";
        $outlet_capacity = $entry['outletCurrentCapacity'] * 0.001;

        $status_value  = $entry['outletCurrentThStatus'];

        $current_oid = '.1.3.6.1.4.1.534.6.6.7.6.4.1.3.'  . $outlet_index;
        $status_oid  = '.1.3.6.1.4.1.534.6.6.7.6.4.1.4.'  . $outlet_index;
        $crest_oid   = '.1.3.6.1.4.1.534.6.6.7.6.4.1.9.'  . $outlet_index;
        $percent_oid = '.1.3.6.1.4.1.534.6.6.7.6.4.1.10.' . $outlet_index;

        $options  = [
            'measured_entity_label' => "Unit $unit_id " . $entry['outletName'],
            'measured_class' => 'outlet',
            'entPhysicalClass' => 'outlet'
        ];

        discover_status($device, $status_oid, "outletCurrentThStatus." . $outlet_index, 'outletCurrentThStatus', $outlet_descr, $status_value, $options);

        $limits = [
            'limit_low'       => $entry['outletCurrentThLowerCritical'] * 0.001,
            'limit_low_warn'  => $entry['outletCurrentThLowerWarning'] * 0.001,
            'limit_high'      => $entry['outletCurrentThUpperCritical'] * 0.001,
            'limit_high_warn' => $entry['outletCurrentThUpperWarning'] * 0.001
        ];
        discover_sensor('current', $device, $current_oid, "outletCurrent.$outlet_index", 'eaton-epdu-mib', $outlet_descr, 0.001, $entry['outletCurrent'], array_merge($options, $limits));

        if ($entry['outletCurrentPercentLoad'] >= 0) {
            discover_sensor('load', $device, $percent_oid, "outletCurrentPercentLoad.$outlet_index", 'eaton-epdu-mib', $outlet_descr, 1, $entry['outletCurrentPercentLoad'], $options);
        }

        if ($entry['outletCurrentCrestFactor'] >= 0) {
            discover_sensor('crestfactor', $device, $crest_oid, "outletCurrentCrestFactor.$outlet_index", 'eaton-epdu-mib', $outlet_descr, 0.001, $entry['outletCurrentCrestFactor'], $options);
        }

        if (isset($entry['outletVA']) && $entry['outletVA'] >= 0) {
            $oid_num  = ".1.3.6.1.4.1.534.6.6.7.6.5.1.2." . $outlet_index;
            $oid_name = 'outletVA';
            $value    = $entry[$oid_name];
            discover_sensor_ng($device, 'apower', $mib, $oid_name, $oid_num, $outlet_index, $outlet_descr, 1, $value, $options);
        }

        if (isset($entry['outletWatts']) && $entry['outletWatts'] >= 0) {
            $oid_num  = ".1.3.6.1.4.1.534.6.6.7.6.5.1.3." . $outlet_index;
            $oid_name = 'outletWatts';
            $value    = $entry[$oid_name];
            discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $outlet_index, $outlet_descr, 1, $value, $options);
        }
    }
}

//print_r($outlets);

/// EOF
