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

if (!is_array($cache_storage['F10-CHASSIS-MIB'])) {
    $cache_storage['F10-CHASSIS-MIB'] = snmpwalk_cache_oid($device, 'chRpmMemUsageUtil', [], 'F10-CHASSIS-MIB');
    $cache_storage['F10-CHASSIS-MIB'] = snmpwalk_cache_oid($device, 'chSysProcessorMemSize', $cache_storage['F10-CHASSIS-MIB'], 'F10-CHASSIS-MIB');
} else {
    print_debug('Cached!');
}

$index         = $mempool['mempool_index'];
$cache_mempool = $cache_storage['F10-CHASSIS-MIB'][$index];

$mempool['total'] = $cache_mempool['chSysProcessorMemSize'];
$mempool['perc']  = $cache_mempool['chRpmMemUsageUtil'];

// EOF
