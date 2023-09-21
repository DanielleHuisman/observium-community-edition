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

// ADSL-LINE-MIB stats

$port_module = 'adsl';
if ($ports_modules[$port_module] && $port_stats_count &&
    dbExist('ports', '`device_id` = ? AND `ifType` IN (?, ?, ?)', [$device['device_id'], 'adsl', 'vdsl', 'vdsl2'])) {
    echo("ADSL ");
    $adsl_oids  = ['adslAtucPhysEntry', 'adslAturPhysEntry', 'adslAtucChanEntry',
                   'adslAturChanEntry', 'adslAtucPerfDataEntry', 'adslAturPerfDataEntry'];
    $port_stats = snmpwalk_cache_oid($device, 'adslLineEntry', $port_stats, "ADSL-LINE-MIB");

    $process_port_functions[$port_module] = snmp_status();

    if (snmp_status()) {
        foreach ($adsl_oids as $oid) {
            $port_stats = snmpwalk_cache_oid($device, $oid, $port_stats, "ADSL-LINE-MIB");
        }
    }
    print_debug_vars($port_stats);

    // VDSL2-LINE-MIB
} else {
    return FALSE; // False for do not collect stats
}

// EOF
