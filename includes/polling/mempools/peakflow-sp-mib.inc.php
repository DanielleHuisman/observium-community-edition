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

$mib = 'PEAKFLOW-SP-MIB';

$cache_mempool = snmp_get_multi_oid($device, "devicePhysicalMemory.0 devicePhysicalMemoryInUse.0", [], $mib);

$mempool['total'] = $cache_mempool[$index]['devicePhysicalMemory'];
$mempool['used']  = $cache_mempool[$index]['devicePhysicalMemoryInUse'];

// EOF
