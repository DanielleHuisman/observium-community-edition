<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage discovery
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

/// This is hack for Redback SeOS, while this only os have interfaces in VRF specific SNMP contexts,
/// but only informational fields (without stats)

// Try Ports in VRF SNMP contexts
if ($vrf_contexts = get_device_vrf_contexts($device)) { // SNMP VRF context discovered for device
    // Keep original device array
    $device_original = $device;
    $vrf_ports       = [];

    $data_oids_vrf = [
        //'ifDescr',
        'ifType', 'ifOperStatus', 'ifName', 'ifAlias'
    ];

    foreach ($vrf_contexts as $vrf_name => $snmp_context) {
        print_message("Ports in VRF: $vrf_name...");

        $device['snmp_context'] = $snmp_context;

        // Get ifDescr and validate if has unique ifIndexes
        $port_stats_vrf = snmpwalk_cache_oid($device, 'ifDescr', [], "IF-MIB");

        $has_unique_ports = FALSE;
        foreach ($port_stats_vrf as $ifIndex => $entry) {
            if (!isset($port_stats[$ifIndex])) {
                $has_unique_ports = TRUE;
                break;
            }
        }

        // Walk all other data Oids and merge with main stats
        if ($has_unique_ports) {
            foreach ($data_oids_vrf as $oid) {
                $port_stats_vrf = snmpwalk_cache_oid($device, $oid, $port_stats_vrf, "IF-MIB");
            }

            foreach ($port_stats_vrf as $ifIndex => $entry) {
                // Merge stats
                if (!isset($port_stats[$ifIndex])) {
                    $entry['vrf_name']              = $vrf_name;
                    $port_stats[$ifIndex]           = $entry;
                    $vrf_ports[$vrf_name][$ifIndex] = $entry;
                }
            }
        }
    }
    print_debug_vars($vrf_ports);

    // Clean
    $device = $device_original;
    unset($device_original, $vrf_ports, $port_stats_vrf);
}

// EOF
