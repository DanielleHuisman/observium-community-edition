<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2024 Observium Limited
 *
 */

// Currently not possible convert to definitions because type detection is hard, based on descriptions
// SAME code in ATEN-IPMI-MIB

$oids = snmpwalk_multipart_oid($device, "fgHwSensorTable", [], "FORTINET-FORTIGATE-MIB");
print_debug_vars($oids);

foreach ($oids as $index => $entry) {
    $descr = $entry['fgHwSensorEntName'];

    $oid_name = 'fgHwSensorEntValue';
    $oid_num  = '.1.3.6.1.4.1.12356.101.4.3.2.1.3.' . $index;
    $scale    = 1;
    $value    = $entry[$oid_name];

    if (empty($descr) && $value == 0) {
        continue;
    }

    // Detect class based on descr anv value (this is derp, table not have other data for detect class
    if (str_iends($descr, [' Temp', ' Temperature'])) {
        if ($value == 0) {
            continue;
        }
        $descr = str_replace([' Temperature', ' Temp' ], '', $descr);
        $class = 'temperature';
    } elseif (str_icontains_array($descr, 'Fan')) {
        if ($value == 0) {
            continue;
        }
        if ($value > 100) {
            $class = 'fanspeed';
        } elseif ($value > 0) {
            $class   = 'load';
        }
    } elseif (str_iends($descr, [' Curr', ' Current', ' IIN' ])) {
        if ($value == 0) {
            continue;
        }
        $descr = str_replace([' Curr', ' Current' ], '', $descr);
        $class = 'current';
    } elseif (str_iends($descr, [' Pwr', ' Power', ' POUT' ])) {
        if ($value == 0) {
            continue;
        }
        $descr = str_replace([' Pwr', ' Power' ], '', $descr);
        $class = 'power';
    } elseif (preg_match('/\d+V(SB|DD)?\d*$/', $descr) || preg_match('/P\d+V\d+/', $descr) ||
              str_icontains_array($descr, [ 'VCC', 'VTT', 'VDD', 'VDQ', 'VBAT', 'VSA', 'Vcore', 'VIN', 'VOUT', 'Vbus', 'Vsht', 'VDimm', 'Vcpu', 'PVNN', 'SOC', 'VMEM' ])) {
        if ($value == 0) {
            continue;
        }
        $class = 'voltage';
    } elseif (str_icontains_array($descr, 'Status')) {
        // FORTINET-FORTIGATE-MIB::fgHwSensorEntName.45 = STRING: PS1 Status
        // FORTINET-FORTIGATE-MIB::fgHwSensorEntName.50 = STRING: PS2 Status
        // FORTINET-FORTIGATE-MIB::fgHwSensorEntValue.45 = STRING: 0
        // FORTINET-FORTIGATE-MIB::fgHwSensorEntValue.50 = STRING: 9
        // FORTINET-FORTIGATE-MIB::fgHwSensorEntAlarmStatus.45 = INTEGER: false(0)
        // FORTINET-FORTIGATE-MIB::fgHwSensorEntAlarmStatus.50 = INTEGER: true(1)
        $descr    = str_ireplace('Status', 'Alarm Status', $descr);
        $oid_name = 'fgHwSensorEntAlarmStatus';
        $oid_num  = '.1.3.6.1.4.1.12356.101.4.3.2.1.4.' . $index;
        $type     = 'fgHwSensorEntAlarmStatus';
        $value    = $entry[$oid_name];

        discover_status_ng($device, $mib, $oid_name, $oid_num, $index, $type, $descr, $value, ['entPhysicalClass' => 'powersupply']);
        continue;
    } else {
        if ($value == 0) {
            continue;
        }
        // FIXME, not always?
        $class = 'temperature';
    }

    discover_status_ng($device, $mib, 'fgHwSensorEntAlarmStatus', '.1.3.6.1.4.1.12356.101.4.3.2.1.4.' . $index, $index,
                       'fgHwSensorEntAlarmStatus', $descr, $entry['fgHwSensorEntAlarmStatus']);

    discover_sensor_ng($device, $class, $mib, $oid_name, $oid_num, $index, NULL, $descr, $scale, $value);
}

// EOF
