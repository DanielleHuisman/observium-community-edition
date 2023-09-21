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

$srx_spu_array = snmpwalk_cache_oid($device, 'jnxJsSPUMonitoringNodeDescr', [], $mib);
$srx_spu_array = snmpwalk_cache_oid($device, 'jnxJsSPUMonitoringFPCIndex', $srx_spu_array, $mib);
$srx_spu_array = snmpwalk_cache_oid($device, 'jnxJsSPUMonitoringCPUUsage', $srx_spu_array, $mib);

foreach ($srx_spu_array as $index => $entry) {
    $oid   = ".1.3.6.1.4.1.2636.3.39.1.12.1.1.1.4.$index"; // node0 FPC: SRX3k SPC
    $descr = ($entry['jnxJsSPUMonitoringNodeDescr'] == 'single' ? '' : $entry['jnxJsSPUMonitoringNodeDescr'] . ' ') . 'SPC slot ' . $entry['jnxJsSPUMonitoringFPCIndex'];
    $usage = $entry['jnxJsSPUMonitoringCPUUsage'];

    discover_processor($valid['processor'], $device, $oid, $index, 'junos', $descr, 1, $usage);
}

unset ($srx_spu_array, $oid, $descr, $usage, $index, $entry);

// EOF
