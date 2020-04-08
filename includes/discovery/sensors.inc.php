<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @author     Adam Armstrong <adama@observium.org>
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$valid['sensor'] = array();
$valid['status'] = array();
$valid['counter'] = array();

// Sensor, Status and Counter entities are discovered together since they are often in the same MIBs.

// Run sensor discovery scripts (also discovers state sensors as status entities)
$include_dir = "includes/discovery/sensors";
include($config['install_dir']."/includes/include-dir-mib.inc.php");

// Run status-specific discovery scripts
$include_dir = "includes/discovery/status";
include($config['install_dir']."/includes/include-dir-mib.inc.php");

// Run counter-specific discovery scripts
$include_dir = "includes/discovery/counter";
include($config['install_dir']."/includes/include-dir-mib.inc.php");

$cache_snmp = array();

foreach (get_device_mibs_permitted($device) as $mib)
{
  // Detect sensors by definitions
  if (is_array($config['mibs'][$mib]['sensor']))
  {
    print_cli_data_field($mib);
    foreach ($config['mibs'][$mib]['sensor'] as $oid_data)
    {
      discover_sensor_definition($device, $mib, $oid_data);
    }
    print_cli(PHP_EOL);
  }

  // Detect statuses by definitions
  if (is_array($config['mibs'][$mib]['status']))
  {
    print_cli_data_field($mib);
    foreach ($config['mibs'][$mib]['status'] as $oid_data)
    {
      discover_status_definition($device, $mib, $oid_data);
    }
    print_cli(PHP_EOL);
  }

  // Detect counters by definitions
  if (is_array($config['mibs'][$mib]['counter']))
  {
    print_cli_data_field($mib);
    foreach ($config['mibs'][$mib]['counter'] as $oid_data)
    {
      discover_counter_definition($device, $mib, $oid_data);
    }
    print_cli(PHP_EOL);
  }
}

print_debug_vars($valid['sensor']);
foreach (array_keys($config['sensor_types']) as $type)
{
  check_valid_sensors($device, $type, $GLOBALS['valid']['sensor']);
}

print_debug_vars($valid['status']);
check_valid_status($device, $GLOBALS['valid']['status']);

print_debug_vars($valid['counter']);
check_valid_counter($device);

echo(PHP_EOL);

// EOF
