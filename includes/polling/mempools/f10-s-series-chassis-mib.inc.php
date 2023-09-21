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

$mib = 'F10-S-SERIES-CHASSIS-MIB';

if (!is_array($cache_storage[$mib])) {
    $cache_storage[$mib] = snmpwalk_cache_oid($device, 'chStackUnitMemUsageUtil', [], $mib);
    $cache_storage[$mib] = snmpwalk_cache_oid($device, 'chSysProcessorMemSize', $cache_storage[$mib], $mib);
} else {
    print_debug("Cached!");
}

$index         = $mempool['mempool_index'];
$cache_mempool = $cache_storage[$mib][$index];

$mempool['total'] = $cache_mempool['chSysProcessorMemSize'];
$mempool['perc']  = $cache_mempool['chStackUnitMemUsageUtil'];

// EOF
