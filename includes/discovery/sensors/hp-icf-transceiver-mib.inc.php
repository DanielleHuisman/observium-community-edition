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

$oids = snmpwalk_multipart_oid($device, 'hpicfXcvrChannelInfoEntry', [], 'HP-ICF-TRANSCEIVER-MIB');
if (!snmp_status()) {
    return;
}
print_debug_vars($oids);

// This oids already cached by definition discovery
$extra      = [
  'hpicfXcvrModel', 'hpicfXcvrType', 'hpicfXcvrWavelength',
  'hpicfXcvrBiasLoAlarm', 'hpicfXcvrBiasLoWarn', 'hpicfXcvrBiasHiAlarm', 'hpicfXcvrBiasHiWarn',
  'hpicfXcvrPwrOutLoAlarm', 'hpicfXcvrPwrOutLoWarn', 'hpicfXcvrPwrOutHiAlarm', 'hpicfXcvrPwrOutHiWarn',
  'hpicfXcvrRcvPwrLoAlarm', 'hpicfXcvrRcvPwrLoWarn', 'hpicfXcvrRcvPwrHiAlarm', 'hpicfXcvrRcvPwrHiWarn'
];
$oids_extra = [];
foreach ($extra as $oid) {
    $oids_extra = snmp_cache_table($device, $oid, $oids_extra, 'HP-ICF-TRANSCEIVER-MIB');
}
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelTxBias.353.1 = Gauge32: 34292 microamps
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelTxBias.353.2 = Gauge32: 34960 microamps
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelTxBias.354.1 = Gauge32: 34954 microamps
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelTxBias.354.2 = Gauge32: 34564 microamps
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelTxPower.353.1 = INTEGER: -254 thousandths of dBm
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelTxPower.353.2 = INTEGER: 15 thousandths of dBm
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelTxPower.354.1 = INTEGER: -843 thousandths of dBm
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelTxPower.354.2 = INTEGER: -348 thousandths of dBm
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelRxPower.353.1 = INTEGER: -1471 thousandths of dBm
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelRxPower.353.2 = INTEGER: -1551 thousandths of dBm
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelRxPower.354.1 = INTEGER: -1214 thousandths of dBm
// HP-ICF-TRANSCEIVER-MIB::hpicfXcvrChannelRxPower.354.2 = INTEGER: -1318 thousandths of dBm

foreach ($oids as $ifIndex => $lanes) {
    foreach ($lanes as $lane => $entry) {
        $index = $ifIndex . '.' . $lane;

        if (isset($oids_extra[$ifIndex])) {
            $entry = array_merge($entry, $oids_extra[$ifIndex]);
        }
        $entry['ifIndex'] = $ifIndex;
        $entry['index']   = $index;
        //print_debug_vars($entry);

        $match   = ['measured_match' => ['entity_type' => 'port', 'field' => 'ifIndex', 'match' => '%ifIndex%']];
        $options = entity_measured_match_definition($device, $match, $entry);

        $name = $options['port_label'] . ' Lane ' . $lane;

        // Tx Bias
        $descr    = $name . ' Tx Bias (' . $entry['hpicfXcvrModel'] . ' ' . $entry['hpicfXcvrType'] . ', ' . $entry['hpicfXcvrWavelength'] . ')';
        $descr    = preg_replace('/ *\?\? */', '', $descr);
        $class    = 'current';
        $oid_name = 'hpicfXcvrChannelTxBias';
        $oid_num  = '.1.3.6.1.4.1.11.2.14.11.5.1.82.1.1.2.1.2.' . $index;
        $scale    = 0.000001;
        $value    = $entry[$oid_name];

        $limits_def = [
          'class'               => 'current',
          'limit_scale'         => 0.000001,
          'oid_limit_low'       => 'hpicfXcvrBiasLoAlarm',
          'oid_limit_low_warn'  => 'hpicfXcvrBiasLoWarn',
          'oid_limit_high'      => 'hpicfXcvrBiasHiAlarm',
          'oid_limit_high_warn' => 'hpicfXcvrBiasHiWarn',
        ];
        $limits     = entity_limits_definition($device, $limits_def, $entry, $scale);

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

        // Tx Power
        $descr    = $name . ' Tx Power (' . $entry['hpicfXcvrModel'] . ' ' . $entry['hpicfXcvrType'] . ', ' . $entry['hpicfXcvrWavelength'] . ')';
        $descr    = preg_replace('/ *\?\? */', '', $descr);
        $class    = 'dbm';
        $oid_name = 'hpicfXcvrChannelTxPower';
        $oid_num  = '.1.3.6.1.4.1.11.2.14.11.5.1.82.1.1.2.1.3.' . $index;
        $scale    = 0.001;
        $value    = $entry[$oid_name];

        $limits_def = [
          'class'                   => 'dbm', // class required for unit conversion
          'limit_scale'             => 0.0000001,
          'limit_unit'              => 'W', // Strange choice, limits in different unit
          'oid_limit_low'           => 'hpicfXcvrPwrOutLoAlarm',
          'oid_limit_low_warn'      => 'hpicfXcvrPwrOutLoWarn',
          'oid_limit_high'          => 'hpicfXcvrPwrOutHiAlarm',
          'oid_limit_high_warn'     => 'hpicfXcvrPwrOutHiWarn',
          'invalid_limit_low'       => 0,
          'invalid_limit_low_warn'  => 0,
          'invalid_limit_high'      => 0,
          'invalid_limit_high_warn' => 0,
        ];

        $limits = entity_limits_definition($device, $limits_def, $entry, $scale);

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));

        // Rx Power
        $descr    = $name . ' Rx Power (' . $entry['hpicfXcvrModel'] . ' ' . $entry['hpicfXcvrType'] . ', ' . $entry['hpicfXcvrWavelength'] . ')';
        $descr    = preg_replace('/ *\?\? */', '', $descr);
        $class    = 'dbm';
        $oid_name = 'hpicfXcvrChannelRxPower';
        $oid_num  = '.1.3.6.1.4.1.11.2.14.11.5.1.82.1.1.2.1.4.' . $index;
        $scale    = 0.001;
        $value    = $entry[$oid_name];

        $limits_def = [
          'class'               => 'dbm', // class required for unit conversion
          'limit_scale'         => 0.0000001,
          'limit_unit'          => 'W', // Strange choice, limits in different unit
          'oid_limit_low'       => 'hpicfXcvrRcvPwrLoAlarm',
          'oid_limit_low_warn'  => 'hpicfXcvrRcvPwrLoWarn',
          'oid_limit_high'      => 'hpicfXcvrRcvPwrHiAlarm',
          'oid_limit_high_warn' => 'hpicfXcvrRcvPwrHiWarn',
        ];
        $limits     = entity_limits_definition($device, $limits_def, $entry, $scale);

        discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, array_merge($options, $limits));
    }
}

// EOF
