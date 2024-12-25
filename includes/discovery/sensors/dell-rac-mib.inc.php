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

// table: CMC power information
$oids = snmpwalk_cache_oid($device, 'drsCMCPowerTable', [], $mib);
foreach ($oids as $index => $entry) {
    $options = [ 'rename_rrd' => "dell-rac-$index" ];

    $descr  = "Chassis " . $entry['drsChassisIndex'];
    $oid    = ".1.3.6.1.4.1.674.10892.2.4.1.1.14.$index";
    $object = 'drsAmpsReading';
    discover_sensor_ng($device, 'current', $mib, $object, $oid, $index, $descr, 1, $entry[$object], $options);

    $limits = [ 'limit_high' => $entry['drsMaxPowerSpecification'] ];
    $oid    = ".1.3.6.1.4.1.674.10892.2.4.1.1.13.$index";
    $object = 'drsWattsReading';
    discover_sensor_ng($device, 'power', $mib, $object, $oid, $index, $descr, 1, $entry[$object], array_merge($options, $limits));
}

unset($oids);

// table: CMC PSU info
$oids = snmpwalk_cache_oid($device, 'drsCMCPSUTable', [], $mib);
foreach ($oids as $index => $entry) {
    $options = [ 'rename_rrd' => "dell-rac-$index" ];

    $descr  = 'Chassis ' . $entry['drsPSUChassisIndex'] . ' ' . $entry['drsPSULocation'];
    $oid    = ".1.3.6.1.4.1.674.10892.2.4.2.1.6.$index";
    $object = 'drsPSUAmpsReading';
    discover_sensor_ng($device, 'current', $mib, $object, $oid, $index, $descr, 1, $entry[$object], $options);

    $oid    = ".1.3.6.1.4.1.674.10892.2.4.2.1.5.$index";
    $limits = [];

    ## FIXME this type of inventing/calculating should be done in the Observium voltage function instead!
    if ($entry['drsPSUVoltsReading'] > 360 && $entry['drsPSUVoltsReading'] < 440) {
        // european 400V +/- 10%
        $limits = ['limit_high' => 440, 'limit_low' => 360];
    }
    if ($entry['drsPSUVoltsReading'] > 207 && $entry['drsPSUVoltsReading'] < 253) {
        // european 230V +/- 10%
        $limits = ['limit_high' => 253, 'limit_low' => 207];
    }
    if ($entry['drsPSUVoltsReading'] > 99 && $entry['drsPSUVoltsReading'] < 121) {
        // american 110V +/- 10%
        $limits = ['limit_high' => 99, 'limit_low' => 121];
    }
    $object = 'drsPSUVoltsReading';
    discover_sensor_ng($device, 'voltage', $mib, $object, $oid, $index, $descr, 1, $entry[$object], array_merge($options, $limits));
}

// EOF
