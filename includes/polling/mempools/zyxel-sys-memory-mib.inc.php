<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) Adam Armstrong
 *
 */

$mib  = 'ZYXEL-SYS-MEMORY-MIB';
$oids = ['used'  => 'zySysMemoryPoolUsedSize',
         'total' => 'zySysMemoryPoolTotalSize'];

if (!is_array($cache_storage[$mib])) {
    foreach ($oids as $param => $oid) {
        $cache_mempool = snmpwalk_cache_oid($device, $oid, $cache_mempool, $mib);
    }
    $cache_storage[$mib] = $cache_mempool;
} else {
    print_debug("Cached!");
    $cache_mempool = $cache_storage[$mib];
}

$index = $mempool['mempool_index'];
foreach ($oids as $param => $oid) {
    $mempool[$param] = $cache_mempool[$index][$oid];
}

unset ($index, $oid, $oids);

// EOF
