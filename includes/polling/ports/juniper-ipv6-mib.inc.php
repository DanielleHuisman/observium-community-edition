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

/*
JUNIPER-IPv6-MIB::jnxIpv6IfInOctets.509 = Counter64: 277025128777865
JUNIPER-IPv6-MIB::jnxIpv6IfOutOctets.509 = Counter64: 46668220396161
*/

// JUNIPER-IPv6-MIB

$port_module = 'jnxIpv6IfStats';

if ($ports_modules[$port_module] || TRUE) {
    echo("JUNIPER-IPv6-MIB jnxIpv6IfStats ");

    $port_stats = snmpwalk_cache_oid($device, 'jnxIpv6IfStatsTable', $port_stats, "JUNIPER-IPv6-MIB", NULL, OBS_SNMP_ALL_TABLE);

    $process_port_functions[$port_module] = $GLOBALS['snmp_status'];

}