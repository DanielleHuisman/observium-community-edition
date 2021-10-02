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

$mib = 'JUNIPER-MIB';

$oids = array('jnxOperatingBuffer', 'jnxOperatingDRAMSize', 'jnxOperatingMemory');

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

if ($mempool['mempool_multiplier'] == 1)
{
  $mempool['total'] = $cache_mempool[$index]['jnxOperatingDRAMSize'];
} else {
  $mempool['total'] = $cache_mempool[$index]['jnxOperatingMemory'];
}

$mempool['perc'] = $cache_mempool[$index]['jnxOperatingBuffer'];

// EOF
