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
    if (strpos($entry['deviceUsageName'], 'Memory') !== false)
    {
      $descr = $entry['deviceUsageName'];
      $oid = ".1.3.6.1.4.1.3417.2.4.1.1.1.4.$index";
      $perc = $entry['deviceUsagePercent'];

      discover_mempool($valid['mempool'], $device, $index, $mib, $descr, 1, 100, $perc);
    }
  }
}

unset ($av_array);

// EOF
