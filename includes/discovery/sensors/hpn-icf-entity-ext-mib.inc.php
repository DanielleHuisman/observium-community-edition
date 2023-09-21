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

echo(" HPN-ICF-ENTITY-EXT-MIB ");

$oids         = ['hpnicfEntityExtStateTable', 'entPhysicalName'];
$entity_array = [];
foreach ($oids as $oid) {
    $entity_array = snmpwalk_cache_oid($device, $oid, $entity_array, 'ENTITY-MIB:HPN-ICF-ENTITY-EXT-MIB');
}

foreach ($entity_array as $index => $entry) {
    // HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtTemperature.8 = INTEGER: 50
    // HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtTemperatureThreshold.8 = INTEGER: 85
    // HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtCriticalTemperatureThreshold.8 = INTEGER: 95
    // HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtLowerTemperatureThreshold.8 = INTEGER: -30
    // HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtShutdownTemperatureThreshold.8 = INTEGER: 65535

    $options = ['entPhysicalIndex' => $index];

    if ($entry['hpnicfEntityExtTemperature'] != 65535 &&
        $entry['hpnicfEntityExtTemperatureThreshold'] != 65535 &&
        $entry['hpnicfEntityExtLowerTemperatureThreshold'] != 65535) {
        $options['limit_low']       = $entry['hpnicfEntityExtLowerTemperatureThreshold'];
        $options['limit_high_warn'] = $entry['hpnicfEntityExtTemperatureThreshold'];
        if (($entry['hpnicfEntityExtCriticalTemperatureThreshold'] != 65535 && $entry['hpnicfEntityExtCriticalTemperatureThreshold'] >= $entry['hpnicfEntityExtTemperatureThreshold'])) {
            $options['limit_high'] = $entry['hpnicfEntityExtCriticalTemperatureThreshold'];
        } else {
            $options['limit_high'] = $entry['hpnicfEntityExtTemperatureThreshold'] + 10;
        }

        $value    = $entry['hpnicfEntityExtTemperature'];
        $oid      = ".1.3.6.1.4.1.11.2.14.11.15.2.6.1.1.1.1.12.$index";
        $descr    = $entry['entPhysicalName'];
        $oid_name = 'hpnicfEntityExtTemperature';

        $options['rename_rrd'] = "hh3c-entity-ext-mib-hpnicfEntityExtTemperature.$index";
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
    }

    // HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtVoltage.1 = INTEGER: 0
    // HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtVoltageLowThreshold.1 = INTEGER: 0
    // HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtVoltageHighThreshold.1 = INTEGER: 0

    if ($entry['hpnicfEntityExtVoltage'] != 0) {
        $options['limit_low']  = $entry['hpnicfEntityExtVoltageLowThreshold'];
        $options['limit_high'] = $entry['hpnicfEntityExtVoltageHighThreshold'];

        $oid_name = 'hpnicfEntityExtVoltage';
        $value    = $entry['hpnicfEntityExtVoltage'];
        $oid      = ".1.3.6.1.4.1.11.2.14.11.15.2.6.1.1.1.1.14.$index";
        $descr    = $entry['entPhysicalName'];

        // FIXME scale is unknown, and not documented in the MIB; probably not 1?? My V1910 doesn't have voltage sensors.
        $options['rename_rrd'] = "hh3c-entity-ext-mib-hpnicfEntityExtVoltage.$index";
        discover_sensor_ng($device, 'voltage', $mib, $oid_name, $oid, $index, NULL, $descr, 1, $value, $options);
    }

    //  [hpnicfEntityExtErrorStatus] => normal
}

unset($oids, $index, $value, $descr, $options);

// EOF
