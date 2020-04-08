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

if (!is_array($cache_storage['host-resources-mib']))
{
  $cache_storage['host-resources-mib'] = snmpwalk_cache_oid($device, 'hrStorageEntry', array(), 'HOST-RESOURCES-MIB:HOST-RESOURCES-TYPES');
} else {
  print_debug('Cached!');
}

$index = $mempool['mempool_index'];
$entry = $cache_storage['host-resources-mib'][$index];

$mempool['mempool_multiplier'] = $entry['hrStorageAllocationUnits'];
$mempool['used']               = intval(snmp_dewrap32bit($entry['hrStorageUsed'])); // if hrStorageUsed not set, use 0
$mempool['total']              = snmp_dewrap32bit($entry['hrStorageSize']);

// EOF
