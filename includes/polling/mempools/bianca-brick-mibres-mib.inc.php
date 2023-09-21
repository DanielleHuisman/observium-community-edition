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

$mib = 'BIANCA-BRICK-MIBRES-MIB';

if (!is_array($cache_storage[$mib])) {
    foreach (['memoryTotal', 'memoryInuse'] as $oid) {
        $cache_mempool = snmpwalk_cache_oid($device, $oid, $cache_mempool, $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    }
    $cache_storage[$mib] = $cache_mempool;
} else {
    print_debug("Cached!");
    $cache_mempool = $cache_storage[$mib];
}

$mempool['total'] = $cache_mempool[$index]['memoryTotal'];
$mempool['used']  = $cache_mempool[$index]['memoryInuse'];

// EOF
