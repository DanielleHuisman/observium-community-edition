<?php
/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2023 Observium Limited
 *
 */

echo(' TWAMP-MIB ');

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

foreach (array_keys($sla_db[$mib_lower]) as $index) {
    $test_index = $index . '.1'; // Latest test entry always .1
    $poll_oids = [
        'twampTestId.' . $test_index,
        'twampTestJitterAvg.' . $test_index,
        'twampTestTxPkts.' . $test_index,
        'twampTestRxPkts.' . $test_index,
        'twampTestLossRatio.' . $test_index,
        'twampTestConnectivity.' . $test_index,
        'twampTestRoundTripDelayMin.' . $test_index,
        'twampTestRoundTripDelayMax.' . $test_index,
        'twampTestRoundTripDelayAvg.' . $test_index,
    ];

    $sla_poll = snmp_get_multi_oid($device, $poll_oids, [], 'TWAMP-MIB');
    $entry = $sla_poll[$test_index];
    //print_debug_vars($entry);

    $sla_state = [
        'rtt_value'    => $entry['twampTestRoundTripDelayAvg'],
        'rtt_minimum'  => $entry['twampTestRoundTripDelayMin'],
        'rtt_maximum'  => $entry['twampTestRoundTripDelayMax'],
        'rtt_success'  => $entry['twampTestRxPkts'],
        'rtt_loss'     => $entry['twampTestTxPkts'] - $entry['twampTestRxPkts'],
        'rtt_sense'    => $entry['twampTestConnectivity'],
        'rtt_unixtime' => round(snmp_endtime())
    ];

    // Standard deviation
    $sla_state['rtt_stddev'] = $entry['twampTestJitterAvg']; // FIXME. I not sure!

    // SLA event
    $sla_array = get_state_array('twampTestConnectivity', $sla_state['rtt_sense'], 'TWAMP-MIB');
    $sla_state['rtt_event'] = $sla_array['event'];

    $cache_sla[$mib_lower][$index] = $sla_state;
}


// EOF
