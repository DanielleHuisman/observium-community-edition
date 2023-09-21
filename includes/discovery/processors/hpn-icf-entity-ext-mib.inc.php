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

// HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtCpuUsage.30 = INTEGER: 16
// HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtCpuUsage.36 = INTEGER: 5
// HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtCpuUsage.42 = INTEGER: 5
// HPN-ICF-ENTITY-EXT-MIB::hpnicfEntityExtCpuUsage.48 = INTEGER: 12

$oids             = ['hpnicfEntityExtCpuUsage', 'entPhysicalName'];
$processors_array = [];
foreach ($oids as $oid) {
    $processors_array = snmpwalk_cache_oid($device, $oid, $processors_array, 'ENTITY-MIB:HPN-ICF-ENTITY-EXT-MIB');
}

foreach ($processors_array as $index => $entry) {
    if ($entry['hpnicfEntityExtCpuUsage'] != 0) {
        $oid   = ".1.3.6.1.4.1.11.2.14.11.15.2.6.1.1.1.1.6.$index";
        $descr = $entry['entPhysicalName'];
        $usage = $entry['hpnicfEntityExtCpuUsage'];
        discover_processor($valid['processor'], $device, $oid, $index, 'hh3c-fixed', $descr, 1, $usage);
    }
}

unset ($processors_array);

// EOF
