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

$table_defs['TERADICI-PCOIPv2-MIB']['pcoipGenStatsTable'] = [
  'table'         => 'pcoipGenStatsTable',
  'numeric'       => '.1.3.6.1.4.1.25071.1.2.1.1',
  'call_function' => 'snmpwalk_cache_oid',
  'mib'           => 'TERADICI-PCOIPv2-MIB',
  'mib_dir'       => 'teradici',
  'descr'         => 'PCoIP General statistics',
  'graphs'        => ['pcoip-net-packets'],
  'ds_rename'     => ['pcoipGenStats' => ''],
  'oids'          => [
    'pcoipGenStatsPacketsSent'     => ['numeric' => '2', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.1.1.1.2', 'descr' => 'Packets sent'],
    'pcoipGenStatsBytesSent'       => ['numeric' => '3', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.1.1.1.3', 'descr' => 'Bytes sent'],
    'pcoipGenStatsPacketsReceived' => ['numeric' => '4', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.1.1.1.4', 'descr' => 'Packets received'],
    'pcoipGenStatsBytesReceived'   => ['numeric' => '5', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.1.1.1.5', 'descr' => 'Bytes received'],
    'pcoipGenStatsTxPacketsLost'   => ['numeric' => '6', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.1.1.1.6', 'descr' => 'Packets lost', 'ds_type' => 'GAUGE']
  ]
];

$table_defs['TERADICI-PCOIPv2-MIB']['pcoipNetStatsTable'] = [
  'table'         => 'pcoipNetStatsTable',
  'numeric'       => '.1.3.6.1.4.1.25071.1.2.2.1',
  'call_function' => 'snmpwalk_cache_oid',
  'mib'           => 'TERADICI-PCOIPv2-MIB',
  'mib_dir'       => 'teradici',
  'descr'         => 'PCoIP Network statistics',
  'graphs'        => ['pcoip-net-latency', 'pcoip-net-bits'],
  'ds_rename'     => ['pcoipNetStats' => ''],
  'oids'          => [
    'pcoipNetStatsRoundTripLatencyMs' => ['numeric' => '2', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.2.1.1.2', 'descr' => 'Packet latency', 'ds_type' => 'GAUGE'],
    'pcoipNetStatsRXBWkbitPersec'     => ['numeric' => '3', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.2.1.1.3', 'descr' => 'received', 'ds_type' => 'GAUGE'],
    'pcoipNetStatsTXBWkbitPersec'     => ['numeric' => '6', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.2.1.1.6', 'descr' => 'transmitted', 'ds_type' => 'GAUGE'],
  ]
];

$table_defs['TERADICI-PCOIPv2-MIB']['pcoipImagingStatsTable'] = [
  'table'         => 'pcoipImagingStatsTable',
  'numeric'       => '.1.3.6.1.4.1.25071.1.2.4.1',
  'call_function' => 'snmpwalk_cache_oid',
  'mib'           => 'TERADICI-PCOIPv2-MIB',
  'mib_dir'       => 'teradici',
  'descr'         => 'PCoIP Imaging statistics',
  'graphs'        => ['pcoip-image-quality', 'pcoip-image-fps', 'pcoip-image-pipeline'],
  'ds_rename'     => ['pcoipImagingStats' => ''],
  'oids'          => [
    'pcoipImagingStatsEncodedFramesPersec'  => ['numeric' => '6', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.4.1.1.6', 'descr' => 'fps', 'ds_type' => 'GAUGE'],
    'pcoipImagingStatsActiveMinimumQuality' => ['numeric' => '7', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.4.1.1.7', 'descr' => 'quality', 'ds_type' => 'GAUGE'],
    'pcoipImagingStatsPipelineProcRate'     => ['numeric' => '9', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.4.1.1.9', 'descr' => 'pipeline', 'ds_type' => 'GAUGE', 'ds_max' => '300'],
  ]
];

$table_defs['TERADICI-PCOIPv2-MIB']['pcoipAudioStatsTable'] = [
  'table'         => 'pcoipAudioStatsTable',
  'numeric'       => '.1.3.6.1.4.1.25071.1.2.3.1',
  'call_function' => 'snmpwalk_cache_oid',
  'mib'           => 'TERADICI-PCOIPv2-MIB',
  'mib_dir'       => 'teradici',
  'descr'         => 'PCoIP Audio statistics',
  'graphs'        => ['pcoip-audio-stats'],
  'ds_rename'     => ['pcoipAudioStats' => ''],
  'oids'          => [
    'pcoipAudioStatsRXBWkbitPersec' => ['numeric' => '4', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.3.1.1.4', 'descr' => 'received', 'ds_type' => 'GAUGE'],
    'pcoipAudioStatsTXBWkbitPersec' => ['numeric' => '5', 'index' => '1', 'oid' => '.1.3.6.1.4.1.25071.1.2.3.1.1.5', 'descr' => 'transmitted', 'ds_type' => 'GAUGE'],
  ]
];

// EOF
