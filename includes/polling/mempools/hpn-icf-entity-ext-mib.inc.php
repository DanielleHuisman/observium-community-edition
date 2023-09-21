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

$mib = 'HPN-ICF-ENTITY-EXT-MIB';

if (!is_array($cache_storage[$mib])) {
    $cache_storage[$mib] = snmpwalk_cache_oid($device, 'hpnicfEntityExtMemUsage', [], $mib);
    $cache_storage[$mib] = snmpwalk_cache_oid($device, 'hpnicfEntityExtMemSize', $cache_storage[$mib], $mib);
} else {
    print_debug("Cached!");
}

$index         = $mempool['mempool_index'];
$cache_mempool = $cache_storage[$mib][$index];

$mempool['total'] = snmp_dewrap32bit($cache_mempool['hpnicfEntityExtMemSize']);
$mempool['perc']  = $cache_mempool['hpnicfEntityExtMemUsage'];

// EOF
