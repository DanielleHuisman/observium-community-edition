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

$mib = 'ZHONE-CARD-RESOURCES-MIB';
echo("$mib ");

//ZHONE-CARD-RESOURCES-MIB::cardRuntimeTable
//ZHONE-CARD-RESOURCES-MIB::cardPeakMemUsage.1.1 = INTEGER: 80762
//ZHONE-CARD-RESOURCES-MIB::cardAvailMem.1.1 = INTEGER: 145131
//ZHONE-CARD-RESOURCES-MIB::cardTotalMem.1.1 = INTEGER: 225421
//ZHONE-CARD-RESOURCES-MIB::cardMemStatus.1.1 = INTEGER: ramMemOK(1)

if (!is_array($cache_storage[$mib])) {
    foreach (['cardAvailMem', 'cardTotalMem'] as $oid) {
        $cache_mempool = snmpwalk_cache_oid($device, $oid, $cache_mempool, $mib);
    }
    $cache_storage[$mib] = $cache_mempool;
} else {
    print_debug("Cached!");
}

$index            = $mempool['mempool_index'];
$mempool['free']  = $cache_storage[$mib][$index]['cardAvailMem'];
$mempool['total'] = $cache_storage[$mib][$index]['cardTotalMem'];

unset ($index, $oid);

// EOF
