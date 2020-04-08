<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$processors_array = array();
$processors_array = snmpwalk_cache_multi_oid($device, 'hwEntityCpuUsage', $processors_array, 'HUAWEI-ENTITY-EXTENT-MIB');
$processors_array = snmpwalk_cache_multi_oid($device, 'hwEntityMemSize',  $processors_array, 'HUAWEI-ENTITY-EXTENT-MIB');
$processors_array = snmpwalk_cache_multi_oid($device, 'entPhysicalName',  $processors_array, 'ENTITY-MIB');

if (is_array($processors_array))
{
  foreach ($processors_array as $index => $entry)
  {
    if ($entry['hwEntityMemSize'] != 0)
    {
      print_debug($index . ' ' . $entry['entPhysicalName'] . ' -> ' . $entry['hwEntityCpuUsage'] . ' -> ' . $entry['hwEntityMemSize']);
      $usage_oid = ".1.3.6.1.4.1.2011.5.25.31.1.1.1.1.5.$index";
      $descr = rewrite_entity_name($entry['entPhysicalName']);
      $usage = $entry['hwEntityCpuUsage'];
      if (!strstr($descr, 'No') && !strstr($usage, 'No') && $descr != '')
      {
        discover_processor($valid['processor'], $device, $usage_oid, $index, 'vrp', $descr, 1, $usage);
      }
    } // End if checks
  } // End Foreach
} // End if array

unset ($processors_array);

// EOF
