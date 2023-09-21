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

$port_module = 'etherlike';
// If etherlike extended error statistics are enabled, walk mtxrInterfaceStatsEntry.
if ($ports_modules[$port_module]) {
    echo("mtxrInterfaceStats ");

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

    //$port_stats = snmpwalk_cache_oid($device, "mtxrInterfaceStatsEntry", $port_stats, "MIKROTIK-MIB");
    foreach ($mikrotik_stats_oids as $oid) {
        $port_stats = snmpwalk_cache_oid($device, $oid, $port_stats, "MIKROTIK-MIB");
        if ($oid === 'dot3StatsAlignmentErrors') {
            // First Oid
            if (!snmp_status()) {
                break;
            }
            $process_port_functions['mikrotik'] = TRUE;
        }
    }
}

// EOF
