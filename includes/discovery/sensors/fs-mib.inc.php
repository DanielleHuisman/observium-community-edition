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

// fs-MIB::switchThermalTempValue.1.1 = INTEGER: 51
// fs-MIB::switchThermalActionRisingThreshold.1.1.1 = INTEGER: 50
// fs-MIB::switchThermalActionFallingThreshold.1.1.1 = INTEGER: 40

$oids = snmpwalk_multipart_oid($device, 'switchThermalTempValue', [], 'fs-MIB');
if (safe_empty($oids)) {
    return;
}
$limit_oids = snmpwalk_multipart_oid($device, 'switchThermalActionRisingThreshold', [], 'fs-MIB');
$limit_oids = snmpwalk_multipart_oid($device, 'switchThermalActionFallingThreshold', $limit_oids, 'fs-MIB');
print_debug_vars($oids);
print_debug_vars($limit_oids);

foreach ($oids as $unit => $entries) {
    foreach ($entries as $i => $entry) {
        $index = "$unit.$i";

        $descr    = "Sensor $i (Unit $unit)";
        $oid_name = 'switchThermalTempValue';
        $oid_num  = '.1.3.6.1.4.1.52642.2.1.45.1.1.11.1.3.' . $index;
        //$type     = $mib . '-' . $oid_name;
        $value = $entry[$oid_name];

        // Limits
        if (isset($port_sw[$entry['connUnitPortIndex']])) {
            $entry = array_merge($entry, array_shift($limit_oids[$unit][$i]));
        }
        $limits = [
          'limit_high'      => $entry['switchThermalActionRisingThreshold'],
          'limit_high_warn' => $entry['switchThermalActionFallingThreshold']
        ];
        discover_sensor_ng($device, 'temperature', $mib, $oid_name, $oid_num, $index, NULL, $descr, 1, $value, $limits);
    }
}
// EOF
