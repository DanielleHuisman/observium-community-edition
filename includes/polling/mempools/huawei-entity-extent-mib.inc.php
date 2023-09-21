<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package        observium
 * @subpackage     poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

$mib = 'HUAWEI-ENTITY-EXTENT-MIB';

$oids = ['hwEntityMemUsage', 'hwEntityMemSize'];

if (!is_array($cache_storage[$mib])) {
    foreach ($oids as $oid) {
        $cache_mempool = snmpwalk_cache_oid($device, $oid, $cache_mempool, $mib);
    }
    $cache_storage[$mib] = $cache_mempool;
} else {
    print_debug("Cached!");
    $cache_mempool = $cache_storage[$mib];
}

$index            = $mempool['mempool_index'];
$mempool['total'] = isset($cache_mempool[$index]['hwEntityMemSize']) && $cache_mempool[$index]['hwEntityMemSize'] > 0 ? $cache_mempool[$index]['hwEntityMemSize'] : 100;
$mempool['perc']  = $cache_mempool[$index]['hwEntityMemUsage'];

// EOF
