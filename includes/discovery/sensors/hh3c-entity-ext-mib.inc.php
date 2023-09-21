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

$oids         = ['h3cEntityExtStateTable', 'entPhysicalName'];
$entity_array = [];
foreach ($oids as $oid) {
    $entity_array = snmpwalk_cache_oid($device, $oid, $entity_array, 'ENTITY-MIB:HH3C-ENTITY-EXT-MIB');
}

foreach ($entity_array as $index => $entry) {
    // HH3C-ENTITY-EXT-MIB::hh3cEntityExtTemperature.8 = INTEGER: 50
    // HH3C-ENTITY-EXT-MIB::hh3cEntityExtTemperatureThreshold.8 = INTEGER: 85
    // HH3C-ENTITY-EXT-MIB::hh3cEntityExtCriticalTemperatureThreshold.8 = INTEGER: 95
    // HH3C-ENTITY-EXT-MIB::hh3cEntityExtLowerTemperatureThreshold.8 = INTEGER: -30
    // HH3C-ENTITY-EXT-MIB::hh3cEntityExtShutdownTemperatureThreshold.8 = INTEGER: 65535
    if ($entry['hh3cEntityExtTemperature'] != 0 &&
        $entry['hh3cEntityExtTemperature'] != 65535 &&
        $entry['hh3cEntityExtTemperatureThreshold'] != 65535 &&
        $entry['hh3cEntityExtLowerTemperatureThreshold'] != 65535) {

        $options = [];

        if ($entry['hh3cEntityExtLowerTemperatureThreshold'] != 65535) {
            $options['limit_low'] = $entry['hh3cEntityExtLowerTemperatureThreshold'];
        }
        if ($entry['hh3cEntityExtTemperatureThreshold'] != 65535 && $entry['hh3cEntityExtTemperatureThreshold'] != 0) {
            $options['limit_high_warn'] = $entry['hh3cEntityExtTemperatureThreshold'];
        }

        if (($entry['hh3cEntityExtCriticalTemperatureThreshold'] != 65535 && $entry['hh3cEntityExtCriticalTemperatureThreshold'] != 0 &&
             $entry['hh3cEntityExtCriticalTemperatureThreshold'] >= $entry['hh3cEntityExtTemperatureThreshold'])) {
            $options['limit_high'] = $entry['hh3cEntityExtCriticalTemperatureThreshold'];
        } elseif (isset($options['limit_high_warn'])) {
            $options['limit_high'] = $options['limit_high_warn'] + 10;
        }

        $value    = $entry['hh3cEntityExtTemperature'];
        $oid      = ".1.3.6.1.4.1.25506.2.6.1.1.1.1.12.$index";
        $oid_name = "hh3cEntityExtTemperature";
        $descr    = $entry['entPhysicalName'];

        $options['rename_rrd'] = "hh3c-entity-ext-mib-hh3cEntityExtTemperature.$index";
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
    }

    // HH3C-ENTITY-EXT-MIB::hh3cEntityExtVoltage.1 = INTEGER: 0
    // HH3C-ENTITY-EXT-MIB::hh3cEntityExtVoltageLowThreshold.1 = INTEGER: 0
    // HH3C-ENTITY-EXT-MIB::hh3cEntityExtVoltageHighThreshold.1 = INTEGER: 0

    if ($entry['hh3cEntityExtVoltage'] != 0) {
        $options['limit_low']  = $entry['hh3cEntityExtVoltageLowThreshold'];
        $options['limit_high'] = $entry['hh3cEntityExtVoltageHighThreshold'];

        $value    = $entry['hh3cEntityExtVoltage'];
        $oid      = ".1.3.6.1.4.1.25506.2.6.1.1.1.1.14.$index";
        $oid_name = "hh3cEntityExtVoltage";
        $descr    = $entry['entPhysicalName'];

        $options['rename_rrd'] = "hh3c-entity-ext-mib-hh3cEntityExtVoltage.$index";
        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
        // FIXME scale is unknown, and not documented in the MIB; probably not 1?? My V1910 doesn't have voltage sensors.
    }

    //  [hh3cEntityExtErrorStatus] => normal
}

unset($oids, $index, $value, $descr);

// EOF
