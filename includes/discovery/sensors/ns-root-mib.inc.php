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

if (!is_array($ns_sensor_array) && strpos($device['hardware'], 'NetScaler Virtual Appliance') === FALSE) {
    $ns_sensor_array = [];
    echo(" sysHealthCounterValue ");
    $ns_sensor_array = snmpwalk_cache_oid($device, "sysHealthCounterValue", $ns_sensor_array, "NS-ROOT-MIB");
}

foreach ($ns_sensor_array as $descr => $data) {
    $value = $data['sysHealthCounterValue'];

    $oid = ".1.3.6.1.4.1.5951.4.1.1.41.7.1.2." . snmp_string_to_oid($descr);

    if (strpos($descr, "Temp") !== FALSE) {
        $scale = 1;
        $type  = "temperature";
    } elseif (strpos($descr, "Fan") !== FALSE) {
        $scale = 1;
        $type  = "fanspeed";
    } elseif (strpos($descr, "Volt") !== FALSE) {
        $scale = 0.001;
        $type  = "voltage";
    } elseif (strpos($descr, "Vtt") !== FALSE) {
        $scale = 0.001;
        $type  = "voltage";
    } elseif (preg_match('/PowerSupply\dFailureStatus/', $descr)) {
        $physical = 'power';
        $type     = "state";
    } else {
        continue;
    } // Skip all other

    if ($type == 'state') {
        // FIXME, when will converted to definition-based, note that here used "named" index instead numeric
        discover_status($device, $oid, $descr, 'netscaler-state', $descr, $value, ['entPhysicalClass' => $physical]);
    } elseif (is_numeric($value) && $value !== '0') {
        // FIXME, when will converted to definition-based, note that here used "named" index instead numeric
        discover_sensor($type, $device, $oid, $descr, 'netscaler-health', $descr, $scale, $value);
    }
}

unset($ns_sensor_array);

// EOF
