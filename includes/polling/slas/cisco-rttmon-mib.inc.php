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

echo(' CISCO-RTTMON-MIB ');

$sla_oids = [
  'jitter'     => [ 'rttMonLatestJitterOperRTTMin', 'rttMonLatestJitterOperRTTMax', 'rttMonLatestJitterOperNumOfRTT',
                    'rttMonLatestJitterOperPacketLossSD', 'rttMonLatestJitterOperPacketLossDS' ],
  'icmpjitter' => [ 'rttMonLatestIcmpJitterRTTMin', 'rttMonLatestIcmpJitterRTTMax',
                    'rttMonLatestIcmpJitterNumRTT', 'rttMonLatestIcmpJitterPktLoss' ],
];

$sla_poll = snmpwalk_cache_oid($device, "rttMonLatestRttOperEntry", [], 'CISCO-RTTMON-MIB');
foreach (dbFetchColumn("SELECT DISTINCT `rtt_type` FROM `slas` WHERE `device_id` = ? AND `rtt_type` != ? AND `deleted` = 0 AND `sla_status` = 'active';", [$device['device_id'], 'echo']) as $rtt_type) {
    switch ($rtt_type) {
        case 'jitter': // Additional data for Jitter
        case 'pathjitter':
        case 'ethernetJitter':
            $sla_poll = snmpwalk_cache_oid($device, "rttMonLatestJitterOperEntry", $sla_poll, 'CISCO-RTTMON-MIB');
            break;
        case 'icmpjitter': // Additional data for ICMP jitter
            $sla_poll = snmpwalk_cache_oid($device, "rttMonLatestIcmpJitterOperEntry", $sla_poll, 'CISCO-RTTMON-ICMP-MIB');
            break;
    }
}

// Uptime offset for timestamps
$uptime        = timeticks_to_sec($poll_device['sysUpTime']);
$uptime_offset = time() - (int)$uptime / 100; /// WARNING. System timezone BOMB

foreach ($sla_poll as $sla_index => $entry) {
    if (!isset($entry['rttMonLatestRttOperCompletionTime']) && !isset($entry['rttMonLatestRttOperSense'])) {
        // Skip additional multiindex entries from table
        continue;
    }

    // Convert timestamps to unixtime
    $entry['UnixTime'] = (int)(timeticks_to_sec($entry['rttMonLatestRttOperTime']) / 100 + $uptime_offset);

    // SLA event
    $sla_array = get_state_array('RttResponseSense', $entry['rttMonLatestRttOperSense'], 'CISCO-RTTMON-MIB');

    $sla_state = [
      'rtt_value'    => $entry['rttMonLatestRttOperCompletionTime'],
      'rtt_sense'    => $entry['rttMonLatestRttOperSense'],
      'rtt_unixtime' => $entry['UnixTime'],
      'rtt_event'    => $sla_array['event']
    ];

    switch ($sla_db[$mib_lower][$sla_index]['rtt_type']) {
        case 'jitter':
        case 'pathjitter':
        case 'ethernetJitter':
            if (is_numeric($entry['rttMonLatestJitterOperNumOfRTT'])) {
                $sla_state['rtt_minimum'] = $entry['rttMonLatestJitterOperRTTMin'];
                $sla_state['rtt_maximum'] = $entry['rttMonLatestJitterOperRTTMax'];
                $sla_state['rtt_success'] = $entry['rttMonLatestJitterOperNumOfRTT'];
                $sla_state['rtt_loss']    = $entry['rttMonLatestJitterOperPacketLossSD'] + $entry['rttMonLatestJitterOperPacketLossDS'];
            }
            break;
        case 'icmpjitter':
            if (is_numeric($entry['rttMonLatestIcmpJitterNumRTT'])) {
                $sla_state['rtt_minimum'] = $entry['rttMonLatestIcmpJitterRTTMin'];
                $sla_state['rtt_maximum'] = $entry['rttMonLatestIcmpJitterRTTMax'];
                $sla_state['rtt_success'] = $entry['rttMonLatestIcmpJitterNumRTT'];
                $sla_state['rtt_loss']    = $entry['rttMonLatestIcmpJitterPktLoss'];
            }
            break;
    }
    $cache_sla[$mib_lower][$sla_index] = $sla_state;
}

// EOF
