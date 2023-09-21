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

$mib = 'DELL-NETWORKING-CHASSIS-MIB';

if (!is_array($cache_storage[$mib])) {
    $cache_storage[$mib] = snmpwalk_cache_oid($device, 'dellNetCpuUtilMemUsage', [], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
    $cache_storage[$mib] = snmpwalk_cache_oid($device, 'dellNetProcessorMemSize', $cache_storage[$mib], $mib, NULL, OBS_SNMP_ALL_NUMERIC_INDEX);
} else {
    print_debug("Cached!");
}

$index         = $mempool['mempool_index'];
$cache_mempool = $cache_storage[$mib][$index];

$mempool['total'] = $cache_mempool['dellNetProcessorMemSize'];
$mempool['perc']  = $cache_mempool['dellNetCpuUtilMemUsage'];

// EOF
