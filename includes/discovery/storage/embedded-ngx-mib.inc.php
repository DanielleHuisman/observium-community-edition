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

# lookup for storage data
$entry = snmpwalk_cache_oid($device, 'swStorage', NULL, 'EMBEDDED-NGX-MIB');

if (is_array($entry))
{
  $index  = 0;
  $descr  = "Config Storage";
  $free   = $entry[$index]['swStorageConfigFree']  * 1024;
  $total  = $entry[$index]['swStorageConfigTotal'] * 1024;
  $used   = $total - $free;

  discover_storage($valid['storage'], $device, $index, 'StorageConfig', 'EMBEDDED-NGX-MIB', $descr, 1024, $total, $used);
}

unset ($entry, $index, $descr, $total, $used, $free);

// EOF
