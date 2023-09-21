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

echo(" (Bias) ");
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrBias", [], "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrHighBiasAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrLowBiasAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));

foreach ($oids as $index => $entry) {
    $entry['descr']   = dbFetchCell("SELECT `ifDescr` FROM `ports` WHERE `device_id` = ? AND `ifName` = ?", [$device['device_id'], $index]) . " Bias mA";
    $entry['oid']     = ".1.3.6.1.4.1.6141.2.60.4.1.1.1.1.18." . $index;
    $entry['current'] = $entry['wwpLeosPortXcvrBias'];
    $options          = ['limit_high'       => $entry['wwpLeosPortXcvrHighBiasAlarmThreshold'],
                         'limit_low'        => $entry['wwpLeosPortXcvrLowBiasAlarmThreshold'],
                         'entPhysicalIndex' => $index];

    discover_sensor('current', $device, $entry['oid'], $index, 'ciena-dom', $entry['descr'], 1, $entry['current'], $options);
}

# WWP-LEOS-PORT-XCVR-MIB::wwpLeosPortXcvrRxDbmPower.11 = INTEGER: -10679 dBm

echo(" (RX) ");
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrRxDbmPower", [], "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrHighRxDbmPwAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrLowRxDbmPwAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));

$scale = 0.0001;
foreach ($oids as $index => $entry) {
    $entry['descr']   = dbFetchCell("SELECT ifDescr FROM `ports` WHERE `device_id` = ? AND `ifName` = ?", [$device['device_id'], $index]) . " Rx power";
    $entry['oid']     = ".1.3.6.1.4.1.6141.2.60.4.1.1.1.1.105." . $index;
    $entry['current'] = $entry['wwpLeosPortXcvrRxDbmPower'];
    $options          = ['limit_high'       => $entry['wwpLeosPortXcvrHighRxDbmPwAlarmThreshold'] * $scale,
                         'limit_low'        => $entry['wwpLeosPortXcvrLowRxDbmPwAlarmThreshold'] * $scale,
                         'entPhysicalIndex' => $index];

    discover_sensor('dbm', $device, $entry['oid'], $index, 'ciena-dom-rx', $entry['descr'], $scale, $entry['current'], $options);
}

# WWP-LEOS-PORT-XCVR-MIB::wwpLeosPortXcvrTxDbmPower.11 = INTEGER: -10679 dBm

echo(" (TX) ");
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrTxDbmPower", [], "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrHighTxDbmPwAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrLowTxDbmPwAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));

$scale = 0.0001;
foreach ($oids as $index => $entry) {
    $entry['descr']   = dbFetchCell("SELECT `ifDescr` FROM `ports` WHERE `device_id` = ? AND `ifName` = ?", [$device['device_id'], $index]) . " Tx power";
    $entry['oid']     = ".1.3.6.1.4.1.6141.2.60.4.1.1.1.1.106." . $index;
    $entry['current'] = $entry['wwpLeosPortXcvrTxDbmPower'];
    $options          = ['limit_high'       => $entry['wwpLeosPortXcvrHighTxDbmPwAlarmThreshold'] * $scale,
                         'limit_low'        => $entry['wwpLeosPortXcvrLowTxDbmPwAlarmThreshold'] * $scale,
                         'entPhysicalIndex' => $index];

    discover_sensor('dbm', $device, $entry['oid'], $index, 'ciena-dom-tx', $entry['descr'], $scale, $entry['current'], $options);
}

# WWP-LEOS-PORT-XCVR-MIB::wwpLeosPortXcvrTemperature (Transceiver temp)
# WWP-LEOS-PORT-XCVR-MIB::wwpLeosPortXcvrHighTempAlarmThreshold
# WWP-LEOS-PORT-XCVR-MIB::wwpLeosPortXcvrLowTempAlarmThreshold

echo(" (Temp) ");
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrTemperature", [], "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrHighTempAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrLowTempAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));

foreach ($oids as $index => $entry) {
    $entry['descr']   = dbFetchCell("SELECT `ifDescr` FROM `ports` WHERE `device_id` = ? AND `ifName` = ?", [$device['device_id'], $index]) . " DegC";
    $entry['oid']     = ".1.3.6.1.4.1.6141.2.60.4.1.1.1.1.16." . $index;
    $entry['current'] = $entry['wwpLeosPortXcvrTemperature'];
    $options          = ['limit_high'       => $entry['wwpLeosPortXcvrHighTempAlarmThreshold'],
                         'limit_low'        => $entry['wwpLeosPortXcvrLowTempAlarmThreshold'],
                         'entPhysicalIndex' => $index];

    discover_sensor('temperature', $device, $entry['oid'], $index, 'ciena-dom-temp', $entry['descr'], 1, $entry['current'], $options);
}

# WWP-LEOS-PORT-XCVR-MIB::wwpLeosPortXcvrHighVccAlarmThreshold
# WWP-LEOS-PORT-XCVR-MIB::wwpLeosPortXcvrLowVccAlarmThreshold
# WWP-LEOS-PORT-XCVR-MIB::wwpLeosPortXcvrVcc

echo(" (Vcc) ");
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrVcc", [], "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrHighVccAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));
$oids = snmpwalk_cache_oid($device, "wwpLeosPortXcvrLowVccAlarmThreshold", $oids, "WWP-LEOS-PORT-XCVR-MIB", mib_dirs(['wwp', 'ciena']));

foreach ($oids as $index => $entry) {
    $entry['descr']   = dbFetchCell("SELECT ifDescr FROM `ports` WHERE `device_id` = ? AND `ifName` = ?", [$device['device_id'], $index]) . " Volts";
    $entry['oid']     = ".1.3.6.1.4.1.6141.2.60.4.1.1.1.1.17." . $index;
    $entry['current'] = $entry['wwpLeosPortXcvrVcc'];
    $options          = ['limit_high'       => $entry['wwpLeosPortXcvrHighVccAlarmThreshold'],
                         'limit_low'        => $entry['wwpLeosPortXcvrLowVccAlarmThreshold'],
                         'entPhysicalIndex' => $index];

    discover_sensor('voltage', $device, $entry['oid'], $index, 'ciena-dom-volt', $entry['descr'], 1, $entry['current'], $options);
}

// EOF
