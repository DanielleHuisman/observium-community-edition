<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage graphs
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2020 Observium Limited
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
if (!isset($include_order))
{
  // Order for include MIBs definitions, default: 'model,os,group,default'
  $include_order = NULL;
}

foreach (get_device_mibs_permitted($device, $include_order) as $mib)
{
  $inc_dir  = $config['install_dir'] . '/' . $include_dir . '/' . strtolower($mib);
  $inc_file = $inc_dir . '.inc.php';

  if (is_file($inc_file))
  {
    print_cli_data_field("$mib ");
    include($inc_file);
    echo(PHP_EOL);

    if ($include_lib && is_file($inc_dir . '.lib.php'))
    {
      // separated functions include, for exclude fatal redeclare errors
      include_once($inc_dir . '.lib.php');
    }
  }
  elseif (is_dir($inc_dir))
  {
    if (OBS_DEBUG) { echo("[[$mib]]"); }
    foreach (glob($inc_dir.'/*.inc.php') as $dir_file)
    {
      if (is_file($dir_file))
      {
        print_cli_data_field("$mib ");
        include($dir_file);
        echo(PHP_EOL);
      }
    }

    if ($include_lib && is_file($inc_dir . '.lib.php'))
    {
      // separated functions include, for exclude fatal redeclare errors
      include_once($inc_dir . '.lib.php');
    }
  }

}

unset($include_dir, $include_lib, $include_order, $inc_file, $inc_dir, $dir_file, $mib);

// EOF
