<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) Adam Armstrong
 *
 */

// HWG-PWR-MIB

echo("HWG-PWR-MIB ");

$meters = snmpwalk_cache_oid($device, 'mtEntry', [], 'HWG-PWR-MIB');
$oids   = snmpwalk_cache_twopart_oid($device, 'mtvalEntry', [], 'HWG-PWR-MIB');
print_debug_vars($meters);
print_debug_vars($oids);

foreach ($oids as $meter => $entry1) {
    $name = $meters[$meter]['mtName'];
    foreach ($entry1 as $idx => $entry) {
        $index = "{$meter}.{$idx}";
        $descr = $entry['mtvalName'];
        if ($name) {
            $descr .= ' - ' . $name;
        }

        $oid_name = 'mtvalMbusValue';
        $oid_num  = ".1.3.6.1.4.1.21796.4.6.1.3.1.6.{$index}";
        $scale    = si_to_scale($entry['mtvalExp']);
        $value    = $entry[$oid_name];

        if ($entry['mtvalAlarmState'] == 'invalid' && $value == 0) {
            continue;
        } // Skip invalid empty entries

        $sensor_class = FALSE;
        switch (strtolower($entry['mtvalUnit'])) {
            case 'm3':
                $scale = si_to_scale($entry['mtvalExp'] + 3); // Convert to L
            // not break here
            case 'l':
                $sensor_class = 'volume';
                break;
            case 'kwh':
                $scale = si_to_scale($entry['mtvalExp'] + 3); // Convert to Wh
            // not break here
            case 'wh':
                $sensor_class = 'energy';
                break;
            case 'kw':
                $scale = si_to_scale($entry['mtvalExp'] + 3); // Convert to W
            // not break here
            case 'w':
                $sensor_class = 'power';
                break;
            case 'v':
                $sensor_class = 'voltage';
                break;
            case 'a':
                $sensor_class = 'current';
                break;
            case '':
                if (str_icontains_array($entry['mtvalName'], 'Power factor')) {
                    $sensor_class = 'powerfactor';
                } elseif (str_icontains_array($entry['mtvalName'], 'counter')) {
                    $sensor_class = 'counter';
                }
                break;
        }
        if (in_array($sensor_class, [ 'counter', 'energy' ])) {
            // Counters
            discover_counter($device, $sensor_class, $mib, $oid_name, $oid_num, $index, $descr, $scale, $value);
        } elseif ($sensor_class) {
            discover_sensor_ng($device, $sensor_class, $mib, $oid_name, $oid_num, $index, $descr, $scale, $value);
        }

        $oid_name = 'mtvalAlarmState';
        $oid_num  = ".1.3.6.1.4.1.21796.4.6.1.3.1.8.{$index}";
        $type     = 'mtvalAlarmState';
        $value    = $entry[$oid_name];

        // FIXME convert to discover_status_ng()
        discover_status($device, $oid_num, $oid_name . '.' . $index, $type, $descr, $value, ['entPhysicalClass' => 'other']);
    }
}

// EOF
