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

// OSPF-MIB::ospfNbrIpAddr.103.52.56.3.0 = IpAddress: 103.52.56.3
// OSPF-MIB::ospfNbrIpAddr.103.52.56.4.0 = IpAddress: 103.52.56.4
// OSPF-MIB::ospfNbrAddressLessIndex.103.52.56.3.0 = INTEGER: 0
// OSPF-MIB::ospfNbrAddressLessIndex.103.52.56.4.0 = INTEGER: 0
// OSPF-MIB::ospfNbrRtrId.103.52.56.3.0 = IpAddress: 103.52.56.3
// OSPF-MIB::ospfNbrRtrId.103.52.56.4.0 = IpAddress: 10.20.3.1
// OSPF-MIB::ospfNbrOptions.103.52.56.3.0 = INTEGER: 66
// OSPF-MIB::ospfNbrOptions.103.52.56.4.0 = INTEGER: 2
// OSPF-MIB::ospfNbrPriority.103.52.56.3.0 = INTEGER: 1
// OSPF-MIB::ospfNbrPriority.103.52.56.4.0 = INTEGER: 1
// OSPF-MIB::ospfNbrState.103.52.56.3.0 = INTEGER: full(8)
// OSPF-MIB::ospfNbrState.103.52.56.4.0 = INTEGER: full(8)
// OSPF-MIB::ospfNbrEvents.103.52.56.3.0 = Counter32: 6
// OSPF-MIB::ospfNbrEvents.103.52.56.4.0 = Counter32: 5
// OSPF-MIB::ospfNbrLsRetransQLen.103.52.56.3.0 = Gauge32: 0
// OSPF-MIB::ospfNbrLsRetransQLen.103.52.56.4.0 = Gauge32: 0
// OSPF-MIB::ospfNbmaNbrStatus.103.52.56.3.0 = INTEGER: active(1)
// OSPF-MIB::ospfNbmaNbrStatus.103.52.56.4.0 = INTEGER: active(1)
// OSPF-MIB::ospfNbmaNbrPermanence.103.52.56.3.0 = INTEGER: permanent(2)
// OSPF-MIB::ospfNbmaNbrPermanence.103.52.56.4.0 = INTEGER: permanent(2)
// OSPF-MIB::ospfNbrHelloSuppressed.103.52.56.3.0 = INTEGER: false(2)
// OSPF-MIB::ospfNbrHelloSuppressed.103.52.56.4.0 = INTEGER: false(2)

// OSPF-MIB::ospfNbrRtrId[103.52.56.3][0] = IpAddress: 103.52.56.3
// OSPF-MIB::ospfNbrRtrId[103.52.56.4][0] = IpAddress: 10.20.3.1
// OSPF-MIB::ospfNbmaNbrStatus[103.52.56.3][0] = INTEGER: active(1)
// OSPF-MIB::ospfNbmaNbrStatus[103.52.56.4][0] = INTEGER: active(1)

$ospf_array = snmpwalk_cache_twopart_oid($device, 'ospfNbmaNbrStatus', [], 'OSPF-MIB', NULL, OBS_SNMP_ALL_TABLE);
if (snmp_status()) {
    $ospf_array = snmpwalk_cache_twopart_oid($device, 'ospfNbrRtrId', $ospf_array, 'OSPF-MIB', NULL, OBS_SNMP_ALL_TABLE);
    print_debug_vars($ospf_array);

    foreach ($ospf_array as $ip => $entry) {
        if ($ip === '0.0.0.0') {
            continue;
        }

        foreach ($entry as $if => $ospf) {
            if ($ospf['ospfNbmaNbrStatus'] !== 'active' || $ospf['ospfNbrRtrId'] === '0.0.0.0') {
                continue;
            }

            // Try find remote device and check if already cached
            $remote_device_id = get_autodiscovery_device_id($device, $ospf['ospfNbrRtrId']);
            if (is_null($remote_device_id) &&                 // NULL - never cached in other rounds
                check_autodiscovery($ospf['ospfNbrRtrId'])) { // Check all previous autodiscovery rounds
                // Neighbour never checked, try autodiscovery
                $remote_device_id = autodiscovery_device($ospf['ospfNbrRtrId'], NULL, 'OSPF', NULL, $device);
            }
        }
    }
}

// EOF

