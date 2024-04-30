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

$mib = 'DISMAN-PING-MIB';
echo("$mib ");

// Base results table
$sla_poll = snmpwalk_cache_oid($device, "pingResultsEntry", [], $mib);

// Additional mibs for vendor specific Types
$vendor_mib = FALSE;
if (is_device_mib($device, 'JUNIPER-PING-MIB', FALSE)) {
    // JUNIPER-PING-MIB
    echo("JUNIPER-PING-MIB ");
    $vendor_mib = 'JUNIPER-PING-MIB';
    $sla_poll   = snmpwalk_cache_oid($device, "jnxPingResultsEntry", $sla_poll, $vendor_mib);
    //$sla_poll = snmpwalk_cache_oid($device, "jnxPingResultsStatus", $sla_poll, $vendor_mib);
    //$sla_poll = snmpwalk_cache_oid($device, "jnxPingResultsTime", $sla_poll, $vendor_mib);
} elseif (is_device_mib($device, 'HH3C-NQA-MIB', FALSE)) {
    // HH3C-NQA-MIB
    echo("HH3C-NQA-MIB ");
    $vendor_mib = 'HH3C-NQA-MIB';
    $sla_poll   = snmpwalk_cache_oid($device, "hh3cNqaResultsEntry", $sla_poll, $vendor_mib);

    //$flags = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
    //$sla_history = snmpwalk_cache_threepart_oid($device, "Hh3cNqaStatisticsResultsEntry", array(), $vendor_mib, NULL, $flags);
    // walk of separate oids not do sppedup and in some situations longer
    //foreach ($sla_history as $sla_owner => $data)
    //{
    //  foreach ($data as $sla_name => $entry)
    //  {
    //    $index = $sla_owner . '.' . $sla_name;
    //    // Find last history entry (by highest key)
    //    $last = max(array_keys($entry));
    //
    //    // Add this entry to main poll array and get timestamp entry
    //    //$sla_poll[$index] = array_merge($sla_poll[$index], $entry[$last]);
    //  }
    //}
    //unset($sla_history, $last, $index);
} elseif (is_device_mib($device, 'HUAWEI-DISMAN-PING-MIB', FALSE)) {
    // HUAWEI-DISMAN-PING-MIB
    echo("HUAWEI-DISMAN-PING-MIB ");
    $vendor_mib = 'HUAWEI-DISMAN-PING-MIB';
    /* Hrm, not sure if we require extended stats there
    $sla_poll = snmpwalk_cache_oid($device, "hwpingResultsEntry", $sla_poll, $vendor_mib);
    // HUAWEI Jitter Statistics
    if (dbExist('slas', '`device_id` = ? AND `rtt_type` = ? AND `deleted` = 0 AND `sla_status` = ?', [$device['device_id'], 'jitter', 'active']))
    {
      $sla_poll = snmpwalk_cache_oid($device, "hwPingJitterStatsEntry", $sla_poll, $vendor_mib);
    }
    */
} else {
    // Heh, DISMAN-PING-MIB stores correct timestamp and states in huge history table, here trick for get last one
    // FIXME need found more speedup way! but currently only vendor specific is best!
    $flags       = OBS_SNMP_ALL ^ OBS_QUOTES_STRIP;
    $sla_history = snmpwalk_cache_threepart_oid($device, "pingProbeHistoryStatus", [], $mib, NULL, $flags);
    //$sla_history = snmpwalk_cache_threepart_oid($device, "pingProbeHistoryTime", $sla_history, $mib, NULL, $flags);
    foreach ($sla_history as $sla_owner => $data) {
        foreach ($data as $sla_index => $entry) {
            $index = $sla_owner . '.' . $sla_index;
            // Find last history entry (by highest key)
            $last = max(array_keys($entry));

            // Add this entry to main poll array and get timestamp entry
            $sla_poll[$index]['pingProbeHistoryStatus'] = $entry[$last]['pingProbeHistoryStatus'];
            //$sla_poll[$index]['pingProbeHistoryTime']   = $entry[$last]['pingProbeHistoryTime'];
        }
    }
    unset($sla_history, $last, $index);
}
print_debug_vars($sla_poll);

foreach ($sla_poll as $index => $entry) {
    if (($vendor_mib === 'JUNIPER-PING-MIB' && !isset($entry['jnxPingResultsStatus'])) ||
        !isset($entry['pingResultsOperStatus'])) {
        // Skip additional multiindex entries from table
        continue;
    }

    [ $sla_owner, $sla_index ] = explode('.', $index, 2);

    $sla_state = [
      'rtt_value'   => $entry['pingResultsAverageRtt'],
      'rtt_minimum' => $entry['pingResultsMinRtt'],
      'rtt_maximum' => $entry['pingResultsMaxRtt'],
      'rtt_success' => $entry['pingResultsProbeResponses'],
      'rtt_loss'    => $entry['pingResultsSentProbes'] - $entry['pingResultsProbeResponses'],
    ];

    // Vendor specific changes
    switch ($vendor_mib) {
        case 'JUNIPER-PING-MIB':
            $sla_state['rtt_value']   = $entry['jnxPingResultsRttUs'] / 1000;
            $sla_state['rtt_minimum'] = $entry['jnxPingResultsMinRttUs'] / 1000;
            $sla_state['rtt_maximum'] = $entry['jnxPingResultsMaxRttUs'] / 1000;
            // Standard deviation
            $sla_state['rtt_stddev'] = $entry['jnxPingResultsStdDevRttUs'] / 1000;

            $sla_state['rtt_sense'] = $entry['jnxPingResultsStatus'];
            $entry['UnixTime']      = $entry['jnxPingResultsTime'];
            break;
        case 'HH3C-NQA-MIB':
        case 'HUAWEI-DISMAN-PING-MIB':
            // Note, Stats table is not correct place for values, because in stats used long intervals > 200-300 sec
            //$sla_state['rtt_value']   = $entry['hh3cNqaStatResAverageRtt'];
            //$sla_state['rtt_minimum'] = $entry['hh3cNqaStatResMinRtt'];
            //$sla_state['rtt_maximum'] = $entry['hh3cNqaStatResMaxRtt'];

            // HH3C-NQA-MIB not has any status/sense entry, use pseudo sense
            // FIXME. Need more snmpwalks examples with other errors
            //HH3C-NQA-MIB::hh3cNqaResultsRttNumDisconnects."cncback"."1" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttNumDisconnects."cncmaster"."oper" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttNumDisconnects."imcl2topo"."ping" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttTimeouts."cncback"."1" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttTimeouts."cncmaster"."oper" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttTimeouts."imcl2topo"."ping" = Gauge32: 1
            //HH3C-NQA-MIB::hh3cNqaResultsRttBusies."cncback"."1" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttBusies."cncmaster"."oper" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttBusies."imcl2topo"."ping" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttNoConnections."cncback"."1" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttNoConnections."cncmaster"."oper" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttNoConnections."imcl2topo"."ping" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttDrops."cncback"."1" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttDrops."cncmaster"."oper" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttDrops."imcl2topo"."ping" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttSequenceErrors."cncback"."1" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttSequenceErrors."cncmaster"."oper" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttSequenceErrors."imcl2topo"."ping" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttStatsErrors."cncback"."1" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttStatsErrors."cncmaster"."oper" = Gauge32: 0
            //HH3C-NQA-MIB::hh3cNqaResultsRttStatsErrors."imcl2topo"."ping" = Gauge32: 0

            // HUAWEI-DISMAN-PING-MIB::hwpingResultsRttNumDisconnects."slatest"."besteffort" = Gauge32: 0
            // HUAWEI-DISMAN-PING-MIB::hwpingResultsRttTimeouts."slatest"."besteffort" = Gauge32: 0
            // HUAWEI-DISMAN-PING-MIB::hwpingResultsRttBusies."slatest"."besteffort" = Gauge32: 0
            // HUAWEI-DISMAN-PING-MIB::hwpingResultsRttNoConnections."slatest"."besteffort" = Gauge32: 0
            // HUAWEI-DISMAN-PING-MIB::hwpingResultsRttDrops."slatest"."besteffort" = Gauge32: 0
            // HUAWEI-DISMAN-PING-MIB::hwpingResultsRttSequenceErrors."slatest"."besteffort" = Gauge32: 0
            // HUAWEI-DISMAN-PING-MIB::hwpingResultsRttStatsErrors."slatest"."besteffort" = Gauge32: 0
            // HUAWEI-DISMAN-PING-MIB::hwpingResultsMaxDelaySD."slatest"."besteffort" = Gauge32: 24 milliseconds
            // HUAWEI-DISMAN-PING-MIB::hwpingResultsMaxDelayDS."slatest"."besteffort" = Gauge32: 24 milliseconds
            // HUAWEI-DISMAN-PING-MIB::hwpingResultsLostPacketRatio."slatest"."besteffort" = Gauge32: 0 milliseconds
            if ($sla_state['rtt_success'] > 0) {
                if ($sla_state['rtt_value'] > 0) {
                    $sla_state['rtt_sense'] = 'responseReceived';
                } elseif ($entry['hh3cNqaResultsRttTimeouts'] > 0) {
                    $sla_state['rtt_sense'] = 'requestTimedOut';
                } else {
                    $sla_state['rtt_sense'] = 'internalError'; // or 'unknown'
                }
            } else {
                if ($entry['hh3cNqaResultsRttTimeouts'] > 0) {
                    $sla_state['rtt_sense'] = 'requestTimedOut';
                } else {
                    $sla_state['rtt_sense'] = 'noRouteToTarget';
                }
            }

            //$sla_state['rtt_sense'] = $entry['pingResultsOperStatus'];
            $entry['UnixTime'] = $entry['pingResultsLastGoodProbe'];
            break;
        default:
            // FIXME. in DISMAN-PING-MIB exist only 'pingResultsRttSumOfSquares', I not know how calculate STDDEV from it
            $sla_state['rtt_sense'] = $entry['pingProbeHistoryStatus'];
            $entry['UnixTime']      = $entry['pingResultsLastGoodProbe'];
    }

    $sla_state['rtt_unixtime'] = datetime_to_unixtime($entry['UnixTime']);

    // SLA event
    $sla_array = get_state_array('OperationResponseStatus', $sla_state['rtt_sense'], 'DISMAN-PING-MIB');
    $sla_state['rtt_event'] = $sla_array['event'];

    // Note, here used complex index (owner.index)
    $cache_sla[$mib_lower][$index] = $sla_state;
}

// EOF
