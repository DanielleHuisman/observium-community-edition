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

// ProxyAV devices hide their CPUs/Memory/Interfaces in here

$av_array = snmpwalk_cache_oid($device, 'deviceUsage', array(), $mib);

if (is_array($av_array))
{
  foreach ($av_array as $index => $entry)
  {
    if (strpos($entry['deviceUsageName'], 'CPU') !== FALSE)
    {
      $descr = $entry['deviceUsageName'];
      $oid = ".1.3.6.1.4.1.3417.2.4.1.1.1.4.$index";
      $usage = $entry['deviceUsagePercent'];

      discover_processor($valid['processor'], $device, $oid, $index, 'cpu', $descr, 1, $usage);
    }
  }
}

unset ($av_array, $descr, $oid, $usage, $index, $entry);

// EOF
