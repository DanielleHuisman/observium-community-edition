<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage common
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

/* not finished yet --mike
if (isset($config['os'][$device['os']]['detect']) && $config['os'][$device['os']]['detect'])
{
  $detect_mibs = array();
  foreach (array('os', 'os_group') as $e)
  {
    foreach ($config[$e] as $entry)
    {
      if (is_array($entry['mibs'])) { $detect_mibs = array_merge($detect_mibs, $entry['mibs']); }
    }
  }
  $config['os'][$device['os']]['mibs'] = array_unique($detect_mibs);
  var_dump($config['os'][$device['os']]['mibs']);
}
*/

// This is an include so that we don't lose variable scope.

$include_lib = isset($include_lib) && $include_lib;
if (!isset($include_order)) {
  // Order for include MIBs definitions, default: 'model,os,group,default'
  $include_order = NULL;
}

// Extend or init stats
$include_stats = safe_count($include_stats) ? $include_stats : [];

foreach (get_device_mibs_permitted($device, $include_order) as $mib) {
  $inc_dir  = $config['install_dir'] . '/' . $include_dir . '/' . strtolower($mib);
  $inc_file = $inc_dir . '.inc.php';

  if (is_file($inc_file)) {
    print_cli_data_field("$mib ");

    $inc_start = microtime(TRUE); // MIB timing start
    $inc_status = include($inc_file);
    echo(PHP_EOL);

    if ($include_lib && is_file($inc_dir . '.lib.php')) {
      // separated functions include, for exclude fatal redeclare errors
      include_once($inc_dir . '.lib.php');
    }
    if ($inc_status !== FALSE) {
      // MIB timing only for valid includes
      $include_stats[$mib] = microtime(TRUE) - $inc_start;
    }

  } elseif (is_dir($inc_dir)) {
    if (OBS_DEBUG) { echo("[[$mib]]"); }

    $inc_start = microtime(TRUE); // MIB timing start
    foreach (glob($inc_dir.'/*.inc.php') as $dir_file) {
      if (is_file($dir_file)) {
        print_cli_data_field("$mib ");
        include($dir_file);
        echo(PHP_EOL);
      }
    }

    if ($include_lib && is_file($inc_dir . '.lib.php')) {
      // separated functions include, for exclude fatal redeclare errors
      include_once($inc_dir . '.lib.php');
    }
    $include_stats[$mib] += microtime(TRUE) - $inc_start; // MIB timing

  }
}

unset($include_dir, $include_lib, $include_order, $inc_file, $inc_dir, $dir_file, $mib);

// EOF
