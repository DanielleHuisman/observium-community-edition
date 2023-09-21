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

//FA-EXT-MIB::swSfpTemperature.'...... .........'.1 = STRING: "51     " centigrade
//FA-EXT-MIB::swSfpTemperature.'...... .........'.2 = STRING: "52     " centigrade
//FA-EXT-MIB::swSfpVoltage.'...... .........'.1 = STRING: "3319.1 " milli voltage
//FA-EXT-MIB::swSfpVoltage.'...... .........'.2 = STRING: "3304.6 " milli voltage
//FA-EXT-MIB::swSfpCurrent.'...... .........'.1 = STRING: "8.892  " milli amphere
//FA-EXT-MIB::swSfpCurrent.'...... .........'.2 = STRING: "8.086  " milli amphere
//FA-EXT-MIB::swSfpRxPower.'...... .........'.1 = STRING: "-4.6   " dBm
//FA-EXT-MIB::swSfpRxPower.'...... .........'.2 = STRING: "-inf   " dBm
//FA-EXT-MIB::swSfpTxPower.'...... .........'.1 = STRING: "-4.6   " dBm
//FA-EXT-MIB::swSfpTxPower.'...... .........'.2 = STRING: "-4.9   " dBm

$flags = OBS_SNMP_ALL_NUMERIC_INDEX;

$oids = snmpwalk_cache_oid($device, 'swSfpStatTable', [], 'FA-EXT-MIB', NULL, $flags);
if (!safe_count($oids)) {
    return;
}
$oids = snmpwalk_cache_oid($device, 'connUnitPortIndex', $oids, 'FCMGMT-MIB', NULL, $flags);
//$oids = snmpwalk_cache_oid($device, 'connUnitPortName',                    $oids, 'FCMGMT-MIB', NULL, $flags);

$port_sw = snmpwalk_cache_oid($device, 'swFCPortSpecifier', [], 'SW-MIB');

//print_vars($oids);
foreach ($oids as $index => $entry) {
    if (isset($port_sw[$entry['connUnitPortIndex']])) {
        $entry = array_merge($entry, $port_sw[$entry['connUnitPortIndex']]);
    }
    $port_fc = $entry['swFCPortSpecifier'];

    $port = dbFetchRow('SELECT * FROM `ports` WHERE `device_id` = ? AND (`ifName` = ? OR `ifDescr` REGEXP ?)', [$device['device_id'], $port_fc, '^FC[[:alnum:]]* port ' . $port_fc . '$']);
    if (!$port && is_numeric($port_fc)) {
        // non-bladed
        $port_fc = '0/' . $port_fc;
        $port    = dbFetchRow('SELECT * FROM `ports` WHERE `device_id` = ? AND (`ifName` = ? OR `ifDescr` REGEXP ?)', [$device['device_id'], $port_fc, '^FC[[:alnum:]]* port ' . $port_fc . '$']);
    }

    $options = ['entPhysicalIndex' => $entry['connUnitPortIndex']];
    if ($port) {
        $name                       = $port['ifDescr'];
        $options['measured_class']  = 'port';
        $options['measured_entity'] = $port['port_id'];
    } else {
        $name = $entry['connUnitPortName'];
    }

    $descr    = $name . ' Temperature';
    $oid_name = 'swSfpTemperature';
    $oid_num  = '.1.3.6.1.4.1.1588.2.1.1.1.28.1.1.1.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $entry['connUnitPortIndex'], NULL, $descr, 1, $value, $options);

    $descr    = $name . ' Voltage';
    $oid_name = 'swSfpVoltage';
    $oid_num  = '.1.3.6.1.4.1.1588.2.1.1.1.28.1.1.2.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid_num, $entry['connUnitPortIndex'], NULL, $descr, 0.001, $value, $options);

    $descr    = $name . ' Bias Current';
    $oid_name = 'swSfpCurrent';
    $oid_num  = '.1.3.6.1.4.1.1588.2.1.1.1.28.1.1.3.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, 'current', $mib, $oid_name, $oid_num, $entry['connUnitPortIndex'], NULL, $descr, 0.001, $value, $options);

    $descr    = $name . ' Receive Power';
    $oid_name = 'swSfpRxPower';
    $oid_num  = '.1.3.6.1.4.1.1588.2.1.1.1.28.1.1.4.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = str_replace('-inf', '-40', $entry[$oid_name]);

    discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $entry['connUnitPortIndex'], NULL, $descr, 1, $value, $options);

    $descr    = $name . ' Transmit Power';
    $oid_name = 'swSfpTxPower';
    $oid_num  = '.1.3.6.1.4.1.1588.2.1.1.1.28.1.1.5.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $entry['connUnitPortIndex'], NULL, $descr, 1, $value, $options);

}

// EOF
