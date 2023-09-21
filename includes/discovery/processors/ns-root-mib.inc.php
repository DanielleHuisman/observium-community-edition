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

#root@alpha:/home/observium/dev# snmpwalk -v2c -c // -M mibs -m +NS-ROOT-MIB netscaler.test nsCPUTable
#NS-ROOT-MIB::nsCPUname."cpu0" = STRING: "cpu0"
#NS-ROOT-MIB::nsCPUusage."cpu0" = Gauge32: 0

if (safe_empty($nsaarray)) {
    $nsarray = snmpwalk_cache_oid($device, 'nsCPUTable', [], $mib);
}

foreach ($nsarray as $descr => $data) {

    $current = $data['nsCPUusage'];

    $oid   = '.1.3.6.1.4.1.5951.4.1.1.41.6.1.2.' . snmp_string_to_oid($descr);
    $descr = $data['nsCPUname'];

    // FIXME, when will converted to definition-based, note that here used "named" index instead numeric
    discover_processor($valid['processor'], $device, $oid, $descr, 'netscaler', $descr, 1, $current);
}

unset($nsarray, $oid, $descr, $current);

// EOF
