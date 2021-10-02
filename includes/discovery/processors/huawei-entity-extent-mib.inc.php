<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2021 Observium Limited
 *
 */

$processors_array = array();
$processors_array = snmpwalk_cache_oid($device, 'hwEntityCpuUsage', $processors_array, 'HUAWEI-ENTITY-EXTENT-MIB');
$processors_array = snmpwalk_cache_oid($device, 'hwEntityCpuUsageThreshold', $processors_array, 'HUAWEI-ENTITY-EXTENT-MIB');
$processors_array = snmpwalk_cache_oid($device, 'hwEntityMemSize', $processors_array, 'HUAWEI-ENTITY-EXTENT-MIB');
$processors_array = snmpwalk_cache_oid($device, 'entPhysicalName', $processors_array, 'ENTITY-MIB');

if (is_array($processors_array)) {
  foreach ($processors_array as $index => $entry) {
    if (isset($entry['hwEntityMemSize'])) {
      // not all platforms have hwEntityMemSize.. what tf
      if ($entry['hwEntityMemSize'] == 0) {
        print_debug("Entity is not support CPU usage:");
        print_debug_vars($entry);
        continue;
      }
    } elseif (isset($entry['hwEntityCpuUsageThreshold']) && $entry['hwEntityCpuUsageThreshold'] == 0 && $entry['hwEntityCpuUsage'] == 0) {
      print_debug("Entity is not support CPU usage:");
      print_debug_vars($entry);
      continue;
    }
    print_debug($index . ' ' . $entry['entPhysicalName'] . ' -> ' . $entry['hwEntityCpuUsage'] . ' -> ' . $entry['hwEntityMemSize']);
    $usage_oid = ".1.3.6.1.4.1.2011.5.25.31.1.1.1.1.5.$index";
    $descr = rewrite_entity_name($entry['entPhysicalName']);
    $usage = $entry['hwEntityCpuUsage'];
    if (!safe_empty($descr) && !str_contains($descr, 'No') && !str_contains($usage, 'No')) {
      discover_processor($valid['processor'], $device, $usage_oid, $index, 'vrp', $descr, 1, $usage);
    }
  } // End Foreach
} // End if array

unset($processors_array);

// EOF
