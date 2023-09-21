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
DPS-MIB-V38::channelNumber.1 = 1
DPS-MIB-V38::channelNumber.5 = 5
DPS-MIB-V38::channelNumber.17 = 17
DPS-MIB-V38::enabled.1 = disabled
DPS-MIB-V38::enabled.5 = enabled
DPS-MIB-V38::enabled.17 = enabled
DPS-MIB-V38::description.1 = ""
DPS-MIB-V38::description.5 = "CR- NETGUARDIAN DC VOLTAGE A-PWR"
DPS-MIB-V38::description.17 = "CR- NETGUARDIAN INTERNAL TEMPERATURE"
DPS-MIB-V38::value.1 = " 0.0000"
DPS-MIB-V38::value.5 = "-53.7922"
DPS-MIB-V38::value.17 = " 78.1976"
DPS-MIB-V38::thresholds.1 = noAlarms
DPS-MIB-V38::thresholds.5 = noAlarms
DPS-MIB-V38::thresholds.17 = noAlarms
 */
$oids = snmpwalk_cache_oid($device, "analogChannels", [], "DPS-MIB-V38");

foreach ($oids as $index => $entry) {

    if ($entry['enabled'] == "disabled") {
        continue;
    }

    $descr    = $entry['description'];
    $oid_name = 'value';
    $oid_num  = '.1.3.6.1.4.1.2682.1.2.6.1.4.' . $index;
    $type     = $mib . '-' . $oid_name;

    // Detect class by description
    foreach (['temperature', 'humidity', 'voltage', 'current', 'power'] as $class) {
        if (str_icontains_array($descr, $class)) {
            discover_sensor($class, $device, $oid_num, $index, $type, $descr, 1, $value);
            break; // stop foreach
        }
    }

    // Statuses
    $oid_name = 'thresholds';
    $oid_num  = '.1.3.6.1.4.1.2682.1.2.6.1.5.' . $index;
    $type     = 'dpsThresholds';
    $value    = $entry[$oid_name];

    discover_status_ng($device, $mib, 'thresholds', $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);

}

// EOF
