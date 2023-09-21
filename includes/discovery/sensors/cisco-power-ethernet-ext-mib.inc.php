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

/*
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortEnable.1.2 = INTEGER: static(2)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortEnable.1.3 = INTEGER: auto(1)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortDiscoverMode.1.2 = INTEGER: unknown(1)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortDiscoverMode.1.3 = INTEGER: unknown(1)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortDeviceDetected.1.2 = INTEGER: false(2)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortDeviceDetected.1.3 = INTEGER: false(2)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortIeeePd.1.2 = INTEGER: true(1)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortIeeePd.1.3 = INTEGER: true(1)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortAdditionalStatus.1.2 = BITS: 00
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortAdditionalStatus.1.3 = BITS: 00
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrMax.1.2 = Gauge32: 15400 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrMax.1.3 = Gauge32: 15400 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrAllocated.1.2 = Gauge32: 15400 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrAllocated.1.3 = Gauge32: 10250 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrAvailable.1.2 = Gauge32: 15400 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrAvailable.1.3 = Gauge32: 10250 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrConsumption.1.2 = Gauge32: 7881 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrConsumption.1.3 = Gauge32: 6285 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortMaxPwrDrawn.1.2 = Gauge32: 16727 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortMaxPwrDrawn.1.3 = Gauge32: 6394 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortEntPhyIndex.1.2 = INTEGER: 1006
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortEntPhyIndex.1.3 = INTEGER: 1007
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPolicingCapable.1.2 = INTEGER: true(1)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPolicingCapable.1.3 = INTEGER: true(1)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPolicingEnable.1.2 = INTEGER: off(2)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPolicingEnable.1.3 = INTEGER: off(2)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPolicingAction.1.2 = INTEGER: deny(1)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPolicingAction.1.3 = INTEGER: deny(1)
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrManAlloc.1.2 = Gauge32: 0 milliwatts
CISCO-POWER-ETHERNET-EXT-MIB::cpeExtPsePortPwrManAlloc.1.3 = Gauge32: 0 milliwatts
*/

$oids = snmpwalk_cache_oid($device, 'cpeExtPsePortEntry', [], $mib);
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    if ($entry['cpeExtPsePortEnable'] == 'disable' ||
        ($entry['cpeExtPsePortPwrAllocated'] == 0 &&
         $entry['cpeExtPsePortPwrAvailable'] == 0 &&
         $entry['cpeExtPsePortPwrConsumption'] == 0 &&
         $entry['cpeExtPsePortMaxPwrDrawn'] == 0)) {
        // Skip PoE disabled or Down ports
        continue;
    }

    // Detect PoE Group and port
    [$pethPsePortGroupIndex, $cpeExtPsePortIndex] = explode('.', $index);

    $group = $pethPsePortGroupIndex > 1 ? " Group $pethPsePortGroupIndex" : ''; // Add group name if group number greater than 1

    $options = ['entPhysicalIndex' => $entry['cpeExtPsePortEntPhyIndex']];
    $port    = get_port_by_ent_index($device, $entry['cpeExtPsePortEntPhyIndex']);
    //print_vars($port);

    if (is_array($port)) {
        $entry['ifDescr']                     = $port['ifDescr'];
        $options['measured_class']            = 'port';
        $options['measured_entity']           = $port['port_id'];
        $options['entPhysicalIndex_measured'] = $port['ifIndex'];
    } else {
        $entry['ifDescr'] = "Port $cpeExtPsePortIndex";
    }

    $descr = $entry['ifDescr'] . ' PoE Power' . $group;
    $scale = 0.001;

    $oid_name = 'cpeExtPsePortPwrConsumption';
    $oid_num  = '.1.3.6.1.4.1.9.9.402.1.2.1.9.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    // Limits
    $options['limit_high'] = max($entry['cpeExtPsePortPwrAllocated'], $entry['cpeExtPsePortPwrAvailable']) * $scale;
    if ($options['limit_high'] > 0) {
        $options['limit_high_warn'] = $options['limit_high'] - ($options['limit_high'] / 10);
    } else {
        unset($options['limit_high']);
    }

    discover_sensor_ng($device, 'power', $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value, $options);
}

// EOF
