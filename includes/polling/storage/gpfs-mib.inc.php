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

echo(' GPFS-MIB: ');

$index = $storage['storage_index'];

if (!is_array($cache_storage['gpfs-mib']))
{
  foreach (array('gpfsFileSystemTotalSpaceL', 'gpfsFileSystemTotalSpaceH', 'gpfsFileSystemFreeSpaceL', 'gpfsFileSystemFreeSpaceH') as $param)
  {
    $cache_storage['gpfs-mib'] = snmpwalk_cache_oid($device, $param, $cache_storage['gpfs-mib'], 'GPFS-MIB');
  }
  if (OBS_DEBUG && count($cache_storage['gpfs-mib'])) { print_vars($cache_storage['gpfs-mib']); }
}


$entry = $cache_storage['gpfs-mib'][$index];

$storage['units'] = 1024; // Hardcode units.
$storage['size'] = snmp_size64_high_low($entry['gpfsFileSystemTotalSpaceH'], $entry['gpfsFileSystemTotalSpaceL']) * 1024;
$storage['free'] = snmp_size64_high_low($entry['gpfsFileSystemFreeSpaceH'],  $entry['gpfsFileSystemFreeSpaceL'])  * 1024;
$storage['used'] = $storage['size'] - $storage['free'];

unset($index, $entry);

// EOF
