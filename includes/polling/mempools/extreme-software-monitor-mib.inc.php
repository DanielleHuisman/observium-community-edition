<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

$mib = 'EXTREME-SOFTWARE-MONITOR-MIB';

$oids = array('extremeMemoryMonitorSystemTotal', 'extremeMemoryMonitorSystemFree');

if (!is_array($cache_storage[$mib]))
{
  foreach ($oids as $oid)
  {
    $cache_mempool = snmpwalk_cache_oid($device, $oid, $cache_mempool, $mib);
  }
  $cache_storage[$mib] = $cache_mempool;
} else {
  print_debug("Cached!");
  $cache_mempool = $cache_storage[$mib];
}

$index            = $mempool['mempool_index'];
$mempool['free']  = $cache_mempool[$index]['extremeMemoryMonitorSystemFree'];
$mempool['total'] = $cache_mempool[$index]['extremeMemoryMonitorSystemTotal'];
$mempool['used']  = $mempool['total'] - $mempool['free'];

// EOF
