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

$processors_array = snmpwalk_cache_oid($device, 'jnxOperatingCPU', [], $mib);

if (!safe_empty($processors_array)) {
    $processors_array = snmpwalk_cache_oid($device, 'jnxOperatingDRAMSize', $processors_array, $mib);
    $processors_array = snmpwalk_cache_oid($device, 'jnxOperatingDescr', $processors_array, $mib);

    foreach ($processors_array as $index => $entry) {
        if (str_contains_array($entry['jnxOperatingDescr'], ['Routing Engine', 'FPC']) ||
            $entry['jnxOperatingDRAMSize'] > 0) {

            if (!is_numeric($entry['jnxOperatingCPU']) ||
                str_icontains_array($entry['jnxOperatingDescr'], ['sensor', 'fan', 'pcmcia', 'no'])) {
                continue;
            }

            $oid   = ".1.3.6.1.4.1.2636.3.1.13.1.8.$index";
            $descr = $entry['jnxOperatingDescr'];
            $usage = $entry['jnxOperatingCPU'];

            discover_processor($valid['processor'], $device, $oid, $index, 'junos', $descr, 1, $usage);
        } // End if checks
    } // End Foreach
} // End if array

unset($processors_array);

// EOF
