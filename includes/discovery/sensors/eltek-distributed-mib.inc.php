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

// enterprises.eltek.eltekDistributedPlantV7.acDistribution.acVoltage1.0 = INTEGER: 227
// enterprises.eltek.eltekDistributedPlantV7.acDistribution.acVoltage2.0 = INTEGER: 227
// enterprises.eltek.eltekDistributedPlantV7.acDistribution.acVoltage3.0 = INTEGER: 0
// .1.3.6.1.4.1.12148.9.6.1.0 = 235
// .1.3.6.1.4.1.12148.9.6.2.0 = 235
// .1.3.6.1.4.1.12148.9.6.3.0 = 237

$list[] = ['oid' => '.1.3.6.1.4.1.12148.9.6.1.0', 'descr' => 'AC Voltage 1', 'index' => '1', 'type' => 'acVoltage'];
$list[] = ['oid' => '.1.3.6.1.4.1.12148.9.6.2.0', 'descr' => 'AC Voltage 2', 'index' => '2', 'type' => 'acVoltage'];
$list[] = ['oid' => '.1.3.6.1.4.1.12148.9.6.3.0', 'descr' => 'AC Voltage 3', 'index' => '3', 'type' => 'acVoltage'];

foreach ($list as $entry) {

    $value = snmp_get_oid($device, $entry['oid'], 'ELTEK-DISTRIBUTED-MIB');

    if (is_numeric($value)) {
        discover_sensor('voltage', $device, $entry['oid'], $entry['index'], 'eltek-distributed-mib_acVoltage', $entry['descr'], 1, $value);
    }

}

//    [rectifierStatusID] => 2
//    [rectifierStatusStatus] => normal
//    [rectifierStatusOutputCurrent] => 42
//    [rectifierStatusOutputVoltage] => 5430
//    [rectifierStatusTemp] => 32
//    [rectifierStatusType] => FLATPACK2 48/2000 HE
//    [rectifierStatusSKU] => 241115.105
//    [rectifierStatusSerialNo] => 1051711xxxx
//    [rectifierStatusRevisionLevel] => 2.1

//.1.3.6.1.4.1.12148.9.5.5.2.1.1.1 = 2
//.1.3.6.1.4.1.12148.9.5.5.2.1.2.1 = normal
//.1.3.6.1.4.1.12148.9.5.5.2.1.3.1 = 42
//.1.3.6.1.4.1.12148.9.5.5.2.1.4.1 = 5429
//.1.3.6.1.4.1.12148.9.5.5.2.1.5.1 = 32
//.1.3.6.1.4.1.12148.9.5.5.2.1.6.1 = FLATPACK2 48/2000 HE
//.1.3.6.1.4.1.12148.9.5.5.2.1.7.1 = 241115.105
//.1.3.6.1.4.1.12148.9.5.5.2.1.8.1 = 1051711xxxx
//.1.3.6.1.4.1.12148.9.5.5.2.1.9.1 = 2.1

$oids = snmpwalk_cache_oid($device, 'rectifierStatusTable', [], 'ELTEK-DISTRIBUTED-MIB');
foreach ($oids as $index => $entry) {
    if ($entry['rectifierStatusStatus'] != 'notPresent') {
        $descr = 'Rectifier ' . $entry['rectifierStatusID'] . ' (' . $entry['rectifierStatusType'] . ')';
        discover_sensor('voltage', $device, '.1.3.6.1.4.1.12148.9.5.5.2.1.4.' . $index, $index, 'eltek-distributed-mib_rectifierStatusOutputVoltage', $descr, 0.01, $entry['rectifierStatusOutputVoltage']);
        discover_sensor('current', $device, '.1.3.6.1.4.1.12148.9.5.5.2.1.3.' . $index, $index, 'eltek-distributed-mib_rectifierStatusOutputCurrent', $descr, 0.1, $entry['rectifierStatusOutputCurrent']);
        discover_sensor('temperature', $device, '.1.3.6.1.4.1.12148.9.5.5.2.1.5.' . $index, $index, 'eltek-distributed-mib_rectifierStatusTemp', $descr, 1, $entry['rectifierStatusTemp']);

        discover_status($device, '.1.3.6.1.4.1.12148.9.5.5.2.1.2.' . $index, 'rectifierStatusStatus.' . $index, 'eltek-distributed-mib_rectifierStatusStatus', $descr, $entry['rectifierStatusStatus']);
    }
}

// Note that the units measured may be in Amperes or in Deciamperes depending on global system settings,
// but snmp output not have attribute for detect scale

// ELTEK-DISTRIBUTED-MIB::batteryCurrent.0 = INTEGER: 1 Amperes or DeciAmperes
// ELTEK-DISTRIBUTED-MIB::batteryChargeCurrentLimitValue.0 = INTEGER: 20 Amperes or DeciAmperes
// or
// ELTEK-DISTRIBUTED-MIB::batteryCurrent.0 = INTEGER: 5 Amperes or DeciAmperes
// ELTEK-DISTRIBUTED-MIB::batteryChargeCurrentLimitValue.0 = INTEGER: 350 Amperes or DeciAmperes

$limit = snmp_get_oid($device, 'batteryChargeCurrentLimitValue.0', 'ELTEK-DISTRIBUTED-MIB');
$value = snmp_get_oid($device, 'batteryCurrent.0', 'ELTEK-DISTRIBUTED-MIB');

$oid      = '.1.3.6.1.4.1.12148.9.3.3.0';
$oid_name = 'batteryCurrent';
$descr    = 'Battery Current';
if ($limit > 100) {
    // Hack for detect scale, not sure if always correct
    $scale = 0.1;
    $limit *= $scale;
} else {
    $scale = 1;
}

$options = ['limit_high' => $limit];

discover_sensor_ng($device, 'current', $mib, $oid_name, $oid, '0', NULL, $descr, $scale, $value, $options);

// EOF
