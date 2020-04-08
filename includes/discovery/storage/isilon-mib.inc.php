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

// Note, this mountpoint '/ifs' also discovered in HOST-RESOURCES-MIB, but ignored

//ISILON-MIB::ifsTotalBytes.0 = Counter64: 71376260235264
//ISILON-MIB::ifsUsedBytes.0 = Counter64: 38523365810176
//ISILON-MIB::ifsAvailableBytes.0 = Counter64: 28651530510336
//ISILON-MIB::ifsFreeBytes.0 = Counter64: 32852894425088
$cache_discovery['ISILON-MIB'] = snmp_get_multi_oid($device, 'ifsTotalBytes.0 ifsUsedBytes.0', array(), 'ISILON-MIB');
if (is_array($cache_discovery['ISILON-MIB'][0]))
{
  $hc    = 1;
  $size  = $cache_discovery['ISILON-MIB'][0]['ifsTotalBytes'];
  $used  = $cache_discovery['ISILON-MIB'][0]['ifsUsedBytes'];

  discover_storage($valid['storage'], $device, 0, 'volume', 'ISILON-MIB', '/ifs', 1, $size, $used, array('storage_hc' => $hc));
}

// EOF
