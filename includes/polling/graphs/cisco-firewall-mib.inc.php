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

// CISCO-FIREWALL-MIB::cfwConnectionStatDescription.protoIp.currentInUse = STRING: number of connections currently in use by the entire firewall
// CISCO-FIREWALL-MIB::cfwConnectionStatDescription.protoIp.high = STRING: highest number of connections in use at any one time since system startup
// CISCO-FIREWALL-MIB::cfwConnectionStatCount.protoIp.currentInUse = Counter32: 0
// CISCO-FIREWALL-MIB::cfwConnectionStatCount.protoIp.high = Counter32: 0
// CISCO-FIREWALL-MIB::cfwConnectionStatValue.protoIp.currentInUse = Gauge32: 6135
// CISCO-FIREWALL-MIB::cfwConnectionStatValue.protoIp.high = Gauge32: 28581

$graph = 'firewall_sessions_ipv4'; // Current graph

//if (!isset($graphs_db[$graph]) || $graphs_db[$graph] === TRUE)
//{
$session_count = snmp_get($device, ".1.3.6.1.4.1.9.9.147.1.2.2.2.1.5.40.6", "-OQUvs", "CISCO-FIREWALL-MIB");

if (is_numeric($session_count)) {
    $rrd_filename = "firewall-sessions-ipv4.rrd";

    rrdtool_create($device, $rrd_filename, " DS:value:GAUGE:600:0:100000000 ");
    rrdtool_update($device, $rrd_filename, "N:" . $session_count);

    $graphs[$graph] = TRUE;
}
//}

unset($graph, $session_count);

// EOF
