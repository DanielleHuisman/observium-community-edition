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

// Process in main ports loop
function process_port_mikrotik(&$this_port, $device, $port)
{
    // Used to loop below for StatsD
    $etherlike_oids      = [
      'dot3StatsAlignmentErrors', 'dot3StatsFCSErrors', 'dot3StatsSingleCollisionFrames', 'dot3StatsMultipleCollisionFrames',
      'dot3StatsSQETestErrors', 'dot3StatsDeferredTransmissions', 'dot3StatsLateCollisions', 'dot3StatsExcessiveCollisions',
      'dot3StatsInternalMacTransmitErrors', 'dot3StatsCarrierSenseErrors', 'dot3StatsFrameTooLongs', 'dot3StatsInternalMacReceiveErrors',
      'dot3StatsSymbolErrors'
    ];
    $mikrotik_stats_oids = [
      'dot3StatsAlignmentErrors'         => 'mtxrInterfaceStatsRxAlignError',
      'dot3StatsFCSErrors'               => 'mtxrInterfaceStatsRxFCSError',
      'dot3StatsSingleCollisionFrames'   => 'mtxrInterfaceStatsTxSingleCollision',
      'dot3StatsMultipleCollisionFrames' => 'mtxrInterfaceStatsTxMultipleCollision',
      //'dot3StatsSQETestErrors',
      'dot3StatsDeferredTransmissions'   => 'mtxrInterfaceStatsTxDeferred',
      'dot3StatsLateCollisions'          => 'mtxrInterfaceStatsTxLateCollision',
      'dot3StatsExcessiveCollisions'     => 'mtxrInterfaceStatsTxExcessiveCollision',
      //'dot3StatsInternalMacTransmitErrors',
      'dot3StatsCarrierSenseErrors'      => 'mtxrInterfaceStatsRxCarrierError',
      'dot3StatsFrameTooLongs'           => 'mtxrInterfaceStatsRxTooLong',
      //'dot3StatsInternalMacReceiveErrors',
      //'dot3StatsSymbolErrors'
    ];

    if ($this_port['ifType'] === "ethernetCsmacd" && isset($this_port['mtxrInterfaceStatsRxAlignError'])) {
        // Check to make sure Port data is cached.

        rrdtool_update_ng($device, 'port-dot3', [
          'AlignmentErrors'     => $this_port['mtxrInterfaceStatsRxAlignError'],
          'FCSErrors'           => $this_port['mtxrInterfaceStatsRxFCSError'],
          'SingleCollisionFram' => $this_port['mtxrInterfaceStatsTxSingleCollision'],
          'MultipleCollisionFr' => $this_port['mtxrInterfaceStatsTxMultipleCollision'],
          'SQETestErrors'       => 0,
          'DeferredTransmissio' => $this_port['mtxrInterfaceStatsTxDeferred'],
          'LateCollisions'      => $this_port['mtxrInterfaceStatsTxLateCollision'],
          'ExcessiveCollisions' => $this_port['mtxrInterfaceStatsTxExcessiveCollision'],
          'InternalMacTransmit' => 0,
          'CarrierSenseErrors'  => $this_port['mtxrInterfaceStatsRxCarrierError'],
          'FrameTooLongs'       => $this_port['mtxrInterfaceStatsRxTooLong'],
          'InternalMacReceiveE' => 0,
          'SymbolErrors'        => 0,
        ],                get_port_rrdindex($port));

        if ($GLOBALS['config']['statsd']['enable'] == TRUE) {
            foreach ($etherlike_oids as $oid) {
                $value = isset($mikrotik_stats_oids[$oid]) ? $this_port[$mikrotik_stats_oids[$oid]] : 0;
                // Update StatsD/Carbon
                StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'port' . '.' . $this_port['ifIndex'] . '.' . $oid, $value);
            }
        }

    }
}

// EOF
