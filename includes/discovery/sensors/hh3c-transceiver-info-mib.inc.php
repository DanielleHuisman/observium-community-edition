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

// hh3cTransceiverHardwareType.54 = STRING: "MM"
// hh3cTransceiverType.54 = STRING: "10G_BASE_SR_SFP"
// hh3cTransceiverWaveLength.54 = INTEGER: 850
// hh3cTransceiverVendorName.54 = STRING: "HP"
// hh3cTransceiverSerialNumber.54 = STRING: "210231A0A6X103000755"
// hh3cTransceiverFiberDiameterType.54 = INTEGER: fiber50(2)
// hh3cTransceiverTransferDistance.54 = INTEGER: 80
// hh3cTransceiverDiagnostic.54 = INTEGER: true(1)

// FIXME; Above data is (currently) not used here, serial number is present in ENTITY-MIB for inventory, other data is not.
//        Possibly useful to include there as well (somehow?)?
// FIXME -- definitions


$oids = snmpwalk_cache_oid($device, 'hh3cTransceiverInfoTable ', [], 'HH3C-TRANSCEIVER-INFO-MIB');

// Index = ifIndex
foreach ($oids as $index => $entry) {
    $options        = [];
    $entry['index'] = $index;

    // Associate measured port
    $def = ['measured_match' => ['match' => '%index%', 'field' => 'ifIndex', 'entity_type' => 'port']];
    if ($measured = entity_measured_match_definition($device, $def, $entry)) {
        $options = $measured;
    } else {
        $options['port_label'] = "Port $index";
    }

    $transceiver = ' (' . rewrite_vendor($entry['hh3cTransceiverVendorName']) . ' ' . $entry['hh3cTransceiverHardwareType'] .
                   ' ' . $entry['hh3cTransceiverType'] . ' ' . $entry['hh3cTransceiverWaveLength'] . 'nm)';

    // hh3cTransceiverTemperature.54 = INTEGER: 39
    $descr    = $options['port_label'] . ' Temperature';
    $descr    .= $transceiver;
    $value    = $entry['hh3cTransceiverTemperature'];
    $scale    = 1;
    $oid      = ".1.3.6.1.4.1.25506.2.70.1.1.1.15.$index";
    $oid_name = 'hh3cTransceiverTemperature';

    if ($value != 0 && $value < 2147483647) {
        $scale_limit           = 0.001; // Limits have different scale
        $limits                = [
          'limit_high'      => $entry['hh3cTransceiverTempHiAlarm'] * $scale_limit,
          'limit_high_warn' => $entry['hh3cTransceiverTempHiWarn'] * $scale_limit,
          'limit_low_warn'  => $entry['hh3cTransceiverTempLoWarn'] * $scale_limit,
          'limit_low'       => $entry['hh3cTransceiverTempLoAlarm'] * $scale_limit,
        ];
        $options['rename_rrd'] = "hh3c-transceiver-info-mib-hh3cTransceiverTemperature.$index";
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
    }

    // hh3cTransceiverBiasCurrent.54 = INTEGER: 532
    $descr    = $options['port_label'] . ' Bias';
    $descr    .= $transceiver;
    $value    = $entry['hh3cTransceiverBiasCurrent'];
    $scale    = 0.00001;
    $oid      = ".1.3.6.1.4.1.25506.2.70.1.1.1.17.$index";
    $oid_name = 'hh3cTransceiverBiasCurrent';

    if ($value != 0 && $value < 2147483647) {
        $scale_limit           = 0.000001; // Limits have different scale
        $limits                = [
          'limit_high'      => $entry['hh3cTransceiverBiasHiAlarm'] * $scale_limit,
          'limit_high_warn' => $entry['hh3cTransceiverBiasHiWarn'] * $scale_limit,
          'limit_low_warn'  => $entry['hh3cTransceiverBiasLoWarn'] * $scale_limit,
          'limit_low'       => $entry['hh3cTransceiverBiasLoAlarm'] * $scale_limit,
        ];
        $options['rename_rrd'] = "hh3c-transceiver-info-mib-hh3cTransceiverBiasCurrent.$index";
        discover_sensor_ng($device, 'current', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
    }

    // hh3cTransceiverVoltage.54 = INTEGER: 325
    $descr    = $options['port_label'] . ' Voltage';
    $descr    .= $transceiver;
    $value    = $entry['hh3cTransceiverVoltage'];
    $scale    = 0.01;
    $oid      = ".1.3.6.1.4.1.25506.2.70.1.1.1.16.$index";
    $oid_name = 'hh3cTransceiverVoltage';

    if ($value != 0 && $value < 2147483647) {
        $scale_limit           = 0.0001; // Limits have different scale
        $limits                = [
          'limit_high'      => $entry['hh3cTransceiverVccHiAlarm'] * $scale_limit,
          'limit_high_warn' => $entry['hh3cTransceiverVccHiWarn'] * $scale_limit,
          'limit_low_warn'  => $entry['hh3cTransceiverVccLoWarn'] * $scale_limit,
          'limit_low'       => $entry['hh3cTransceiverVccLoAlarm'] * $scale_limit,
        ];
        $options['rename_rrd'] = "hh3c-transceiver-info-mib-hh3cTransceiverVoltage.$index";
        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
    }

    // hh3cTransceiverCurTXPower.54 = INTEGER: -251
    $descr    = $options['port_label'] . ' TX Power';
    $descr    .= $transceiver;
    $value    = $entry['hh3cTransceiverCurTXPower'];
    $scale    = 0.01;
    $oid      = ".1.3.6.1.4.1.25506.2.70.1.1.1.9.$index";
    $oid_name = 'hh3cTransceiverCurTXPower';

    if ($value != 0 && $value < 2147483647) {
        // derp, power in dbm, but limits in watts....
        // 22387 -> 0.0022387W -> 3.5dBm
        $scale_limit           = 0.0000001; // Limits have different scale
        $limits                = [
          'limit_high'      => value_to_si($entry['hh3cTransceiverPwrOutHiAlarm'] * $scale_limit, 'W', 'dbm'),
          'limit_high_warn' => value_to_si($entry['hh3cTransceiverPwrOutHiWarn'] * $scale_limit, 'W', 'dbm'),
          'limit_low_warn'  => value_to_si($entry['hh3cTransceiverPwrOutLoWarn'] * $scale_limit, 'W', 'dbm'),
          'limit_low'       => value_to_si($entry['hh3cTransceiverPwrOutLoAlarm'] * $scale_limit, 'W', 'dbm'),
        ];
        $options['rename_rrd'] = "hh3c-transceiver-info-mib-hh3cTransceiverCurTXPower.$index";
        discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
    }

    // hh3cTransceiverCurRXPower.54 = INTEGER: -834
    $descr    = $options['port_label'] . ' RX Power';
    $descr    .= $transceiver;
    $value    = $entry['hh3cTransceiverCurRXPower'];
    $scale    = 0.01;
    $oid      = ".1.3.6.1.4.1.25506.2.70.1.1.1.12.$index";
    $oid_name = 'hh3cTransceiverCurRXPower';

    if ($value != 0 && $value < 2147483647) {
        // derp, power in dbm, but limits in watts....
        // 22387 -> 0.0022387W -> 3.5dBm
        $scale_limit           = 0.0000001; // Limits have different scale
        $limits                = [
          'limit_high'      => value_to_si($entry['hh3cTransceiverRcvPwrHiAlarm'] * $scale_limit, 'W', 'dbm'),
          'limit_high_warn' => value_to_si($entry['hh3cTransceiverRcvPwrHiWarn'] * $scale_limit, 'W', 'dbm'),
          'limit_low_warn'  => value_to_si($entry['hh3cTransceiverRcvPwrLoWarn'] * $scale_limit, 'W', 'dbm'),
          'limit_low'       => value_to_si($entry['hh3cTransceiverRcvPwrLoAlarm'] * $scale_limit, 'W', 'dbm'),
        ];
        $options['rename_rrd'] = "hh3c-transceiver-info-mib-hh3cTransceiverCurRXPower.$index";
        discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
    }
}

// EOF
