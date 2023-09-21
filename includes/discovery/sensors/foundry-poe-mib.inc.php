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

////// Global PSE Statistics

//
//FOUNDRY-POE-MIB::snAgentPoeGblPowerCapacityTotal.0 = Gauge32: 740000
//FOUNDRY-POE-MIB::snAgentPoeGblPowerCapacityFree.0 = Gauge32: 440000
//FOUNDRY-POE-MIB::snAgentPoeGblPowerAllocationsRequestsHonored.0 = Gauge32: 94

// Stacking:
//FOUNDRY-POE-MIB::snAgentPoeUnitIndex.1 = Gauge32: 1
//FOUNDRY-POE-MIB::snAgentPoeUnitPowerCapacityTotal.1 = Gauge32: 740000
//FOUNDRY-POE-MIB::snAgentPoeUnitPowerCapacityFree.1 = Gauge32: 440000
//FOUNDRY-POE-MIB::snAgentPoeUnitPowerAllocationsRequestsHonored.1 = Gauge32: 94

$oids = snmpwalk_cache_oid($device, 'snAgentPoeUnitEntry', [], $mib);
if (safe_count($oids)) {
    // Stacked devices
    foreach ($oids as $index => $entry) {
        if ($entry['snAgentPoeUnitPowerCapacityTotal'] == 0) {
            continue;
        } // skip zero sensors

        $descr    = "PoE Allocated Power Unit $index";
        $oid_name = 'snAgentPoeUnitPowerCapacityFree';
        $oid_num  = ".1.3.6.1.4.1.1991.1.1.2.14.4.1.1.3.$index";
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name]; // What the f**k FOUNDRY, why not just used value??
        $scale    = 0.001;
        $negative = -1;

        // Options
        $options = ['limit_high' => $entry['snAgentPoeUnitPowerCapacityTotal'] * $scale];

        $options['limit_high_warn'] = $options['limit_high'] * 0.9; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function

        // Note, this is not same what we do in unit conversion, since this is not different unit
        // Here used combination with negative scale and negative addition, ie:
        // 740 - 440 === -1 * (-740 + 440)
        $options['sensor_addition'] = $entry['snAgentPoeUnitPowerCapacityTotal'] * $negative;

        // Warning, negative scale here!
        discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $negative * $scale, $value, $options);
    }
} else {
    // All other
    $oids = snmp_get_multi_oid($device, 'snAgentPoeGblPowerCapacityTotal.0 snAgentPoeGblPowerCapacityFree.0', [], $mib);
    if (is_array($oids[0]) && $oids[0]['snAgentPoeGblPowerCapacityTotal'] != 0) {
        $index = 0;
        $entry = $oids[$index];

        $descr    = "PoE Allocated Power";
        $oid_name = 'snAgentPoeGblPowerCapacityFree';
        $oid_num  = ".1.3.6.1.4.1.1991.1.1.2.14.1.2.$index";
        $type     = $mib . '-' . $oid_name;
        $value    = $entry[$oid_name];                                                   // What the f**k FOUNDRY, why not just used value??
        $scale    = 0.001;
        $negative = -1;

        // Options
        $options = ['limit_high' => $entry['snAgentPoeGblPowerCapacityTotal'] * $scale]; // Hardcode 0 as lower limit. Low warning limit will be calculated.

        $options['limit_high_warn'] = $options['limit_high'] * 0.9; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function

        // Note, this is not same what we do in unit conversion, since this is not different unit
        // Here used combination with negative scale and negative addition, ie:
        // 740 - 440 === -1 * (-740 + 440)
        $options['sensor_addition'] = $entry['snAgentPoeGblPowerCapacityTotal'] * $negative;

        // Warning, negative scale here!
        discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $negative * $scale, $value, $options);
    }
}

print_debug_vars($oids);

////// Per-port Statistics

//FOUNDRY-POE-MIB::snAgentPoePortNumber.4 = INTEGER: 4
//FOUNDRY-POE-MIB::snAgentPoePortControl.4 = INTEGER: disable(2)
//FOUNDRY-POE-MIB::snAgentPoePortWattage.4 = INTEGER: 0
//FOUNDRY-POE-MIB::snAgentPoePortClass.4 = INTEGER: 0
//FOUNDRY-POE-MIB::snAgentPoePortPriority.4 = INTEGER: other(5)
//FOUNDRY-POE-MIB::snAgentPoePortConsumed.4 = INTEGER: 0
//FOUNDRY-POE-MIB::snAgentPoePortType.4 = STRING:

//FOUNDRY-POE-MIB::snAgentPoePortNumber.9 = INTEGER: 9
//FOUNDRY-POE-MIB::snAgentPoePortControl.9 = INTEGER: enable(3)
//FOUNDRY-POE-MIB::snAgentPoePortWattage.9 = INTEGER: 30000
//FOUNDRY-POE-MIB::snAgentPoePortClass.9 = INTEGER: 0
//FOUNDRY-POE-MIB::snAgentPoePortPriority.9 = INTEGER: low(3)
//FOUNDRY-POE-MIB::snAgentPoePortConsumed.9 = INTEGER: 2723
//FOUNDRY-POE-MIB::snAgentPoePortType.9 = STRING: 802.3af

$oids = snmpwalk_cache_oid($device, 'snAgentPoePortEntry', [], $mib);

print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    if ($entry['snAgentPoePortControl'] == 'disable' ||
        (($entry['snAgentPoePortPriority'] == 'other' || $entry['snAgentPoePortType'] == 'n/a') &&
         $entry['snAgentPoePortConsumed'] == 0)) // && empty($entry['snAgentPoePortType'])))
    {
        // Skip PoE disabled ports
        continue;
    }

    $options = ['entPhysicalIndex' => $index];
    $port    = get_port_by_ifIndex($device['device_id'], $index);
    // print_vars($port);

    if (is_array($port)) {
        $entry['ifDescr']                     = $port['port_label'];
        $options['measured_class']            = 'port';
        $options['measured_entity']           = $port['port_id'];
        $options['entPhysicalIndex_measured'] = $port['ifIndex'];
    } else {
        $entry['ifDescr'] = "Port $index";
    }

    $descr    = $entry['ifDescr'] . ' PoE Power';
    $oid_name = 'snAgentPoePortConsumed';
    $oid_num  = ".1.3.6.1.4.1.1991.1.1.2.14.2.2.1.6.$index";
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];
    $scale    = 0.001;

    // Limits
    $options['limit_high'] = $entry['snAgentPoePortWattage'] * $scale;
    if ($options['limit_high'] > 0) {
        $options['limit_high_warn'] = $options['limit_high'] * 0.9; // Warning at 90% of power limit - FIXME should move to centralized smart calculation function
    } else {
        unset($options['limit_high']);
    }
    discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
}

// EOF
