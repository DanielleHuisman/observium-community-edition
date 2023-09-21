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

// EtherLike-MIB functions

// Process in main ports loop
function process_port_etherlike(&$this_port, $device, $port)
{
    // Used to loop below for StatsD
    $etherlike_oids = [
      'dot3StatsAlignmentErrors', 'dot3StatsFCSErrors', 'dot3StatsSingleCollisionFrames', 'dot3StatsMultipleCollisionFrames',
      'dot3StatsSQETestErrors', 'dot3StatsDeferredTransmissions', 'dot3StatsLateCollisions', 'dot3StatsExcessiveCollisions',
      'dot3StatsInternalMacTransmitErrors', 'dot3StatsCarrierSenseErrors', 'dot3StatsFrameTooLongs', 'dot3StatsInternalMacReceiveErrors',
      'dot3StatsSymbolErrors'
    ];

    // Overwrite ifDuplex with dot3StatsDuplexStatus if it exists
    if (isset($this_port['dot3StatsDuplexStatus'])) {
        // echo("dot3Duplex, ");
        // FIX for issue when device report incorrect type:
        // EtherLike-MIB::dot3StatsDuplexStatus.1 = Wrong Type (should be INTEGER): Counter32: 2
        switch ($this_port['dot3StatsDuplexStatus']) {
            case '1':
                $this_port['ifDuplex'] = 'unknown';
                break;
            case '2':
                $this_port['ifDuplex'] = 'halfDuplex';
                break;
            case '3':
                $this_port['ifDuplex'] = 'fullDuplex';
                break;
            default:
                $this_port['ifDuplex'] = $this_port['dot3StatsDuplexStatus'];
        }
    }

    if ($this_port['ifType'] === "ethernetCsmacd" && isset($this_port['dot3StatsIndex'])) {
        // Check to make sure Port data is cached.

        rrdtool_update_ng($device, 'port-dot3', [
          'AlignmentErrors'     => $this_port['dot3StatsAlignmentErrors'],
          'FCSErrors'           => $this_port['dot3StatsFCSErrors'],
          'SingleCollisionFram' => $this_port['dot3StatsSingleCollisionFrames'],
          'MultipleCollisionFr' => $this_port['dot3StatsMultipleCollisionFrames'],
          'SQETestErrors'       => $this_port['dot3StatsSQETestErrors'],
          'DeferredTransmissio' => $this_port['dot3StatsDeferredTransmissions'],
          'LateCollisions'      => $this_port['dot3StatsLateCollisions'],
          'ExcessiveCollisions' => $this_port['dot3StatsExcessiveCollisions'],
          'InternalMacTransmit' => $this_port['dot3StatsInternalMacTransmitErrors'],
          'CarrierSenseErrors'  => $this_port['dot3StatsCarrierSenseErrors'],
          'FrameTooLongs'       => $this_port['dot3StatsFrameTooLongs'],
          'InternalMacReceiveE' => $this_port['dot3StatsInternalMacReceiveErrors'],
          'SymbolErrors'        => $this_port['dot3StatsSymbolErrors'],
        ],                get_port_rrdindex($port));

        if ($GLOBALS['config']['statsd']['enable'] == TRUE) {
            foreach ($etherlike_oids as $oid) {
                // Update StatsD/Carbon
                StatsD ::gauge(str_replace(".", "_", $device['hostname']) . '.' . 'port' . '.' . $this_port['ifIndex'] . '.' . $oid, $this_port[$oid]);
            }
        }


    }
}

// EOF
