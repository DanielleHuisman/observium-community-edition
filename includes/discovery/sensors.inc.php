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

$cache_snmp       = [];
$valid['sensor']  = [];
$valid['status']  = [];
$valid['counter'] = [];

// Sensor, Status and Counter entities are discovered together since they are often in the same MIBs.
// Definitions discovery first

foreach (get_device_mibs_permitted($device) as $mib) {
    // Detect sensors by definitions
    if (is_array($config['mibs'][$mib]['sensor'])) {
        print_cli_data_field($mib);
        foreach ($config['mibs'][$mib]['sensor'] as $oid_data) {
            discover_sensor_definition($device, $mib, $oid_data);
        }
        print_cli(PHP_EOL);
    }

    // Detect statuses by definitions
    if (is_array($config['mibs'][$mib]['status'])) {
        print_cli_data_field($mib);
        foreach ($config['mibs'][$mib]['status'] as $oid_data) {
            discover_status_definition($device, $mib, $oid_data);
        }
        print_cli(PHP_EOL);
    }

    // Detect counters by definitions
    if (is_array($config['mibs'][$mib]['counter'])) {
        print_cli_data_field($mib);
        foreach ($config['mibs'][$mib]['counter'] as $oid_data) {
            discover_counter_definition($device, $mib, $oid_data);
        }
        print_cli(PHP_EOL);
    }
}

// Run sensor discovery scripts (also discovers state sensors as status entities)
$include_dir = "includes/discovery/sensors";
include($config['install_dir'] . "/includes/include-dir-mib.inc.php");

// Run status-specific discovery scripts
$include_dir = "includes/discovery/status";
include($config['install_dir'] . "/includes/include-dir-mib.inc.php");

// Run counter-specific discovery scripts
$include_dir = "includes/discovery/counter";
include($config['install_dir'] . "/includes/include-dir-mib.inc.php");

// Detect static sensors

if (is_array($config['sensors']['static'])) {

    print_cli_data_field('STATIC SENSORS');

    foreach ($config['sensors']['static'] as $sensor) {
        if ($sensor['device_id'] == $device['device_id']) {
            $value = snmp_get_oid($device, $sensor['oid']);
            if (snmp_status()) {
                $value = snmp_fix_numeric($value);
                if (is_numeric($value)) {
                    $options = [];
                    $fields  = ['limit', 'limit_low', 'limit_warn', 'limit_low_warn'];
                    foreach ($fields as $field) {
                        if (isset($sensor[$field])) {
                            $options[$field] = $sensor[$field];
                        }
                    }

                    discover_sensor_ng($device, $sensor['class'], 'STATIC', 'static', $sensor['oid'], $sensor['oid'], 'static', $sensor['descr'], $sensor['multiplier'], $value, $options);
                }
            }
        }
    }
}

// Detect static counters

if (is_array($config['counters']['static'])) {
    print_cli_data_field('Static Counters');
    foreach ($config['counters']['static'] as $counter) {
        if ($counter['device_id'] == $device['device_id']) {
            $value = snmp_get_oid($device, $counter['oid']);
            if (snmp_status()) {
                $value = snmp_fix_numeric($value);
                if (is_numeric($value)) {
                    $options = [];
                    $fields  = ['counter_unit', 'limit_auto', 'limit', 'limit_low', 'limit_warn', 'limit_low_warn'];
                    foreach ($fields as $field) {
                        if (isset($counter[$field])) {
                            $options[$field] = $counter[$field];
                        }
                    }
                    if (!isset($counter['class'])) {
                        $counter['class'] = 'counter';
                    }

                    discover_counter($device, $counter['class'], 'STATIC', 'static', $counter['oid'], $counter['oid'], $counter['descr'], $counter['multiplier'], $value, $options);

                }
            }
        }
    }
}

print_debug_vars($valid['sensor']);
foreach (array_keys($config['sensor_types']) as $type) {
    check_valid_sensors($device, $type, $GLOBALS['valid']['sensor']);
}

print_debug_vars($valid['status']);
check_valid_status($device, $GLOBALS['valid']['status']);

print_debug_vars($valid['counter']);
check_valid_counter($device);

echo(PHP_EOL);

// EOF
