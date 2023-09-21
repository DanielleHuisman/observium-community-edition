<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

//ZYXEL-SYS-MEMORY-MIB::zySysMemoryPoolName.1 = STRING: "common"
//ZYXEL-SYS-MEMORY-MIB::zySysMemoryPoolTotalSize.1 = Gauge32: 17636992
//ZYXEL-SYS-MEMORY-MIB::zySysMemoryPoolUsedSize.1 = Gauge32: 7209008
//ZYXEL-SYS-MEMORY-MIB::zySysMemoryPoolUtilization.1 = Gauge32: 40

$mempool_array = snmpwalk_cache_oid($device, 'zyxelSysMemoryPoolEntry', [], $mib);
print_debug_vars($mempool_array);

foreach ($mempool_array as $index => $entry) {

    $descr = $entry['zySysMemoryPoolName'];

    $oid_name = 'zySysMemoryPoolUsedSize';
    $oid_num  = '.1.3.6.1.4.1.890.1.15.3.50.1.1.1.4.' . $index;
    $type     = $mib . '-' . $oid_name;

    $total = $entry['zySysMemoryPoolTotalSize'];
    $used  = $entry[$oid_name];

    discover_mempool($valid['mempool'], $device, $index, $mib, $descr, 1, $total, $used);
}

unset($mempool_array, $index, $descr, $precision, $total, $used);

// EOF
