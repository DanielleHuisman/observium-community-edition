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

// OMNITRON-POE-MIB::ostPoeGlobalCfgPwrLimitationEnable.0 = INTEGER: false(2)
// OMNITRON-POE-MIB::ostPoeGlobalCfgTotalPwr.0 = STRING: 2.7618
// OMNITRON-POE-MIB::ostPoePortPseEnable.2 = INTEGER: pseEnabled(2)
// OMNITRON-POE-MIB::ostPoePortPse60wMode.2 = INTEGER: pse60wAuto(1)
// OMNITRON-POE-MIB::ostPoePortPdMode.2 = INTEGER: pdModeClass3(7)
// OMNITRON-POE-MIB::ostPoePortPseVoltageSupplied.2 = STRING:  57.42
// OMNITRON-POE-MIB::ostPoePortPseCurrentSupplied.2 = STRING:  52.74
// OMNITRON-POE-MIB::ostPoePortPseStatus.2 = INTEGER: pdNormal(2)
// OMNITRON-POE-MIB::ostPoePortHeartbeatEnable.2 = INTEGER: heartbeatDisabled(1)
// OMNITRON-POE-MIB::ostPoePortHeartbeatIpAddress.2 = IpAddress: 0.0.0.0
// OMNITRON-POE-MIB::ostPoePortHeartbeatInterval.2 = Gauge32: 1
// OMNITRON-POE-MIB::ostPoePortHeartbeatErrorDetection.2 = Gauge32: 3
// OMNITRON-POE-MIB::ostPoePortHeartbeatErrorAction.2 = INTEGER: errorLostIgnored(1)
// OMNITRON-POE-MIB::ostPoePortHeartbeatNumberRestarts.2 = Gauge32: 0
// OMNITRON-POE-MIB::ostPoEPortHeartbeatStatus.2 = INTEGER: heartbeatDisabled(1)

$mib = 'OMNITRON-POE-MIB';

$oids = snmpwalk_cache_oid($device, 'ostPoePortCfgEntry', [], $mib);
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    if ($entry['ostPoePortPseEnable'] == 'pseDisabled') {
        continue;
    }

    $options = ['entPhysicalIndex' => $index, 'entPhysicalClass' => 'port'];
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

    // Current Supplied
    $descr    = $entry['ifDescr'] . ' PoE Current';
    $scale    = 0.001;
    $oid_name = 'ostPoePortPseCurrentSupplied';
    $oid_num  = '.1.3.6.1.4.1.7342.15.2.1.6.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('current', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

    // Voltage Supplied
    $descr    = $entry['ifDescr'] . ' PoE Voltage';
    $scale    = 1;
    $oid_name = 'ostPoePortPseVoltageSupplied';
    $oid_num  = '.1.3.6.1.4.1.7342.15.2.1.5.' . $index;
    $type     = $mib . '-' . $oid_name;
    $value    = $entry[$oid_name];

    discover_sensor('voltage', $device, $oid_num, $index, $type, $descr, $scale, $value, $options);

    $descr    = $entry['ifDescr'] . ' PoE Status';
    $oid_name = 'ostPoePortPseStatus';
    $oid_num  = '.1.3.6.1.4.1.7342.15.2.1.7.' . $index;
    $type     = 'ostPoePortPseStatus';
    $value    = $entry[$oid_name];

    discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, $options);
}

// EOF
