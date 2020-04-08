<?php

/**
 * Observium
 *
 *   This file is part of Observium.
 *
 * @package    observium
 * @subpackage poller
 * @copyright  (C) 2006-2013 Adam Armstrong, (C) 2013-2019 Observium Limited
 *
 */

echo(' HPICF-IPSLA-MIB ');

$sla_states = &$GLOBALS['config']['mibs']['HPICF-IPSLA-MIB']['sla_states']; // Events from MIB definitions

// Derp table, not have latest RTT time, mostly Oids is empty
$sla_poll = snmpwalk_cache_multi_oid($device, "hpicfIpSlaHistMinRTT",       array(), 'HPICF-IPSLA-MIB');
$sla_poll = snmpwalk_cache_multi_oid($device, "hpicfIpSlaHistMaxRTT",     $sla_poll, 'HPICF-IPSLA-MIB');

$sla_poll = snmpwalk_cache_multi_oid($device, "hpicfIpSlaHistPacketLoss", $sla_poll, 'HPICF-IPSLA-MIB');
$sla_poll = snmpwalk_cache_multi_oid($device, "hpicfIpSlaHistSentPktNum", $sla_poll, 'HPICF-IPSLA-MIB');
$sla_poll = snmpwalk_cache_multi_oid($device, "hpicfIpSlaAttrNumPkts",    $sla_poll, 'HPICF-IPSLA-MIB');

// History table
$sla_hist = snmp_walk_multipart_oid($device, "hpicfIpSlaHistSummStartTime", array(), 'HPICF-IPSLA-MIB');
$sla_hist = snmp_walk_multipart_oid($device, "hpicfIpSlaHistSummRTT",     $sla_hist, 'HPICF-IPSLA-MIB');
$sla_hist = snmp_walk_multipart_oid($device, "hpicfIpSlaHistSummStatus",  $sla_hist, 'HPICF-IPSLA-MIB');

//print_debug_vars($sla_poll);
//print_debug_vars($sla_hist);

foreach ($sla_hist as $sla_index => $hist)
{
  // Find latest entry
  $unixtime = 0; // initial
  foreach ($hist as $hist_index => $tmp)
  {
    if ($tmp['hpicfIpSlaHistSummStartTime'] > $unixtime)
    {
      $unixtime = $tmp['hpicfIpSlaHistSummStartTime'];
      $entry = $tmp;
    }
  }
  $entry = array_merge($sla_poll[$sla_index], $entry);
  //print_vars($entry);

  // Use "named" status
  switch ($entry['hpicfIpSlaHistSummStatus'])
  {
    case 0:
      $entry['hpicfIpSlaHistSummStatus'] = 'alert';
      break;
    case 1:
      $entry['hpicfIpSlaHistSummStatus'] = 'ok';
      break;
    default:
      // not sure
      $entry['hpicfIpSlaHistSummStatus'] = 'unknown';
  }

  // Convert timestamps to unixtime
  $entry['UnixTime'] = $entry['hpicfIpSlaHistSummStartTime'];

  $sla_state = array(
    'rtt_value'    => $entry['hpicfIpSlaHistSummRTT'],
    'rtt_sense'    => $entry['hpicfIpSlaHistSummStatus'],
    'rtt_unixtime' => $entry['UnixTime'],
  );

  // SLA event
  $sla_state['rtt_event'] = $sla_states[$sla_state['rtt_sense']]['event'];

  $sla_state['rtt_minimum'] = $entry['hpicfIpSlaHistMinRTT'];
  $sla_state['rtt_maximum'] = $entry['hpicfIpSlaHistMaxRTT'];
  if ($entry['hpicfIpSlaHistSentPktNum'] > 0)
  {
    // HPICF-IPSLA-MIB::hpicfIpSlaHistSentPktNum.4 = Gauge32: 10
    // HPICF-IPSLA-MIB::hpicfIpSlaHistPacketLoss.4 = Gauge32: 100
    $sla_state['rtt_loss']    = ($entry['hpicfIpSlaHistPacketLoss'] > $entry['hpicfIpSlaHistSentPktNum']) ? $entry['hpicfIpSlaHistSentPktNum'] : $entry['hpicfIpSlaHistPacketLoss'];
    $sla_state['rtt_success'] = $entry['hpicfIpSlaHistSentPktNum'] - $sla_state['rtt_loss'];
  } else {
    $sla_state['rtt_loss']    = ($entry['hpicfIpSlaHistPacketLoss'] > $entry['hpicfIpSlaAttrNumPkts']) ? $entry['hpicfIpSlaAttrNumPkts'] : $entry['hpicfIpSlaHistPacketLoss'];
    $sla_state['rtt_success'] = $entry['hpicfIpSlaAttrNumPkts'] - $sla_state['rtt_loss'];
  }

  $cache_sla[$mib_lower][$sla_index] = $sla_state;
}

// EOF
