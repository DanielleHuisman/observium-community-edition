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

// NETONIX incorrectly use pethMainPseTable for ports statuses instead pethPsePortTable
// Also I not sure that pethMainPseConsumptionPower is correctly Watts (but not something else)

//POWER-ETHERNET-MIB::pethMainPseOperStatus.10 = INTEGER: off(2)
//POWER-ETHERNET-MIB::pethMainPseOperStatus.11 = INTEGER: on(1)
//POWER-ETHERNET-MIB::pethMainPseConsumptionPower.10 = Gauge32: 0 Watts
//POWER-ETHERNET-MIB::pethMainPseConsumptionPower.11 = Gauge32: 1 Watts

$oids = snmpwalk_cache_oid($device, 'pethMainPseTable', [], 'POWER-ETHERNET-MIB');

//NETONIX-SWITCH-MIB::poeStatus.10 = STRING: Off
//NETONIX-SWITCH-MIB::poeStatus.11 = STRING: 48V

$oids = snmpwalk_cache_oid($device, 'poeStatusTable', $oids, 'NETONIX-SWITCH-MIB');

print_debug_vars($oids);

////// Per-port Statistics

foreach ($oids as $index => $entry) {
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

    $descr = $entry['ifDescr'] . ' PoE Status';

    $oid_name = 'pethMainPseOperStatus';
    $oid_num  = ".1.3.6.1.2.1.105.1.3.1.1.3.$index";
    $type     = 'power-ethernet-mib-pse-state';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, $options);

    if ($entry['pethMainPseOperStatus'] != 'off') {
        $descr = $entry['ifDescr'] . ' PoE Power';

        $oid_name = 'pethMainPseConsumptionPower';
        $oid_num  = ".1.3.6.1.2.1.105.1.3.1.1.4.$index";
        $type     = 'power-ethernet-mib'; // $mib . '-' . $oid_name;
        $scale    = 1;
        $value    = $entry[$oid_name];

        discover_sensor('power', $device, $oid_num, "pethMainPseConsumptionPower.$index", $type, $descr, $scale, $value, $options);
    }

    // Another status
    $descr = $entry['ifDescr'] . ' PoE';

    $oid_name = 'poeStatus';
    $oid_num  = ".1.3.6.1.4.1.46242.5.1.2.$index";
    $type     = 'netonix-poeStatus';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, $options);
}

// EOF
