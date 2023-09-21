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

$oids = snmpwalk_cache_oid($device, 'sfpDmiSlotIndex', [], $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiMediaPortIndex', $oids, $mib);

$oids = snmpwalk_cache_oid($device, 'sfpDmiRealTimeTemp', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiHighTempAlarm', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiLowTempAlarm', $oids, $mib);

$oids = snmpwalk_cache_oid($device, 'sfpDmiRealTimeVolt', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiHighVoltAlarm', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiLowVoltAlarm', $oids, $mib);

$oids = snmpwalk_cache_oid($device, 'sfpDmiRealTimeTxBias', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiHighTxBiasAlarm', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiLowTxBiasAlarm', $oids, $mib);

$oids = snmpwalk_cache_oid($device, 'sfpDmiRealTimeTxPower', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiHighTxPowerAlarm', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiLowTxPowerAlarm', $oids, $mib);

$oids = snmpwalk_cache_oid($device, 'sfpDmiRealTimeRxPower', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiHighRxPowerAlarm', $oids, $mib);
$oids = snmpwalk_cache_oid($device, 'sfpDmiLowRxPowerAlarm', $oids, $mib);

$scale = 1;
foreach ($oids as $index => $entry) {
    $ifDescr = "slot" . $entry['sfpDmiSlotIndex'] . "_port" . $entry['sfpDmiMediaPortIndex'];

    print "Index: $index\n";
    print "Port: $ifDescr\n";

    /*
      if (is_array($ifDescr))
      {
        $options['measured_class']  = 'port';
        $options['measured_entity'] = $ifDescr;
      }
    */

    if (is_numeric($entry['sfpDmiRealTimeTemp'])) {
        $descr   = $ifDescr . ' Temperature';
        $oid     = ".1.3.6.1.4.1.1966.21.1.1.1.1.5.1.12.$index";
        $value   = $entry['sfpDmiRealTimeTemp'];
        $options = ['limit_high' => $entry['sfpDmiHighTempAlarm'],
                    'limit_low'  => $entry['sfpDmiLowTempAlarm']];


        discover_sensor('temperature', $device, $oid, $index, 'perle-mcr-mgt-mib-temperature', $descr, $scale, $value, $options);
    }

    if (is_numeric($entry['sfpDmiRealTimeVolt'])) {
        $descr   = $ifDescr . ' Voltage';
        $oid     = ".1.3.6.1.4.1.1966.21.1.1.1.1.5.1.18.$index";
        $value   = $entry['sfpDmiRealTimeVolt'];
        $options = ['limit_high' => $entry['sfpDmiHighVoltAlarm'],
                    'limit_low'  => $entry['sfpDmiLowVoltAlarm']];


        discover_sensor('voltage', $device, $oid, $index, 'perle-mcr-mgt-mib-voltage', $descr, $scale, $value, $options);
    }

    if (is_numeric($entry['sfpDmiRealTimeTxBias'])) {
        $descr   = $ifDescr . ' TX Bias';
        $oid     = ".1.3.6.1.4.1.1966.21.1.1.1.1.5.1.24.$index";
        $value   = $entry['sfpDmiRealTimeTxBias'];
        $options = ['limit_high' => $entry['sfpDmiHighTxBiasAlarm'] * 0.001,
                    'limit_low'  => $entry['sfpDmiLowTxBiasAlarm'] * 0.001];


        discover_sensor('current', $device, $oid, $index, 'perle-mcr-mgt-mib-tx-bias', $descr, 0.001, $value, $options);
    }

    if (is_numeric($entry['sfpDmiRealTimeTxPower'])) {
        $descr   = $ifDescr . ' TX Power';
        $oid     = ".1.3.6.1.4.1.1966.21.1.1.1.1.5.1.30.$index";
        $value   = $entry['sfpDmiRealTimeTxPower'];
        $options = ['limit_high' => $entry['sfpDmiHighTxPowerAlarm'],
                    'limit_low'  => $entry['sfpDmiLowTxPowerAlarm']];


        discover_sensor('dbm', $device, $oid, $index, 'perle-mcr-mgt-mib-tx-power', $descr, $scale, $value, $options);
    }

    if (is_numeric($entry['sfpDmiRealTimeRxPower']) || $entry['sfpDmiRealTimeRxPower'] == "-inf") {
        $descr = $ifDescr . ' RX Power';
        $oid   = ".1.3.6.1.4.1.1966.21.1.1.1.1.5.1.36.$index";
        if (is_numeric($entry['sfpDmiRealTimeRxPower'])) {
            $value = $entry['sfpDmiRealTimeRxPower'];
        } else {
            $value = -50;
        }
        $options = ['limit_high' => $entry['sfpDmiHighRxPowerAlarm'],
                    'limit_low'  => $entry['sfpDmiLowRxPowerAlarm']];

        discover_sensor('dbm', $device, $oid, $index, 'perle-mcr-mgt-mib-rx-power', $descr, $scale, $value, $options);
    }


}

// EOF
