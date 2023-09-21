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

$mib = 'CISCO-ENHANCED-MEMPOOL-MIB';

if ($mempool['mempool_hc']) {
    $cemp_oid = 'cempMemPoolHC';
} else {
    $cemp_oid = 'cempMemPool';
}
$oids = ['used' => $cemp_oid . 'Used',
         'free' => $cemp_oid . 'Free'];

if (!is_array($cache_storage[$mib])) {
    foreach ($oids as $param => $oid) {
        $cache_mempool = snmpwalk_cache_oid($device, $oid, $cache_mempool, $mib);
        if ($device['os'] == 'iosxr' && !$GLOBALS['snmp_status']) {
            // Hack for some old IOS-XR, sometime return "Timeout: No Response".
            // See http://jira.observium.org/browse/OBSERVIUM-1170
            $cache_mempool = snmpwalk_cache_oid($device, $oid, $cache_mempool, $mib);
        }
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

$mempool['total'] = $mempool['used'] + $mempool['free'];

unset ($index, $cemp_oid, $oid);

// EOF
