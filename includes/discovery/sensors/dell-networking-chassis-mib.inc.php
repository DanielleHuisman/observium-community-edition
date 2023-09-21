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

// DELL-NETWORKING-CHASSIS-MIB

#DELL-NETWORKING-CHASSIS-MIB::dellNetStackUnitTemp.1 = Gauge32: 32
#DELL-NETWORKING-CHASSIS-MIB::dellNetStackUnitStatus.1 = INTEGER: ok(1)

echo("DELL-NETWORKING-CHASSIS-MIB ");

$units = [];

$oids = snmpwalk_cache_oid($device, "dellNetStackUnitTemp", [], "DELL-NETWORKING-CHASSIS-MIB");
$oids = snmpwalk_cache_oid($device, "dellNetStackUnitStatus", $oids, "DELL-NETWORKING-CHASSIS-MIB");

foreach ($oids as $index => $entry) {

    $descr         = "Unit " . strval($index - 1);
    $units[$index] = $descr; // Store Unit name for other sensors

    $oid_name = 'dellNetStackUnitTemp';
    $oid_num  = ".1.3.6.1.4.1.6027.3.26.1.3.4.1.13.{$index}";
    $type     = $mib . '-' . $oid_name;
    $scale    = 1;
    $value    = $entry[$oid_name];

    discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);

    $oid_name = 'dellNetStackUnitStatus';
    $oid_num  = ".1.3.6.1.4.1.6027.3.26.1.3.4.1.8.{$index}";
    $type     = 'dellNetStackUnitStatus';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr . ' Status', $value, ['entPhysicalClass' => 'device']);
}

$oids = snmpwalk_cache_oid($device, "dellNetFanTrayOperStatus", [], "DELL-NETWORKING-CHASSIS-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
$oids = snmpwalk_cache_oid($device, "dellNetPowerSupplyOperStatus", $oids, "DELL-NETWORKING-CHASSIS-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
$oids = snmpwalk_cache_oid($device, "dellNetPowerSupplyUsage", $oids, "DELL-NETWORKING-CHASSIS-MIB", NULL, OBS_SNMP_ALL_NUMERIC_INDEX);

foreach ($oids as $index => $entry) {
    [$type, $unit, $tray] = explode('.', $index);
    if (!isset($units[$unit])) {
        // Skip inactive Units
        continue;
    }

    $descr = $units[$unit];

    $oid_name = 'dellNetFanTrayOperStatus';
    $oid_num  = ".1.3.6.1.4.1.6027.3.26.1.4.7.1.4.{$index}";
    $type     = 'dellNetOperStatus';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr . ' Fan ' . $tray, $value, ['entPhysicalClass' => 'fan']);

    $oid_name = 'dellNetPowerSupplyOperStatus';
    $oid_num  = ".1.3.6.1.4.1.6027.3.26.1.4.6.1.4.{$index}";
    $type     = 'dellNetOperStatus';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr . ' PowerSupply ' . $tray, $value, ['entPhysicalClass' => 'powersupply']);

    $oid_name = 'dellNetPowerSupplyUsage';
    $oid_num  = ".1.3.6.1.4.1.6027.3.26.1.4.6.1.10.{$index}";
    $type     = $mib . '-' . $oid_name;
    $scale    = 1;
    $value    = $entry[$oid_name];

    if ($value > 0) {
        discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr . ' PowerSupply ' . $tray, $scale, $value);
    }
}

// DOM sensors

//DELL-NETWORKING-CHASSIS-MIB::dellNetSysIfXfpRecvPower.2097156 = INTEGER: 655.35 dB
//DELL-NETWORKING-CHASSIS-MIB::dellNetSysIfXfpRecvPower.2097284 = INTEGER: -8.50 dB
//DELL-NETWORKING-CHASSIS-MIB::dellNetSysIfXfpRecvPower.2097412 = INTEGER: .00 dB
//DELL-NETWORKING-CHASSIS-MIB::dellNetSysIfXfpRecvTemp.2097156 = INTEGER: 65535
//DELL-NETWORKING-CHASSIS-MIB::dellNetSysIfXfpRecvTemp.2097284 = INTEGER: 32
//DELL-NETWORKING-CHASSIS-MIB::dellNetSysIfXfpRecvTemp.2097412 = INTEGER: 0
//DELL-NETWORKING-CHASSIS-MIB::dellNetSysIfXfpTxPower.2097156 = INTEGER: 655.35 dB
//DELL-NETWORKING-CHASSIS-MIB::dellNetSysIfXfpTxPower.2097284 = INTEGER: -5.36 dB
//DELL-NETWORKING-CHASSIS-MIB::dellNetSysIfXfpTxPower.2097412 = INTEGER: .00 dB

$oids = snmpwalk_cache_oid($device, "dellNetSysIfXfpRecvTemp", [], "DELL-NETWORKING-CHASSIS-MIB");
if (safe_count($oids)) {
    $oids = snmpwalk_cache_oid($device, "dellNetSysIfXfpRecvPower", $oids, "DELL-NETWORKING-CHASSIS-MIB");
    $oids = snmpwalk_cache_oid($device, "dellNetSysIfXfpTxPower", $oids, "DELL-NETWORKING-CHASSIS-MIB");
    if (OBS_DEBUG > 1) {
        print_vars($oids);
    }

    foreach ($oids as $index => $entry) {
        if (($entry['dellNetSysIfXfpRecvPower'] === '655.35' && $entry['dellNetSysIfXfpTxPower'] === '655.35' && $entry['dellNetSysIfXfpRecvTemp'] === '65535') ||
            ($entry['dellNetSysIfXfpRecvPower'] === '.00' && $entry['dellNetSysIfXfpTxPower'] === '.00' && $entry['dellNetSysIfXfpRecvTemp'] === '0') ||
            ($entry['dellNetSysIfXfpRecvPower'] === '.00' && !is_numeric($entry['dellNetSysIfXfpTxPower']) && !is_numeric($entry['dellNetSysIfXfpRecvTemp']))) {
            continue;
        }

        $port    = get_port_by_index_cache($device['device_id'], $index);
        $options = ['entPhysicalIndex' => $index,
                    'measured_class'   => 'port',
                    'measured_entity'  => $port['port_id']];

        if (is_numeric($entry['dellNetSysIfXfpRecvPower'])) {
            $descr = $port['ifDescr'] . " RX Power";

            $oid_name = 'dellNetSysIfXfpRecvPower';
            $oid_num  = ".1.3.6.1.4.1.6027.3.26.1.4.10.1.5.{$index}";
            $type     = $mib . '-' . $oid_name;
            $scale    = 0.01;
            $value    = $entry[$oid_name] * 100; // Yes, multiple here, because here used inside-mib convert

            discover_sensor_ng($device, 'dbm', $mib, $oid_num, $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }

        if (is_numeric($entry['dellNetSysIfXfpTxPower'])) {
            $descr = $port['ifDescr'] . " TX Power";

            $oid_name = 'dellNetSysIfXfpTxPower';
            $oid_num  = ".1.3.6.1.4.1.6027.3.26.1.4.10.1.7.{$index}";
            $type     = $mib . '-' . $oid_name;
            $scale    = 0.01;
            $value    = $entry[$oid_name] * 100; // Yes, multiple here, because here used inside-mib convert

            discover_sensor_ng($device, 'dbm', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }

        if (is_numeric($entry['dellNetSysIfXfpRecvTemp'])) {
            $descr = $port['ifDescr'] . " DOM";

            $oid_name = 'dellNetSysIfXfpRecvTemp';
            $oid_num  = ".1.3.6.1.4.1.6027.3.26.1.4.10.1.6.{$index}";
            $type     = $mib . '-' . $oid_name;
            $scale    = 1;
            $value    = $entry[$oid_name] * 100; // Yes, multiple here, because here used inside-mib convert

            discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
        }
    }
}

// EOF
