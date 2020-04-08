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

$table_defs['TERADICI-PCOIPv2-MIB']['pcoipGenStatsTable'] = array (
  'table'         => 'pcoipGenStatsTable',
  'numeric'       => '.1.3.6.1.4.1.25071.1.2.1.1',
  'call_function' => 'snmpwalk_cache_oid',
  'mib'           => 'TERADICI-PCOIPv2-MIB',
  'mib_dir'       => 'teradici',
  'descr'         => 'PCoIP General statistics',
  'graphs'        => array('pcoip-net-packets'),
  'ds_rename'     => array('pcoipGenStats' => ''),
  'oids'          => array(
     'pcoipGenStatsPacketsSent'     =>  array('numeric' => '2', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.1.1.1.2', 'descr' => 'Packets sent'),
     'pcoipGenStatsBytesSent'   =>  array('numeric' => '3', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.1.1.1.3', 'descr' => 'Bytes sent'),
     'pcoipGenStatsPacketsReceived'  =>  array('numeric' => '4', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.1.1.1.4',  'descr' => 'Packets received'),
     'pcoipGenStatsBytesReceived'  =>  array('numeric' => '5', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.1.1.1.5', 'descr' => 'Bytes received'),
     'pcoipGenStatsTxPacketsLost'  =>  array('numeric' => '6', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.1.1.1.6', 'descr' => 'Packets lost', 'ds_type'   => 'GAUGE')
  )
);

$table_defs['TERADICI-PCOIPv2-MIB']['pcoipNetStatsTable'] = array (
  'table'         => 'pcoipNetStatsTable',
  'numeric'       => '.1.3.6.1.4.1.25071.1.2.2.1',
  'call_function' => 'snmpwalk_cache_oid',
  'mib'           => 'TERADICI-PCOIPv2-MIB',
  'mib_dir'       => 'teradici',
  'descr'         => 'PCoIP Network statistics',
  'graphs'        => array('pcoip-net-latency','pcoip-net-bits'),
  'ds_rename'     => array('pcoipNetStats' => ''),
  'oids'          => array(
     'pcoipNetStatsRoundTripLatencyMs'     =>  array('numeric' => '2', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.2.1.1.2', 'descr' => 'Packet latency', 'ds_type'   => 'GAUGE'),
     'pcoipNetStatsRXBWkbitPersec'     =>  array('numeric' => '3', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.2.1.1.3', 'descr' => 'received', 'ds_type'   => 'GAUGE'),
     'pcoipNetStatsTXBWkbitPersec'     =>  array('numeric' => '6', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.2.1.1.6', 'descr' => 'transmitted', 'ds_type'   => 'GAUGE'),
  )
);

$table_defs['TERADICI-PCOIPv2-MIB']['pcoipImagingStatsTable'] = array (
  'table'         => 'pcoipImagingStatsTable',
  'numeric'       => '.1.3.6.1.4.1.25071.1.2.4.1',
  'call_function' => 'snmpwalk_cache_oid',
  'mib'           => 'TERADICI-PCOIPv2-MIB',
  'mib_dir'       => 'teradici',
  'descr'         => 'PCoIP Imaging statistics',
  'graphs'        => array('pcoip-image-quality', 'pcoip-image-fps','pcoip-image-pipeline'),
  'ds_rename'     => array('pcoipImagingStats' => ''),
  'oids'          => array(
     'pcoipImagingStatsEncodedFramesPersec'     =>  array('numeric' => '6', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.4.1.1.6', 'descr' => 'fps', 'ds_type'   => 'GAUGE'),
     'pcoipImagingStatsActiveMinimumQuality'   =>  array('numeric' => '7', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.4.1.1.7', 'descr' => 'quality', 'ds_type'   => 'GAUGE'),
     'pcoipImagingStatsPipelineProcRate'   =>  array('numeric' => '9', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.4.1.1.9', 'descr' => 'pipeline', 'ds_type'   => 'GAUGE', 'ds_max'=>'300'),
  )
);

$table_defs['TERADICI-PCOIPv2-MIB']['pcoipAudioStatsTable'] = array (
  'table'         => 'pcoipAudioStatsTable',
  'numeric'       => '.1.3.6.1.4.1.25071.1.2.3.1',
  'call_function' => 'snmpwalk_cache_oid',
  'mib'           => 'TERADICI-PCOIPv2-MIB',
  'mib_dir'       => 'teradici',
  'descr'         => 'PCoIP Audio statistics',
  'graphs'        => array('pcoip-audio-stats'),
  'ds_rename'     => array('pcoipAudioStats' => ''),
  'oids'          => array(
     'pcoipAudioStatsRXBWkbitPersec'     =>  array('numeric' => '4', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.3.1.1.4', 'descr' => 'received', 'ds_type'   => 'GAUGE'),
     'pcoipAudioStatsTXBWkbitPersec'   =>  array('numeric' => '5', 'index'=>'1', 'oid'=>'.1.3.6.1.4.1.25071.1.2.3.1.1.5', 'descr' => 'transmitted', 'ds_type'   => 'GAUGE'),
  )
);

// EOF
