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

$mib = 'S5-CHASSIS-MIB';

$oids = ['s5ChasUtilMemoryTotalMB', 's5ChasUtilMemoryAvailableMB'];

if (!is_array($cache_storage[$mib])) {
    foreach ($oids as $oid) {
        $cache_mempool = snmpwalk_cache_oid($device, $oid, $cache_mempool, $mib);
    }
    $cache_storage[$mib] = $cache_mempool;
} else {
    print_debug("Cached!");
    $cache_mempool = $cache_storage[$mib];
}

$mempool['total'] = $cache_mempool[$index]['s5ChasUtilMemoryTotalMB'];
$mempool['free']  = $cache_mempool[$index]['s5ChasUtilMemoryAvailableMB'];
$mempool['used']  = $mempool['total'] - $mempool['free'];

// EOF
