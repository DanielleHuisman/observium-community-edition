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

// FIXME: Currently no EMD "stack" support

echo('outletConfigDesc ');
$cache['ipoman']['out'] = snmpwalk_cache_oid($device, 'outletConfigDesc', $cache['ipoman']['out'], $mib);
echo('outletConfigLocation ');
$cache['ipoman']['out'] = snmpwalk_cache_oid($device, 'outletConfigLocation', $cache['ipoman']['out'], $mib);
echo('inletConfigDesc ');
$cache['ipoman']['in'] = snmpwalk_cache_oid($device, 'inletConfigDesc', $cache['ipoman'], $mib);

$oids_in  = [];
$oids_out = [];

echo('inletConfigCurrentHigh ');
$oids_in = snmpwalk_cache_oid($device, 'inletConfigCurrentHigh', $oids_in, $mib);
echo('inletStatusCurrent ');
$oids_in = snmpwalk_cache_oid($device, 'inletStatusCurrent', $oids_in, $mib);
echo('outletConfigCurrentHigh ');
$oids_out = snmpwalk_cache_oid($device, 'outletConfigCurrentHigh', $oids_out, $mib);
echo('outletStatusCurrent ');
$oids_out = snmpwalk_cache_oid($device, 'outletStatusCurrent', $oids_out, $mib);

$scale = 0.001;
foreach ($oids_in as $index => $entry) {
    $descr  = (trim($cache['ipoman']['in'][$index]['inletConfigDesc'], '"') != '' ? trim($cache['ipoman']['in'][$index]['inletConfigDesc'], '"') : "Inlet $index");
    $oid    = ".1.3.6.1.4.1.2468.1.4.2.1.3.1.3.1.3.$index";
    $value  = $entry['inletStatusCurrent'];
    $limits = ['limit_high' => $entry['inletConfigCurrentHigh'] / 10];

    if (is_numeric($value)) {
        discover_sensor('current', $device, $oid, '1.3.1.3.' . $index, 'ipoman', $descr, $scale, $value, $limits);
    }
}

foreach ($oids_out as $index => $entry) {
    $descr  = (trim($cache['ipoman']['out'][$index]['outletConfigDesc'], '"') != '' ? trim($cache['ipoman']['out'][$index]['outletConfigDesc'], '"') : "Output $index");
    $oid    = ".1.3.6.1.4.1.2468.1.4.2.1.3.2.3.1.3.$index";
    $value  = $entry['outletStatusCurrent'];
    $limits = ['limit_high' => $entry['outletConfigCurrentHigh'] / 10];

    if (is_numeric($value)) {
        discover_sensor('current', $device, $oid, '2.3.1.3.' . $index, 'ipoman', $descr, $scale, $value, $limits);
    }
}

$oids = [];

echo('inletConfigFrequencyHigh ');
$oids = snmpwalk_cache_oid($device, 'inletConfigFrequencyHigh', $oids, $mib);
echo('inletConfigFrequencyLow ');
$oids = snmpwalk_cache_oid($device, 'inletConfigFrequencyLow', $oids, $mib);
echo('inletStatusFrequency ');
$oids = snmpwalk_cache_oid($device, 'inletStatusFrequency', $oids, $mib);

$scale = 0.1;
foreach ($oids as $index => $entry) {
    $descr  = (trim($cache['ipoman']['in'][$index]['inletConfigDesc'], '"') != '' ? trim($cache['ipoman']['in'][$index]['inletConfigDesc'], '"') : "Inlet $index");
    $oid    = ".1.3.6.1.4.1.2468.1.4.2.1.3.1.3.1.4.$index";
    $value  = $entry['inletStatusFrequency'];
    $limits = [
      'limit_high' => ($entry['inletConfigFrequencyHigh'] != 0 ? $entry['inletConfigFrequencyHigh'] : NULL),
      'limit_low'  => ($entry['inletConfigFrequencyLow'] != 0 ? $entry['inletConfigFrequencyLow'] : NULL)
    ];

    if (is_numeric($value)) {
        discover_sensor('frequency', $device, $oid, $index, 'ipoman', $descr, $scale, $value, $limits);
    }
}

// FIXME: What to do with ipmEnvEmdConfigHumiOffset.0 ?

$emd_installed = snmp_get($device, 'ipmEnvEmdStatusEmdType.0', ' -Oqv', $mib);
$scale         = 0.1;
if ($emd_installed == 'eMD-HT') {
    $descr  = snmp_get($device, 'ipmEnvEmdConfigHumiName.0', '-Oqv', $mib);
    $oid    = '.1.3.6.1.4.1.2468.1.4.2.1.5.1.1.3.0';
    $value  = snmp_get($device, 'ipmEnvEmdStatusHumidity.0', '-Oqv', $mib);
    $limits = ['limit_high' => snmp_get($device, 'ipmEnvEmdConfigHumiHighSetPoint.0', '-Oqv', $mib),
               'limit_low'  => snmp_get($device, 'ipmEnvEmdConfigHumiLowSetPoint.0', '-Oqv', $mib)];

    if ($descr != '' && is_numeric($value) && $value > 0) {
        $descr = trim(str_replace('"', '', $descr));

        discover_sensor('humidity', $device, $oid, 1, 'ipoman', $descr, $scale, $value, $limits);
    }
}

if ($emd_installed != 'disabled') {
    $descr  = snmp_get($device, 'ipmEnvEmdConfigTempName.0', '-Oqv', $mib);
    $oid    = '.1.3.6.1.4.1.2468.1.4.2.1.5.1.1.2.0';
    $value  = snmp_get($device, 'ipmEnvEmdStatusTemperature.0', '-Oqv', $mib);
    $limits = ['limit_high' => snmp_get($device, 'ipmEnvEmdConfigTempHighSetPoint.0', '-Oqv', $mib),
               'limit_low'  => snmp_get($device, 'ipmEnvEmdConfigTempLowSetPoint.0', '-Oqv', $mib)];

    if ($descr != '' && is_numeric($value) && $value > 0) {
        $descr = trim(str_replace('"', '', $descr));

        discover_sensor('temperature', $device, $oid, 1, 'ipoman', $descr, $scale, $value, $limits);
    }
}

// Inlet Disabled due to the fact thats it's Kwh instead of just Watt

#  $oids_in = array();
$oids_out = [];

#  echo('inletStatusWH ');
#  $oids_in = snmpwalk_cache_oid($device, 'inletStatusWH', $oids_in, $mib);
echo('outletStatusWH ');
$oids_out = snmpwalk_cache_oid($device, 'outletStatusWH', $oids_out, $mib);

#  foreach ($oids_in as $index => $entry)
#  {
#    $descr = (trim($cache['ipoman']['in'][$index]['inletConfigDesc'],'"') != '' ? trim($cache['ipoman']['in'][$index]['inletConfigDesc'],'"') : "Inlet $index");
#    $oid   = ".1.3.6.1.4.1.2468.1.4.2.1.3.1.3.1.5.$index";
#    $value = $entry['inletStatusWH'];
#
#    discover_sensor('power', $device, $oid, '1.3.1.3.'.$index, 'ipoman', $descr, $scale, $value);
#  }

$scale = 0.1;
foreach ($oids_out as $index => $entry) {
    $descr = (trim($cache['ipoman']['out'][$index]['outletConfigDesc'], '"') != '' ? trim($cache['ipoman']['out'][$index]['outletConfigDesc'], '"') : "Output $index");
    $oid   = ".1.3.6.1.4.1.2468.1.4.2.1.3.2.3.1.5.$index";
    $value = $entry['outletStatusWH'];

    if (is_numeric($value)) {
        discover_sensor('power', $device, $oid, '2.3.1.3.' . $index, 'ipoman', $descr, $scale, $value);
    }
}

$oids = [];

echo('inletConfigVoltageHigh ');
$oids = snmpwalk_cache_oid($device, 'inletConfigVoltageHigh', $oids, $mib);
echo('inletConfigVoltageLow ');
$oids = snmpwalk_cache_oid($device, 'inletConfigVoltageLow', $oids, $mib);
echo('inletStatusVoltage ');
$oids = snmpwalk_cache_oid($device, 'inletStatusVoltage', $oids, $mib);

$scale = 0.1;
foreach ($oids as $index => $entry) {
    $descr  = (trim($cache['ipoman']['in'][$index]['inletConfigDesc'], '"') != '' ? trim($cache['ipoman']['in'][$index]['inletConfigDesc'], '"') : "Inlet $index");
    $oid    = ".1.3.6.1.4.1.2468.1.4.2.1.3.1.3.1.2.$index";
    $value  = $entry['inletStatusVoltage'];
    $limits = ['limit_high' => $entry['inletConfigVoltageHigh'], 'limit_low' => $entry['inletConfigVoltageLow']];

    if (is_numeric($value)) {
        discover_sensor('voltage', $device, $oid, $index, 'ipoman', $descr, $scale, $value, $limits);
    }
}

// EOF
