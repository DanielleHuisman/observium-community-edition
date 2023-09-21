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

if (!$config['autodiscovery']['ospf']) {
    print_debug("Autodiscovery for OSPF disabled.");
    return;
}

// OSPFV3-MIB::ospfv3NbrAddressType.4.0.3110374402 = INTEGER: ipv6(2)
// OSPFV3-MIB::ospfv3NbrAddressType.4.0.3110374403 = INTEGER: ipv6(2)
// OSPFV3-MIB::ospfv3NbrAddress.4.0.3110374402 = Hex-STRING: FE 80 00 00 00 00 00 00 02 25 90 FF FE E5 63 D0
// OSPFV3-MIB::ospfv3NbrAddress.4.0.3110374403 = Hex-STRING: FE 80 00 00 00 00 00 00 02 25 90 FF FE E3 DF 1A
// OSPFV3-MIB::ospfv3NbrOptions.4.0.3110374402 = INTEGER: 19
// OSPFV3-MIB::ospfv3NbrOptions.4.0.3110374403 = INTEGER: 19
// OSPFV3-MIB::ospfv3NbrPriority.4.0.3110374402 = INTEGER: 10
// OSPFV3-MIB::ospfv3NbrPriority.4.0.3110374403 = INTEGER: 10
// OSPFV3-MIB::ospfv3NbrState.4.0.3110374402 = INTEGER: full(8)
// OSPFV3-MIB::ospfv3NbrState.4.0.3110374403 = INTEGER: full(8)
// OSPFV3-MIB::ospfv3NbrEvents.4.0.3110374402 = Counter32: 6
// OSPFV3-MIB::ospfv3NbrEvents.4.0.3110374403 = Counter32: 6
// OSPFV3-MIB::ospfv3NbrLsRetransQLen.4.0.3110374402 = Gauge32: 0
// OSPFV3-MIB::ospfv3NbrLsRetransQLen.4.0.3110374403 = Gauge32: 0
// OSPFV3-MIB::ospfv3NbrHelloSuppressed.4.0.3110374402 = INTEGER: false(2)
// OSPFV3-MIB::ospfv3NbrHelloSuppressed.4.0.3110374403 = INTEGER: false(2)
// OSPFV3-MIB::ospfv3NbrIfId.4.0.3110374402 = INTEGER: 4
// OSPFV3-MIB::ospfv3NbrIfId.4.0.3110374403 = INTEGER: 3

$ospf_array = snmpwalk_cache_oid($device, 'ospfv3NbrAddress', [], 'OSPFV3-MIB', NULL, OBS_SNMP_ALL_HEX);
if (snmp_status()) {
    $ospf_array = snmpwalk_cache_oid($device, 'ospfv3NbrIfId', $ospf_array, 'OSPFV3-MIB');
    print_debug_vars($ospf_array);

    foreach ($ospf_array as $index => $entry) {
        $ip            = hex2ip($entry['ospfv3NbrAddress']);
        $ip_compressed = ip_compress($ip);
        if ($ip_compressed === '::') {
            continue;
        }

        // Try find remote device and check if already cached
        $remote_device_id = get_autodiscovery_device_id($device, $ip);
        if (is_null($remote_device_id) && // NULL - never cached in other rounds
            check_autodiscovery($ip)) {   // Check all previous autodiscovery rounds

            // Neighbour never checked, try autodiscovery
            $port             = get_port_by_index_cache($device, $entry['ospfv3NbrIfId']);
            $remote_device_id = autodiscovery_device($ip, NULL, 'OSPF', NULL, $device, $port);
        }
    }
}

// EOF
