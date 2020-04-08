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

// EMBEDDED-NGX-MIB

if (!is_array($cache_storage['ISILON-MIB']))
{
  $cache_storage['ISILON-MIB'] = snmp_get_multi_oid($device, 'ifsTotalBytes.0 ifsUsedBytes.0', array(), 'ISILON-MIB');
}

$entry = $cache_storage['ISILON-MIB'][$storage['storage_index']];

$storage['units'] = 1;
$storage['size']  = $entry['ifsTotalBytes'];
$storage['used']  = $entry['ifsUsedBytes'];

$storage['free']  = $storage['size'] - $storage['used'];

// EOF
