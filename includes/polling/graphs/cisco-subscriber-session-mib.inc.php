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

echo(" CISCO-SUBSCRIBER-SESSION-MIB ");
$graph = 'bng_active_sessions'; // Current graph
//$graphs[$graph] = FALSE;           // Disable graph by default

//if (!isset($graphs_db[$graph]) || $graphs_db[$graph] === TRUE)
//{
//walk BNG-sessions from all RSPs
// FIXME. Need more example
// CISCO-SUBSCRIBER-SESSION-MIB::csubAggStatsUpSessions.physical.1.all = Gauge32: 0 sessions
$rsp_sessions = snmpwalk_cache_oid($device, "csubAggStatsUpSessions.physical", [], "CISCO-SUBSCRIBER-SESSION-MIB");
//the active RSP will have most or all of the sessions, return only the value for the active RSP
if (safe_count($rsp_sessions)) {
    $session_count = max($rsp_sessions);
    if (is_numeric($session_count['csubAggStatsUpSessions'])) {
        $rrd_filename = "bng-active-sessions.rrd";

        rrdtool_create($device, $rrd_filename, " DS:value:GAUGE:600:0:100000000 ");
        rrdtool_update($device, $rrd_filename, "N:" . $session_count['csubAggStatsUpSessions']);

        $graphs[$graph] = TRUE;
    }
}

//}

unset($graph, $session_count);

// EOF
