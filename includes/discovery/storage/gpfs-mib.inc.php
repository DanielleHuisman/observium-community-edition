<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2015 Observium Limited
 *
 */

$mib = 'GPFS-MIB';
$cache_discovery['gpfs-mib'] = snmpwalk_cache_oid($device, "gpfsFileSystemStatusTable", array(), $mib);

if (count($cache_discovery['gpfs-mib']))
{
  echo(" $mib ");

  /*
  Available data:

       Array
       (
       [gpfsFileSystemName] => scratch_gs
       [gpfsFileSystemStatus] => recovered
       [gpfsFileSystemXstatus] => OFW
       [gpfsFileSystemTotalSpaceL] => 1946157056
       [gpfsFileSystemTotalSpaceH] => 94
       [gpfsFileSystemNumTotalInodesL] => 402653184
       [gpfsFileSystemNumTotalInodesH] => 0
       [gpfsFileSystemFreeSpaceL] => 37208064
       [gpfsFileSystemFreeSpaceH] => 26
       [gpfsFileSystemNumFreeInodesL] => 326910126
       [gpfsFileSystemNumFreeInodesH] => 0
       )
  */

  foreach ($cache_discovery['gpfs-mib'] as $index => $storage)
  {
    $fstype = "gpfs";
    $descr  = "/".$storage['gpfsFileSystemName'];
    $hc = 1;
    $size = snmp_size64_high_low($storage['gpfsFileSystemTotalSpaceH'], $storage['gpfsFileSystemTotalSpaceL']) * 1024;
    $free = snmp_size64_high_low($storage['gpfsFileSystemFreeSpaceH'],  $storage['gpfsFileSystemFreeSpaceL'])  * 1024;
    $used = $size - $free;

    discover_storage($valid['storage'], $device, $index, $fstype, $mib, $descr, 1024, $size, $used, array('storage_hc' => $hc));

    unset($deny, $fstype, $descr, $size, $used, $free, $percent, $hc);
  }
  unset($index, $storage);
}
