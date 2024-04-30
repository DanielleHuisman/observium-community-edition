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

// Active
//TWAMP-MIB::twampSessionId.1 = Gauge32: 1
//TWAMP-MIB::twampSessionDuration.1 = Gauge32: 120 s
//TWAMP-MIB::twampSessionInterval.1 = Gauge32: 10 s
//TWAMP-MIB::twampSessionState.1 = INTEGER: active(1)
//TWAMP-MIB::twampSessionSrcAddr.1 = STRING: "200.215.216.22"
//TWAMP-MIB::twampSessionDstAddr.1 = STRING: "200.215.220.226"
//TWAMP-MIB::twampSessionDstPort.1 = Gauge32: 15000
//TWAMP-MIB::twampSessionPktSize.1 = Gauge32: 1280 B
//TWAMP-MIB::twampSessionDSCP.1 = Gauge32: 0
//TWAMP-MIB::twampSessionTotalTests.1 = Gauge32: 1856
//TWAMP-MIB::twampSessionTotalTxPkts.1 = Gauge32: 1111744
//TWAMP-MIB::twampSessionTotalRxPkts.1 = Gauge32: 1111744

//TWAMP-MIB::twampTestSessionId.1.1 = Gauge32: 1
//TWAMP-MIB::twampTestSessionId.1.10 = Gauge32: 1
//TWAMP-MIB::twampTestIndex.1.1 = Gauge32: 1
//TWAMP-MIB::twampTestIndex.1.10 = Gauge32: 10
//TWAMP-MIB::twampTestId.1.1 = Gauge32: 1856
//TWAMP-MIB::twampTestId.1.10 = Gauge32: 1847
//TWAMP-MIB::twampTestDelayMin.1.1 = INTEGER: 1.57 ms
//TWAMP-MIB::twampTestDelayMin.1.10 = INTEGER: 1.58 ms
//TWAMP-MIB::twampTestDelayMax.1.1 = INTEGER: 1.83 ms
//TWAMP-MIB::twampTestDelayMax.1.10 = INTEGER: 1.76 ms
//TWAMP-MIB::twampTestDelayAvg.1.1 = INTEGER: 1.65 ms
//TWAMP-MIB::twampTestDelayAvg.1.10 = INTEGER: 1.64 ms
//TWAMP-MIB::twampTestJitterMin.1.1 = INTEGER: .00 ms
//TWAMP-MIB::twampTestJitterMin.1.10 = INTEGER: .00 ms
//TWAMP-MIB::twampTestJitterMax.1.1 = INTEGER: .18 ms
//TWAMP-MIB::twampTestJitterMax.1.10 = INTEGER: .13 ms
//TWAMP-MIB::twampTestJitterAvg.1.1 = INTEGER: .04 ms
//TWAMP-MIB::twampTestJitterAvg.1.10 = INTEGER: .03 ms
//TWAMP-MIB::twampTestTxPkts.1.1 = Gauge32: 599
//TWAMP-MIB::twampTestTxPkts.1.10 = Gauge32: 599
//TWAMP-MIB::twampTestRxPkts.1.1 = Gauge32: 599
//TWAMP-MIB::twampTestRxPkts.1.10 = Gauge32: 599
//TWAMP-MIB::twampTestLossRatio.1.1 = Gauge32: .00
//TWAMP-MIB::twampTestLossRatio.1.10 = Gauge32: .00
//TWAMP-MIB::twampTestConnectivity.1.1 = INTEGER: yes(1)
//TWAMP-MIB::twampTestConnectivity.1.10 = INTEGER: yes(1)
//TWAMP-MIB::twampTestRoundTripDelayMin.1.1 = INTEGER: 1.58 ms
//TWAMP-MIB::twampTestRoundTripDelayMin.1.10 = INTEGER: 1.58 ms
//TWAMP-MIB::twampTestRoundTripDelayMax.1.1 = INTEGER: 1.83 ms
//TWAMP-MIB::twampTestRoundTripDelayMax.1.10 = INTEGER: 1.76 ms
//TWAMP-MIB::twampTestRoundTripDelayAvg.1.1 = INTEGER: 1.65 ms
//TWAMP-MIB::twampTestRoundTripDelayAvg.1.10 = INTEGER: 1.65 ms

// Inactive
//TWAMP-MIB::twampSessionId.2 = Gauge32: 0
//TWAMP-MIB::twampSessionDuration.2 = Gauge32: 0 s
//TWAMP-MIB::twampSessionInterval.2 = Gauge32: 0 s
//TWAMP-MIB::twampSessionState.2 = INTEGER: inactive(0)
//TWAMP-MIB::twampSessionSrcAddr.2 = STRING: "0.0.0.0"
//TWAMP-MIB::twampSessionDstAddr.2 = STRING: "0.0.0.0"
//TWAMP-MIB::twampSessionDstPort.2 = Gauge32: 0
//TWAMP-MIB::twampSessionPktSize.2 = Gauge32: 0 B
//TWAMP-MIB::twampSessionDSCP.2 = Gauge32: 0
//TWAMP-MIB::twampSessionTotalTests.2 = Gauge32: 0
//TWAMP-MIB::twampSessionTotalTxPkts.2 = Gauge32: 0
//TWAMP-MIB::twampSessionTotalRxPkts.2 = Gauge32: 0

// Detect Active sessions
foreach (snmpwalk_cache_oid($device, "twampSessionTable", [], 'TWAMP-MIB') as $session_id => $session) {
    if ($session['twampSessionState'] === 'inactive' || $session['twampSessionState'] === '0') {
        // Skip inactive
        continue;
    }
    $data = [
        'device_id'  => $device['device_id'],
        'sla_mib'    => $mib,
        'sla_index'  => $session_id,
        'sla_owner'  => $session['twampSessionSrcAddr'], // owner used in rrd index
        'sla_tag'    => $session['twampSessionDstAddr'],
        'sla_target' => $session['twampSessionDstAddr'],
        'sla_graph'  => 'jitter',
        'rtt_type'   => 'twamp',
        'sla_status' => $session['twampSessionState'],  // Possible: active, notInService, notReady, createAndGo, createAndWait, destroy
        'deleted'    => 0,
    ];

    // Limits (in ms)
    $data['sla_limit_high']      = 5000;
    $data['sla_limit_high_warn'] = (int)($data['sla_limit_high'] / 5);

    $sla_table[$mib][$session_id] = $data; // Pull to array for main processing
}

// EOF
