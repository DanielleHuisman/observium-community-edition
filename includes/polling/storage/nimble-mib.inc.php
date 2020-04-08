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

$index = $storage['storage_index'];

if (!is_array($cache_storage['NIMBLE-MIB']))
{
  foreach (array('volSizeLow', 'volSizeHigh', 'volUsageLow', 'volUsageHigh', 'volOnline') as $param)
  {
    $cache_storage['NIMBLE-MIB'] = snmpwalk_cache_oid($device, $param, $cache_storage['NIMBLE-MIB'], 'NIMBLE-MIB');
  }
  if (OBS_DEBUG > 1 && count($cache_storage['NIMBLE-MIB'])) { print_vars($cache_storage['NIMBLE-MIB']); }
}

$entry = $cache_storage['NIMBLE-MIB'][$index];

// FIXME, probably need additional field for storages like OperStatus up/down
$ignore = in_array($entry['volOnline'], array('0', 'false')) ? 1 : 0;
if ($storage['storage_ignore'] != $ignore)
{
  force_discovery($device, 'storage');
}

$storage['units'] = 1048576; // Hardcode units. In MIB is written that bytes, but really Mbytes
$storage['size']  = snmp_size64_high_low($entry['volSizeHigh'],  $entry['volSizeLow'])  * $storage['units'];
$storage['used']  = snmp_size64_high_low($entry['volUsageHigh'], $entry['volUsageLow']) * $storage['units'];
$storage['free']  = $storage['size'] - $storage['used'];

unset($index, $entry, $ignore);

// EOF
