<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
 *
 */

// Collect data for non-entity graphs

$include_dir = "includes/polling/graphs/";
include("includes/include-dir-mib.inc.php");

// Merge in mib definitions with $table_defs
foreach (get_device_mibs_permitted($device) as $mib)
{
  // Detect sensors by definitions
  if (is_array($config['mibs'][$mib]['graphs']))
  {
    if (isset($table_defs[$mib]))
    {
      $table_defs[$mib] = array_merge($table_defs[$mib], $config['mibs'][$mib]['graphs']);
    } else {
      $table_defs[$mib] = $config['mibs'][$mib]['graphs'];
    }
  }
}

foreach ($table_defs as $mib_name => $mib_tables)
{
  print_cli_data_field("$mib_name", 2);
  foreach ($mib_tables as $table_name => $table_def)
  {
    // Compat with old table format
    if (!isset($table_def['mib']))
    {
      $table_def['mib'] = $mib_name;
    }

    if (FALSE && is_numeric($table_name))
    {
      // WIP.
      echo("NEW GRAPHS polling ");
      //collect_table_ng($device, $table_def, $graphs);
    } else {
      // CLEANME. remove after migrating to collect_table_ng()
      echo("$table_name ");
      collect_table($device, $table_def, $graphs);
    }
  }
  echo PHP_EOL;
}

// EOF
