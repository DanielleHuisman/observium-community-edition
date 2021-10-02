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

$mib = 'TIMETRA-SYSTEM-MIB';

if (!is_array($cache_storage[$mib]))
{
  foreach (array('sgiMemoryAvailable', 'sgiMemoryUsed') as $oid)
  {
    $cache_mempool = snmpwalk_cache_oid($device, $oid, $cache_mempool, $mib);
  }
  $cache_storage[$mib] = $cache_mempool;
} else {
  print_debug("Cached!");
  $cache_mempool = $cache_storage[$mib];
}

$mempool['total'] = $cache_mempool[$index]['sgiMemoryAvailable'];
$mempool['used']  = $cache_mempool[$index]['sgiMemoryUsed'];

// EOF
